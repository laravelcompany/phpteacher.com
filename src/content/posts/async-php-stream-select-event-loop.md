---
title: "Async PHP Without Libraries - Stream Select and Event Loops"
description: "Learn how to write asynchronous PHP using only built-in functions like stream_select and stream_set_blocking. Understand event loops and non-blocking I/O without ReactPHP or Swoole."
pubDate: "2023-09-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Async", "Event Loop", "Stream Select", "Non-blocking I/O", "ReactPHP", "Swoole", "Streams"]
selected: false
---

Asynchronous programming in PHP sounds like an oxymoron to many developers. PHP was designed as a synchronous, request-response language. Each request spawns a process that executes line by line, function by function, and then dies. There is no shared memory, no persistent state, and no event loop baked into the language.

Yet, async PHP is real. Libraries like ReactPHP and Swoole handle thousands of concurrent connections. PHP 8.1 introduced fibers for cooperative multitasking. But these tools abstract away the underlying mechanics, leaving developers without a mental model for what is actually happening.

This article strips away the abstractions. You will build an event loop using only PHP's built-in functions — `stream_select`, `stream_set_blocking`, and `stream_get_contents`. By the end, you will understand exactly how tools like ReactPHP work under the hood.

## What You'll Learn

- How PHP streams work and why they matter for async programming
- The difference between blocking and non-blocking I/O
- Building an event loop with `stream_select`
- Reading multiple files and HTTP sockets concurrently
- How ReactPHP and Swoole use these same primitives
- When async PHP actually makes sense versus when it adds complexity

## Understanding PHP Streams

Every I/O operation in PHP — reading a file, making an HTTP request, opening a socket — passes through the streams layer. Streams are the universal abstraction for data flow in PHP.

When you call `file_get_contents('https://example.com')`, PHP opens an HTTP stream, sends the request, and waits for the response. The execution of your script pauses until the entire response is received. That is blocking I/O.

```php
$fileContent1 = file_get_contents('file1.txt');
$fileContent2 = file_get_contents('file2.txt');
$fileContent3 = file_get_contents('file3.txt');
$fileContent4 = file_get_contents('file4.txt');
$fileContent5 = file_get_contents('file5.txt');
```

The CPU sits idle while the operating system waits for the disk or network to deliver data. For sequential operations this is acceptable. For concurrent operations — reading five files simultaneously, making ten API calls in parallel — it is wasteful.

The solution is non-blocking I/O combined with an event loop.

## Non-Blocking Streams with stream_set_blocking

PHP allows you to switch any stream to non-blocking mode using `stream_set_blocking`. When a stream is non-blocking, read operations return immediately with whatever data is available — even if that is nothing.

```php
$fileStreamList = [
    fopen('file1.txt', 'r'),
    fopen('file2.txt', 'r'),
    fopen('file3.txt', 'r'),
    fopen('file4.txt', 'r'),
    fopen('file5.txt', 'r'),
];

foreach ($fileStreamList as $fileStream) {
    stream_set_blocking($fileStream, false);
}
```

After this, reading from any of these streams will not block the script. But without an event loop, you have no way to know when data is ready. You would have to poll each stream in a busy loop, which wastes CPU cycles.

## The stream_select Function

`stream_select` solves this problem. It accepts an array of streams and monitors them for activity. When one or more streams have data available for reading, the function returns. The calling code can then process the ready streams and loop back to wait for more.

```php
do {
    $streamsToRead = $fileStreamList;
    $streamsWithUpdates = stream_select(
        $streamsToRead,
        $write,
        $except,
        seconds: 1,
        microseconds: 0
    );

    if ($streamsWithUpdates === false) {
        echo 'Unexpected error';
        exit(1);
    }

    if ($streamsWithUpdates === 0) {
        continue;
    }

    foreach ($streamsToRead as $index => $fileStream) {
        $content = stream_get_contents($fileStream);
        // process file content
        echo $content . PHP_EOL;
        if (feof($fileStream)) {
            fclose($fileStream);
            unset($fileStreamList[$index]);
        }
    }
} while (!empty($fileStreamList));
```

Let us break this down piece by piece.

### Parameters Passed by Reference

The first three parameters of `stream_select` are passed by reference. The first parameter is the array of streams to monitor for reading. After the call, this array is modified to contain only the streams that are ready for reading.

The second and third parameters are for write and exceptional data. We pass undefined variables because we do not need those. They are required by the function signature because the parameters are by-reference.

The fourth and fifth parameters form the timeout. One second means the function will wait up to one second before returning with zero ready streams. If all five files are ready within that second, it returns immediately with a count of five.

### Return Value

The function returns the number of streams that have data available. If it returns zero, the timeout expired with no activity, and we loop again. If it returns false, an error occurred.

### Reading the Data

After the call, `$streamsToRead` contains only the streams that are ready. We iterate over them, read the contents with `stream_get_contents`, and check for the end of the file with `feof`. When a file is fully read, we close its stream and remove it from the original list. Once all streams are processed, the loop exits.

This is an event loop. It is rudimentary — no error handling for partial reads, no support for writing, no signal handling — but it is the same pattern that powers ReactPHP's event loop.

## Mixing Files and HTTP Requests

The power of this approach becomes apparent when you mix different I/O operations. Sockets can be made non-blocking too, which means you can read local files and make HTTP requests concurrently.

```php
$fileStreamList = [
    fopen(__DIR__ . '/text_files/file1.txt', 'r'),
    fopen(__DIR__ . '/text_files/file2.txt', 'r'),
    stream_socket_client(
        "tcp://example.com:80", $errno, $errstr
    ),
    fopen(__DIR__ . '/text_files/file3.txt', 'r'),
    fopen(__DIR__ . '/text_files/file4.txt', 'r'),
    fopen(__DIR__ . '/text_files/file5.txt', 'r'),
];

foreach ($fileStreamList as $fileStream) {
    stream_set_blocking($fileStream, false);
}

$httpRequest = 'GET / HTTP/1.1' . "\r\n";
$httpRequest .= 'Host: example.com' . "\r\n";
$httpRequest .= 'Connection: close' . "\r\n";

fwrite($fileStreamList[2], $httpRequest . "\r\n");

do {
    $streamsToRead = $fileStreamList;
    $streamsWithUpdates = stream_select(
        $streamsToRead,
        $write, $except,
        seconds: 1, microseconds: 0
    );

    if ($streamsWithUpdates === false) {
        echo 'Unexpected error';
        exit(1);
    }

    if ($streamsWithUpdates === 0) { continue; }

    foreach ($streamsToRead as $index => $fileStream) {
        $content = stream_get_contents($fileStream);
        $bodyOffset = strpos($content, "\r\n\r\n") + 4;

        if ($bodyOffset !== false) {
            echo substr($content, $bodyOffset);
        } else {
            echo $content;
        }

        if (feof($fileStream)) {
            fclose($fileStream);
            unset($fileStreamList[$index]);
        }
    }
} while (!empty($fileStreamList));
```

The socket connection to example.com is the third stream. Even though it was opened third, it may be the last to become ready for reading because HTTP responses take longer than local file reads. The event loop processes streams as they become ready, not in the order they were opened.

This is the fundamental advantage of non-blocking I/O. Multiple requests are sent simultaneously, and responses are processed in whatever order they arrive. No single slow request blocks the others.

## What Is Happening Under the Hood

The `stream_select` function delegates to the operating system's `select()` system call. The OS kernel monitors the file descriptors and wakes the PHP process when data is available. Between calls to `stream_select`, PHP is idle — it consumes no CPU cycles while waiting.

This is why an event loop is more efficient than busy-waiting or spawning separate processes for each I/O operation. The operating system does the heavy lifting of monitoring file descriptors, and PHP only runs when there is actual work to do.

## How ReactPHP and Swoole Build on This

ReactPHP's event loop uses `stream_select` as its default driver. It wraps the raw PHP functions in a clean API with event handlers, timers, and promises.

```php
$loop = React\EventLoop\Loop::get();

$loop->addReadStream($stream, function ($stream) use ($loop) {
    $content = stream_get_contents($stream);
    echo $content;
    $loop->removeReadStream($stream);
});

$loop->run();
```

ReactPHP adds convenience, but the underlying mechanism is the same `stream_select` call you just implemented.

Swoole takes a different approach. It replaces PHP's native stream functions entirely with its own event-driven implementation using `epoll` on Linux. This gives it finer control over I/O and better performance for certain workloads, but the conceptual model is identical: non-blocking operations coordinated by an event loop.

## When Async PHP Actually Makes Sense

Non-blocking I/O is not a universal improvement. It adds complexity. You must manage state across callback invocations, handle partial reads, and deal with the fact that your code no longer executes top-to-bottom in a single request.

Use async PHP when:

- Your application makes many concurrent network calls (API gateways, web scrapers, proxy servers)
- You are building a real-time server that maintains persistent connections (WebSocket servers, chat applications, game servers)
- You need to process many files simultaneously (log aggregators, data pipelines)
- You are running a long-lived application server with Swoole or ReactPHP

Do not use async PHP when:

- You are building a standard web application with occasional I/O
- Your framework (Laravel, Symfony) already handles concurrency through process-level parallelism
- You need simple request-response cycles with occasional database queries
- Your team lacks experience with event-driven programming

## Real-World Use Cases

**API Gateway Aggregator.** A gateway that fans out requests to multiple microservices and merges the responses benefits directly from concurrent I/O. Each upstream call becomes a non-blocking stream operation in the event loop.

**Log Processor.** PHP scripts that tail multiple log files, parse entries, and forward them to a central aggregator can use `stream_select` to monitor all files simultaneously.

**Web Crawler.** A crawler that downloads hundreds of pages can open multiple HTTP connections and process responses as they arrive, dramatically reducing total execution time.

**Reverse Proxy.** A simple reverse proxy can be built with stream sockets, forwarding requests to backend servers and streaming responses back to clients without buffering.

## Best Practices

**Always check for errors.** `stream_select` can return false on failure. The timeout can expire with zero ready streams. Your loop must handle both cases.

**Close and remove streams promptly.** An unclosed stream in the monitoring array causes the loop to spin forever. Always `fclose` and `unset` when `feof` returns true.

**Do not assume complete reads.** `stream_get_contents` may return partial data for large files. Check the return length and loop if necessary.

**Use the maximum timeout wisely.** A timeout of `null` makes `stream_select` block indefinitely. A timeout of `0` makes it poll. A value between `0` and `null` provides a balance.

## Common Mistakes to Avoid

**Modifying the stream array during iteration.** `stream_select` modifies the array passed as the first parameter. Always pass a copy if you need to preserve the original list.

**Ignoring the return value.** The function returns the number of ready streams. A return value of zero is not an error — it is a timeout.

**Using stream_set_blocking on HTTP wrappers.** `stream_set_blocking` only works with plain files and sockets. It does not work with `http://` or `ftp://` stream wrappers.

**Blocking within the event loop.** If your read handler performs a blocking database query or file write, you have defeated the purpose of the event loop. Keep handlers fast and non-blocking.

## Frequently Asked Questions

**Is stream_select available on Windows?**
Yes, but with limitations. Windows does not support non-blocking pipes, and `stream_select` on Windows may not work with all stream types. For production async PHP on Windows, consider using a library that abstracts platform differences.

**How does this compare to PHP 8.1 fibers?**
Fibers provide cooperative multitasking within a single thread. They allow you to write async code that looks synchronous. Under the hood, they still rely on the same blocking and non-blocking stream primitives. Fibers are a scheduling tool, not an I/O tool.

**Can I use stream_select with database connections?**
Not with PDO or MySQLi. Those extensions manage their own connection pooling and do not expose their underlying sockets as PHP streams. Asynchronous database access requires extensions like `mysqlnd` with `MYSQLI_ASYNC` or dedicated async database libraries.

**Why not just use curl_multi for HTTP requests?**
`curl_multi` is purpose-built for concurrent HTTP requests. It is more capable than raw sockets for HTTP, handling SSL, redirects, headers, and cookies automatically. But it only handles HTTP. `stream_select` works with any stream type.

**Is this production-ready?**
The bare-metal approach shown here is educational. For production, use ReactPHP or Swoole, which handle edge cases, platform differences, and performance optimizations that are impractical to implement manually.

## Conclusion

PHP can do asynchronous I/O using only built-in functions. The `stream_select` function provides an event loop that monitors multiple streams for activity and processes them as data becomes available. This is the same mechanism that powers ReactPHP, AMPHP, and other async PHP libraries.

Understanding this foundation demystifies async PHP. The magic is not magic — it is the operating system's `select()` syscall, accessible through PHP's stream layer.

If you have never explored PHP's stream functions, take an hour to experiment with `stream_select`. Read multiple files concurrently. Make parallel HTTP requests with raw sockets. You will gain a deeper appreciation for what PHP can do beyond the request-response cycle.

The next time someone tells you PHP cannot do async, you will know better.
