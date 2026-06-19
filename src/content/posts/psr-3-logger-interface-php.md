---
title: "PSR-3 Logger Interface - Standardized Logging for PHP Applications"
description: "Master PSR-3, the PHP Logger Interface standard. Learn log levels, logger implementations, Monolog integration, and best practices for application logging."
pubDate: "2022-05-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-3", "PHP", "Logging", "Monolog", "PSR", "PHP-FIG", "Debugging"]
selected: false
---

If you've worked with PHP long enough, you've probably scattered `error_log()` calls throughout your code, built a custom Logger class at least once, or inherited a codebase with five different logging approaches. PSR-3 exists to kill that chaos.

PSR-3 is the PHP-FIG standard for logging interfaces. It doesn't tell you *how* to log. It defines a contract so that any logger implementing it can be swapped in without changing your application code. That contract is `Psr\Log\LoggerInterface`.

## What PSR-3 Defines

The entire standard fits in a single interface with nine methods. Eight correspond to RFC 5424 log levels, and one is a catch-all. Every method accepts a string message, an optional context array, and returns void.

```php
namespace Psr\Log;

interface LoggerInterface
{
    public function emergency(string|\Stringable $message, array $context = []): void;
    public function alert(string|\Stringable $message, array $context = []): void;
    public function critical(string|\Stringable $message, array $context = []): void;
    public function error(string|\Stringable $message, array $context = []): void;
    public function warning(string|\Stringable $message, array $context = []): void;
    public function notice(string|\Stringable $message, array $context = []): void;
    public function info(string|\Stringable $message, array $context = []): void;
    public function debug(string|\Stringable $message, array $context = []): void;
    public function log(mixed $level, string|\Stringable $message, array $context = []): void;
}
```

The `log()` method accepts a `$level` parameter from `Psr\Log\LogLevel`:

```php
class LogLevel
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
}
```

### Placeholders

Messages can contain placeholders like `{user_id}` or `{error_code}`. Implementations **must** replace these with values from the context array. The format uses brace-delimited names matching `/[a-zA-Z_][a-zA-Z0-9_]*/`.

```php
$logger->info('User {user_id} registered from IP {ip}', [
    'user_id' => $user->id(),
    'ip'      => $request->ip(),
]);
```

This pattern keeps messages readable while allowing structured data. A well-built logger will also write the full context array to a structured log file or service, giving you both human-readable messages and machine-parseable metadata.

## PSR-3 Log Levels

The eight levels are borrowed from syslog (RFC 5424). Here's what each means in practice:

### Emergency
The system is unusable. If you're paging someone at 3 AM, this is the level. "The database server has melted and the backup is also on fire."

### Alert
Action must be taken immediately. A critical service is down. Someone needs to fix this *now*.

### Critical
Critical conditions. Components or services are unavailable but the application can degrade gracefully. "The payment gateway is unreachable, falling back to offline processing."

### Error
Runtime errors that don't require immediate action but should be logged and monitored. "Failed to send email notification to user 1234."

### Warning
Exceptional occurrences that aren't errors. Deprecated APIs, poor configuration values, or things that might become problems. "Disk space below 20%."

### Notice
Normal but significant events. "User promoted to admin." "Cache flushed."

### Info
Interesting events. "User logged in." "Order #42 processed." This is your standard operational logging level.

### Debug
Detailed debug information you'd turn on when troubleshooting. Query logs, variable dumps, function entry/exit traces. Never log secrets here.

```php
$logger->emergency('Database connection pool exhausted');
$logger->alert('Payment gateway timeout threshold exceeded');
$logger->critical('Unable to reach inventory service');
$logger->error('Failed to process webhook from stripe', ['webhook_id' => $id]);
$logger->warning('SSL certificate expires in 7 days');
$logger->notice('User {id} role changed to admin', ['id' => $user->id()]);
$logger->info('Order {order_id} placed successfully', ['order_id' => $order->id]);
$logger->debug('SQL query executed', ['query' => $sql, 'bindings' => $bindings]);
```

## Monolog: The De Facto Standard

Monolog is the logger that ships with Laravel, Symfony, and most modern PHP frameworks. It implements PSR-3 and adds the concept of **Handlers** and **Processors**.

A Handler writes logs somewhere—file, socket, database, Slack, email, syslog, whatever. A Processor enriches log records with extra data (PID, IP, session ID, stack trace).

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger('app');
$logger->pushHandler(new StreamHandler('/var/log/app.log', Logger::DEBUG));
$logger->pushProcessor(new IntrospectionProcessor());
```

Monolog lets you chain handlers so critical errors go to email while debug messages only write to a file:

```php
$logger->pushHandler(new StreamHandler('/var/log/app.log', Logger::DEBUG));
$logger->pushHandler(new MailHandler($mailer), Logger::CRITICAL);
```

## Using PSR-3 in Your Applications

Type-hint against `LoggerInterface`, not a concrete class. This is dependency inversion in action.

```php
use Psr\Log\LoggerInterface;

class OrderProcessor
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function process(Order $order): void
    {
        try {
            // business logic
            $this->logger->info('Order {id} processed', ['id' => $order->id()]);
        } catch (\Throwable $e) {
            $this->logger->error('Order processing failed: {message}', [
                'message' => $e->getMessage(),
                'order_id' => $order->id(),
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
```

Your consuming code never knows or cares which logger is underneath. Swap Monolog for a different PSR-3 implementation without touching business logic.

### NullLogger

PSR-3 includes `Psr\Log\NullLogger` — a logger that accepts everything and does nothing. Useful as a default dependency so your library doesn't force logging on consumers:

```php
class YourLibraryService
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }
}
```

### LoggerAwareTrait

PSR-3 provides a convenience trait and interface for injectable loggers:

```php
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class SomeService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function doSomething(): void
    {
        $this->logger?->info('Doing something');
    }
}
```

## PSR-3 Compatible Logger Examples

Beyond Monolog, several PSR-3 implementations exist:

- **Analog** — A minimalist logger using a similar concept to Monolog but far lighter.
- **Apix Log** — A PSR-3 logger with syslog and file support.
- **Symfony Logger** — Symfony's own implementation, used internally and available for applications.
- **Laravel Log** — Laravel's facade wraps a PSR-3 logger; you can resolve `LoggerInterface` from the container.

You can also write your own. A simple implementation that writes JSON lines is only a few dozen lines:

```php
use Psr\Log\AbstractLogger;

class JsonFileLogger extends AbstractLogger
{
    public function __construct(private string $logPath) {}

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $record = [
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'level'     => $level,
            'message'   => (string) $message,
            'context'   => $this->sanitizeContext($context),
        ];

        file_put_contents(
            $this->logPath,
            json_encode($record) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    private function sanitizeContext(array $context): array
    {
        // Strip passwords and secrets before logging
        unset($context['password'], $context['secret'], $context['token']);
        return $context;
    }
}
```

## Best Practices

### Structured Logging

Log in structured formats (JSON) rather than plain text. Your log aggregation tool (ELK, Grafana Loki, Datadog) will thank you. Structured logs let you query by field value instead of regexing through text blobs.

```php
$logger->info('Payment processed', [
    'amount'   => $amount,
    'currency' => 'USD',
    'gateway'  => 'stripe',
    'duration' => $elapsedMs,
]);
```

### Context Data

Add enough context to each log line that you can debug without cross-referencing a dozen sources. Request IDs, user IDs, correlation IDs, and relevant entity IDs all belong in context.

But keep context bounded. Don't dump entire objects or request bodies. Include identifiers, not representations.

### Never Log Secrets

Passwords, credit card numbers, API keys, session tokens — these never belong in logs. Build sanitization into your logger or use processors to redact known patterns.

Monolog makes this easy with processors:

```php
$logger->pushProcessor(function ($record) {
    $record['context'] = array_diff_key($record['context'], array_flip(['password', 'cc_number']));
    return $record;
});
```

### Choose the Right Level

Default to `info` for normal operations. Use `debug` for detail you'd only need while troubleshooting. Don't log everything at `error` — reserve that for things that actually need human attention. Too much noise and your team will ignore the logs.

## Integration with Laravel

Laravel's logging system is Monolog under the hood. You configure channels in `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
    ],
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => 'info',
    ],
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'level' => 'critical',
    ],
],
```

You can resolve `Psr\Log\LoggerInterface` from the container throughout your application:

```php
use Psr\Log\LoggerInterface;

class ReportController
{
    public function __construct(private LoggerInterface $logger) {}

    public function generate(): Response
    {
        $this->logger->info('Report generation started');
        // ...
    }
}
```

## Integration with Symfony

Symfony uses its own implementation but it implements PSR-3. Configuration goes in `config/packages/monolog.yaml`:

```yaml
monolog:
    handlers:
        main:
            type: stream
            path: '%kernel.logs_dir%/%kernel.environment%.log'
            level: debug
        critical:
            type: stream
            path: '%kernel.logs_dir%/critical.log'
            level: critical
```

You inject the logger via autowiring:

```php
use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(private LoggerInterface $logger) {}
}
```

## PSR-3 in Library Development

If you're writing a PHP library, always depend on `psr/log` (the interface package), never on a specific logger. Your library should:

1. Accept an optional `LoggerInterface` in the constructor
2. Default to `NullLogger`
3. Log at appropriate levels throughout

```php
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ImageProcessor
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function process(string $path): void
    {
        $this->logger->info('Processing image: {path}', ['path' => $path]);

        try {
            // processing logic
        } catch (\RuntimeException $e) {
            $this->logger->error('Image processing failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

This keeps your library lightweight and framework-agnostic while giving consumers full logging control.

## Conclusion

PSR-3 solves a simple problem elegantly: it decouples logging from loggers. Your application code says *what* happened and *how important* it is, without caring *where* that information ends up. Monolog handles the heavy lifting in production, but the interface stays the same whether you're writing to files, sending to Slack, or piping into Datadog.

Type-hint against `LoggerInterface`, keep context meaningful, never log secrets, and choose levels intentionally. That's the entire playbook.
