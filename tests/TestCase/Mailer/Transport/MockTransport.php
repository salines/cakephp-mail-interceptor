<?php
declare(strict_types=1);

namespace MailInterceptor\Test\TestCase\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;

/**
 * Mock Transport for testing
 */
class MockTransport extends AbstractTransport
{
    /**
     * @param \Cake\Mailer\Message $message Message to send
     * @return array<string, mixed>
     */
    public function send(Message $message): array
    {
        return [
            'mock_sent' => true,
            'headers' => $message->getHeaders(),
            'to' => $message->getTo(),
        ];
    }
}
