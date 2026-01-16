# CakePHP Mail Interceptor

A CakePHP 5.x plugin that intercepts all outgoing emails and redirects them to a specified address. Perfect for development and staging environments where you want to test email functionality without sending emails to real users.

## Why Use This Plugin?

When developing or testing applications that send emails, you need a way to prevent emails from reaching real users. There are several approaches:

**Paid services** like Mailtrap or Mailosaur work great but require subscriptions and external dependencies.

**Local tools** like Mailpit, MailHog, or MailCatcher are excellent free alternatives, but they require local installation and configuration - which isn't always possible in shared hosting environments, Docker-less setups, or restricted infrastructure.

**This plugin** offers a zero-infrastructure solution:
- No additional services to install or maintain
- Works with your existing email transport (SMTP, Mailgun, SES, etc.)
- Simple configuration change - just wrap your existing transport
- Ideal for shared staging environments where installing local tools isn't an option
- Perfect for quick local development without setting up additional services

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
        'subjectPrefix' => 'DEV', // Optional: tag for subject line
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
| `subjectPrefix` | string | `'INTERCEPTED'` | Tag used in subject line prefix |
| `includeOriginalInSubject` | bool | `true` | Whether to include original recipients in subject prefix |
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
- Subject: `[DEV: user@example.com] Your Order Confirmation`
- Header `X-Original-To`: `user@example.com`
- Header `X-Original-Cc`: `manager@example.com`

## Recommended: Use Plus-Addressed Emails

We recommend using plus-addressed (subaddressed) emails for intercepted mail:

```php
'to' => 'dev+projectname@example.com',
// or with environment identifier
'to' => 'dev+myapp-staging@example.com',
```

**Benefits of plus addressing:**
- **Easy filtering** - Create inbox rules to automatically sort intercepted emails by project or environment
- **Quick identification** - Instantly see which project/environment an email came from
- **Single inbox** - Use one email account for all projects without mixing emails
- **No extra accounts** - No need to create separate email addresses for each project

Most email providers support plus addressing, including Gmail, Outlook, ProtonMail, Fastmail, and others.

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
