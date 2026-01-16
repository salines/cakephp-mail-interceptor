<?php

declare(strict_types=1);

namespace MailInterceptor\Test\TestCase\Mailer\Transport;

use Cake\Log\Log;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\Mailer\TransportFactory;
use InvalidArgumentException;
use MailInterceptor\Mailer\Transport\InterceptTransport;
use PHPUnit\Framework\TestCase;

/**
 * InterceptTransport Test Case
 */
class InterceptTransportTest extends TestCase
{
    private InterceptTransport $transport;

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure array log for capturing log messages
        if (!Log::getConfig('default')) {
            Log::setConfig('default', [
                'className' => 'Array',
                'levels' => ['info', 'error', 'warning', 'debug'],
            ]);
        }

        // Register a mock transport for testing
        if (!TransportFactory::getConfig('test_underlying')) {
            TransportFactory::setConfig('test_underlying', [
                'className' => MockTransport::class,
            ]);
        }

        $this->transport = new InterceptTransport();
    }

    /**
     * Teardown test fixtures
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear log messages
        Log::drop('default');

        // Clear transport configs
        if (TransportFactory::getConfig('test_underlying')) {
            TransportFactory::drop('test_underlying');
        }
    }

    /**
     * Test that missing transport config throws exception
     */
    public function testSendThrowsExceptionWhenTransportConfigMissing(): void
    {
        $this->transport->setConfig([
            'to' => 'intercept@example.com',
        ]);

        $message = new Message();
        $message->setTo('user@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InterceptTransport requires "transport" config option');

        $this->transport->send($message);
    }

    /**
     * Test that empty transport config throws exception
     */
    public function testSendThrowsExceptionWhenTransportConfigEmpty(): void
    {
        $this->transport->setConfig([
            'transport' => '',
            'to' => 'intercept@example.com',
        ]);

        $message = new Message();
        $message->setTo('user@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InterceptTransport requires "transport" config option');

        $this->transport->send($message);
    }

    /**
     * Test that missing to config throws exception
     */
    public function testSendThrowsExceptionWhenToConfigMissing(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
        ]);

        $message = new Message();
        $message->setTo('user@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InterceptTransport requires "to" config option');

        $this->transport->send($message);
    }

    /**
     * Test that empty to config throws exception
     */
    public function testSendThrowsExceptionWhenToConfigEmpty(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => '',
        ]);

        $message = new Message();
        $message->setTo('user@example.com');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InterceptTransport requires "to" config option');

        $this->transport->send($message);
    }

    /**
     * Test email is redirected to intercept address
     */
    public function testEmailIsRedirectedToInterceptAddress(): void
    {
        $interceptEmail = 'intercept@example.com';
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => $interceptEmail,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $to = $message->getTo();
        $this->assertCount(1, $to);
        $this->assertArrayHasKey($interceptEmail, $to);
    }

    /**
     * Test Cc and Bcc are cleared
     */
    public function testCcAndBccAreCleared(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setCc('cc@example.com');
        $message->setBcc('bcc@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $this->assertEmpty($message->getCc());
        $this->assertEmpty($message->getBcc());
    }

    /**
     * Test original To recipients are stored in X-Original-To header
     */
    public function testOriginalToIsStoredInHeader(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo([
            'user1@example.com' => 'User One',
            'user2@example.com' => 'User Two',
        ]);
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $headers = $message->getHeaders();
        $this->assertArrayHasKey('X-Original-To', $headers);
        $this->assertStringContainsString('user1@example.com', $headers['X-Original-To']);
        $this->assertStringContainsString('user2@example.com', $headers['X-Original-To']);
    }

    /**
     * Test original Cc recipients are stored in X-Original-Cc header
     */
    public function testOriginalCcIsStoredInHeader(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setCc('cc@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $headers = $message->getHeaders();
        $this->assertArrayHasKey('X-Original-Cc', $headers);
        $this->assertStringContainsString('cc@example.com', $headers['X-Original-Cc']);
    }

    /**
     * Test original Bcc recipients are stored in X-Original-Bcc header
     */
    public function testOriginalBccIsStoredInHeader(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setBcc('bcc@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $headers = $message->getHeaders();
        $this->assertArrayHasKey('X-Original-Bcc', $headers);
        $this->assertStringContainsString('bcc@example.com', $headers['X-Original-Bcc']);
    }

    /**
     * Test X-Original-Cc header is not added when no Cc recipients
     */
    public function testXOriginalCcHeaderNotAddedWhenNoCc(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $headers = $message->getHeaders();
        $this->assertArrayNotHasKey('X-Original-Cc', $headers);
    }

    /**
     * Test X-Original-Bcc header is not added when no Bcc recipients
     */
    public function testXOriginalBccHeaderNotAddedWhenNoBcc(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $this->transport->send($message);

        $headers = $message->getHeaders();
        $this->assertArrayNotHasKey('X-Original-Bcc', $headers);
    }

    /**
     * Test subject prefix is added
     */
    public function testSubjectPrefixIsAdded(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'subjectPrefix' => '[TEST] ',
            'includeOriginalInSubject' => false,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $this->assertEquals('[TEST] Original Subject', $message->getSubject());
    }

    /**
     * Test default subject prefix is used
     */
    public function testDefaultSubjectPrefixIsUsed(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'includeOriginalInSubject' => false,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $this->assertEquals('[INTERCEPTED] Original Subject', $message->getSubject());
    }

    /**
     * Test original recipients are included in subject
     */
    public function testOriginalRecipientsIncludedInSubject(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'subjectPrefix' => '',
            'includeOriginalInSubject' => true,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $this->assertEquals('Original Subject [to: original@example.com]', $message->getSubject());
    }

    /**
     * Test original recipients not included in subject when disabled
     */
    public function testOriginalRecipientsNotIncludedInSubjectWhenDisabled(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'subjectPrefix' => '[TEST] ',
            'includeOriginalInSubject' => false,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $this->assertEquals('[TEST] Original Subject', $message->getSubject());
    }

    /**
     * Test subject not modified when prefix is empty and includeOriginalInSubject is false
     */
    public function testSubjectNotModifiedWhenBothOptionsDisabled(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'subjectPrefix' => '',
            'includeOriginalInSubject' => false,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $this->assertEquals('Original Subject', $message->getSubject());
    }

    /**
     * Test multiple To recipients in subject
     */
    public function testMultipleToRecipientsInSubject(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'subjectPrefix' => '',
            'includeOriginalInSubject' => true,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo([
            'user1@example.com' => 'User One',
            'user2@example.com' => 'User Two',
        ]);
        $message->setFrom('sender@example.com');
        $message->setSubject('Original Subject');

        $this->transport->send($message);

        $subject = $message->getSubject();
        $this->assertStringContainsString('user1@example.com', $subject);
        $this->assertStringContainsString('user2@example.com', $subject);
    }

    /**
     * Test underlying transport is called
     */
    public function testUnderlyingTransportIsCalled(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $result = $this->transport->send($message);

        $this->assertArrayHasKey('mock_sent', $result);
        $this->assertTrue($result['mock_sent']);
    }

    /**
     * Test transport returns result from underlying transport
     */
    public function testReturnsResultFromUnderlyingTransport(): void
    {
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => 'intercept@example.com',
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo('original@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Test Subject');

        $result = $this->transport->send($message);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mock_sent', $result);
    }

    /**
     * Test full interception flow with all features enabled
     */
    public function testFullInterceptionFlow(): void
    {
        $interceptEmail = 'dev@example.com';
        $this->transport->setConfig([
            'transport' => 'test_underlying',
            'to' => $interceptEmail,
            'subjectPrefix' => '[DEV] ',
            'includeOriginalInSubject' => true,
            'logInterceptions' => false,
        ]);

        $message = new Message();
        $message->setTo([
            'user@example.com' => 'Test User',
        ]);
        $message->setCc('cc@example.com');
        $message->setBcc('bcc@example.com');
        $message->setFrom('sender@example.com');
        $message->setSubject('Important Email');

        $this->transport->send($message);

        // Verify redirection
        $to = $message->getTo();
        $this->assertCount(1, $to);
        $this->assertArrayHasKey($interceptEmail, $to);

        // Verify Cc and Bcc cleared
        $this->assertEmpty($message->getCc());
        $this->assertEmpty($message->getBcc());

        // Verify headers
        $headers = $message->getHeaders();
        $this->assertEquals('user@example.com', $headers['X-Original-To']);
        $this->assertEquals('cc@example.com', $headers['X-Original-Cc']);
        $this->assertEquals('bcc@example.com', $headers['X-Original-Bcc']);

        // Verify subject
        $this->assertEquals('[DEV] Important Email [to: user@example.com]', $message->getSubject());
    }
}

/**
 * Mock Transport for testing
 */
class MockTransport extends AbstractTransport
{
    /**
     * @param Message $message Message to send
     * @return array
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
