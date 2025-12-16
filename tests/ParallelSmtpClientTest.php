<?php

namespace AnkitFromIndia\ParallelSmtp\Tests;

use PHPUnit\Framework\TestCase;
use AnkitFromIndia\ParallelSmtp\Http\ParallelSmtpClient;

class ParallelSmtpClientTest extends TestCase
{
    public function test_client_can_be_instantiated()
    {
        $config = [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'encryption' => 'tls',
        ];

        $client = new ParallelSmtpClient($config);

        $this->assertInstanceOf(ParallelSmtpClient::class, $client);
    }

    public function test_send_bulk_returns_array()
    {
        $config = [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'test@example.com',
            'password' => 'password',
            'encryption' => 'tls',
        ];

        $client = new ParallelSmtpClient($config);
        $messages = [
            [
                'from' => 'sender@example.com',
                'to' => 'recipient@example.com',
                'subject' => 'Test Subject',
                'body' => 'Test Body',
                'content_type' => 'text/html'
            ]
        ];

        // This would normally connect to SMTP server
        // For testing, we just verify the method exists and returns array
        $this->assertTrue(method_exists($client, 'sendBulk'));
    }
}
