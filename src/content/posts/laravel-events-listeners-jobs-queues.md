---
title: "Laravel Events, Listeners, Jobs, and Queues Explained"
description: "Master Laravel's event-driven architecture. Learn when to use events vs listeners vs jobs vs queues and how they work together to build decoupled, responsive apps."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laravel", "Events", "Listeners", "Jobs", "Queues", "Event-Driven Architecture", "Laravel Queues", "Async PHP"]
selected: false
---

In 2008, a PHP application could handle a request with a single database transaction. Query the database, render HTML, return the response. Simple. Fast. Done.

Today, a single HTTP request can touch a dozen systems. Update the database. Call an external API. Send an email. Push a Slack notification. Log to the audit trail. Trigger a report generation. Update a search index. Invalidate a cache. Each of these operations adds latency. A request that should return in 200 milliseconds takes 5 seconds.

Laravel provides an elegant solution: events, listeners, jobs, and queues. Together, these tools let you defer work, decouple systems, and keep your HTTP responses fast. But knowing which tool to use for which job is the difference between a well-architected application and a tangled mess.

This article explains when and how to use each tool — with practical examples and real-world patterns.

## What You'll Learn

- The difference between events/listeners and jobs/queues
- When to use events vs. observers vs. jobs
- How to configure queue workers for background processing
- Practical patterns for async workflows in Laravel
- Common pitfalls with queued listeners and job dispatch
- Monitoring and debugging queue failures

## Events and Listeners

Events and listeners are Laravel's implementation of the observer pattern. An event is a class that describes something that happened. A listener is a class that reacts to that event.

The critical distinction: events and listeners are not inherently queue-driven. When you dispatch an event, its listeners execute synchronously within the same request — unless you explicitly configure them to run on the queue.

### Defining an Event

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderShipped
{
    use Dispatchable;

    public function __construct(
        public Order $order,
        public \DateTimeImmutable $shippedAt = new \DateTimeImmutable(),
    ) {}
}
```

### Defining a Listener

```php
<?php

namespace App\Listeners;

use App\Events\OrderShipped;

class SendShipmentNotification
{
    public function handle(OrderShipped $event): void
    {
        $event->order->customer->notify(
            new OrderShippedNotification($event->order)
        );
    }
}
```

### Registering Events and Listeners

```php
<?php

namespace App\Providers;

use App\Events\OrderShipped;
use App\Listeners\SendShipmentNotification;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderShipped::class => [
            SendShipmentNotification::class,
            UpdateInventoryFromShipment::class,
            LogShipmentActivity::class,
        ],
    ];
}
```

When `OrderShipped` is dispatched, all three listeners execute in sequence. If any listener is slow, the entire response is slow.

## Making Listeners Async

Laravel makes it trivial to send listeners to the queue. Implement the `ShouldQueue` interface.

```php
<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendShipmentNotification implements ShouldQueue
{
    public $queue = 'notifications';

    public function handle(OrderShipped $event): void
    {
        $event->order->customer->notify(
            new OrderShippedNotification($event->order)
        );
    }
}
```

Now `SendShipmentNotification` executes on the queue worker. The HTTP response completes immediately. The notification is sent asynchronously.

### Queue Assignment

You can assign listeners to specific queues:

```php
<?php

public $queue = 'high-priority'; // or 'slow', 'default', 'emails'
```

This is useful when you have different queue priorities. Email notifications might go to the `emails` queue. Report generation goes to the `slow` queue. Time-sensitive operations go to `high-priority`.

## Observers: Model-Specific Listeners

Observers are a specialized form of listeners that react to Eloquent model events: `created`, `updated`, `deleted`, `restored`, and `forceDeleted`.

When should you use an observer instead of a manual event?

Use an observer when the side effect is directly tied to the model lifecycle. Every time a user is created, send a welcome email. Every time an order is updated, recalculate totals. The observer keeps this logic in one place.

```php
<?php

namespace App\Observers;

use App\Models\Bookmark;

class BookmarkObserver
{
    public function created(Bookmark $bookmark): void
    {
        // Update the user's bookmark count cache
        Cache::forget("user.{$bookmark->user_id}.bookmark_count");
    }

    public function deleted(Bookmark $bookmark): void
    {
        // Clean up associated resources
        Storage::delete($bookmark->thumbnail_path);
        Cache::forget("user.{$bookmark->user_id}.bookmark_count");
    }
}
```

Register observers in a service provider:

```php
<?php

namespace App\Providers;

use App\Models\Bookmark;
use App\Observers\BookmarkObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $observers = [
        Bookmark::class => [BookmarkObserver::class],
    ];
}
```

## Jobs: Self-Contained Tasks

Events and listeners are a publish-subscribe pattern. Jobs are a command pattern. A job is a single, self-contained unit of work.

When should you use a job instead of an event + listener?

Use a job when there is exactly one thing that needs to happen, and it does not make sense as a broadcast event. Generating a report, processing a file upload, sending a single email — these are jobs.

```php
<?php

namespace App\Jobs;

use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Bookmark $bookmark
    ) {}

    public function handle(): void
    {
        $data = $this->bookmark->entries()->get()->toArray();

        $pdf = Pdf::loadView('reports.bookmark', [
            'bookmark' => $this->bookmark,
            'data' => $data,
        ]);

        Storage::put(
            "reports/bookmark-{$this->bookmark->id}.pdf",
            $pdf->output()
        );

        $this->bookmark->user->notify(
            new ReportReady($this->bookmark)
        );
    }
}
```

Dispatch a job:

```php
<?php

// Simple dispatch
GenerateReport::dispatch($bookmark);

// Dispatch with delay
GenerateReport::dispatch($bookmark)->delay(now()->addMinutes(30));

// Dispatch to a specific queue
GenerateReport::dispatch($bookmark)->onQueue('reports');

// Dispatch after the response is sent
GenerateReport::dispatchAfterResponse($bookmark);
```

### Synchronous Jobs

Jobs can run synchronously by omitting `ShouldQueue`:

```php
<?php

use Illuminate\Foundation\Bus\Dispatchable;

class UpdateUserLastLogin
{
    use Dispatchable;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $this->user->update(['last_login_at' => now()]);
    }
}

// This runs synchronously in the current request
UpdateUserLastLogin::dispatch($user);
```

Synchronous jobs are useful when you want the organizational benefits of the job pattern but need immediate execution.

## Queues: The Engine

Queues are the infrastructure. They store job data in a backend — database, Redis, SQS, Beanstalkd — and queue workers process them asynchronously.

### Queue Configuration

```php
// config/queue.php

'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 90,
    ],

    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => 'default',
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

### Running Queue Workers

```bash
# Process the default queue
php artisan queue:work

# Process a specific queue
php artisan queue:work --queue=notifications,default

# Process with a specific number of attempts
php artisan queue:work --tries=3

# Process with a delay between retries
php artisan queue:work --backoff=30
```

### Queue Workers with Supervisor

In production, use Supervisor to keep queue workers running. Laravel Forge configures this automatically.

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log
stopwaitsecs=3600
```

The `numprocs=8` directive runs eight queue worker processes. Each process can handle one job at a time. With eight processes, your application can process up to eight jobs concurrently.

### Horizon

For Redis-based queues, Laravel Horizon provides a beautiful dashboard and configuration interface. It replaces Supervisor for Redis queues.

```php
// config/horizon.php

'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'notifications', 'reports'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
    ],
],
```

Horizon's auto-balancing feature dynamically adjusts the number of processes per queue based on workload.

## When to Use What

### Use Events + Listeners When

- Multiple things need to happen in response to one action
- The listeners are conceptually independent subscribers
- Some listeners should be synchronous and others async
- You want other developers to be able to hook into the event later

### Use Observers When

- The side effect is directly related to an Eloquent model lifecycle event
- You want to centralize model-related side effects
- The logic is straightforward (one action per model event)

### Use Jobs When

- There is exactly one thing that needs to happen
- The operation is something you might want to retry independently
- The task is a command ("generate report") rather than a notification ("order was shipped")

### Use Queues When

- The operation is slow and should not block the HTTP response
- The operation must survive server restarts
- You need retry logic for failed operations
- You need to control concurrency

## Real-World Use Cases

### User Registration Flow

When a user registers, multiple things happen: send a verification email, notify the admin, create default settings, track analytics. This is a perfect event scenario. The `Registered` event fires, and each listener handles one concern. The email goes to the queue. The analytics tracking runs synchronously.

### Report Generation

A user requests a report. The controller dispatches a `GenerateReport` job and returns immediately. The frontend polls for completion. The job processes on the `reports` queue. When complete, it sends a notification. This keeps the HTTP response under 200ms.

### E-commerce Order Processing

An order is placed. The `OrderPlaced` event triggers: charge the payment, reserve inventory, send confirmation, update analytics, trigger shipping. Each listener runs independently on its queue. If payment fails, the other listeners still handle their responsibilities.

## Best Practices

### Use Specific Queue Names

Name queues by purpose: `notifications`, `emails`, `reports`, `webhooks`. This lets you tune worker allocation per queue and understand queue performance.

### Set Job Expiration

Jobs should not live forever. Set `$timeout` and `$retryUntil` to bound job lifespan.

```php
<?php

class ProcessPayment implements ShouldQueue
{
    public $timeout = 120;
    public $tries = 5;

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }
}
```

### Handle Failures Gracefully

Every failed job creates noise. Log the failure context, notify the team, and decide whether the job should retry or fail permanently.

```php
<?php

class SendWebhook implements ShouldQueue
{
    public function failed(\Throwable $e): void
    {
        Log::error('Webhook failed after all retries', [
            'webhook_url' => $this->url,
            'error' => $e->getMessage(),
        ]);

        $this->order->update(['webhook_status' => 'failed']);
    }
}
```

### Monitor Queue Health

Watch queue size, processing time, and failure rates. Set up alerts for queues that grow beyond expected limits. Laravel Horizon, Laravel Pulse, or third-party tools like Laravel Telescope provide this visibility.

## Common Mistakes to Avoid

**Dispatching jobs synchronously for testing but forgetting to switch back.** Use `Queue::fake()` in tests to assert jobs were dispatched without actually processing them.

**Not handling serialization.** Jobs and events that implement `SerializesModels` will serialize and deserialize Eloquent models. Be careful about model state changing between dispatch and processing.

**Sending too many jobs.** A `foreach` loop dispatching a job per item can overwhelm the queue. Use batch processing instead.

```php
<?php

// Bad: one job per item
foreach ($orders as $order) {
    ProcessOrder::dispatch($order);
}

// Better: batch processing
$batch = Bus::batch(
    $orders->map(fn ($order) => new ProcessOrder($order))
)->dispatch();
```

**Ignoring failed jobs.** The `failed_jobs` table fills up. Monitor it. Re-run failed jobs or investigate recurring failures.

## Frequently Asked Questions

### Should I use events or jobs?

Use events when multiple things need to happen in response to one action. Use jobs when a single unit of work needs to be processed, especially if it might be retried independently.

### When should I implement ShouldQueue on a listener?

Whenever the listener performs a slow operation: email sending, API calls, file generation, external notifications. Anything that would add more than 100ms to the response time should be queued.

### Can a job dispatch another job?

Yes, but be careful about creating infinite loops. Jobs dispatching jobs that eventually dispatch the original job is a bug pattern to watch for.

### How do I handle job failures gracefully?

Set `$tries` to a reasonable number, implement the `failed()` method to handle cleanup, and monitor the `failed_jobs` table. Use Laravel Horizon or Pulse for visibility.

### What queue connection should I use for development?

Use the `sync` driver for local development. Jobs execute synchronously, so you can debug them in the request context. Switch to `database` or `redis` for staging and production.

## Conclusion

Laravel's event-driven architecture gives you powerful tools for building responsive, decoupled applications. Events and listeners let you broadcast that something happened and let independent subscribers react. Jobs let you encapsulate units of work and process them asynchronously. Queues provide the infrastructure for background processing.

The key is choosing the right tool for each job. Use events for broadcast-style notifications. Use jobs for command-style tasks. Use observers for model lifecycle hooks. Use queues to keep everything fast.

Start by identifying the slow operations in your application. Extract them into jobs or queueable listeners. Watch your response times drop and your architecture improve.

**Ready to level up your Laravel application?** Find one slow endpoint in your application — one that makes API calls, sends emails, or processes files synchronously. Extract the slow operations into a queued job or listener. Measure the response time improvement. Your users will thank you.
