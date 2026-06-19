---
title: "Laravel Horizon - Supercharge Your Queue Management"
description: "Learn how to configure and monitor Laravel Horizon for queue management. From Redis setup to production deployment, master Laravel's powerful queue dashboard."
pubDate: "2022-03-14 21:00:00"
category: "laravel"
banner: "/logo.svg"
tags: ["Laravel", "Horizon", "Queues", "Redis", "PHP", "Job Processing", "Performance", "Laravel Queue"]
selected: false
---

Your application is running smoothly in development. Then you deploy to production. Users start uploading files, requesting reports, and triggering email notifications. Suddenly your response times crawl past five seconds. PHP-FPM workers are all busy. New requests queue up at the web server. Your carefully built application is now a parking lot at rush hour.

You need queues. And if you're on Laravel with Redis, you need Horizon.

Horizon gives you a real-time dashboard for your queues—job throughput, failed jobs, wait times, worker counts—all visible from a single interface. It auto-balances workers across your queues, tags jobs for easy monitoring, and integrates directly with Laravel's queue system. No more SSH-ing into servers to grep log files. No more guessing whether a worker is alive or dead.

## What You'll Learn

This guide covers everything you need to run Laravel Horizon in production. You'll install Horizon, configure it for your application's workload, deploy it safely, and monitor your queues like a pro. By the end, you'll have a complete queue observability stack that tells you exactly what your workers are doing at all times.

## Prerequisites

Before you install Horizon, make sure your stack meets these requirements:

- **Laravel 8 or later** — Horizon ships as a first-party package maintained by the Laravel team. It supports Laravel 8, 9, 10, and 11.
- **Redis** — Horizon only works with the Redis queue driver. It leverages Redis' data structures for job storage, rate limiting, and the dashboard's real-time metrics. You cannot use Horizon with Beanstalkd, Amazon SQS, or the database driver.
- **Composer** — You'll install Horizon via Composer like any other Laravel package.
- **PHP 8.0+** — Horizon requires a recent PHP version for its job tagging and event system.

### Installing Redis

On Ubuntu or Debian:

```bash
sudo apt-get install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

On macOS with Homebrew:

```bash
brew install redis
brew services start redis
```

Verify Redis is running:

```bash
redis-cli ping
# Should return: PONG
```

### Configure Laravel's Queue Driver

Open your `.env` file and set the queue connection to Redis:

```
QUEUE_CONNECTION=redis
```

Laravel uses the `QUEUE_CONNECTION` environment variable to determine which queue driver to use for all queue operations. Setting it to `redis` tells Laravel to store jobs in Redis lists instead of the default database table.

### Install the Redis PHP Extension

Laravel supports two Redis clients: `predis` (PHP package) and `phpredis` (C extension). Horizon works with both, but `phpredis` is recommended for production because it's significantly faster and uses less memory.

Install phpredis:

```bash
sudo pecl install redis
```

Or use the predis package:

```bash
composer require predis/predis
```

Configure which client Laravel uses in `config/database.php`:

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    // ...
],
```

## Installing Horizon

Install Horizon via Composer:

```bash
composer require laravel/horizon
```

Publish the configuration and assets:

```bash
php artisan horizon:install
```

This command publishes two things:

1. `config/horizon.php` — The main configuration file where you define environments, supervisors, and queue balancing rules.
2. `app/Providers/HorizonServiceProvider.php` — A service provider for authorization and customization hooks.

## Configuration Deep Dive

The `config/horizon.php` file controls everything about your Horizon installation. Let's walk through every section.

### Environments

Horizon supports multiple environments so you can run different configurations locally, on staging, and in production:

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'reports'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 20,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],

    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'simple',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

Each environment key maps to the value of `APP_ENV` in your `.env` file. Horizon loads only the configuration for the current environment, ignoring the rest. This means you can define production-specific supervisors with aggressive scaling and keep local development lightweight.

### Supervisor Configuration

A supervisor is a group of worker processes that monitor one or more queues. You can define multiple supervisors per environment, each with its own configuration. This is useful when you have workloads with different requirements—for example, a supervisor for high-priority jobs and another for batch processing.

Key supervisor options:

| Option | Description |
|--------|-------------|
| `connection` | The Redis connection to use (default: `redis`) |
| `queue` | Array of queue names this supervisor monitors |
| `balance` | Worker balancing strategy: `auto`, `simple`, or `false` |
| `maxProcesses` | Maximum worker processes for `auto` balancing |
| `minProcesses` | Minimum worker processes for `auto` balancing (Laravel 9+) |
| `processes` | Fixed worker count for `simple` or `false` balancing |
| `tries` | Maximum retry attempts per job |
| `timeout` | Maximum seconds a job can run before the worker kills it |
| `memory` | Memory limit in MB per worker |
| `nice` | Process priority (0 = normal, higher = lower priority) |
| `maxTime` | Maximum seconds a worker can run before restarting (0 = unlimited) |
| `maxJobs` | Maximum jobs a worker can process before restarting (0 = unlimited) |

### Balancing Strategies

Horizon offers three balancing strategies that determine how worker processes are distributed across your queues.

**`balance => 'auto'`**

The recommended strategy for production. Horizon monitors the workload on each queue and dynamically allocates worker processes to the queues that need them most. If your `notifications` queue has 500 pending jobs and your `default` queue is empty, Horizon shifts workers to notifications automatically.

The `autoScalingStrategy` option controls how Horizon decides which queues need more workers:

- `time` (default) — Based on job processing time. Queues with slower jobs get more workers.
- `size` — Based on queue depth. Queues with more pending jobs get more workers.

```php
'supervisor-1' => [
    'balance' => 'auto',
    'autoScalingStrategy' => 'time',
    'minProcesses' => 2,
    'maxProcesses' => 20,
],
```

Horizon with `auto` balancing is the primary reason to use Horizon over a vanilla `php artisan queue:work` setup. It adapts to traffic patterns without manual intervention.

**`balance => 'simple'`**

Distributes worker processes equally across all queues in round-robin fashion. Each queue gets the same number of workers. This is useful for development or when all queues have similar workloads:

```php
'supervisor-1' => [
    'balance' => 'simple',
    'processes' => 3,
    'queue' => ['default', 'notifications', 'reports'],
],
```

With three processes and three queues, each queue gets one dedicated worker. If one queue backs up, it stays backed up until its worker clears it—no dynamic rebalancing.

**`balance => false`**

Disables balancing entirely. All workers process jobs from all queues in the order they're listed. Jobs in the first queue are processed first. This is the default `queue:work` behavior:

```php
'supervisor-1' => [
    'balance' => false,
    'processes' => 5,
    'queue' => ['high', 'default', 'low'],
],
```

All five workers process `high` queue jobs first, then `default`, then `low`. If `high` is empty, workers move to `default`. No queue starvation, but no prioritization beyond the ordering.

### Wait Time Calculation

Horizon calculates wait times by dividing the number of pending jobs by the processing rate. This appears in the dashboard as an estimated wait time for each queue. The calculation uses a sliding window of recent processing history, so it adapts to changing conditions.

The formula is:

```
estimated_wait_seconds = pending_jobs / (jobs_processed_last_minute / 60)
```

A queue with 600 pending jobs and a processing rate of 120 jobs per minute shows a 5-minute estimated wait. This helps you spot bottlenecks before they become customer-facing problems.

## Tags

Horizon's tagging system lets you associate metadata with every job. Tags appear in the dashboard, making it trivial to filter and search for specific jobs:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Bus\Queueable;

class ProcessPodcast implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function __construct(
        public Podcast $podcast,
    ) {}

    public function tags(): array
    {
        return [
            'podcast:' . $this->podcast->id,
            'author:' . $this->podcast->author_id,
        ];
    }

    public function handle(AudioProcessor $processor): void
    {
        $processor->process($this->podcast);
    }
}
```

The `tags()` method returns an array of strings. Horizon automatically includes the job class name as a tag, so `ProcessPodcast` jobs always have at least the `ProcessPodcast` tag.

In the dashboard, you can search by tag to see all jobs related to a specific podcast, user, or order. This is invaluable when debugging a failure for a specific customer—just search for their user ID tag and see every job processed for them.

### Tagging with Models

The most common pattern is tagging with Eloquent model keys:

```php
public function tags(): array
{
    return [
        'user:' . $this->user->id,
        'team:' . $this->user->team_id,
        'order:' . $this->order->id,
    ];
}
```

Now when a user reports that their order confirmation never arrived, search the Horizon dashboard for `user:42` or `order:1001`. You'll see every job related to that user or order, including any failures.

### Tagging for Monitoring

Tags also enable metrics aggregation. You can see how many jobs are running, failed, or completed for any tag combination. This is useful for tracking business-level metrics:

```php
public function tags(): array
{
    return [
        'billing:invoice',
        'customer:' . $this->customer->id,
        'tier:' . $this->customer->tier,
    ];
}
```

Now you can monitor invoice processing times per customer tier. If enterprise-tier customers are seeing slow processing, you'll see it in the dashboard immediately.

## The Horizon Dashboard

Once Horizon is installed and configured, start the dashboard with:

```bash
php artisan horizon
```

Navigate to `/horizon` in your browser. The dashboard shows several sections:

### Overview

The main view displays:
- **Jobs processed per minute** — Throughput chart showing recent activity
- **Jobs failed per minute** — Failure rate at a glance
- **Job wait times** — Estimated seconds until each queue is empty
- **Throughput** — Jobs completed per second
- **Runtime** — How long Horizon has been running
- **Recent jobs** — The last 25 processed jobs with their status

### Monitoring Tab

Shows all running jobs with their tags, the queue they're on, and how long they've been processing. If a job is stuck, you'll see it here immediately.

### Failed Jobs Tab

Every failed job appears here with the full exception message, stack trace, the job payload, and the tags. You can retry individual failed jobs or retry all of them with a single click. Horizon stores failed jobs in Redis by default—they persist until you retry or purge them.

### Jobs Tab

Searchable, filterable list of all recent jobs. This is where tags shine. Type `user:42` in the search box and see every job related to that user.

### Queue Metrics

Shows per-queue throughput, wait times, and process counts. Use this to identify queues that need more workers or optimization.

## Starting Horizon in Production

In production, you don't run `php artisan horizon` directly. You use a process supervisor—typically SupervisorD—to keep Horizon running continuously.

Install SupervisorD:

```bash
sudo apt-get install supervisor
```

Create a configuration file:

```ini
; /etc/supervisor/conf.d/horizon.conf

[program:horizon]
process_name=%(program_name)s
command=php /var/www/artisan horizon
user=forge
autostart=true
autorestart=true
stopwaitsecs=3600
stopasgroup=true
killasgroup=true
stdout_logfile=/var/www/storage/logs/horizon.log
stderr_logfile=/var/www/storage/logs/horizon.log
```

The critical settings are:

- **`stopwaitsecs=3600`** — Gives Horizon up to an hour to finish processing jobs before SupervisorD sends SIGKILL. Horizon handles the SIGTERM gracefully, letting current jobs finish and preventing new ones from starting.
- **`stopasgroup=true`** and **`killasgroup=true`** — Ensures the entire Horizon process tree (all child workers) is terminated together. Without this, orphaned worker processes can continue running after Horizon restarts.

Reload SupervisorD:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon
```

### Deploying Horizon Updates

When you deploy new code, you need to restart Horizon so workers pick up the changes:

```bash
php artisan horizon:terminate
```

This sends a SIGTERM to the Horizon master process, which then waits for all workers to finish their current jobs before exiting. SupervisorD detects that the process stopped and automatically restarts it, loading your new code.

The `horizon:terminate` command is safe to run in deployment scripts. It won't kill in-flight jobs—Horizon waits for them to complete (up to the `stopwaitsecs` limit).

## Authorization

Horizon's dashboard should not be publicly accessible. By default, Horizon only allows access in the `local` environment. In production, you need to configure authorization.

Open `app/Providers/HorizonServiceProvider.php`:

```php
use Illuminate\Support\Facades\Gate;

protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}
```

Horizon calls this gate before rendering any dashboard page. If the gate returns `false`, the user gets a 403 response.

You can use any authorization logic here—check for a specific role, permission, or email domain:

```php
Gate::define('viewHorizon', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('developer');
});
```

For applications using Laravel Nova or Spark, Horizon integrates with their existing authorization gates automatically.

### IP-Based Restriction

Combine the Horizon gate with IP whitelisting for defense in depth. In your `app/Providers/HorizonServiceProvider.php`:

```php
protected function gate(): void
{
    Gate::define('viewHorizon', function ($user) {
        if (! in_array(request()->ip(), config('horizon.allowed_ips'))) {
            return false;
        }

        return $user->hasRole('admin');
    });
}
```

## Notifications for Failed Queues

Horizon can notify you when a queue has too many recent failures. Configure this in `config/horizon.php`:

```php
'waits' => [
    'redis:default' => 60,
],

'notifications' => [
    'environments' => [
        'production' => ['slack'],
    ],

    'slack' => [
        'webhook_url' => env('HORIZON_SLACK_WEBHOOK_URL'),
    ],

    'sms' => [
        'phone_number' => env('HORIZON_SMS_NUMBER'),
    ],

    'email' => [
        'to' => ['admin@example.com'],
    ],
],
```

The `waits` array sets the threshold (in seconds) for each queue connection. When the estimated wait time exceeds this value, Horizon checks to see if failures are the cause and sends a notification.

The `notifications` array controls where alerts go. You can configure Slack, SMS (via Nexmo/Vonage), or email. Enable notifications only in production environments—you don't need alerts when you're running a single worker locally.

Send a test notification to verify your configuration:

```bash
php artisan horizon:test-notification
```

### Configuring Slack

For Slack notifications, create a webhook in your Slack workspace:

1. Go to `https://your-workspace.slack.com/apps/new/A0F7XDUAZ-incoming-webhooks`
2. Select a channel
3. Copy the webhook URL
4. Set it in your `.env`:

```
HORIZON_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00/B00/xxxxx
```

Horizon sends a message with the queue name, wait time, and failure count when the threshold is breached.

## Real-World Monitoring Tips

Running Horizon in production for real traffic reveals patterns you won't see in staging. Here are practical tips from production deployments.

### Watch Queue Wait Times, Not Just Worker Counts

It's tempting to monitor "are all workers running?" but that tells you nothing about whether jobs are piling up. A fully healthy Horizon with all workers active can still have a 30-minute queue backlog if you're under-provisioned. Monitor the estimated wait time per queue and alert when it exceeds acceptable thresholds.

### Configure maxJobs for Memory Leaks

PHP's garbage collector handles most memory leaks, but not all. Some libraries cache data in static properties that persist across jobs. Set `maxJobs` on your supervisors to restart workers periodically:

```php
'supervisor-1' => [
    'balance' => 'auto',
    'maxJobs' => 500,
    'maxTime' => 3600,
],
```

This restarts each worker after 500 jobs or 1 hour, whichever comes first. The restart is graceful—the worker finishes its current job before exiting. Horizon spawns a replacement immediately.

### Use Separate Queues for Different Priorities

Don't put everything on the `default` queue. Define separate queues for different workload types:

- `high` — Password resets, payment processing, account critical operations
- `default` — Email notifications, standard processing
- `low` — Report generation, data exports, cleanup tasks
- `reports` — Long-running report generation

Configure supervisors to handle them appropriately:

```php
'production' => [
    'supervisor-high' => [
        'connection' => 'redis',
        'queue' => ['high'],
        'balance' => 'auto',
        'maxProcesses' => 10,
        'tries' => 1,
        'timeout' => 30,
    ],

    'supervisor-default' => [
        'connection' => 'redis',
        'queue' => ['default', 'low', 'reports'],
        'balance' => 'auto',
        'maxProcesses' => 20,
        'tries' => 3,
        'timeout' => 120,
    ],
],
```

High-priority jobs get their own supervisor with no competing queues. If a report generation job hangs for 10 minutes, it doesn't delay password reset emails.

### Monitor Redis Memory

Horizon stores everything in Redis—pending jobs, dashboard metrics, failed jobs, tags. Redis is an in-memory database, which means it uses RAM. A runaway queue can fill Redis memory and crash your Redis server.

Monitor Redis memory usage:

```bash
redis-cli INFO memory
# Look for: used_memory_human
```

Set a max memory policy in your `redis.conf`:

```
maxmemory 2gb
maxmemory-policy allkeys-lru
```

Horizon's failed jobs can accumulate quickly if you have a bug in production. Purge failed jobs regularly via the dashboard or the CLI:

```bash
php artisan horizon:purge
```

Add a cron job to clean up old failed jobs automatically:

```php
// App\Console\Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('horizon:purge')->hourly();
}
```

### Use Snapshot Mode for High-Traffic Applications

If you're processing thousands of jobs per minute, the Horizon dashboard's real-time metrics can become expensive. Enable snapshot mode to reduce Redis overhead:

```bash
php artisan horizon:snapshot
```

This writes dashboard data to Redis at intervals instead of continuously. In `config/horizon.php`:

```php
'use' => 'default',

'waits' => [
    'redis:default' => 60,
],
```

For very high traffic, consider running a dedicated Redis instance for Horizon separate from your application cache to prevent queue operations from evicting cache data.

### Tag Meaningfully, Not Excessively

Tags are searchable in the dashboard, so they're your primary debugging tool. But don't go overboard—every tag is stored in Redis. Tag with identifiers that help you debug:

- **User IDs** — `user:42`
- **Order IDs** — `order:1001`
- **Customer tiers** — `tier:enterprise`
- **Job types** — `export:csv`, `email:welcome`
- **Source events** — `source:webhook`, `source:api`

Avoid tagging with timestamps, UUIDs, or other high-cardinality values that create millions of unique tags. Redis doesn't handle that well.

### Graceful Shutdown During Deployment

Horizon handles SIGTERM gracefully, but your job code might not. Make sure your jobs respect the shutdown signal:

```php
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public function handle(ReportGenerator $generator): void
    {
        // Check if Horizon is shutting down
        if ($this->job && $this->job->hasFailed()) {
            return;
        }

        $generator->generateLargeReport();
    }
}
```

Jobs that loop internally should check the loop condition regularly:

```php
public function handle(DataImporter $importer): void
{
    $importer->chunk(100, function ($records) {
        if ($this->job->isDeleted()) {
            return false;
        }

        foreach ($records as $record) {
            $this->processRecord($record);
        }
    });
}
```

### Combine Horizon with Laravel Pulse

Laravel Pulse (introduced in Laravel 11) provides system-level monitoring—CPU usage, memory, HTTP requests, database queries. Horizon covers queue-specific metrics. Together they give you full observability:

- Pulse shows you *why* your application is slow (slow queries, high memory)
- Horizon shows you *where* the work is piling up (queue depth, failed jobs)

If Pulse shows a database bottleneck and Horizon shows a growing queue, you know slow queries are causing jobs to time out.

## Horizon vs. Other Queue Systems

How does Horizon compare to the alternatives?

### Horizon vs. Beanstalkd

Beanstalkd is a standalone queue server. It's lightweight, easy to configure, and works with any language. But it has no built-in dashboard, no job tagging, and no auto-balancing. You manage workers with SupervisorD and monitor queues with custom tooling.

Use Beanstalkd when you need a queue server without the Laravel dependency or when you're processing jobs from multiple language runtimes.

Use Horizon when you're already on Laravel and want observability without cobbling together monitoring tools.

### Horizon vs. Amazon SQS

SQS is a fully managed queue service from AWS. It handles infinite scaling, has built-in dead letter queues, and integrates with Lambda for serverless processing. But SQS has no dashboard for job inspection, no per-job tagging, and no auto-balancing across multiple queues.

Use SQS when you need infinite throughput (10,000+ jobs per second) or when your infrastructure is already on AWS.

Use Horizon when you need visibility into individual jobs and you process moderate throughput (<10,000 jobs per second).

### Horizon vs. Database Queues

Laravel's database queue driver stores jobs in a MySQL or PostgreSQL table. It requires no additional infrastructure, but it introduces polling overhead on your database and lacks Horizon's dashboard and balancing.

Use database queues for development, testing, or very low-volume applications (<100 jobs per hour).

Use Horizon for anything that needs monitoring, multiple queues, or production reliability.

### Horizon vs. Symfony Messenger

Symfony Messenger is a message bus abstraction with transport adapters for Doctrine, Redis, SQS, and AMQP. It has a CLI command for consuming messages and supports middleware for cross-cutting concerns. But it has no built-in dashboard or auto-balancing.

Use Symfony Messenger when you're building on Symfony or when you need a framework-agnostic message bus.

Use Horizon when you're building on Laravel and want a batteries-included queue solution.

## FAQ

**Can I use Horizon without Redis?** No. Horizon is tightly coupled to Redis for job storage, queue metrics, and the real-time dashboard.

**Does Horizon support Beanstalkd or SQS?** No. Horizon only works with the Redis queue driver. Laravel's base queue system supports other drivers, but without the Horizon dashboard.

**How many supervisors should I define?** Start with one supervisor per group of queues with similar processing requirements. Add separate supervisors for queues that need different timeouts, retry counts, or worker counts.

**What happens when I deploy new code?** Run `php artisan horizon:terminate`. Horizon finishes current jobs and restarts with the new code. SupervisorD handles the restart automatically.

**Can I run Horizon on multiple servers?** Yes. Horizon's Redis backend supports multiple server deployments. Run `php artisan horizon` on each server and the dashboard aggregates metrics from all of them.

**Does Horizon persist failed jobs across restarts?** Yes. Failed jobs are stored in Redis, which persists across Horizon restarts. They survive until you retry or purge them.

**Is Horizon suitable for high-throughput applications?** Yes, with proper configuration. Use snapshot mode, dedicate a Redis instance, and monitor memory usage. Horizon has been tested at 1,000+ jobs per second.

**Can I rate-limit jobs in Horizon?** Yes. Laravel's queue middleware for rate limiting works with Horizon:

```php
use Illuminate\Queue\Middleware\RateLimited;

public function middleware(): array
{
    return [new RateLimited('stripe')];
}
```

## Conclusion

Laravel Horizon transforms queue management from a black box into a fully observable system. You install it with a single Composer command, configure it in one file, and get a real-time dashboard showing every job, worker, and queue in your application.

The auto-balancing feature alone is worth the setup. Instead of manually tuning worker counts for each queue, Horizon shifts workers dynamically based on workload. When a report generation spike hits the `reports` queue, Horizon pulls workers from quieter queues without restarting anything.

Job tagging turns debugging from log-diving into point-and-click. A failed job notification arrives, you search by user ID, and you see every job that customer triggered.

Production deployment is straightforward with SupervisorD. The graceful shutdown on `horizon:terminate` means zero job loss during deployments.

If you're running Laravel with Redis and processing background jobs of any significance, Horizon is not optional—it's essential. Install it, configure it, and get your queue observability sorted before your next traffic spike hits.
