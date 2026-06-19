---
title: "Graphing Relational Database Models With PHP and Neo4j"
description: "Learn how to convert relational database models to graph models using PHP and Neo4j. Step-by-step guide covering SQL to Cypher migration, the Neo4j PHP driver, and querying graph data."
pubDate: "2022-08-10 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Neo4j", "Graph Database", "PHP", "Relational Database", "Data Modeling"]
selected: false
---

Every PHP developer knows the classic LAMP stack: Linux, Apache, MySQL, and PHP. SQL databases have served us well for decades. But as your application grows, the limits of relational modeling start to show. Slow joins, excessive caching, and convoluted workarounds become the norm. This is where graph databases like Neo4j shine.

This article walks through converting a relational database model into a graph model using PHP 8.1 and the Neo4j PHP driver. You'll learn how to map SQL schemas to nodes and relationships, handle foreign keys, pivot tables, and polymorphic associations, and query the resulting graph with Cypher.

## When to Move From SQL to Graph

Performance issues are the biggest trigger for migrating from SQL to a graph database. Once teams make the switch, they often discover a two-for-one deal: both data modeling and application structure become smoother, with fewer hacks and workarounds.

Here are common symptoms that signal it might be time to consider a graph database:

- **Slow join operations.** Tables in SQL have no way to treat relationships as first-class citizens. They rely on foreign key constraints to emulate this behavior. Once the dataset grows large enough, SQL becomes the bottleneck.

- **Caching for everything.** Caching is an excellent optimization tool but also a significant source of bugs and developer confusion. When you need to cache simply to make queries performant, the complexity of your application rises.

- **Esoteric solutions to simple problems.** Custom indexing systems and workarounds for thinking in tables are red flags.

- **Non-normalized tables.** Because joins are expensive, duplicating small pieces of information in tables becomes common practice.

- **Multiple foreign keys on one table where only one relationship exists.** If a table can point to two or more different tables but only one per row, you introduce redundancy through multiple foreign key columns.

## Setting Up the Neo4j PHP Driver

The Neo4j PHP client library gives you a clean interface for connecting to a Neo4j database and running Cypher queries. Install it with Composer:

```bash
composer require neo4j/neo4j-client
```

Creating a connection requires only a few lines:

```php
use Neo4j\Neo4jClient;
use Neo4j\Driver\Driver;

$driver = Driver::create('neo4j://neo4j:test@localhost');

$driver->verifyConnectivity() ?? throw new \RuntimeException(
    'Cannot connect to database'
);

$session = $driver->createSession();
```

For comparison, a traditional PDO connection looks familiar:

```php
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=test',
    'test',
    'sql'
);
```

With both tools ready, let's start migrating data.

## Inserting Rows as Nodes

Most rows translate directly to nodes in a graph database. The naive approach is straightforward:

```php
$articles = $pdo->query('SELECT * FROM articles')
    ->fetchAll(PDO::FETCH_ASSOC);

$session->run(<<<'CYPHER'
UNWIND $articles AS row
MERGE (a:Article {id: row['id']})
ON CREATE SET a = row
CYPHER, compact('articles'));
```

This works, but it pulls every row into memory at once. For large tables, that's a memory overflow waiting to happen. Use generators instead:

```php
public function yieldRows(string $table): \Generator
{
    $statement = $this->pdo->query(
        sprintf('SELECT * FROM %s', $table)
    );

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $row['created_at'] = new \DateTime($row['created_at']);
        $row['updated_at'] = $row['updated_at'] === null
            ? null
            : new \DateTime($row['updated_at']);

        yield $row;
    }
}
```

Then process in chunks:

```php
foreach (Helper::chunk($pdo->yieldRows('articles'), 25000) as $chunk) {
    $nodes->storeRowsAsNodes('Article', $chunk);
}
```

This keeps memory usage low and gives you fine-grained control over the migration.

## Mapping Foreign Keys to Relationships

Relationships are second-class citizens in SQL but first-class in Neo4j. A basic foreign key migration connects nodes by matching IDs:

```php
public function connectArticles(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (child:Article),
          (parent:Article {id: child['parent_id']})
    MERGE (child) - [:HAS_PARENT] -> (parent)
    CYPHER)->getSummary();
}
```

The `ResultSummary` object provides useful metadata about the operation, including counts of created nodes and relationships.

## Multiple Foreign Keys

Multiple foreign keys in a single table are often the first hurdle newcomers encounter. The wrong approach is trying to create multiple relationships in a single query:

```php
// WRONG: This will fail for comments pointing to other comments
public function connectCommentToArticles(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (c:Comment), (a:Article {id: c.article_id})
    MERGE (c) - [:COMMENTED_ON] -> (a)
    MATCH (c), (p:Comment {id: c.comment_id})
    MERGE (c) - [:COMMENTED_ON] -> (p)
    CYPHER)->getSummary();
}
```

The problem is that the initial comment match is reused, and comments with a `null` `comment_id` never match anything. Split them into separate, clear queries:

```php
public function connectCommentToArticles(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (c:Comment), (a:Article {id: c.article_id})
    MERGE (c) - [:COMMENTED_ON] -> (a)
    CYPHER)->getSummary();
}

public function connectParentComments(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (c:Comment), (p:Comment {id: c.parent_id})
    MERGE (c) - [:COMMENTED_ON] -> (p)
    CYPHER)->getSummary();
}
```

The consensus in the Neo4j community is to prefer smaller, simple queries over complex all-in-one queries.

## Pivot Tables as Relationships

Pivot tables are relationships in disguise. They exist solely to define many-to-many connections between two tables. An important insight is that pivot tables are bidirectional, but in Neo4j, every relationship is unidirectional. You can still query without a direction, though.

When defining the relationship type, choose an active verb that conveys direction. For example, a Tag TAGS an Article. If the verb becomes passive, the direction inverts: an Article is TAGGED_BY a Tag.

```php
public function connectTags(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (at:ArticleTag), (t:Tag {id: at['tag_id']}),
                          (a:Article {id: at['article_id']})
    MERGE (t) - [ta:TAGS] -> (a)
    ON CREATE SET ta = at
    CYPHER)->getSummary();
}
```

Notice how relationship attributes work the same way as node properties—you can copy pivot table column data directly onto the relationship.

## Polymorphic Relations

Polymorphic associations are the most complex migration case. Tables and rows are often used interchangeably when comparing SQL to graph databases, but a node translates to a row, not a table. Node labels use singular nouns while tables use plural.

Create a translation table to map table names to node labels:

```php
private const TRANSLATION_TABLE = [
    'article_tags' => 'ArticleTag',
    'articles' => 'Article',
    'comments' => 'Comment',
    'polymorphic_categories' => 'Category',
    'tags' => 'Tag',
    'users' => 'User',
];
```

Use generators to defer the mapping until the Neo4j driver sends data over the network:

```php
$categories = $this->yieldRows('polymorphic_categories');
$categories = Helper::map(
    $categories,
    static fn (array $x) => [
        ...$x,
        'label' => self::TRANSLATION_TABLE[$x['resource_table']],
    ]
);
$this->storeRowsAsNodes('Category', $categories);
```

Now wire the relationships together:

```php
public function connectCategories(): ResultSummary
{
    return $this->session->run(<<<'CYPHER'
    MATCH (c:Category), (x {id: c.resource_id})
    WHERE c.label IN labels(x)
    MERGE (c) - [:CATEGORIZES] -> (x)
    CYPHER)->getSummary();
}
```

## Real-Time Queries With Cypher

Once your data lives in Neo4j, you can ask questions that would require complex multi-join SQL queries. Here are three examples.

### List All Tags for an Article (Including Sub-Article Tags)

```php
public function listAllTags(int $articleId): array
{
    return $this->session->run(<<<'CYPHER'
    MATCH p = (:Article {id: $articleId}) <- [:HAS_PARENT*0..] - (:Article)
    UNWIND nodes(p) AS article
    WITH DISTINCT article
    MATCH (article) <- [:TAGS] - (tag:Tag)
    RETURN tag.name AS tag
    CYPHER, compact('articleId'))
        ->pluck('tag')
        ->toArray();
}
```

### Find the Node With the Most Categories

```php
public function topCategoryNode(): void
{
    $node = $this->session->run(<<<'CYPHER'
    MATCH (c:Category) - [:CATEGORIZES] -> (node)
    WITH node, collect(c) AS categoryDegree
    RETURN node
    ORDER BY categoryDegree DESC
    LIMIT 1
    CYPHER)
        ->getAsCypherMap(0)
        ->getAsNode('node');

    echo 'LABEL: ' . $node->getLabels()->first() . PHP_EOL;
    echo 'ID: ' . $node->getProperty('id') . PHP_EOL;
}
```

### Find Users Who Commented on Two Different Articles

```php
public function doubleCommenters(): array
{
    return $this->session->run(<<<'CYPHER'
    MATCH (b:Article) <- [:COMMENTED_ON*1..] -
          (:Comment) <- [:Commented] - (u:User),
          (u) - [:COMMENTED] -> (:Comment) -
          [:COMMENTED_ON*1..] -> (a:Article)
    WHERE a <> b
    RETURN DISTINCT u AS user
    CYPHER)
        ->pluck('user')
        ->toArray();
}
```

## Using Generators for Memory Efficiency

Generators are one of PHP's most underused features. They let you iterate over large datasets without loading everything into memory. Combined with arrow functions and array spreading, the migration code becomes both efficient and expressive:

```php
public function storeRowsAsNodes(string $tag, iterable $nodes): void
{
    $this->session->run(<<<CYPHER
    UNWIND \$nodes AS node
    MERGE (x:$tag {id: node.id})
    ON CREATE SET x = node
    CYPHER, compact('nodes'));
}
```

The `iterable` type hint accepts both arrays and `Generator` instances, making your code flexible without sacrificing type safety.

## Understanding the Translation

Here's a quick reference for the conceptual mapping between SQL and Neo4j:

| SQL | Neo4j |
|-----|-------|
| Row | Node |
| Table | Node label (singular) |
| Foreign key | Relationship |
| Pivot table | Relationship with properties |
| Column | Property |
| JOIN | Traversal |
| SELECT | MATCH + RETURN |
| INSERT | MERGE or CREATE |

## When Graph Databases Aren't the Answer

Graph databases excel at highly connected data and deep traversal queries. They are not a replacement for SQL in every scenario. If your application primarily does simple CRUD with flat data structures, a relational database is likely still the right choice. Graph databases shine when the relationships between entities are as important as the entities themselves.

## Conclusion

Migrating from SQL to Neo4j is a significant undertaking. This article covered the core patterns: rows to nodes, foreign keys to relationships, pivot tables to relationship properties, and polymorphic associations using dynamic labels. Use generators for memory efficiency, split complex queries into smaller Cypher statements, and always verify your connection before running migrations.

The Neo4j PHP driver and Cypher query language give PHP developers a powerful tool for working with connected data. Start with the free interactive course at GraphAcademy to build your foundation, then apply these migration patterns to your own projects.
