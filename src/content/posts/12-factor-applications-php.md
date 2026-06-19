---
title: "12 Factor Applications for PHP: Parts 7-12 Complete Guide"
description: "Master the remaining 12 Factor Application principles for PHP: port binding, concurrency, disposability, dev/prod parity, logs, and admin processes. Practical PHP examples."
pubDate: "2023-04-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["12 Factor App", "PHP", "Cloud Native", "DevOps", "Docker", "Monolog", "PHP-FPM", "Application Architecture", "Scalability"]
selected: false
---

Building distributed applications that are robust, maintainable, and scalable requires more than writing good code. It requires architectural discipline. The 12 Factor Application methodology, originally developed by Heroku engineer Adam Wiggins in 2011, provides a framework for exactly this kind of discipline.

In the first half of this series, Chris Tankersley covered factors one through six — codebase, dependencies, config, backing services, build/release/run, and processes. Those factors focused primarily on how code is stored, configured, and executed. The remaining six factors address how applications are architected, how they communicate, and how they behave in production.

PHP developers are in a unique position with respect to these principles. PHP's shared-nothing architecture — where each request starts fresh and dies cleanly — aligns naturally with several 12 Factor tenets. But as PHP evolves into long-running applications via Swoole, ReactPHP, and OpenSwoole, understanding these factors becomes critical.

## What You'll Learn

- How port binding standardizes service discovery for PHP applications
- Why PHP's process model is inherently aligned with horizontal scaling
- How to design disposable PHP applications for containerized environments
- Strategies for achieving dev/prod parity across your PHP stack
- The correct way to handle logging with Monolog and standard streams
- How to run admin tasks as one-off processes in production environments

## 7. Port Binding: Export Services Via Port Binding

The seventh factor states that applications should export their services via port binding. This means the type of service an application provides should be self-evident from the port it binds to. Port 80 or 443 indicates an HTTP service. Port 9000 indicates PHP-FPM. Port 5432 indicates PostgreSQL.

For PHP applications running behind traditional web servers, this factor is largely handled by the infrastructure layer. Your Apache or Nginx configuration binds to port 80 or 443 and proxies requests to PHP-FPM on port 9000. The application itself never concerns itself with port binding.

However, when deploying to containerized environments like Docker or Kubernetes, port binding becomes your responsibility. Your Dockerfile's `EXPOSE` directive and your docker-compose port mappings are explicit declarations of the service contract:

```yaml
services:
  app:
    build: .
    ports:
      - "8080:8080"
    environment:
      SERVER_PORT: 8080
```

The container binds to port 8080 internally. The orchestration layer maps external ports to this internal port as needed. Kubernetes might assign a random high port and handle routing through a service mesh. The key insight is that the application declares what port it speaks on, and the infrastructure adapts around it.

For PHP applications behind PHP-FPM, the FPM process binds to port 9000 by default. This is a well-known port that load balancers and reverse proxies can discover without custom configuration. The IANA maintains a registry of reserved ports, but the convention is more important than the registry — teams can agree on custom ports for internal services as long as they remain consistent.

### Practical PHP Port Binding

When building a PHP application that serves directly (not behind a traditional web server), you specify the bind address and port in your server configuration:

```php
// For ReactPHP or similar async PHP servers
$server = new React\Http\HttpServer($handler);
$socket = new React\Socket\SocketServer('0.0.0.0:8080');
$server->listen($socket);
```

The port becomes part of the application's deployment contract, not an infrastructure afterthought.

## 8. Concurrency: Scale Out Via the Process Model

The eighth factor mandates horizontal scaling through additional processes rather than vertical scaling through larger machines. PHP has an inherent advantage here. Since PHP 4, the language has used a shared-nothing architecture where each request spawns an independent process.

This design was initially a limitation — PHP couldn't maintain state between requests without external storage. But in the context of concurrency and scaling, it became a superpower. Each PHP process is isolated. There are no shared memory race conditions. No complex thread synchronization. A request arrives, PHP processes it, and the process terminates.

### PHP's Concurrency Evolution

The execution models have evolved:

- **CGI**: The web server spawned a new PHP process for every request. Maximum isolation, maximum overhead.
- **mod_php**: PHP embedded directly into Apache's worker threads. Better performance, shared state risks.
- **PHP-FPM**: A dedicated process manager keeps a pool of PHP workers ready. FastCGI protocol handles communication. This is the modern standard for traditional PHP hosting.
- **Async PHP (ReactPHP, Swoole, OpenSwoole)**: Long-running processes that handle multiple requests in a single thread. High performance but requires careful state management.

The 12 Factor approach prefers PHP-FPM's process pool model for most applications. Need to handle more traffic? Increase the `pm.max_children` setting or add more containers. Each new process or container is an identical replica of every other.

### Why This Matters for Async PHP

If you're using Swoole or ReactPHP, concurrency becomes an active concern rather than an infrastructure detail. You cannot store "the current user" as a global variable because a single process handles multiple requests simultaneously:

```php
// DANGEROUS in async PHP - shared state between requests
$currentUser = null; // Multiple requests overwrite this!

$server->onRequest(function ($request, $response) {
    // Other request might have changed $currentUser
    $currentUser = authenticate($request);
    $response->end("Hello, {$currentUser}");
});
```

Instead, request-scoped data must be stored in the request context:

```php
$server->onRequest(function ($request, $response) use ($container) {
    $context = new RequestContext($request);
    $container->set(RequestContext::class, $context);
    // Each request gets its own context instance
});
```

This mental shift is essential as PHP moves toward long-running process models.

## 9. Disposability: Maximize Robustness With Fast Startup and Graceful Shutdown

The ninth factor emphasizes that applications should be started and stopped at a moment's notice. Fast startup enables rapid scaling and deployment. Graceful shutdown ensures ongoing requests are completed before the process terminates.

Traditional PHP applications already excel at this. A PHP-FPM worker starts, handles a request, and dies. There's no long-lived state to clean up. No connections to drain. No caches to warm.

### Signal Handling for Long-Running PHP

If you're running async PHP, disposability requires explicit signal handling:

```php
pcntl_signal(SIGTERM, function () use ($server) {
    echo "Received SIGTERM. Stopping gracefully...\n";
    $server->stop();
});

pcntl_signal(SIGHUP, function () use ($config) {
    echo "Received SIGHUP. Reloading configuration...\n";
    $config->reload();
});
```

`SIGTERM` tells the application to stop accepting new requests, finish processing active ones, and terminate. `SIGHUP` triggers a configuration reload without a full restart. This allows container orchestration systems like Kubernetes to manage the application lifecycle without data loss.

### Startup Optimization

Every millisecond of startup time multiplies across your fleet during a rolling deployment. Cache configuration aggressively. Use OPcache for class loading. Defer expensive initialization to the first request when possible. A 100ms startup time on 50 containers means 5 seconds of cumulative delay during a deploy.

## 10. Dev/Prod Parity: Keep Environments as Similar as Possible

The tenth factor addresses the classic "it works on my machine" problem. Development, staging, and production environments should be as similar as possible — ideally identical.

### Infrastructure as Code

Tools like Ansible, Puppet, and Chef allow you to define system configurations programmatically. When combined with virtualization tools like Vagrant, these configurations can be applied to local development machines:

```bash
# Ansible playbook to ensure PHP 8.2 is installed
- name: Install PHP 8.2
  apt:
    name: php8.2-fpm
    state: present
```

The same playbook runs on developer laptops, CI servers, and production. Environment drift becomes impossible because every environment is rebuilt from the same definition.

### Containers as the Parity Solution

Docker containers provide byte-for-byte reproducibility. The Dockerfile defines the exact operating system, PHP version, extensions, and configuration:

```dockerfile
FROM php:8.2-fpm
RUN docker-php-ext-install pdo_mysql opcache
COPY php.ini /usr/local/etc/php/
```

This image is built once, pushed to a registry, and pulled by every environment. The PHP version, extension versions, and configuration are identical whether the container runs on a developer's laptop or in production.

The critical nuance: parity does not mean "my application only runs on PHP 8.0.16 with MySQL 8.0.12 on Ubuntu 23.04." That is fragility, not parity. Design your application to run in any environment that meets minimum requirements, then use containers to ensure those requirements are consistently met.

### Practical Dev/Prod Parity Checklist

- Same PHP version everywhere (use `php-version` in CI to enforce it)
- Same extensions enabled in dev and prod
- Same configuration files, differing only in secret values
- Same operating system base image
- Same database version (use Docker for both dev and prod databases)

## 11. Logs: Treat Logs as Event Streams

The eleventh factor fundamentally changes how PHP developers think about logging. Logs should be treated as event streams written to stdout or stderr, not as files managed by the application.

### Why stdout and stderr?

Standard streams are the universal interface between processes and their execution environment. When your application writes to stdout or stderr, the environment decides what to do with that output:

- In development, logs appear directly in the terminal
- In Docker, logs are captured by the container runtime and accessible via `docker logs`
- In Kubernetes, logs are gathered by the node-level logging agent and forwarded to centralized systems like Elasticsearch or CloudWatch

The application does not need to know about log rotation, file paths, or shipping mechanisms. It simply writes to the standard stream and lets the platform handle the rest.

### Implementing Log Streams With Monolog

PSR-3 (PHP Logger Interface) provides a standardized interface for logging. Monolog is the most widely used implementation. Here is how to configure Monolog to write to stderr:

```php
<?php

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('my-app');

$stream = new StreamHandler(
    'php://stderr',
    Level::Warning
);

$log->pushHandler($stream);

// Usage
$log->warning('Database connection pool is nearing capacity');
$log->error('Failed to process payment for order #12345');
```

The `StreamHandler` with `php://stderr` ensures that log messages go to the standard error stream. The level filter (`Level::Warning`) means debug and info messages are suppressed in production but could be enabled in development by changing the handler configuration.

### A Note on stderr vs. stdout

Many developers assume stderr is only for errors. In the Unix design philosophy, stderr is for diagnostic output — which includes all logs. stdout is for program output: the actual result of the computation. A PHP script that counts words in a file should output "3000" to stdout and log its processing steps to stderr.

This distinction becomes important when composing tools. If your PHP script outputs JSON to stdout (as an API would), logging messages on stdout would corrupt the output. Using stderr for logs keeps the streams cleanly separated.

## 12. Admin Processes: Run Management Tasks as One-Off Processes

The twelfth factor states that administrative tasks should run as one-off processes in the same environment as the application. Database migrations, data imports, cache warming, and reporting scripts are not special — they are processes that happen to perform maintenance instead of serving requests.

### Running Admin Tasks Correctly

The key principle is that admin processes run in the same release and configuration context as the web processes. They share the same codebase, the same environment variables, and the same backing services.

```bash
# Wrong: Running migration with a different PHP version
php /var/www/current/artisan migrate

# Correct: Running migration inside the application container
docker run --rm \
  -e DB_HOST=production-db.internal \
  -e APP_ENV=production \
  my-app:latest \
  php artisan migrate
```

When admin processes run inside the same container image as the application, they inherit the exact same dependencies, configuration, and runtime. There is no risk of a migration script using a different library version than the application code.

### Common Admin Process Patterns

- **Database migrations**: Run as a one-off command in CI/CD pipelines before new code is deployed
- **Data exports**: Triggered by an operator, run in the application environment, output to a shared storage location
- **Cache warming**: A scheduled task that runs as a one-off process in the same container
- **User imports**: A CLI command that accepts a CSV file and processes it within the application context

## Real-World Use Cases

### Cloud-Native PHP on Kubernetes

A PHP application deployed on Kubernetes naturally follows 12 Factor principles. Each container binds to a port, scales horizontally through replica sets, logs to stdout for collection by Fluentd, and runs migrations as Kubernetes Jobs.

### Microservices Architecture

When breaking a monolith into services, each service becomes a 12 Factor application. Port binding enables service discovery. Dev/prod parity ensures that service integration tests in CI match production behavior. Log streams feed into a centralized observability platform.

### Platform-as-a-Service Deployments

Platforms like Laravel Vapor, Platform.sh, and Heroku themselves enforce 12 Factor principles. The platform manages port binding, process scaling, and log collection. The developer focuses on writing application code that adheres to the contract.

## Best Practices

- **Use environment variables for all configuration**: Never hardcode database credentials, API keys, or environment-specific values.
- **Design for disposability**: Ensure your application can be killed and restarted without data loss.
- **Log to stderr, not to files**: Let the execution environment handle log routing and retention.
- **Keep the same dependency versions everywhere**: Pin exact versions in your composer.json and use a lock file.
- **Separate build, release, and run stages**: Build once, configure per environment, run identically.

## Common Mistakes to Avoid

- **Writing logs to files inside the container**: Files are lost when the container restarts. Use stdout/stderr.
- **Ignoring signal handling in async PHP**: Without SIGTERM handlers, container orchestrators must forcefully kill processes, potentially corrupting data.
- **Different PHP extensions in dev and prod**: A missing extension in production causes silent failures that never appear in development.
- **Running migrations as part of the web startup**: Multiple web processes starting simultaneously all try to migrate, causing race conditions.
- **Using environment-specific branches**: Branching per environment violates the codebase factor. Use configuration, not branches.

## Frequently Asked Questions

**Do I need to follow all 12 factors for a small PHP application?**
No. Apply the principles that make sense for your context. A small WordPress site does not need microservice-level discipline, but environment parity and proper logging benefit projects of any size.

**How does PHP-FPM align with the concurrency factor?**
PHP-FPM is a process manager that implements the process model perfectly. Each PHP-FPM worker is an independent process. Scaling is achieved by adding more workers or more FPM instances.

**Should I use Monolog or error_log()?**
Monolog provides structured logging with severity levels, formatters, and handlers. Use it for any non-trivial application. The `error_log()` function is suitable for quick debugging but lacks the flexibility needed for production observability.

**How do I handle secrets in 12 Factor PHP applications?**
Secrets belong in environment variables, not in code. Use your platform's secrets management (Kubernetes Secrets, AWS Secrets Manager, .env files in development) and inject them at runtime.

**Can I follow 12 Factor with shared hosting?**
Partially. Shared hosting restricts port binding and process management. Focus on the factors you can control: strict dependency management, environment parity through local development, and proper logging.

**What about Laravel's built-in logging? Does it support log streams?**
Yes. Laravel's logging configuration supports writing to stdout/stderr via the `stack` or `single` channels configured with `php://stdout`. Update `config/logging.php` to use standard streams for cloud-native deployments.

**Does OPcache affect disposability?**
OPcache improves startup times, which supports disposability. Be aware that OPcache must be warmed after deployment to avoid cold-cache performance penalties on the first requests.

**How do database connections behave in a disposable PHP process?**
PHP-FPM processes hold database connections for their lifetime. When a process is terminated, the connection closes. This is fine because the database driver reconnects automatically on the next request. Long-running processes should use connection pooling and reconnect logic.

## Conclusion

The 12 Factor Application methodology remains remarkably relevant for PHP development in 2023. PHP's traditional shared-nothing architecture naturally embraces several factors — particularly concurrency and disposability. The rise of containerized deployments and async PHP makes the remaining factors essential reading.

Chris Tankersley's exploration of factors 7 through 12 reveals that these principles are not abstract theory. Port binding, log streaming, and dev/prod parity are concrete practices that improve the reliability and maintainability of PHP applications in production.

Start by auditing your current deployment against these six factors. Are you logging to files or to stdout? Can you kill any running process without data loss? Is your development environment a byte-for-byte match for production? Each improvement moves your application toward the robustness that modern cloud infrastructure demands.

The twelve factors are not a checklist to be completed. They are a mindset — a way of thinking about application architecture that prioritizes operational excellence alongside feature development. Apply what fits, learn from the rest, and keep building.
