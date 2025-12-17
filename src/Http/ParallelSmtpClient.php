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
    private int $minBatchSize;
    private int $maxBatchSize;
    private array $smtpConfig;

    public function __construct(array $smtpConfig, int $maxConnections = 10, int $messagesPerConnection = 100, int $minBatchSize = 1, int $maxBatchSize = 1000)
    {
        $this->smtpConfig = $smtpConfig;
        $this->maxConnections = $maxConnections;
        $this->messagesPerConnection = $messagesPerConnection;
        $this->minBatchSize = $minBatchSize;
        $this->maxBatchSize = $maxBatchSize;
    }

    public function send(array $message): array
    {
        return $this->sendMessage($message, 0);
    }

    public function sendBulk(array $messages): array
    {
        $messageCount = count($messages);
        
        if ($messageCount < $this->minBatchSize) {
            throw new \Exception("Minimum batch size is {$this->minBatchSize}, got {$messageCount}");
        }
        
        if ($messageCount > $this->maxBatchSize) {
            throw new \Exception("Maximum batch size is {$this->maxBatchSize}, got {$messageCount}");
        }

        $chunks = array_chunk($messages, $this->maxConnections);
        $results = [];

        foreach ($chunks as $chunk) {
            foreach ($chunk as $index => $message) {
                $connectionId = $index % $this->maxConnections;
                $results[] = $this->sendMessage($message, $connectionId);
            }
        }

        return $results;
    }

    private function sendMessage(array $message, int $connectionId): array
    {
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $mailer = $this->getConnection($connectionId);
                $swiftMessage = $this->createSwiftMessage($message);
                $result = $mailer->send($swiftMessage);
                
                $this->messageCounters[$connectionId]++;
                
                if ($this->messageCounters[$connectionId] >= $this->messagesPerConnection) {
                    $this->resetConnection($connectionId);
                }
                
                return ['success' => true, 'result' => $result];
                
            } catch (\Swift_TransportException $e) {
                $retryCount++;
                
                // Reset connection on transport errors
                $this->resetConnection($connectionId);
                
                if ($retryCount >= $maxRetries) {
                    return ['success' => false, 'error' => 'SMTP Error after ' . $maxRetries . ' retries: ' . $e->getMessage()];
                }
                
                // Wait before retry
                usleep(500000); // 0.5 seconds
                
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return ['success' => false, 'error' => 'Max retries exceeded'];
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
            ->setTimeout(30)
            ->setSourceIp('0.0.0.0');

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
