<?php
declare(strict_types=1);

namespace MailInterceptor\Mailer\Transport;

use Cake\Log\Log;
use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\Mailer\TransportFactory;
use InvalidArgumentException;
use Throwable;
use function Cake\I18n\__d;

/**
 * Intercept Transport
 *
 * Redirects all outgoing emails to a specified address while preserving
 * original recipient information in headers. Useful for development and
 * staging environments.
 */
class InterceptTransport extends AbstractTransport
{
    /**
     * Default configuration
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'transport' => null,
        'to' => null,
        'subjectPrefix' => 'INTERCEPTED',
        'includeOriginalInSubject' => true,
        'logInterceptions' => true,
    ];

    /**
     * Send mail
     *
     * @param \Cake\Mailer\Message $message Message instance
     * @return array<string, mixed>
     * @throws \InvalidArgumentException When required config is missing
     */
    public function send(Message $message): array
    {
        $transport = $this->getConfig('transport');
        if (!is_string($transport) || $transport === '') {
            throw new InvalidArgumentException(
                __d(
                    'mail_interceptor',
                    'InterceptTransport requires "transport" config option to specify the underlying transport.',
                ),
            );
        }

        $to = $this->getConfig('to');
        if (!is_string($to) || $to === '') {
            throw new InvalidArgumentException(
                __d(
                    'mail_interceptor',
                    'InterceptTransport requires "to" config option to specify the intercept email address.',
                ),
            );
        }

        $originalTo = $message->getTo();
        $originalCc = $message->getCc();
        $originalBcc = $message->getBcc();
        $originalSubject = $message->getSubject();

        $originalToList = implode(', ', array_keys($originalTo));
        $originalCcList = implode(', ', array_keys($originalCc));
        $originalBccList = implode(', ', array_keys($originalBcc));

        // Add original recipients to headers for debugging
        $headers = ['X-Original-To' => $originalToList];
        if ($originalCcList !== '') {
            $headers['X-Original-Cc'] = $originalCcList;
        }
        if ($originalBccList !== '') {
            $headers['X-Original-Bcc'] = $originalBccList;
        }
        $message->addHeaders($headers);

        // Redirect all recipients to intercept address
        $message
            ->setTo([$to => $to])
            ->setCc([])
            ->setBcc([]);

        // Modify subject if configured
        $subjectPrefix = (string)$this->getConfig('subjectPrefix');
        if ($subjectPrefix !== '' || $this->getConfig('includeOriginalInSubject')) {
            if ($this->getConfig('includeOriginalInSubject') && $originalToList !== '') {
                $prefix = '[' . $subjectPrefix . ': ' . $originalToList . '] ';
            } elseif ($subjectPrefix !== '') {
                $prefix = '[' . $subjectPrefix . '] ';
            } else {
                $prefix = '';
            }
            $message->setSubject($prefix . $originalSubject);
        }

        // Log interception if enabled
        if ($this->getConfig('logInterceptions')) {
            Log::write(
                'info',
                sprintf(
                    __d(
                        'mail_interceptor',
                        'Mail intercepted: redirected to=%s; original_to=%s; subject=%s',
                    ),
                    $to,
                    $originalToList,
                    $message->getSubject(),
                ),
                ['scope' => 'email'],
            );
        }

        try {
            $result = TransportFactory::get($transport)->send($message);
        } catch (Throwable $e) {
            Log::write(
                'error',
                __d(
                    'mail_interceptor',
                    'InterceptTransport: underlying transport failed: {0}',
                    $e->getMessage(),
                ),
                ['scope' => 'email'],
            );
            throw $e;
        }

        return $result;
    }
}
