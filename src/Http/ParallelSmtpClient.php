<?php

namespace AnkitFromIndia\ParallelSmtp\Http;

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;

class ParallelSmtpClient
{
    private array $connections = [];
    private array $messageCounters = [];
    private int $maxConnections;
    private int $messagesPerConnection;
    private array $smtpConfig;

    public function __construct(array $smtpConfig, int $maxConnections = 10, int $messagesPerConnection = 100)
    {
        $this->smtpConfig = $smtpConfig;
        $this->maxConnections = $maxConnections;
        $this->messagesPerConnection = $messagesPerConnection;
    }

    public function sendBulk(array $messages): array
    {
        $chunks = array_chunk($messages, $this->maxConnections);
        $results = [];

        foreach ($chunks as $chunk) {
            $promises = [];
            
            foreach ($chunk as $index => $message) {
                $connectionId = $index % $this->maxConnections;
                $promises[] = $this->sendMessage($message, $connectionId);
            }

            $results = array_merge($results, $promises);
        }

        return $results;
    }

    private function sendMessage(array $message, int $connectionId): array
    {
        try {
            $mailer = $this->getConnection($connectionId);
            $swiftMessage = $this->createSwiftMessage($message);
            $result = $mailer->send($swiftMessage);
            
            $this->messageCounters[$connectionId]++;
            
            if ($this->messageCounters[$connectionId] >= $this->messagesPerConnection) {
                $this->resetConnection($connectionId);
            }
            
            return ['success' => true, 'result' => $result];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getConnection(int $connectionId): Swift_Mailer
    {
        if (!isset($this->connections[$connectionId])) {
            $transport = (new Swift_SmtpTransport(
                $this->smtpConfig['host'],
                $this->smtpConfig['port'],
                $this->smtpConfig['encryption'] ?? null
            ))
            ->setUsername($this->smtpConfig['username'])
            ->setPassword($this->smtpConfig['password'])
            ->setPipelining(true);

            $this->connections[$connectionId] = new Swift_Mailer($transport);
            $this->messageCounters[$connectionId] = 0;
        }

        return $this->connections[$connectionId];
    }

    private function resetConnection(int $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]->getTransport()->stop();
            unset($this->connections[$connectionId]);
            $this->messageCounters[$connectionId] = 0;
        }
    }

    private function createSwiftMessage(array $message): Swift_Message
    {
        $swiftMessage = (new Swift_Message($message['subject']))
            ->setFrom($message['from'])
            ->setTo($message['to'])
            ->setBody($message['body'], $message['content_type'] ?? 'text/html');

        if (isset($message['cc'])) $swiftMessage->setCc($message['cc']);
        if (isset($message['bcc'])) $swiftMessage->setBcc($message['bcc']);

        return $swiftMessage;
    }

    public function __destruct()
    {
        foreach (array_keys($this->connections) as $connectionId) {
            $this->resetConnection($connectionId);
        }
    }
}
