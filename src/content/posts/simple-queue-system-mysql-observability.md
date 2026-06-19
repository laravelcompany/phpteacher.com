---
title: "Build a Simple MySQL Queue System With Observability in PHP"
description: "Learn to build a simple MySQL-backed queue system in PHP with observability patterns, flow charts, and offline processing for reliable business workflows."
pubDate: "2023-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Queue", "MySQL", "Observability", "DDD", "Background Jobs", "Event Sourcing", "Architecture"]
selected: false
---

Every business application eventually needs to move work offline. A user submits a form, and the application needs to send an email, generate a PDF, sync with a third-party API, or process a large dataset. Doing this synchronously blocks the HTTP response. The user waits, the server connection stays open, and if the operation fails, the user sees a 500 error with no recourse.

A queue system solves this. The work is packaged into a message, stored persistently, and processed by a background worker. The user gets an immediate response, and the application retries failed jobs automatically.

This article walks through building a simple MySQL-backed queue system with observability built in. You will create a queue table, a reservation mechanism for safe concurrent processing, and a flow chart documenting the entire process.

## What You'll Learn

- Designing a MySQL table for queue storage with reservation support
- Implementing a reservation-based queue consumer
- Adding observability with timestamps and process tracking
- Creating flow charts to document business processes
- Patterns for idempotent offline processing
- Error handling and retry strategies

## The Business Problem: Posting Reserve Week Scores

Consider a real-world example from the USA Clay Target League. Competition seasons run six weeks, with an optional Reserve Week before the season starts. Teams can compete during Reserve Week and keep those scores as a backup. If a team is prevented from competing due to weather — snow storms, hurricanes, the realities of outdoor sports — the head coach can opt to use the Reserve Week scores instead.

The complication is that any particular team may compete in multiple disciplines: trap, skeet, 5-stand, and sporting clays. Reserve Week scores exist per discipline. A team might complete their trap competition but get snowed out of skeet. The processing logic needs to determine which teams still need to submit scores, contact the coaches, and then post the Reserve Week scores for those that qualified.

The current flow works, but email notifications are sent synchronously during the scoring process. If the email server is slow or unavailable, the entire scoring operation blocks. This is the problem we solve by moving the email notifications offline into a queue.

## Designing the Queue Table

MySQL makes an excellent queue storage backend for moderate throughput. It provides ACID transactions, persistent storage, and a familiar query interface. The key is to design the table for safe concurrent access.

```sql
CREATE TABLE `email_requests` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `reservation_code` char(32) DEFAULT NULL,
    `reserved_at` timestamp(6) NULL DEFAULT NULL,
    `started` timestamp(6) NULL DEFAULT NULL,
    `completed` timestamp(6) NULL DEFAULT NULL,
    `queue_parameters` mediumtext NOT NULL
        COMMENT 'PHP serialize()',
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reservation_code` (`reservation_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci;
```

Let us examine each column.

The `reservation_code` column is a unique key that prevents duplicate processing. When a worker picks up a job, it generates a UUID and updates this column. The `reserved_at` timestamp records when the job was claimed. `started` and `completed` track the processing lifecycle. `queue_parameters` holds the serialized job data.

The timestamp columns use microsecond precision — `timestamp(6)` — which is critical for observability. When you are debugging a slow worker, millisecond or microsecond precision tells you exactly how long each phase took.

## Reserving a Job Safely

The reservation pattern prevents two workers from processing the same job. A worker updates the row with its reservation code using an atomic `UPDATE` with a `WHERE` clause that only matches unreserved jobs.

```php
public function reserveJob(string $workerId): ?array
{
    $reservationCode = bin2hex(random_bytes(16));
    $table = 'email_requests';

    $affected = DB::update(
        "UPDATE {$table}
         SET reservation_code = ?,
             reserved_at = NOW(6),
             started = NULL,
             completed = NULL
         WHERE reservation_code IS NULL
         ORDER BY id ASC
         LIMIT 1",
        [$reservationCode]
    );

    if ($affected === 0) {
        return null;
    }

    return DB::select(
        "SELECT * FROM {$table}
         WHERE reservation_code = ?",
        [$reservationCode]
    );
}
```

This query is safe under concurrent access because of two factors. First, the `UPDATE` is atomic — MySQL locks the row during the update. Second, the `WHERE reservation_code IS NULL` condition means only unreserved rows are eligible. If two workers call this simultaneously, only one will match the available row. The other will get zero affected rows and try again.

The `ORDER BY id ASC` implements a FIFO queue. The oldest unprocessed job is always claimed first.

## Processing and Completing a Job

Once a job is reserved, the worker processes it and marks it complete.

```php
public function completeJob(int $jobId): void
{
    DB::update(
        "UPDATE email_requests
         SET completed = NOW(6)
         WHERE id = ? AND completed IS NULL",
        [$jobId]
    );
}
```

If processing fails, the worker can release the job back to the queue by clearing the reservation.

```php
public function releaseJob(int $jobId): void
{
    DB::update(
        "UPDATE email_requests
         SET reservation_code = NULL,
             reserved_at = NULL
         WHERE id = ? AND completed IS NULL",
        [$jobId]
    );
}
```

The observability columns allow you to track every state transition. A job's lifecycle — created, reserved, started, completed — is fully documented in the database. This is the foundation of observability.

## The Worker Loop

The worker runs as a long-lived PHP process, polling the queue for new jobs.

```php
class QueueWorker
{
    public function __construct(
        private QueueRepository $queue,
        private EmailService $email,
        private Logger $logger,
    ) {}

    public function run(): void
    {
        $workerId = uniqid('worker-', true);

        while (true) {
            try {
                $job = $this->queue->reserveJob($workerId);

                if ($job === null) {
                    $this->logger->debug(
                        'No jobs available, sleeping'
                    );
                    sleep(1);
                    continue;
                }

                $this->processJob($job, $workerId);

            } catch (\Throwable $e) {
                $this->logger->error(
                    'Worker error: ' . $e->getMessage()
                );
                sleep(5);
            }
        }
    }

    private function processJob(
        array $job,
        string $workerId
    ): void {
        $this->logger->info(
            "Worker {$workerId} processing job {$job['id']}"
        );

        DB::update(
            "UPDATE email_requests
             SET started = NOW(6)
             WHERE id = ?",
            [$job['id']]
        );

        try {
            $params = unserialize($job['queue_parameters']);
            $this->email->send($params);
            $this->queue->completeJob($job['id']);

            $this->logger->info(
                "Job {$job['id']} completed successfully"
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                "Job {$job['id']} failed: " . $e->getMessage()
            );
            $this->queue->releaseJob($job['id']);
        }
    }
}
```

The `started` column is updated separately from the reservation to distinguish between "claimed but waiting" and "actively processing." This distinction matters when you are diagnosing why jobs are accumulating.

## Observability Through Flow Charts

Observability is not just about logging. It is about understanding the system's behavior at a glance. A flow chart documenting the queue process captures the "why" behind the code.

The email queue flow chart documents every state the system passes through:

1. Coach submits scores
2. Scores are validated and saved
3. An email request record is inserted into the queue table
4. The HTTP response is returned immediately
5. A background worker picks up the reservation
6. The worker updates started timestamp
7. Email is sent via the third-party provider
8. On success, completed timestamp is set
9. On failure, the job is released and re-queued

This flow chart serves multiple audiences. Developers use it to understand the system when onboarding. Operations teams use it to diagnose why emails are not being sent. Product managers use it to understand the feature boundaries.

## Real-World Use Cases

**Order Confirmation Emails.** E-commerce platforms should never block the checkout process to send an email. The order is placed, the response is returned, and the email is sent asynchronously.

**PDF Generation.** Invoices, reports, and certificates can be generated offline. The user receives a notification when the document is ready.

**Webhook Delivery.** Applications that notify external systems of events should use a queue for reliable delivery. If the external system is down, the job stays in the queue for retry.

**Batch Processing.** CSV imports, data migrations, and report generation should be queued. The user triggers the process and checks back later for results.

## Best Practices

**Always use timestamp(6) precision.** Microsecond precision is essential for diagnosing performance issues in queue systems. Second-level precision hides the difference between "fast" and "fast enough."

**Use UUIDs for reservation codes.** Sequential IDs reveal processing order to competitors or malicious actors. UUIDs are unpredictable and safe for public exposure.

**Set a maximum processing time.** Jobs that exceed a threshold should be automatically released and flagged for manual inspection. This prevents a single stuck job from blocking the queue forever.

**Log every state transition.** The queue table itself is your audit log. Do not rely solely on application logs, which can be rotated away.

**Monitor queue depth.** Alert when the number of unprocessed jobs exceeds a threshold. A growing queue indicates a problem before users notice.

## Common Mistakes to Avoid

**Skipping the reservation pattern.** Without reservation, multiple workers can process the same job simultaneously, causing duplicate emails, duplicate charges, or data corruption.

**Using MyISAM.** The MyISAM storage engine does not support transactions and uses table-level locking. Always use InnoDB for queue tables.

**Serializing objects in queue_parameters.** Store scalar values or JSON, not serialized PHP objects. Object serialization creates coupling between the producer and consumer that breaks when class definitions change.

**Polling too aggressively.** A busy loop with no sleep between polls wastes CPU and database resources. Sleep for at least one second between empty polls.

## Frequently Asked Questions

**How many jobs per second can this handle?**
On modest hardware, a simple MySQL queue handles hundreds of jobs per second. The bottleneck is typically the worker processing logic, not the database. For thousands of jobs per second, consider Redis or RabbitMQ.

**What happens if the worker crashes mid-job?**
The job remains reserved. A supervisor process should detect stalled jobs — those with `reserved_at` older than a threshold but `completed` null — and release them back to the queue.

**How do I handle job ordering?**
The FIFO ordering is best-effort. If strict ordering is required, use a single worker or implement a sequencing mechanism. For most business processes, best-effort ordering is sufficient.

**Should I delete completed jobs?**
Archive them to a separate table for audit purposes. Deleting rows from an InnoDB table does not reclaim disk space immediately. Use `OPTIMIZE TABLE` during maintenance windows.

**Can I use this for scheduled jobs?**
Yes. Add a `scheduled_at` column and modify the reservation query to filter for jobs where `scheduled_at <= NOW()`. This gives you a simple cron replacement.

## Conclusion

A MySQL-backed queue system with observability is practical, maintainable, and sufficient for most business applications. The reservation pattern ensures safe concurrent processing. The timestamp columns provide complete lifecycle tracking. The flow chart captures the tribal knowledge that would otherwise be lost.

Start simple. Do not introduce Redis, RabbitMQ, or dedicated queue infrastructure until your MySQL queue proves insufficient. You will be surprised how far a well-designed database table can take you.

Document your queue architecture with flow charts. Your future self — and your future teammates — will thank you.
