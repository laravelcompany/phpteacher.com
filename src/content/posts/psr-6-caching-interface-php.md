---
title: "PSR-6 Caching Interface - Standardized Cache Management in PHP"
description: "Learn PSR-6, the PHP-FIG caching standard. Master CacheItemPoolInterface, CacheItemInterface, deferred saves, and cache implementations like Symfony Cache and PHP Cache."
pubDate: "2022-09-26 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PSR-6", "PHP", "Caching", "PSR", "PHP-FIG", "Performance"]
selected: false
---

Caching is a crucial component of modern web applications. It delivers repeatedly requested data almost immediately, improving user satisfaction and reducing server load. Without caching, every request hits the database, runs the same queries, renders the same templates, and generates the same response.

Before PSR-6, every framework and library implemented its own caching. This led to tight coupling — a library could not easily integrate with a different framework's cache without custom adapters. PSR-6 solves this by standardizing the caching interface, letting developers write cache-dependent code that works with any compliant implementation.

## Core Concepts

PSR-6 defines several important terms:

| Term | Definition |
|---|---|
| **TTL** (Time To Live) | How long (in seconds) before cached data is considered stale |
| **Key** | A unique identifier for stored data |
| **Hit** | The key was found and the data is not expired |
| **Miss** | The key was not found, or the data is expired or invalid |

### Data Types

Cache pools must return values exactly as stored, preserving type:

- **Strings** — Arbitrary length, PHP-compatible encoding
- **Integers** — Up to 64-bit signed
- **Floats** — Signed floating point values
- **Booleans** — True or False
- **Null** — Actual null value
- **Objects** — Must be serializable and deserializable losslessly

```php
// Must return exactly the same type
$cache->save($pool->getItem('key')->set(42));
$item = $cache->getItem('key');
var_dump($item->get()); // int(42), NOT string('42')
```

If a cache implementation cannot return the exact value, it must respond with a miss rather than a type-coerced approximation.

## CacheItemPoolInterface

The pool is the central repository of cache items. It follows a data mapper pattern — you ask the pool for an item, manipulate it, and save it back.

```php
interface CacheItemPoolInterface
{
    public function getItem(string $key): CacheItemInterface;
    public function getItems(array $keys = []): iterable;
    public function hasItem(string $key): bool;
    public function clear(): bool;
    public function deleteItem(string $key): bool;
    public function deleteItems(array $keys): bool;
    public function save(CacheItemInterface $item): bool;
    public function saveDeferred(CacheItemInterface $item): bool;
    public function commit(): bool;
}
```

### Key Requirements

- Up to 64 characters in length
- UTF-8 encoded
- Characters: `0-9`, `a-z`, `A-Z`, `_`, `.`
- Reserved characters that cannot be used: `{}()/\@`

## CacheItemInterface

Each cache item represents a single key-value pair with metadata:

```php
interface CacheItemInterface
{
    public function getKey(): string;
    public function get(): mixed;
    public function isHit(): bool;
    public function set(mixed $value): static;
    public function expiresAt(?DateTimeInterface $expiration): static;
    public function expiresAfter(DateInterval|int|null $time): static;
}
```

## Basic Usage

```php
$pool = getCachePool('my_app_cache');

$item = $pool->getItem('team_stats_123');

if (!$item->isHit()) {
    $data = $this->generateTeamStats(123);
    $item->set($data);
    $item->expiresAfter(3600); // 1 hour
    $pool->save($item);
}

return $item->get();
```

The first request generates the data — potentially taking several seconds. Every subsequent request returns the cached value instantly. If 1000 visitors request the same data, you save 999 expensive operations.

### Error Handling

Cache system errors should never bubble up as application errors. Always wrap cache operations:

```php
function getCachedTeamStats(int $teamId): array
{
    try {
        $pool = getCachePool('my_app_cache');
        $item = $pool->getItem('team_stats_' . $teamId);

        if ($item->isHit()) {
            return $item->get();
        }

        $data = $this->generateTeamStats($teamId);

        $item->set($data);
        $item->expiresAfter(3600);
        $pool->save($item);

        return $data;
    } catch (CacheException $e) {
        // Log the error, but never break the application
        $this->logger->error('Cache failure: ' . $e->getMessage());
        return $this->generateTeamStats($teamId);
    }
}
```

## Deferred Saves

Deferred saves batch multiple write operations for performance:

```php
$pool->saveDeferred($item1);
$pool->saveDeferred($item2);
$pool->saveDeferred($item3);
// All three are written in one batch operation
$pool->commit();
```

This is useful when invalidating multiple cache entries at once — clear several items, then commit all deletions in one round trip to Redis or Memcached.

## Expiration Strategies

### Time-Based

```php
$item->expiresAfter(300); // 5 minutes

// Or at a specific time
$item->expiresAt(new DateTime('tomorrow midnight'));
```

### Event-Driven

When underlying data changes, delete the cached item:

```php
class TeamStatsService
{
    public function __construct(
        private CacheItemPoolInterface $pool,
    ) {}

    public function updateTeamStats(int $teamId, array $stats): void
    {
        // Update database
        $this->repository->save($teamId, $stats);

        // Invalidate cache
        $this->pool->deleteItem('team_stats_' . $teamId);
    }
}
```

### Cache Tags (Implementation-Specific)

PSR-6 does not define cache tags, but implementations like Symfony Cache extend the standard with tagging:

```php
use Symfony\Component\Cache\Adapter\RedisAdapter;

$pool = new RedisAdapter($redisConnection);

$item = $pool->getItem('user_456_profile');
$item->set($profileData);
$item->tag(['user_456', 'profile_data']);
$pool->save($item);

// Later, invalidate all items tagged with 'user_456'
$pool->invalidateTags(['user_456']);
```

## Implementing a PSR-6 Cache Pool

Here is a minimal file-based implementation:

```php
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class FileCachePool implements CacheItemPoolInterface
{
    private string $directory;
    private array $deferred = [];

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/');
    }

    public function getItem(string $key): CacheItemInterface
    {
        $path = $this->getPath($key);

        if (!file_exists($path)) {
            return new CacheItem($key, null, false);
        }

        $data = unserialize(file_get_contents($path));

        if ($data['expires'] !== null && $data['expires'] < time()) {
            unlink($path);
            return new CacheItem($key, null, false);
        }

        return new CacheItem($key, $data['value'], true);
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $files = glob($this->directory . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function deleteItem(string $key): bool
    {
        $path = $this->getPath($key);
        if (file_exists($path)) {
            return unlink($path);
        }
        // Deleting a non-existent key is NOT an error
        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $path = $this->getPath($item->getKey());
        $expires = $item->getExpiresAt();
        $data = serialize([
            'value' => $item->get(),
            'expires' => $expires?->getTimestamp(),
        ]);
        return file_put_contents($path, $data) !== false;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;
        return true;
    }

    public function commit(): bool
    {
        foreach ($this->deferred as $item) {
            $this->save($item);
        }
        $this->deferred = [];
        return true;
    }

    private function getPath(string $key): string
    {
        return $this->directory . '/' . md5($key) . '.cache';
    }
}
```

## PSR-6 Implementations

### Symfony Cache

The most widely used PSR-6 implementation. Supports Redis, Memcached, APCu, filesystem, database, and Doctrine:

```bash
composer require symfony/cache
```

```php
use Symfony\Component\Cache\Adapter\RedisAdapter;

$client = RedisAdapter::createConnection('redis://localhost:6379');
$pool = new RedisAdapter($client);
```

### PHP Cache

A meta-package that provides a common interface across multiple backends:

```bash
composer require php-cache/redis-adapter
```

```php
use Cache\Adapter\Redis\RedisCachePool;

$client = new \Redis();
$client->connect('localhost', 6379);
$pool = new RedisCachePool($client);
```

## Performance Best Practices

1. **Cache hot data** — Items requested frequently with low churn are ideal
2. **Set appropriate TTLs** — Short TTLs for data that changes often, long TTLs for reference data
3. **Use deferred saves** — Batch writes for bulk operations
4. **Graceful degradation** — Cache failures should never crash the application
5. **Monitor hit rates** — Low hit rates indicate wrong keys or too-short TTLs

## Summary

PSR-6 standardizes caching in PHP through two interfaces: `CacheItemPoolInterface` (the repository) and `CacheItemInterface` (individual items). The standard ensures that code written against it works with any compliant implementation — Symfony Cache, PHP Cache, or a custom solution.

When applications begin scaling, caching becomes critical for performance and cost. The PSR-6 investment pays off: write your application code once, and swap cache backends as your infrastructure grows. From file-based caches in development to Redis clusters in production, the interface stays the same.
