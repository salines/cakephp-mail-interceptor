# CakePHP Mail Interceptor

A CakePHP 5.x plugin that intercepts all outgoing emails and redirects them to a specified address. Perfect for development and staging environments where you want to test email functionality without sending emails to real users.

## Features

- Redirects all outgoing emails to a single address
- Preserves original recipient information in email headers (`X-Original-To`, `X-Original-Cc`, `X-Original-Bcc`)
- Adds configurable subject prefix to identify intercepted emails
- Optionally includes original recipients in subject line
- Logs all intercepted emails
- Works with any underlying CakePHP mail transport (SMTP, Mailgun, etc.)

## Requirements

- PHP 8.1+
- CakePHP 5.0+

## Installation

```bash
composer require salines/cakephp-mail-interceptor
```

Load the plugin in your `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('MailInterceptor');
}
```

## Configuration

Configure the transport in your `config/app_local.php`:

```php
'EmailTransport' => [
    // Your real transport (used in production)
    'smtp' => [
        'className' => \Cake\Mailer\Transport\SmtpTransport::class,
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'secret',
        'tls' => true,
    ],

    // Intercept transport (used in development/staging)
    'default' => [
        'className' => \MailInterceptor\Mailer\Transport\InterceptTransport::class,
        'transport' => 'smtp', // The underlying transport to use
        'to' => 'dev@example.com', // Where all emails will be redirected
        'subjectPrefix' => '[DEV] ', // Optional: prefix for subject line
        'includeOriginalInSubject' => true, // Optional: add original recipients to subject
        'logInterceptions' => true, // Optional: log intercepted emails
    ],
],
```

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `transport` | string | *required* | Name of the underlying transport to use for sending |
| `to` | string | *required* | Email address where all emails will be redirected |
| `subjectPrefix` | string | `'[INTERCEPTED] '` | Prefix added to email subject |
| `includeOriginalInSubject` | bool | `true` | Whether to append original recipients to subject |
| `logInterceptions` | bool | `true` | Whether to log intercepted emails |

## How It Works

When an email is sent through the `InterceptTransport`:

1. Original recipients (To, Cc, Bcc) are saved to custom headers
2. All recipients are replaced with the configured `to` address
3. Subject is modified with prefix and/or original recipients
4. Email is sent using the underlying transport
5. Interception is logged (if enabled)

### Example

Original email:
- To: `user@example.com`
- Cc: `manager@example.com`
- Subject: `Your Order Confirmation`

Intercepted email:
- To: `dev@example.com`
- Subject: `[DEV] Your Order Confirmation [to: user@example.com]`
- Header `X-Original-To`: `user@example.com`
- Header `X-Original-Cc`: `manager@example.com`

## Environment-Based Configuration

A common pattern is to use the intercept transport only in non-production environments:

```php
// config/app_local.php
'EmailTransport' => [
    'smtp' => [
        'className' => \Cake\Mailer\Transport\SmtpTransport::class,
        // ... smtp config
    ],
    'default' => env('APP_ENV') === 'production'
        ? [
            'className' => \Cake\Mailer\Transport\SmtpTransport::class,
            // ... production config
        ]
        : [
            'className' => \MailInterceptor\Mailer\Transport\InterceptTransport::class,
            'transport' => 'smtp',
            'to' => 'dev@example.com',
        ],
],
```

## License

MIT License. See [LICENSE](LICENSE) for details.
