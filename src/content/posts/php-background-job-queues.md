---
title: "PHP Background Job Queues - From MySQL to Beanstalkd Workers"
description: "Learn how to implement background job queues in PHP. Compare MySQL-based queues, Beanstalkd, Laravel Horizon, and modern queue architectures with practical code examples."
pubDate: "2022-01-22 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Queues", "Background Jobs", "Beanstalkd", "Workers", "Performance", "Async", "Laravel", "DevOps"]
selected: false
---

You have that one API endpoint. The one that kicks off a video transcoding job, generates a massive PDF report, or sends out hundreds of notification emails. The one where you tell the user "please don't close your browser" and hope for the best. It works fine when one person uses it. Then two more teams start relying on it. Suddenly your carefully crafted PHP application starts timing out, consuming all available memory, and your users are staring at loading spinners for 30 seconds at a time.

You need background job queues.

## What You'll Learn

This article walks through the complete journey of implementing background job queues in PHP. You'll start with the fundamental problem—PHP's synchronous, blocking execution model—and build up through real solutions. You'll implement a MySQL-backed queue (and learn why it falls short), then move to a proper queue server with Beanstalkd and the Pheanstalk library. You'll see producer and consumer code, worker lifecycle management, and production best practices. By the end, you'll know exactly what queue architecture fits your application and how to implement it.

## The Blocking PHP Problem

PHP runs in a single process. When a request comes in, PHP processes it line by line, top to bottom, and returns a response. This is fine for 95% of web requests, but it becomes a bottleneck when you hit operations that take real time. Consider this:

```php
$video = $this->uploadVideo($request->file('video'));
$thumbnail = $this->transcodeThumbnail($video); // Takes 15 seconds
$this->notifyUser($video); // Takes 3 more seconds
return response()->json(['video' => $video]);
```

The user's HTTP request stays open for nearly 20 seconds. Their browser shows a spinner. HubSpot research shows that page load time matters most in the first four seconds—after that, conversion rates drop off a cliff. Your user isn't converting. They're closing the tab.

The deeper issue is what happens under load. PHP-FPM has a limited pool of workers. If every request to your video upload endpoint ties up a worker for 20 seconds, you exhaust that pool fast. New requests queue up at the web server level. The entire application slows down for everyone, even for endpoints that normally respond in milliseconds.

A simple `sleep()` example demonstrates the blocking nature:

```php
function handleRequest(array $request): string
{
    $data = $this->processInput($request);
    sleep(10); // Blocking! Nothing else can happen
    $result = $this->doWork($data);
    return $result;
}
```

Every second spent waiting inside that function is a second the PHP process can't serve another request. The solution is to move the slow work somewhere else—out of the request-response cycle entirely. That's where queues come in.

## The MySQL Polling Queue (And Why It Fails)

The most straightforward approach is to store jobs in a database table and have a worker script poll for new work. It's the natural first step for any PHP developer.

### Setting Up the Table

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->text('payload');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('reserved')->default(0);
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
```

### Enqueuing a Job

```php
readonly class ReportDispatcher
{
    public function __construct(
        private PDO $db,
    ) {}

    public function dispatchGenerateReport(
        string $reportType,
        int $userId,
        DateRange $dateRange,
    ): void {
        $query = <<<SQL
            INSERT INTO jobs (queue, payload, attempts, reserved, available_at, created_at)
            VALUES (?, ?, 0, 0, ?, ?)
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([
            'reports',
            json_encode([
                'type' => $reportType,
                'user_id' => $userId,
                'start_date' => $dateRange->start->format('Y-m-d'),
                'end_date' => $dateRange->end->format('Y-m-d'),
            ]),
            time(),
            time(),
        ]);
    }
}
```

### Polling Worker

```php
class MysqlWorker
{
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger,
    ) {}

    public function work(): void
    {
        while (true) {
            $job = $this->getNextJob();

            if ($job === null) {
                usleep(500_000);
                continue;
            }

            try {
                $this->processJob($job);
                $this->deleteJob($job['id']);
            } catch (\Throwable $e) {
                $this->handleFailure($job, $e);
            }
        }
    }

    private function getNextJob(): ?array
    {
        $this->db->beginTransaction();

        $query = <<<SQL
            SELECT * FROM jobs
            WHERE reserved = 0
                AND available_at <= ?
            ORDER BY created_at ASC
            LIMIT 1
            FOR UPDATE
        SQL;

        $statement = $this->db->prepare($query);
        $statement->execute([time()]);
        $job = $statement->fetch(PDO::FETCH_ASSOC);

        if ($job === false) {
            $this->db->rollBack();
            return null;
        }

        $update = $this->db->prepare(
            'UPDATE jobs SET reserved = 1, reserved_at = ? WHERE id = ?'
        );
        $update->execute([time(), $job['id']]);

        $this->db->commit();

        return $job;
    }

    private function processJob(array $job): void
    {
        $payload = json_decode($job['payload'], true, 512, JSON_THROW_ON_ERROR);
    }

    private function deleteJob(int $id): void
    {
        $this->db->prepare('DELETE FROM jobs WHERE id = ?')
            ->execute([$id]);
    }
}
```

### Why MySQL Queues Break Down

This approach works for a small application with minimal job volume. In production, four critical problems surface:

**No multi-worker safety.** The `SELECT ... FOR UPDATE` row lock works, but if a worker crashes after reserving a job but before deleting it, that job stays stuck in a "reserved" state forever. There's no built-in TTL. You need a separate cleanup process to find and re-queue stale reservations.

**Polling overhead.** Every 500 milliseconds, every worker runs a database query that returns nothing most of the time. At scale, this becomes a significant and unnecessary load on your database—the one resource you can least afford to waste.

**No dead letter handling.** Jobs that consistently fail will be retried indefinitely unless you add your own retry counting logic and a separate table for failed jobs. That's more code to write, test, and maintain.

**No priority system.** You're sorting by `created_at ASC`, so every job is equal. You can't say "process this password reset email before that monthly analytics report." Adding priority requires more columns, more indexing, and more complex queries.

These are solvable problems individually, but collectively they represent a significant engineering investment. You're building a queue system from scratch instead of solving your actual business problem.

## Beanstalkd: A Dedicated Queue Server

Beanstalkd is a simple, memory-backed queue server. It was designed specifically for job queues—nothing else. It speaks a tiny ASCII protocol over TCP. You can interact with it using telnet, but in practice you'll use a client library like Pheanstalk.

### Architecture Overview

Beanstalkd organizes jobs into **tubes**. A tube is a named queue. You push jobs into a tube from your application code (the **producer**), and a worker process (the **consumer**) watches that tube and reserves jobs for processing.

The lifecycle of a job in Beanstalkd is:

1. **put** — The producer inserts a job into a tube (default state: "ready")
2. **reserve** — A consumer watching the tube claims a ready job (state changes to "reserved")
3. **delete** — The consumer finishes processing and removes the job
4. **release** — The consumer fails and puts the job back in the "ready" state for retry
5. **bury** — The consumer decides the job is poison and buries it (dead letter equivalent)

### Installing Beanstalkd

On Ubuntu or Debian:

```bash
sudo apt-get install beanstalkd
```

On macOS with Homebrew:

```bash
brew install beanstalkd
```

Start the daemon with:

```bash
beanstalkd -l 127.0.0.1 -p 11300 -b /var/lib/beanstalkd
```

The `-b` flag enables persistent backing storage so jobs survive a server restart.

### Installing Pheanstalk

```bash
composer require pda/pheanstalk
```

## Producer: Putting Jobs on the Queue

The producer is the simplest part of any queue system. It creates a job description and pushes it into Beanstalkd.

```php
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Pheanstalk;

final readonly class ReportProducer
{
    public function __construct(
        private PheanstalkPublisherInterface $pheanstalk,
    ) {}

    public function dispatchReportGeneration(
        int $requestedByUserId,
        ReportType $reportType,
        array $filters,
    ): void {
        $job = new GenerateReportJob(
            requestedBy: $requestedByUserId,
            reportType: $reportType,
            filters: $filters,
            requestedAt: new \DateTimeImmutable(),
        );

        $this->pheanstalk
            ->useTube('reports')
            ->put(
                data: serialize($job),
                priority: 1024,
                delay: 0,
                ttr: 120,
            );
    }
}
```

The four arguments to `put()` are:

- **data** — The serialized job payload. Serialize with a format you control (JSON, PHP serialization, etc.)
- **priority** — Lower numbers process first. Priority 0 runs before priority 1024
- **delay** — Seconds to wait before the job becomes available to workers
- **ttr** — Time-to-run: the maximum seconds a worker has to process the job before Beanstalkd considers it failed and re-releases it

### The Job Data Transfer Object

Using a typed DTO ensures your job payloads stay consistent:

```php
final readonly class GenerateReportJob
{
    public function __construct(
        public int $requestedBy,
        public ReportType $reportType,
        public array $filters,
        public \DateTimeImmutable $requestedAt,
    ) {}

    public function __serialize(): array
    {
        return [
            'requested_by' => $this->requestedBy,
            'report_type' => $this->reportType->value,
            'filters' => $this->filters,
            'requested_at' => $this->requestedAt->format('c'),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->requestedBy = $data['requested_by'];
        $this->reportType = ReportType::from($data['report_type']);
        $this->filters = $data['filters'];
        $this->requestedAt = new \DateTimeImmutable($data['requested_at']);
    }
}
```

## Consumer: The Worker Process

The worker is a long-running PHP CLI script. It watches the `reports` tube, reserves jobs, and processes them.

### Basic Worker Loop

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Pheanstalk\Pheanstalk;

$pheanstalk = Pheanstalk::create('127.0.0.1');
$logger = new \Monolog\Logger('worker', [new \Monolog\Handler\StreamHandler('php://stdout')]);

$handler = new GenerateReportHandler(
    new ReportGenerator(),
    $logger,
);

$pheanstalk->watch('reports');
$pheanstalk->ignore('default');

while (true) {
    try {
        $job = $pheanstalk->reserveWithTimeout(5);

        if ($job === null) {
            continue;
        }

        try {
            $payload = unserialize($job->getData());

            match (true) {
                $payload instanceof GenerateReportJob => $handler->handle($payload),
                default => throw new \RuntimeException('Unknown job type: ' . get_class($payload)),
            };

            $pheanstalk->delete($job);
        } catch (\Throwable $e) {
            $logger->error('Job failed', [
                'job_id' => $job->getId(),
                'error' => $e->getMessage(),
            ]);

            $pheanstalk->release(
                $job,
                priority: 1024,
                delay: 30,
            );
        }
    } catch (\Throwable $e) {
        $logger->emergency('Worker error', ['error' => $e->getMessage()]);
    }
}
```

The worker blocks on `reserveWithTimeout()` waiting for a job. By default, `reserve()` blocks indefinitely. The timeout variant returns `null` if no job arrives within the specified seconds, giving the worker a chance to check for memory usage or shutdown signals.

### Memory Management

PHP's garbage collector handles most memory hygiene, but long-running workers still accumulate memory over time—especially if you use libraries that cache data internally. Restart the worker periodically:

```php
$jobCounter = 0;

while (true) {
    $job = $pheanstalk->reserveWithTimeout(5);

    if ($job === null) {
        continue;
    }

    ++$jobCounter;

    try {
        // Process job...
        $pheanstalk->delete($job);
    } catch (\Throwable $e) {
        $pheanstalk->release($job, 1024, 30);
    }

    if ($jobCounter >= 500) {
        $logger->info('Reached job limit, restarting worker');
        $pheanstalk->delete($job);
        pcntl_exec('/usr/bin/php', [__FILE__]);
        exit(0);
    }
}
```

`pcntl_exec()` replaces the current process with a fresh one. The operating system reaps all memory, closes file handles, and resets connection pools. It's a clean restart without requiring an external process supervisor.

### Graceful Shutdown

Register signal handlers to shut down cleanly:

```php
declare(strict_types=1);

$shutdown = false;

pcntl_signal(SIGTERM, function () use (&$shutdown): void {
    $shutdown = true;
});

pcntl_signal(SIGINT, function () use (&$shutdown): void {
    $shutdown = true;
});

while (true) {
    pcntl_signal_dispatch();

    if ($shutdown) {
        $logger->info('Shutdown signal received, finishing current job...');
        exit(0);
    }

    $job = $pheanstalk->reserveWithTimeout(5);
    // ... process job ...
}
```

## Process Supervision with SupervisorD

A worker script that runs in a `while(true)` loop will eventually crash—an uncaught exception, a segfault in a PHP extension, or the OOM killer. You need a process supervisor to restart it automatically.

Install SupervisorD:

```bash
sudo apt-get install supervisor
```

Create a configuration file:

```ini
; /etc/supervisor/conf.d/report-worker.conf

[program:report-worker]
command=/usr/bin/php /var/www/bin/worker.php --queue=reports
user=www-data
numprocs=4
process_name=%(program_name)s_%(process_num)02d
autostart=true
autorestart=true
startretries=3
stdout_logfile=/var/log/supervisor/report-worker.log
stderr_logfile=/var/log/supervisor/report-worker.log
stdout_logfile_maxbytes=10MB
stderr_logfile_maxbytes=10MB
```

The `numprocs=4` directive starts four separate worker processes, all watching the `reports` tube. Beanstalkd handles distributing jobs to available workers automatically—no additional coordination needed.

## The Queue vs. Async Debate

You might wonder: "Why not just make PHP async?" It's a fair question, especially with PHP 8.1+ introducing Fibers for cooperative multitasking.

### The Async Reality

Async PHP (via ReactPHP, Amp, or Swoole) allows a single process to handle multiple concurrent operations by yielding control during I/O waits. This works brilliantly for HTTP servers and websocket handlers. It works poorly for background jobs because:

**PDO blocks internally.** The standard PHP MySQL driver is synchronous at the C level. You can wrap it in a promise or a fiber, but it still blocks the entire process during a query. You need specialized async drivers (like `react/pdo-mysql` or Swoole's coroutine MySQL client) to get non-blocking database access.

**Redis calls block too.** Same problem: `predis` is blocking unless you use the async version. `phpredis` is a C extension that blocks natively.

**File operations block.** `file_get_contents()`, `fwrite()`, and `move_uploaded_file()` all block the calling process.

The practical result: any worker that touches a database, reads a file, or makes an HTTP request will become blocking at some point. Async helps at the edges but doesn't eliminate the need for a queue architecture.

### Multi-Threading

PHP has `pthreads` (and the newer parallel extension), but these come with serious caveats:

- `pthreads` requires PHP compiled with ZTS (Zend Thread Safety), which most distributions don't enable
- Shared state between threads introduces mutex complexity
- Threads share the same memory space—one segfault kills everything
- Debugging thread contention is hard

Worker processes (separate OS processes, not threads) provide better isolation. One worker can crash without affecting the others. Each worker has its own memory space with no shared state to manage. The operating system handles process scheduling, which it's very good at.

**Simple, separate workers running in parallel under SupervisorD are almost always the right answer.**

## Modern Alternatives

The approach described here—Beanstalkd with Pheanstalk—is a solid, lightweight solution. It's not the only option.

### Laravel Queues + Horizon

If you're building on Laravel, use Laravel's built-in queue system. It supports database, Redis, Beanstalkd, Amazon SQS, and IronMQ drivers with a unified API. Laravel Horizon provides a dashboard for monitoring queues, failed jobs, worker counts, and job throughput:

```php
class ProcessPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Podcast $podcast,
    ) {}

    public function handle(AudioProcessor $processor): void
    {
        $processor->process($this->podcast);
    }
}
```

Horizon handles worker management, job retries, rate limiting, and failed job storage. It's production-ready for all but the highest-throughput applications.

### Symfony Messenger

Symfony Messenger provides a message bus abstraction that can route messages through any transport: synchronous, Doctrine (database), Redis, Amazon SQS, or AMQP (RabbitMQ). It supports middleware for logging, retry, transaction wrapping, and custom behavior:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'App\Message\GenerateReport': async
```

### Redis Queues

Redis Lists provide a natural queue primitive with `LPUSH` (enqueue) and `BRPOP` (blocking dequeue). The `BRPOP` command blocks the connection until an element is available, avoiding the polling problem. PHP libraries like `predis` and `phpredis` support this natively. Redis queues are fast and simple, but they lack Beanstalkd's TTR mechanism and tube-based organization.

### RabbitMQ, AWS SQS, Google Pub/Sub

For high-throughput or distributed systems, consider dedicated message brokers:

- **RabbitMQ** supports complex routing (direct, topic, fanout exchanges), message acknowledgments, dead letter exchanges, and clustering
- **AWS SQS** provides fully managed queues with at-least-once delivery, configurable visibility timeouts, and dead letter queues
- **Google Cloud Pub/Sub** offers push and pull subscriptions, exactly-once delivery, and global message ordering

## Real-World Use Cases

Background queues handle any operation that shouldn't block an HTTP response:

**Report generation.** A user requests a PDF of last quarter's sales data. The report takes 30 seconds to generate and 2 MB to render. Queue the job, return a "processing" response immediately, and notify the user when the report is ready.

**Email and notification delivery.** Sending 1,000 transactional emails through an external API takes minutes. Queue each email as an individual job and let the workers deliver them as fast as the API allows.

**Video and image processing.** Transcoding a video, generating thumbnails, or running OCR on scanned documents are CPU-bound operations that belong in a queue. Multiple workers can process files in parallel.

**Webhook delivery.** Your platform sends webhooks to 5,000 merchants. Queue each webhook call so a failure to reach one merchant doesn't delay the others. Implement retry with exponential backoff by releasing the job back to the queue with increasing delay.

**Data import and export.** Batch importing 50,000 CSV rows or exporting a filtered customer list takes seconds or minutes. Queue the job and let the user check progress later.

**Notification delivery.** Push notifications, SMS messages, Slack alerts—anything that calls an external API should be queued for resilience and retry capability.

## Best Practices

**Retry with exponential backoff.** Don't retry failed jobs immediately. Start with a 10-second delay, then 30 seconds, then 2 minutes, then 10 minutes. Cap the maximum retries at 5-10 before burying the job.

**Dead letter queues.** Jobs that exceed their retry limit should be buried (Beanstalkd) or moved to a failed job table (MySQL) for manual inspection. Never throw them away silently.

**Worker monitoring.** Log every job start, completion, and failure with structured data. Monitor queue depth, worker count, and processing latency with a tool like Prometheus or Laravel Horizon.

**Graceful shutdown.** Workers should catch SIGTERM and SIGINT, finish their current job, and exit cleanly. This prevents orphaned jobs and stale database connections.

**Memory management.** Workers accumulate memory over time. Restart them periodically—either after N jobs, after M minutes, or via SupervisorD's configuration.

**Idempotency.** Design jobs to be safe to run twice. Use database transactions with upsert logic, or track processed job IDs to prevent duplicate side effects.

## Common Mistakes

**Infinite memory growth.** No memory limit checks inside the worker loop. Libraries cache data, log handlers accumulate buffers, and leaked references prevent garbage collection. Always restart workers after a fixed number of jobs.

**No error handling.** Your worker crashes on the first unhandled exception, the reserved job sits there until the TTR expires, and nobody notices until a user complains. Wrap the job processing logic in try/catch, log everything, and release or bury the job.

**Forgetting to delete jobs.** You process a job successfully but never call `delete()`. Beanstalkd re-releases it after the TTR expires, and your worker processes it again. And again. Your users receive 47 copies of the same report.

**Not handling worker crashes.** If your worker process dies (SIGKILL from OOM killer, segfault, `exit()` in vendor code), the reserved job hangs until the TTR expires. Monitor your workers and restart them automatically with SupervisorD.

## Frequently Asked Questions

**When should I use a database-backed queue instead of Beanstalkd?** Use a database queue for very simple applications with low job volume (fewer than 100 jobs per hour) where the operational cost of running Beanstalkd isn't justified. Upgrade to a dedicated queue before you hit 1,000 jobs per hour.

**What's the best queue for high-throughput systems?** RabbitMQ or Amazon SQS for throughput exceeding 10,000 jobs per second. Beanstalkd or Redis for moderate throughput (1,000-10,000 jobs per second).

**How many workers should I run?** Start with `numprocs=4` and watch your job processing latency. Add workers until the queue depth stays near zero during peak load. Watch for database connection pool exhaustion—each worker holds its own connection.

**Does Laravel Horizon support Beanstalkd?** No. Horizon only supports the Redis queue driver. Laravel's base queue system supports Beanstalkd, but without the Horizon dashboard.

**Should I use JSON or PHP serialization for job payloads?** JSON is language-agnostic, human-readable, and smaller. PHP serialization preserves object types and supports `__serialize()`/`__unserialize()` hooks. Use JSON if you might process jobs with non-PHP workers. Use PHP serialization for object fidelity within a PHP-only system.

**How do I handle jobs that need to run at a specific time?** Set the `delay` parameter on `put()`. Beanstalkd won't make the job ready until the delay expires. For more complex scheduling (every day at 3 AM), use cron to enqueue the job at the right time, or use a dedicated scheduler like Laravel's task scheduling.

**What happens when all workers are busy?** Beanstalkd holds ready jobs in memory until a worker reserves them. The queue depth increases but no jobs are lost (assuming persistent storage is enabled). You can add more workers to clear the backlog, and they'll automatically pick up waiting jobs.

**Can I run multiple types of jobs through one set of workers?** Yes. Watch multiple tubes from one worker, or run dedicated workers per tube. Dedicated workers are simpler to reason about—one worker type, one tube, one job handler.

## Conclusion

Background job queues transform a PHP application from a fragile, synchronous monolith into a resilient, scalable system. The journey starts with a simple insight: not all work needs to happen during the HTTP request.

MySQL-backed queues get you started but introduce operational complexity at scale. Beanstalkd provides a purpose-built solution with tubes, TTR, priorities, and dead letter handling—everything you need for a production queue system. The Pheanstalk library makes integration with PHP straightforward, with clear producer and consumer patterns.

Worker lifecycle management matters as much as the queue itself. Graceful shutdowns, periodic restarts, and process supervision with SupervisorD keep your workers running reliably through code updates, memory leaks, and hardware failures.

Modern frameworks like Laravel and Symfony have integrated queue systems that abstract away much of this complexity. But understanding the fundamentals—how jobs move from producer to consumer, how TTR prevents stuck jobs, how worker isolation beats threading—makes you a better engineer regardless of the tools you choose.

Build your queue. Move slow work out of your request cycle. Your users will thank you when that spinner disappears and the page loads in under four seconds.
