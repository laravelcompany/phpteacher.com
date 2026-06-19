---
title: "MongoDB and PHP - A Perfect Match"
seoTitle: "MongoDB and PHP - A Perfect Match for Modern Applications"
description: "Discover why MongoDB and PHP are a perfect match for modern applications. Build scalable data-driven apps with the MongoDB PHP driver and ODM."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-10 21:00:00"
tags: ["MongoDB", "PHP", "Database", "NoSQL", "MongoDB Atlas", "ODM"]
---

Modern applications demand modern tooling. While PHP and MySQL have been the classic duo powering countless web applications, the landscape has shifted dramatically. Large-scale PHP applications now require databases that can scale horizontally, handle massive concurrent traffic, and adapt to changing data models without painful migrations. MongoDB fits this bill perfectly.

MongoDB is a general-purpose document database that works seamlessly with PHP. When paired with MongoDB Atlas, the cloud application data platform, you unlock advanced features like full-text search, visual analytics, and automatic scaling. This guide walks through everything you need to know: document vs. relational modeling, setup, CRUD operations, and advanced querying with aggregation pipelines.

## Document Databases vs. Relational Databases

You may have heard that document databases like MongoDB store data without any schema, letting you dump arbitrary data with no structure. That statement is technically true but misleading. MongoDB is a flexible, schemaless database that can enforce schemas when needed while offering an easy path to evolve your data format as your application grows. By applying consistent schemas when storing data, you can create indexes that deliver the same performance you expect from any serious database.

### Terminology Mapping

MongoDB uses its own vocabulary, but every concept maps directly to what you already know from relational databases:

- A **database** is still a database
- A **collection** is roughly equivalent to a table
- A **document** is roughly equivalent to a row

### Document Modeling in Practice

Consider an application that stores conference speaker profiles. In a relational database, you might normalize this across three tables: `speakers`, `social_links`, and `talks`. Every time you render a speaker page, you pay the cost of JOINs across these tables.

MongoDB approaches this differently. Instead of normalized tables, it uses BSON (Binary JSON) documents:

```php
// Embedded document with social links as an array
[
  '_id' => 1,
  'name' => 'Joel Lord',
  'bio' => 'Developer advocate focused on web technologies...',
  'socials' => [
    'Twitter' => 'https://twitter.com/joel__lord',
    'Github' => 'https://github.com/joellord'
  ]
]
```

This embedded approach follows a simple mantra: **data accessed together should be stored together**. By embedding the social links directly in the speaker document, you eliminate JOINs entirely. Fetching a speaker profile becomes a single query, and the returned document maps directly to what you display on the page.

```php
<?php
$speaker = $collection->findOne(['name' => 'Joel Lord']);
?>
<h1><?= $speaker->name ?></h1>
<h2>Socials</h2>
<ul>
<?php foreach ($speaker->socials as $social => $link): ?>
  <li><a href="<?= $link ?>"><?= $social ?></a></li>
<?php endforeach; ?>
</ul>
```

However, embedding everything isn't always the answer. You wouldn't embed all talks inside a speaker document if you frequently need to display a schedule grid across all speakers—that would mean parsing every document to build the grid. In that case, a `$lookup` (MongoDB's left outer join) or selective data duplication makes more sense.

```php
// Partial data duplication for performance
[
  '_id' => 1,
  'speaker' => [
    '_id' => 1,
    'name' => 'Joel Lord'
  ],
  'conference_id' => 1,
  'title' => 'MongoDB and PHP: A Perfect Match',
  'day' => 'June 1, 2022',
  'time' => '14:15'
]
```

Now a single query powers the schedule grid, and a single query loads the speaker profile. You've eliminated joins entirely while keeping the data model intentional. Disk space is cheap; compute cycles during request handling are not.

The rule of thumb: don't over-embed, and don't try to reproduce a relational schema in documents. A balanced approach delivers the best performance. Data duplication is a valid strategy when it eliminates expensive joins at read time.

### Indexing Strategies

MongoDB supports a rich set of index types that directly map to your query patterns. Without proper indexes, even the most elegantly modeled documents will perform poorly.

**Single field indexes** are the simplest—they index a single field in ascending or descending order:

```php
$collection->createIndex(['name' => 1]);
```

**Compound indexes** cover multiple fields in a single query:

```php
$collection->createIndex(['name' => 1, 'bio' => -1]);
```

**Text indexes** enable full-text search across string fields:

```php
$collection->createIndex(
    ['name' => 'text', 'bio' => 'text'],
    ['weights' => ['name' => 10, 'bio' => 5]]
);
```

**TTL indexes** automatically expire documents after a specified time, perfect for session stores or log data:

```php
$collection->createIndex(
    ['created_at' => 1],
    ['expireAfterSeconds' => 86400] // 24 hours
);
```

When designing indexes, use the MongoDB Compass UI or the `explain()` method to verify your queries are using the expected indexes. A query that scans every document in a large collection can bring your application to a standstill.

## Setting Up MongoDB and PHP

### MongoDB Atlas

The fastest way to get started is a free M0 cluster on MongoDB Atlas. This gives you a complete three-node cluster with access to the entire MongoDB Application Data Platform, including Atlas Search (full-text search) and Charts (visualization tools). Once your cluster is ready, grab the connection string from the Atlas UI.

### Installing the PHP Driver

Install the native MongoDB driver via PECL:

```bash
sudo pecl install mongodb
```

Add the extension to your `php.ini`:

```ini
extension=mongodb.so
```

For Docker users, there's a pre-configured image:

```bash
docker run --rm -d -p 8080:80 -v $(pwd)/www:/var/www joellord/php-mongodb
```

### Installing the Library

Use Composer to install the MongoDB PHP library:

```bash
composer require mongodb/mongodb
```

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
```

## Performing CRUD Operations

All examples use vanilla PHP for clarity, but the patterns apply directly to Laravel, Symfony, or any other framework.

### Connecting to the Server

```php
$client = new MongoDB\Client('YOUR_CONNECTION_STRING');
$db = $client->perfectMatch;
$collection = $db->speakers;
$collection->drop(); // Fresh start for demo
```

### Create

Insert a single document with `insertOne`. If you don't provide an `_id`, MongoDB generates one automatically:

```php
$newSpeaker = [
    'name' => 'Joel Lord',
    'bio' => 'Developer advocate at MongoDB...',
    'socials' => [
        'Twitter' => 'https://twitter.com/joel__lord',
        'Github' => 'https://github.com/joellord'
    ]
];

$result = $collection->insertOne($newSpeaker);
echo 'New user inserted with id ' . $result->getInsertedId();
```

For multiple documents, use `insertMany`:

```php
$otherSpeakers = [
    ['name' => 'William Wright'],
    ['name' => 'Amanda Ryan']
];

$result = $collection->insertMany($otherSpeakers);
echo 'Added ' . $result->getInsertedCount() . ' documents';
```

### Read

`findOne` returns the first matching document. With no filter, it returns the first document in the collection:

```php
$speaker = $collection->findOne();
echo 'The first speaker found is ' . $speaker->name;

foreach ($speaker->socials as $social => $link) {
    echo $social . ' (' . $link . ')';
}
```

Filter by specific fields:

```php
$speaker = $collection->findOne(['name' => 'William Wright']);
echo 'Found a match with id ' . $speaker->_id;
```

`find` returns a cursor you can iterate:

```php
$speakers = $collection->find()->toArray();
echo 'This collection contains ' . count($speakers) . ' documents';
```

### Update

`updateOne` takes a filter and an update operator. The `$set` operator updates specific fields:

```php
$result = $collection->updateOne(
    ['name' => 'Joel Lord'],
    ['$set' => ['bio' => 'Updated bio for Joel']]
);

$speaker = $collection->findOne(['name' => 'Joel Lord']);
echo $speaker->bio; // "Updated bio for Joel"
```

Use `updateMany` to update multiple documents matching a filter.

### Delete

```php
$result = $collection->deleteOne(['name' => 'William Wright']);

$speakers = $collection->find()->toArray();
echo 'Collection now contains ' . count($speakers) . ' documents';
```

Delete many with `deleteMany`.

## Advanced Querying with Aggregation Pipelines

Aggregation pipelines transform data through a series of stages. Each stage passes its results to the next. This is MongoDB's answer to complex queries, GROUP BY operations, and data transformations.

### A Simple Pipeline

To get the first five speakers alphabetically, showing only names and socials:

```php
$pipeline = [
    ['$project' => [
        '_id' => 0,
        'name' => 1,
        'socials' => 1
    ]],
    ['$sort' => [
        'name' => 1
    ]],
    ['$limit' => 5]
];

$speakers = $collection->aggregate($pipeline);
foreach ($speakers as $speaker) {
    echo $speaker->name . PHP_EOL;
}
```

### A Complex Faceted Pipeline

Want to count how many speakers have Twitter, how many have GitHub, and how many have both? The `$facet` stage lets you run multiple pipelines within a single stage:

```php
$pipeline = [
    [
        '$facet' => [
            'twitter' => [
                ['$match' => ['socials.Twitter' => ['$exists' => 1]]],
                ['$count' => 'count']
            ],
            'github' => [
                ['$match' => ['socials.Github' => ['$exists' => 1]]],
                ['$count' => 'count']
            ],
            'both' => [
                ['$match' => ['$and' => [
                    ['socials.Github' => ['$exists' => 1]],
                    ['socials.Twitter' => ['$exists' => 1]]
                ]]],
                ['$count' => 'count']
            ]
        ]
    ],
    ['$unwind' => ['path' => '$twitter']],
    ['$unwind' => ['path' => '$github']],
    ['$unwind' => ['path' => '$both']],
    ['$set' => [
        'twitter' => '$twitter.count',
        'github' => '$github.count',
        'both' => '$both.count'
    ]]
];

$result = $collection->aggregate($pipeline)->toArray();
```

This kind of faceted aggregation is where document databases shine—you get analytics-style queries without the complexity of multiple JOINs and subqueries.

## When to Use MongoDB with PHP

MongoDB excels in scenarios where:

1. **Your data has a natural document structure** — JSON-like documents map directly to your domain objects
2. **You need flexible schemas** — Adding fields doesn't require ALTER TABLE migrations
3. **You're building applications that scale** — MongoDB's horizontal scaling with sharding is built-in
4. **You work with heterogeneous data** — Documents in the same collection can have different fields
5. **You need integrated search** — Atlas Search provides full-text search without running Elasticsearch separately

Modern PHP frameworks have excellent MongoDB support. Laravel's Eloquent has MongoDB integrations via the `jenssegers/mongodb` package, and Symfony works seamlessly through Doctrine's ODM (Object Document Mapper). These give you the same ActiveRecord or repository patterns you're used to, while MongoDB handles the persistence.

### MongoDB with Laravel

When using Laravel with the MongoDB Eloquent integration, your models look almost identical to standard Eloquent models:

```php
<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Speaker extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'speakers';

    protected $fillable = ['name', 'bio', 'socials'];

    public function scopeWithTwitter(Builder $query): Builder
    {
        return $query->where('socials.Twitter', 'exists', true);
    }

    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
```

Your controllers use the same Eloquent API you already know:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Speaker;
use Illuminate\Http\JsonResponse;

class SpeakerController extends Controller
{
    public function index(): JsonResponse
    {
        $speakers = Speaker::withTwitter()
            ->alphabetical()
            ->take(10)
            ->get();

        return response()->json($speakers);
    }

    public function show(string $id): JsonResponse
    {
        $speaker = Speaker::findOrFail($id);

        return response()->json($speaker);
    }
}
```

### MongoDB with Symfony and Doctrine ODM

In Symfony, Doctrine's ODM maps MongoDB documents to PHP objects via annotations or PHP 8 attributes:

```php
<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'speakers')]
class Speaker
{
    #[MongoDB\Id]
    private string $id;

    #[MongoDB\Field(type: 'string')]
    private string $name;

    #[MongoDB\Field(type: 'string')]
    private string $bio;

    #[MongoDB\EmbedMany(targetDocument: SocialLink::class)]
    private iterable $socials;

    public function __construct()
    {
        $this->socials = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addSocial(SocialLink $social): void
    {
        $this->socials->add($social);
    }

    public function getSocials(): iterable
    {
        return $this->socials;
    }
}
```

The repository pattern works the same way you'd expect from Doctrine ORM, but with MongoDB-specific features available:

```php
<?php

namespace App\Repository;

use App\Document\Speaker;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class SpeakerRepository extends DocumentRepository
{
    public function findBySocialNetwork(string $network): array
    {
        return $this->createQueryBuilder()
            ->field('socials.' . $network)->exists(true)
            ->sort('name', 'asc')
            ->getQuery()
            ->execute();
    }

    public function aggregateSpeakerCountByNetwork(): array
    {
        return $this->createAggregationBuilder()
            ->project()
                ->excludeField('_id')
                ->includeField('name')
            ->sort('name', 'asc')
            ->getAggregation()
            ->getIterator()
            ->toArray();
    }
}
```

## Error Handling and Transactions

MongoDB's PHP driver throws exceptions that you can catch and handle gracefully. The most common ones are `MongoDB\Driver\Exception\ConnectionTimeoutException` for network issues and `MongoDB\Driver\Exception\BulkWriteException` for write failures.

```php
<?php

use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Exception\BulkWriteException;

try {
    $result = $collection->insertOne($document);
} catch (ConnectionTimeoutException $e) {
    // Log the error, queue for retry
    Log::error('MongoDB connection failed: ' . $e->getMessage());
    throw new \RuntimeException('Database temporarily unavailable');
} catch (BulkWriteException $e) {
    // Handle duplicate key or validation errors
    foreach ($e->getWriteResult()->getWriteErrors() as $error) {
        Log::warning('Write error: ' . $error->getMessage());
    }
}
```

MongoDB 4.0+ supports multi-document transactions for operations that require atomicity across multiple documents or collections. In PHP, transactions follow a straightforward pattern:

```php
$session = $client->startSession();
$session->startTransaction();

try {
    $accounts->updateOne(
        ['account_id' => 'ABC123'],
        ['$inc' => ['balance' => -100]],
        ['session' => $session]
    );

    $accounts->updateOne(
        ['account_id' => 'XYZ789'],
        ['$inc' => ['balance' => 100]],
        ['session' => $session]
    );

    $session->commitTransaction();
} catch (\Throwable $e) {
    $session->abortTransaction();
    throw $e;
}
```

Transactions carry a performance cost, so use them sparingly. In many cases, MongoDB's document-level atomicity (a single document write is always atomic) is sufficient, and the need for multi-document transactions indicates your data model may need revisiting.

## Performance Optimization

**Projection**: Only request the fields you need. `findOne(['name' => 'Joel'], ['projection' => ['name' => 1, 'socials' => 1]])` returns fewer bytes over the wire and reduces memory pressure.

**Batch operations**: When inserting or updating many documents, use `insertMany()` and `bulkWrite()` instead of looping with `insertOne()`. Bulk operations reduce round trips by orders of magnitude.

```php
$bulk = $collection->bulkWrite([
    [
        'insertOne' => [['name' => 'Speaker A']],
    ],
    [
        'updateMany' => [
            ['name' => ['$regex' => '^J']],
            ['$set' => ['tag' => 'starts-with-J']],
        ],
    ],
    [
        'deleteMany' => [
            ['name' => 'Obsolete Speaker'],
        ],
    ],
]);

echo 'Inserted: ' . $bulk->getInsertedCount() . PHP_EOL;
echo 'Updated: ' . $bulk->getModifiedCount() . PHP_EOL;
```

**Read preferences**: For read-heavy applications, configure read preferences to route reads to secondary nodes in a replica set. This offloads primary nodes for writes while scaling read capacity.

Connection string: `mongodb+srv://cluster0.example.mongodb.net/?readPreference=secondaryPreferred`

## Conclusion

MongoDB and PHP complement each other naturally. Document databases let you store and query data in a way that mirrors your application's mental model—without complex JOINs or rigid schemas. The data flows directly from the database into your PHP objects, and when you need sophisticated transformations, the aggregation pipeline framework handles it.

Start with a free MongoDB Atlas cluster, install the PHP driver, and explore the extensive documentation and community forums. The combination of PHP's flexibility and MongoDB's scalability is hard to beat for modern application development.
