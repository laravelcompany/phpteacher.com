---
title: "PHP Generators: Memory-Efficient Data Processing"
description: "Master PHP generators for memory-efficient data processing. Learn to handle large datasets, streaming data, and lazy loading with practical examples."
pubDate: "2023-12-20 22:30:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Generators", "Memory Management", "Performance", "Iterators", "PHP 8.x", "Lazy Loading", "Streaming Data"]
selected: false
---

Every PHP developer eventually faces the memory problem. You need to process a CSV file with 500,000 rows. Or stream data from an API that sends updates indefinitely. Or load database records for a report.

The naive approach reads everything into memory at once. The server runs out of memory. The script crashes. The report never generates.

PHP generators solve this. They let you process data one item at a time, without loading the entire dataset into memory. The technique is simple, elegant, and one of PHP's most underutilized features.

## What You'll Learn

- How generators reduce memory usage compared to traditional arrays
- Creating generators for file processing, streaming data, and database queries
- Lazy loading patterns using generators
- Sending data back to generators for coroutine-like behavior
- The difference between generators and PHP 8.1 fibers

## How Generators Work

A generator is a function that can yield multiple values over time. When called, it returns a `Generator` object that can be iterated with `foreach`. Each `yield` statement pauses execution and returns a value. The next iteration resumes where it left off.

```php
<?php

function simpleGenerator(): Generator
{
    yield 'first';
    yield 'second';
    yield 'third';
}

foreach (simpleGenerator() as $value) {
    echo $value . "\n";
}
```

The critical insight: the generator function's state is preserved between yields. Local variables, loop positions, and execution context are all maintained. Only one value exists in memory at a time.

## Before Generators: The Memory Problem

Before PHP 5.5 introduced generators, processing large datasets meant loading everything into memory:

```php
<?php

class DataService
{
    private array $data = [];

    public function __construct(string $filename)
    {
        $fh = fopen($filename, 'r');

        while (($row = fgetcsv($fh, 1024)) !== false) {
            $this->data[] = $row;
        }

        fclose($fh);
    }

    public function getData(): array
    {
        return $this->data;
    }
}

$dataService = new DataService('really-big-file.csv');

foreach ($dataService->getData() as $row) {
    // Do something with $row
}
```

Every row of the CSV is stored in `$this->data` before processing begins. For a 500MB CSV file, the memory footprint exceeds 500MB. This approach doesn't scale.

## With Generators: Streaming Processing

Rewrite the same class using a generator:

```php
<?php

class DataService
{
    public function __construct(
        private string $filename
    ) {}

    public function getData(): Generator
    {
        $fh = fopen($this->filename, 'r');

        while (($row = fgetcsv($fh, 1024)) !== false) {
            yield $row;
        }

        fclose($fh);
    }
}

$dataService = new DataService('really-big-file.csv');

foreach ($dataService->getData() as $row) {
    // Do something with $row
}
```

The memory difference is dramatic. Instead of storing the entire file, only one row (up to 1024 bytes) exists in memory at a time. When the loop moves to the next row, the previous row can be garbage collected.

## Processing Streaming Data

Generators excel at handling data streams where the total size is unknown or infinite:

```php
<?php

class SocketDataGenerator
{
    public function __construct(private $socket) {}

    public function readData(): Generator
    {
        while (($buffer = fread($this->socket, 1024)) !== false) {
            yield $buffer;
        }
    }
}

$host = '127.0.0.1';
$port = 5000;
$socket = stream_socket_client(
    "tcp://{$host}:{$port}",
    $errno,
    $errstr,
    30
);

$dataGenerator = new SocketDataGenerator($socket);

foreach ($dataGenerator->readData() as $data) {
    // Process each chunk as it arrives
}
```

The generator yields data chunks as they become available. Processing starts immediately, without waiting for the complete transmission. This is particularly valuable for log processing, real-time analytics, and API response streaming.

## Lazy Loading with Generators

Lazy loading defers data retrieval until the moment it's needed. Compare the traditional iterator approach with a generator.

### Traditional Iterator

```php
<?php

class LazyDatabaseUserIterator implements Iterator
{
    private mixed $row;
    private int $index = 0;
    private mixed $statement;

    public function __construct(private PDO $pdo) {}

    public function current(): mixed
    {
        return $this->row;
    }

    public function next(): void
    {
        $this->row = $this->statement->fetch(PDO::FETCH_OBJ);
        $this->index++;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return !empty($this->row);
    }

    public function rewind(): void
    {
        $this->statement = $this->pdo->prepare("SELECT * FROM users");
        $this->statement->execute();
        $this->row = $this->statement->fetch(PDO::FETCH_OBJ);
    }
}

$users = new LazyDatabaseUserIterator($pdo);

foreach ($users as $user) {
    // Process each user
}
```

### Equivalent Generator

```php
<?php

class UserLoader
{
    public function __construct(private PDO $pdo) {}

    public function getUsers(): Generator
    {
        $statement = $this->pdo->prepare("SELECT * FROM users");
        $statement->execute();

        while ($row = $statement->fetch(PDO::FETCH_OBJ)) {
            yield $row;
        }
    }
}

$userLoader = new UserLoader($pdo);

foreach ($userLoader->getUsers() as $user) {
    // Process each user
}
```

The generator version removes the boilerplate of implementing the `Iterator` interface while achieving the same lazy-loading behavior. PHP handles the internal state management — no need to track the current index, validate row existence, or rewind manually.

## Sending Values to Generators

Generators support bidirectional communication. After yielding a value, a generator can receive data from the caller via the `send` method:

```php
<?php

class SocketDataGenerator
{
    private bool $isActive = true;

    public function __construct(private $socket) {}

    public function readData(): Generator
    {
        while (
            $this->isActive
            && ($buffer = fread($this->socket, 1024)) !== false
        ) {
            $command = yield $buffer;

            if ($command === 'disconnect') {
                $this->isActive = false;
                socket_close($this->socket);
                break;
            }
        }
    }
}

$socket = stream_socket_client("tcp://127.0.0.1:5000", $errno, $errstr, 30);
$dataGenerator = new SocketDataGenerator($socket);

foreach ($dataGenerator->readData() as $data) {
    // Process the data

    if (str_contains($data, '--TERMINATE--')) {
        $dataGenerator->send('disconnect');
    }
}
```

The `yield` expression receives the sent value. This enables coroutine-like patterns where the generator and caller communicate bidirectionally.

## Generators vs. Fibers

PHP 8.1 introduced fibers, which also use a `yield`-like mechanism. Understanding the difference is important:

- **Generators** are for producing sequences of values efficiently. They maintain state between yields but are designed for iteration.
- **Fibers** are for concurrent task management. They allow cooperative multitasking within a single thread, useful for async operations.

```php
<?php

// Generator: efficient iteration
function countTo(int $max): Generator
{
    for ($i = 1; $i <= $max; $i++) {
        yield $i;
    }
}

// Fiber: cooperative concurrency
$fiber = new Fiber(function (): void {
    $value = Fiber::suspend('first suspend');
    echo "Resumed with: {$value}\n";
});
```

Choose generators for data iteration. Choose fibers for async workflow management.

## Chaining Generators for Pipelines

Multiple generators can be chained to create efficient data processing pipelines:

```php
<?php

function readLines(string $filename): Generator
{
    $handle = fopen($filename, 'r');

    while (($line = fgets($handle)) !== false) {
        yield rtrim($line, "\n\r");
    }

    fclose($handle);
}

function parseCsvLines(Generator $lines): Generator
{
    foreach ($lines as $line) {
        yield str_getcsv($line);
    }
}

function filterRows(Generator $rows, callable $callback): Generator
{
    foreach ($rows as $row) {
        if ($callback($row)) {
            yield $row;
        }
    }
}

$pipeline = filterRows(
    parseCsvLines(readLines('large.csv')),
    fn($row) => $row[0] !== ''
);

foreach ($pipeline as $row) {
    // Process each filtered row
}
```

Each stage operates on one item at a time. No intermediate arrays. No memory spikes. The pipeline processes the file as a stream.

## Yield From: Delegating to Sub-generators

The `yield from` syntax delegates to another generator, array, or Traversable:

```php
<?php

function countToThree(): Generator
{
    yield 1;
    yield 2;
    yield 3;
}

function countToSix(): Generator
{
    yield from countToThree();
    yield 4;
    yield 5;
    yield 6;
}

foreach (countToSix() as $number) {
    echo $number . "\n"; // 1, 2, 3, 4, 5, 6
}
```

This is particularly useful when composing complex generators from simpler ones:

```php
<?php

function readUsers(string $filename): Generator
{
    yield from parseCsvLines(readLines($filename));
}

function readOrders(string $filename): Generator
{
    yield from parseCsvLines(readLines($filename));
}

function allRecords(): Generator
{
    yield 'users' => readUsers('users.csv');
    yield 'orders' => readOrders('orders.csv');
}
```

## Performance Considerations

Generators introduce a small overhead per yield operation. For most applications, this is negligible compared to the memory savings. However, there are cases to consider:

- **Tiny datasets**: For arrays under 100 items, the generator overhead may exceed any benefit. Use plain arrays.
- **Hot paths**: In performance-critical loops, generator overhead may matter. Profile before optimizing.
- **Deep chains**: Very long generator chains (5+ levels) can create noticeable overhead. Measure and flatten if needed.

The memory-usage difference is dramatic. Processing a 500MB CSV line-by-line uses ~1MB of memory. Loading it into an array uses 500MB+. The memory savings dwarf the CPU overhead.

## Real-World Use Cases

- **CSV/Excel imports**: Process millions of rows without exhausting memory
- **API response streaming**: Consume large API responses chunk by chunk
- **Database exports**: Generate report data without loading all records into memory
- **File processing**: Handle log files, JSON arrays, and XML documents of any size
- **Data transformation pipelines**: Chain multiple generators for ETL workflows

## Best Practices

- **Always type hint Generator**: Declare `Generator` as the return type so callers know they can iterate the result
- **Handle resource cleanup**: Close file handles and database connections in the generator, not in the calling code
- **Use generators for large datasets only**: The overhead of generator calls isn't justified for small in-memory arrays
- **Chain generators for complex pipelines**: Pass one generator's output as another's input for efficient transformation chains
- **Consider memory vs. speed tradeoffs**: Generators save memory but may be slightly slower than array iteration for small datasets

## Common Mistakes to Avoid

- **Returning instead of yielding**: A function that calls `return` instead of `yield` returns a single value, not a generator
- **Collecting generator results into an array**: This defeats the memory-saving purpose. Process values as they yield
- **Modifying generator state from outside**: Don't assume the generator's internal state; use `send` for communication
- **Nesting generators without yield from**: Use `yield from` to delegate to sub-generators instead of manual iteration
- **Ignoring resource cleanup**: If a `foreach` loop breaks early, resources in the generator may not be released

## Frequently Asked Questions

**Can generators be rewound?**
No. Generators are forward-only. Once consumed, you must create a new instance to iterate again.

**Are generators faster than arrays?**
For small datasets, no. The function call overhead and state management make generators slightly slower. The advantage is memory efficiency for large datasets.

**Can I use generators with array functions?**
Many array functions don't work with generators directly. You may need to convert to an array first, which defeats the purpose. Use iterator functions instead.

**Can generators return a final value?**
Yes. After the last yield, a generator can use `return` to provide a final value, accessible via `$generator->getReturn()`.

**What's yield from?**
`yield from` (PHP 7+) delegates to another generator, array, or Traversable object, yielding all its values sequentially within the current generator.

**Do generators work with foreach?**
Yes, the `Generator` class implements `Iterator`, making it compatible with `foreach` and all iterator-based operations.

**Can generators be used with Swoole or ReactPHP?**
Yes. Generators integrate well with event loops and async frameworks, providing an elegant way to handle I/O operations without blocking.

## Conclusion

PHP generators are one of the language's most powerful features for handling large-scale data processing. By yielding values one at a time instead of returning an entire array, generators reduce memory usage from O(n) to O(1) for sequential data access.

The next time you process a CSV file, stream API data, or query a large database result set, reach for a generator. Your memory usage will drop, your application will scale, and your code will be cleaner for it.

Real-world PHP applications deal with increasingly large datasets. Generators ensure your code grows with your data.
