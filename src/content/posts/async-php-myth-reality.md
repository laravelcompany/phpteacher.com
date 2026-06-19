---
title: "Async PHP Is a Lie - Understanding PHP's Single-Threaded Reality"
description: "Does async PHP actually work? Explore PHP's single-threaded architecture, event loops, coroutines, and when tools like ReactPHP and Swoole actually make sense for your application."
pubDate: "2022-02-16 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Async", "ReactPHP", "Swoole", "Performance", "Event Loop", "Coroutines", "Fibers", "Concurrency"]
selected: false
---

"Async PHP." You've seen the talks, the blog posts, the Twitter threads. People claiming PHP can now handle thousands of concurrent connections just like Node.js. Libraries like ReactPHP promise event-driven, non-blocking I/O. PHP 8.1 shipped fibers, which proponents call "green threads." Open Swoole claims true multi-threading.

It's mostly marketing fluff.

Here's the uncomfortable truth: PHP was designed to process a request, spit out HTML, and die. The entire ecosystem—every framework, every ORM, every library—is built around that synchronous, blocking paradigm. Slapping an event loop on top doesn't change the fundamental architecture any more than putting racing stripes on a minivan makes it a sports car.

This isn't FUD. It's physics. Let me show you exactly why async PHP is a lie, and more importantly, when it actually isn't.

## What You'll Learn

By the end of this post, you'll understand PHP's single-threaded execution model at a deep level, how the call stack actually works with real blocking operations, what event loops and coroutines do (and don't do), why PHP 8.1 fibers won't save you, and—most importantly—when async tooling genuinely makes sense versus when it's just complexity theater.

## The Single-Threaded Truth

PHP executes one instruction at a time, in order, from top to bottom. Period. There is no hidden parallelism. There is no time-slicing. When PHP runs a line of code, it finishes that line before moving to the next one.

```php
function handleRequest(): void
{
    $start = microtime(true);
    $data = file_get_contents('/var/logs/large-file.log');
    $elapsed = microtime(true) - $start;
    
    echo "Reading the file took {$elapsed} seconds.";
}
```

That `file_get_contents` call blocks the entire process until the operating system finishes reading the file into memory. Nothing else happens. No other requests get processed. No timers fire. No callbacks execute. The PHP process is frozen.

This is not a bug. It's the foundational design choice of the language. PHP prioritizes simplicity and request isolation over concurrent I/O. Every PHP process owns its memory space, its database connection, its file handles. There are no shared-state race conditions because there is no shared state.

But it means async is not native. It never has been.

## The Call Stack Explained

When PHP executes your code, it maintains a call stack—a LIFO (Last In, First Out) queue of function calls. Visualize it as a stack of papers on your desk. You put a new paper on top, work through it, then remove it to reveal the paper underneath.

```php
function func1(): void
{
    echo "Start func1\n";
    func2();
    echo "End func1\n";
}

function func2(): void
{
    echo "Start func2\n";
    func3();
    echo "End func2\n";
}

function func3(): void
{
    echo "Inside func3\n";
    sleep(10);
    echo "Leaving func3\n";
}
```

When you call `func1()`, PHP pushes `func1` onto the stack. `func1` calls `func2`, which gets pushed on top. `func2` calls `func3`, which gets pushed on top. Now `func3` calls `sleep(10)`.

The entire call stack—all three functions—is now blocked for ten seconds. Nothing below `func3` on the stack can proceed until `sleep` returns. `func2` can't finish. `func1` can't finish. Your HTTP response can't be sent.

This is why `sleep()` is the classic teaching example. Replace `sleep(10)` with `file_get_contents('https://slow-api.com/endpoint')` or `$pdo->query('SELECT ... SLEEP(10)')` and the mechanism is identical. A blocking I/O operation halts the entire call stack.

## How PHP Actually Handles Concurrency

If PHP is single-threaded and blocking, how does your shared hosting server handle twenty requests at once?

It doesn't. PHP does. But not the way you think.

The web server—Apache, NGINX, Caddy—receives the HTTP request. Apache can spawn threads or fork processes to handle multiple connections simultaneously. PHP-FPM maintains a pool of worker processes, each running an independent PHP interpreter. When a request arrives, the process manager hands it to an available worker.

```
NGINX -> PHP-FPM master -> worker process 1 (blocked on DB query)
                        -> worker process 2 (blocked on file read)
                        -> worker process 3 (sending response)
                        -> worker process 4 (idle, waiting)
```

Each worker is a completely separate PHP process with its own memory space. Worker 1 being blocked on a database query has zero impact on Worker 3 sending a response to a different user. From the operating system's perspective, these are independent processes scheduled by the kernel.

This is horizontal scaling within a single machine. It works because PHP-FPM manages the pool, not because PHP itself is concurrent. PHP never had to solve the concurrency problem because the process manager solved it at a higher level.

The trade-off is memory. Each PHP-FPM worker consumes 20-50 MB of RAM. A server with 8 GB of RAM can handle maybe 150-200 concurrent PHP workers before memory pressure becomes a problem. Node.js handles orders of magnitude more concurrent connections in a single thread because it never blocks on I/O.

But here's the thing: most web applications don't need 10,000 concurrent connections. They need 50, maybe 200. PHP-FPM handles that just fine, and it does so with a model that is trivially simple to understand and debug.

## Event Loops: ReactPHP

ReactPHP is the most well-known attempt to bring async to PHP. It implements an event loop—a programming construct that continuously checks for and dispatches events.

```php
use React\EventLoop\Loop;

Loop::addTimer(2.0, function () {
    echo "Two seconds later\n";
});

Loop::addTimer(1.0, function () {
    echo "One second later\n");
});

echo "Starting loop\n";
Loop::run();
echo "Loop ended\n";
```

This prints "Starting loop", then "One second later", then "Two seconds later", then "Loop ended." The event loop managed two timers without blocking. In Node.js, this is the default. In PHP, it's a library that has to take over your application's control flow.

You can build a crude event loop yourself to understand the mechanism:

```php
class CrudeEventLoop
{
    private array $ticks = [];

    public function addTick(callable $callback): void
    {
        $this->ticks[] = $callback;
    }

    public function run(): void
    {
        while ($this->ticks) {
            $callback = array_shift($this->ticks);
            $callback();
        }
    }
}

$loop = new CrudeEventLoop();
$loop->addTick(fn() => print "Task A\n");
$loop->addTick(fn() => print "Task B\n");
$loop->addTick(fn() => print "Task C\n");
$loop->run();
```

Real event loops are more sophisticated, handling stream I/O, signals, timers, and child processes. But the fundamental principle is the same: maintain a queue of work, process it iteratively, and never block.

The problem is that **most PHP code is not async-aware**.

```php
use React\EventLoop\Loop;
use React\Http\Message\Response;
use React\Http\HttpServer;
use Psr\Http\Message\ServerRequestInterface;

$server = new HttpServer(function (ServerRequestInterface $request) {
    $pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
    $stmt = $pdo->query('SELECT SLEEP(5)'); // Blocks the ENTIRE event loop
    $results = $stmt->fetchAll();
    
    return new Response(200, ['Content-Type' => 'application/json'],
        json_encode($results));
});

$socket = new React\Socket\SocketServer('127.0.0.1:8080');
$server->listen($socket);
echo "Server running on http://127.0.0.1:8080\n";
Loop::run();
```

That `$pdo->query('SELECT SLEEP(5)')` call blocks the event loop for five seconds. Every other request waiting in the loop is delayed. The timer callbacks are delayed. The entire single-threaded process is stuck waiting for MySQL.

ReactPHP works well only when every library in your dependency graph is also async-aware. Doctrine is not. Guzzle (in its default mode) is not. Most PSR-7 implementations assume blocking I/O. You need async-specific libraries: `react/pdo` for database, `react/http` for HTTP clients, `clue/reactphp-redis` for Redis. The ecosystem exists but it is thin compared to the synchronous PHP world.

## Coroutines and Generators

Before fibers, PHP developers used generators to simulate cooperative multitasking. A generator function can yield control back to the caller, then be resumed later with new input.

```php
function myGenerator(): Generator
{
    echo "Start\n";
    $value = yield "First yield\n";
    echo "Received: {$value}\n";
    $value = yield "Second yield\n";
    echo "Received: {$value}\n";
}

$gen = myGenerator();
echo $gen->current(); // "Start\nFirst yield\n"
echo $gen->send("Hello"); // "Received: Hello\nSecond yield\n"
echo $gen->send("World"); // "Received: World\n"
```

The first call to `current()` starts the generator and runs until the first `yield` statement. The `send()` method resumes execution, passing a value back into the generator as the return value of `yield`.

This bidirectional communication is the basis of coroutines. A scheduler can manage multiple coroutines, yielding execution when one blocks on I/O and resuming another that has data ready:

```php
function coroutineTask(string $name, int $steps): Generator
{
    for ($i = 0; $i < $steps; $i++) {
        echo "{$name}: step {$i}\n";
        yield; // Yield control back to scheduler
    }
}

$tasks = [
    coroutineTask("Task A", 3),
    coroutineTask("Task B", 3),
];

while ($tasks) {
    foreach ($tasks as $key => $task) {
        if ($task->valid()) {
            $task->next(); // Resume
        } else {
            unset($tasks[$key]);
        }
    }
}
```

This outputs Task A and Task B interleaved. Neither blocks the other. But crucially, this is cooperative multitasking. A task must explicitly yield control. If `coroutineTask` called `sleep(10)`, the scheduler would block entirely.

Nikita Popov's 2012 article on cooperative multitasking demonstrated this pattern using a wrapper around generators that called stream_select() internally to detect which streams were ready for reading or writing. It was clever. It also required every I/O operation to go through the scheduler, which meant you couldn't use standard PHP functions.

Coroutines in PHP are a leaky abstraction. They work beautifully in isolated demos and fall apart the moment you need to call a third-party library that doesn't know they exist.

## Fibers (PHP 8.1)

PHP 8.1 introduced fibers, which are essentially built-in coroutines with better ergonomics. A fiber can suspend its execution from anywhere in the call stack, not just from within a generator function.

```php
$fiber = new Fiber(function (): void {
    echo "Inside fiber: start\n";
    Fiber::suspend();
    echo "Inside fiber: resumed\n";
});

echo "Outside fiber: before start\n";
$fiber->start();
echo "Outside fiber: after start, before resume\n";
$fiber->resume();
echo "Outside fiber: after resume\n";
```

Output: "Outside fiber: before start", "Inside fiber: start", "Outside fiber: after start, before resume", "Inside fiber: resumed", "Outside fiber: after resume."

Fibers are a syntax improvement over generator-based coroutines. You can suspend from a deeply nested function call, not just from the generator function itself. This makes it possible to write code that looks synchronous but can be interrupted.

```php
function deepNestedFunction(): void
{
    echo "Deep: about to suspend\n";
    Fiber::suspend();
    echo "Deep: resumed\n";
}

$fiber = new Fiber(function (): void {
    echo "Top: start\n";
    deepNestedFunction();
    echo "Top: end\n";
});

$fiber->start();
echo "Main: suspended\n";
$fiber->resume();
echo "Main: done\n";
```

But fibers do not make I/O non-blocking. They do not add threads. They do not magically make `file_get_contents` or `PDO::query` asynchronous. A fiber that calls `sleep(10)` blocks the entire process, including all other fibers, the event loop, everything.

Fibers are a tool for building async frameworks, not a solution to async PHP. The framework must use fibers internally and provide async-aware I/O operations. Without that infrastructure, fibers are just expensive GOTO statements.

## Open Swoole

Open Swoole (formerly Swoole) takes a fundamentally different approach. It's a PHP extension written in C that creates a multi-threaded or multi-process event-driven server. It provides its own HTTP server, WebSocket server, coroutines, and thread pools.

```php
use Swoole\Coroutine;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server('0.0.0.0', 9501);

$server->on('request', function (Request $req, Response $res) {
    Coroutine::create(function () use ($res) {
        Coroutine::sleep(2); // Non-blocking sleep
        $res->end('Hello after 2 seconds');
    });
});

$server->start();
```

The `Coroutine::sleep()` call does not block the worker. Internally, Swoole hooks into PHP's I/O functions at the C level, replacing blocking operations with async alternatives. When a coroutine yields, Swoole's event loop continues processing other coroutines and resumes the yielded one when the I/O completes.

This is genuine async, and it works. But there are strings attached.

Swoole requires you to rewrite significant portions of your application. Standard PDO does not work with Swoole's coroutines—you must use `Swoole\Coroutine\MySQL` or `Swoole\Coroutine\PostgreSQL`. Standard `file_get_contents` blocks the coroutine—you must use `Swoole\Coroutine\System::readFile`. If any library in your dependency chain calls a blocking function, your coroutine blocks.

Laravel Octane abstracts much of this complexity. It boots Laravel once, handles multiple requests within the same process, and leverages Swoole or RoadRunner for the underlying async engine. Your application code stays the same. Octane handles the async plumbing.

```bash
php artisan octane:start --server=swoole --host=0.0.0.0 --port=8000
```

This works because Octane forces a clean application state between requests. Services are reset, singletons are managed, and the framework handles coroutine safety. But Octane is not a magic "make it faster" button. It's a performance optimization for applications that benefit from shared memory and reduced bootstrap overhead. If your application spends most of its time in database queries that are already fast, Octane won't help.

## When Async Makes Sense

Async PHP is not worthless. It's just narrowly applicable. Here is where it genuinely helps:

**WebSocket servers.** PHP's traditional request-response model cannot maintain persistent bidirectional connections. A ReactPHP or Swoole WebSocket server can handle thousands of concurrent connections in a single process. This is the one area where async PHP dominates.

**Real-time dashboards and streaming.** Server-sent events, streaming JSON responses, and live data feeds benefit from event-driven architectures. ReactPHP's HTTP server can stream responses without tying up PHP-FPM workers.

**Gateway and proxy services.** An async PHP service that sits in front of multiple upstream APIs can parallelize outgoing requests efficiently. For example, an aggregation endpoint that fetches data from three different microservices can use ReactPHP's async HTTP client to issue all three requests simultaneously and combine the results.

```php
use React\Http\Browser;
use React\EventLoop\Loop;
use Psr\Http\Message\ResponseInterface;

$browser = new Browser();

$promises = [
    $browser->get('https://api.service1.com/data'),
    $browser->get('https://api.service2.com/data'),
    $browser->get('https://api.service3.com/data'),
];

$results = Loop::await(
    React\Promise\all($promises)
);

// All three requests executed concurrently
```

**High-concurrency API gateways.** If you're building an API gateway that handles thousands of concurrent connections with minimal logic per request, Octane or Swoole can significantly reduce infrastructure costs by handling more requests per server.

## When Async Does NOT Make Sense

For a standard CRUD web application, async PHP is complexity without payoff.

Consider a typical request flow: receive HTTP request -> authenticate -> validate input -> execute two or three database queries -> render template or serialize JSON -> send response. The bottleneck is the database queries, and they execute sequentially because each depends on the previous one. No amount of async magic parallelizes that.

PHP-FPM with OPcache already handles this efficiently. A well-tuned server processes typical requests in 50-200 milliseconds. The overhead of async—managing coroutine state, ensuring library compatibility, debugging race conditions—is not justified when the synchronous version already works at scale.

The argument that async saves memory is also misleading. Yes, an async server can handle more concurrent connections with fewer processes. But PHP-FPM's per-request memory model has a crucial advantage: memory is freed completely when the request ends. There are no stale references, no leaked coroutine state, no gradual memory creep. Async servers require careful memory management.

## The Verdict for Web Developers

Here is the bottom line.

If you are building a typical Laravel or Symfony web application with a relational database, form validation, server-side rendering, and standard CRUD operations: PHP-FPM is correct. Do not add async. You will gain negligible performance improvements and significant debugging complexity.

If you are building a WebSocket server, a real-time dashboard, a streaming proxy, or an API gateway with extreme concurrency requirements: ReactPHP, Swoole, or Octane are valid choices. Measure first. Profile your bottlenecks. Confirm that async addresses them.

If you are using Laravel Octane because you want faster request handling: test it with your actual workload. Octane reduces bootstrap time by keeping the framework in memory. For applications with fast database queries, the bootstrap overhead is often a small fraction of total response time. You may see a 10-20% improvement, not the 10x boost the hype suggests.

## Best Practices

Profile before optimizing. Xdebug's profiling output or Tideways traces will show you exactly where time is spent. Do not guess your bottlenecks—measure them.

Scale horizontally before vertically. Adding a second $5 server is cheaper and simpler than converting your application to an async architecture. PHP-FPM scales horizontally with near-perfect linearity because each process is independent.

Use queues for slow work. Email sending, PDF generation, video transcoding, and any operation that takes more than 200 milliseconds belongs in a background job queue. This is the correct solution for 90% of PHP performance problems.

Know your bottlenecks. Are you CPU-bound? You need more processes or faster hardware. Are you I/O-bound? You need faster storage or query optimization. Are you connection-bound? You need horizontal scaling or connection pooling. Async only addresses the third category.

## Common Mistakes

**Assuming async equals faster.** Async is about concurrency, not raw throughput. A single synchronous request that takes 100ms will not be faster when converted to async. It may even be slower due to event loop overhead.

**Using blocking database drivers in ReactPHP.** PDO and Doctrine block the event loop. You must use react/pdo or an async-specific driver. This limitation is invisible until your server stops responding under load.

**Ignoring PHP-FPM process management.** Most PHP performance problems are actually PHP-FPM pm.max_children configuration issues. Tuning process pools, request termination timeouts, and slow-log thresholds solves more problems than async ever will.

**Premature optimization.** Async architecture is irreversible for most of your codebase. Once you commit to coroutines and event loops, you cannot safely add a synchronous library without testing its async compatibility. The cost of this constraint is real.

## FAQ

**Does PHP 8.1 fibers make PHP truly async?**

No. Fibers are a mechanism for cooperative multitasking within a single thread. They do not make I/O non-blocking. A fiber that calls a blocking function blocks the entire process. Fibers enable async frameworks but do not replace them.

**Can Laravel Octane make my application 10x faster?**

Almost certainly not. Octane eliminates framework bootstrap overhead, which is typically 10-30ms for a warmed application. If your total response time is 200ms, Octane saves at most 15%. The remaining 85% is database queries and business logic.

**How does async PHP compare to Node.js?**

Node.js was designed for async from the ground up. Its standard library is non-blocking. Its ecosystem assumes event-driven patterns. PHP was designed for synchronous request processing. Its standard library blocks. Its ecosystem assumes blocking I/O. Async PHP is a retrofit on a synchronous architecture.

**When should I use queues instead of async?**

Always, unless you need real-time bidirectional communication. Queues move slow work out of the request-response cycle entirely. Async keeps it in the same process with added complexity. For email sending, report generation, batch processing, and third-party API calls, queues are the simpler, more reliable solution.

**Is ReactPHP production-ready?**

ReactPHP is stable and used in production for specific use cases like WebSocket servers and event-driven daemons. It is not suitable as a general-purpose HTTP server replacement for most PHP applications due to library compatibility constraints.

**Does Open Swoole work with existing PHP libraries?**

Not directly. Libraries must be Swoole-aware or you must use Swoole's coroutine-compatible replacements. Laravel Octane abstracts some of this, but any library that uses global state, static properties, or blocking I/O can cause bugs in a Swoole coroutine context.

**What is the memory difference between PHP-FPM and async servers?**

A PHP-FPM worker consumes approximately 20-50 MB per process. An async server like Swoole can handle hundreds of coroutines within a single process. However, async servers require careful memory management to prevent leaks, whereas PHP-FPM releases all memory at request completion.

**Can I mix async and synchronous PHP in the same application?**

You can, but it requires careful architecture. Use ReactPHP or Swoole as a front-end gateway that delegates synchronous work to PHP-FPM workers. This gives you the concurrency benefits of async for connection handling while preserving synchronous simplicity for request processing.

## Conclusion

Async PHP is not a complete lie. It works for specific use cases with the right tools and the right expectations. But the framing is dishonest when it promises that PHP can be "just like Node.js" or that fibers solve the concurrency problem.

PHP's single-threaded, blocking architecture is not a bug. It's the design choice that makes PHP one of the simplest languages to deploy, debug, and scale. The process-per-request model eliminates memory leaks, race conditions, and state management issues that plague long-running applications.

Before jumping on the async bandwagon, ask yourself: do you have a problem that async solves, or do you have a problem that async sounds like it should solve? The answer will save you months of debugging and a lot of regret.

Know your architecture. Profile your bottlenecks. Choose the right tool. And if you're building a standard web application, trust that PHP-FPM has been solving this problem correctly for over twenty years.
