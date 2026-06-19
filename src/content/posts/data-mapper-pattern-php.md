---
title: "Data Mapper Pattern in PHP - Clean Persistence Without ORM Lock-In"
description: "Learn how to implement the Data Mapper pattern in PHP. A complete guide to keeping your domain models clean from database concerns with practical code examples."
pubDate: "2023-01-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Data Mapper", "PHP", "Design Patterns", "ORM", "Doctrine", "Active Record", "Persistence", "Domain Model", "Architecture"]
selected: false
---

ORMs are incredible tools for rapid application development. You install a package, define a few mappings, and within minutes you're querying your database with expressive object-oriented syntax. But there's a price for that convenience — and it compounds as your application grows.

Active Record ORMs like Eloquent bundle database logic directly into your model objects. A `User` class isn't just a user — it's also responsible for saving itself, loading related records, and managing database connections. This coupling feels harmless in a small app, but in a large codebase it creates heavy objects that make batch processing painful, testing difficult, and architectural changes costly.

The Data Mapper pattern offers a different approach. Instead of your domain objects knowing about the database, a separate Mapper class handles all persistence concerns. Your domain objects stay pure PHP classes focused entirely on business logic. This separation gives you flexibility, testability, and long-term maintainability.

## What You'll Learn

- The difference between Active Record and Data Mapper
- How to build a custom PHP data mapper from scratch
- Handling reflection for private property access
- Implementing CRUD operations with clean separation
- Advanced query patterns without leaking schema details

## Active Record vs Data Mapper

There are two dominant strategies for persisting domain objects to a relational database.

**Active Record** — followed by Eloquent, Propel, and similar ORMs — makes the domain object itself responsible for persistence. Your `Product` class extends a base model class that provides `save()`, `delete()`, and `find()` methods. The object and its persistence logic are one and the same.

**Data Mapper** — used by Doctrine, Cycle, and custom implementations — keeps domain objects completely unaware of the database. A separate Mapper class knows about the domain object's internal structure, the database schema, and the translation between them. The domain object is just a plain PHP class.

The Active Record pattern is intuitive and great for prototyping. But as your domain complexity grows, the coupling between business logic and persistence becomes a liability. Data Mapper gives you a clean boundary between your domain model and infrastructure.

## Building a Domain Model

Let's start with a simple `Product` class that represents a product in an e-commerce system:

```php
class Product
{
    private int $id;
    private string $title;
    private string $brandName;
    private int $catalogPrice;

    public function __construct(
        int $id,
        string $title,
        string $brandName,
        int $catalogPrice
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->brandName = $brandName;
        $this->catalogPrice = $catalogPrice;
    }
}
```

Notice what this class does **not** have. There's no `save()` method. No `extends Model`. No database connection. It's a plain PHP class with typed properties and a constructor. This class only cares about holding product data — not about how that data gets persisted.

## Implementing the Mapper

The Data Mapper approach moves all persistence knowledge into a dedicated Mapper class:

```php
class ProductMapper
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(
        string $title,
        string $brandName,
        int $catalogPrice
    ): Product {
        // ...
    }

    public function update(Product $product): void { /* ... */ }

    public function delete(Product $product): void { /* ... */ }

    public function findById(int $id): Product { /* ... */ }

    public function findByTitle(string $title): ProductCollection { /* ... */ }
}
```

The Mapper class sits between your domain objects and the database. It knows about both sides — the structure of `Product` and the schema of the `products` table — but your domain objects remain blissfully unaware of SQL.

### The Reflection Problem

The first challenge you'll face is accessing private properties. Your `ProductMapper` needs to read and write the `id`, `title`, `brandName`, and `catalogPrice` properties of a `Product` object, but those properties are private.

You have several options:

1. **Make properties public** — simple but breaks encapsulation
2. **Use getters/setters** — requires adding boilerplate to every domain class
3. **Use reflection** — bypasses visibility without changing your domain classes
4. **Package-level visibility** — works in some languages but not PHP

For this implementation, we'll use a clean reflection wrapper:

```php
class ReflectionWrapper
{
    private object $object;
    private ReflectionClass $reflectedObj;

    public function __construct(object $object)
    {
        $this->object = $object;
        $this->reflectedObj = new ReflectionClass($object);
    }

    public function get(string $propertyName): mixed
    {
        $property = $this->reflectedObj->getProperty($propertyName);

        if ($property->isPrivate() || $property->isProtected()) {
            $property->setAccessible(true);
            $value = $property->getValue($this->object);
            $property->setAccessible(false);
        } else {
            $value = $property->getValue($this->object);
        }

        return $value;
    }
}
```

Using this wrapper, your mapper can access properties without modifying your domain classes:

```php
$publicProduct = new ReflectionWrapper($product);
$title = $publicProduct->get('title');
```

## CRUD Operations

### Create

The create method inserts a new row and constructs a `Product` with the generated ID:

```php
public function create(
    string $title,
    string $brandName,
    int $catalogPrice
): Product {
    $sql = 'INSERT INTO products (title, brand_name, catalog_price)
            VALUES (?, ?, ?)';

    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(1, $title, PDO::PARAM_STR);
    $statement->bindValue(2, $brandName, PDO::PARAM_STR);
    $statement->bindValue(3, $catalogPrice, PDO::PARAM_INT);

    try {
        $this->pdo->beginTransaction();
        $statement->execute();
        $productId = $this->pdo->lastInsertId();
        $this->pdo->commit();
    } catch (PDOException $e) {
        $this->pdo->rollBack();
        throw $e;
    }

    return new Product((int) $productId, $title, $brandName, $catalogPrice);
}
```

### Update and Delete

The update and delete methods use the reflection wrapper to access private properties:

```php
public function update(Product $product): void
{
    $wrapped = new ReflectionWrapper($product);

    $sql = 'UPDATE products SET title = :title,
            brand_name = :brandName,
            catalog_price = :catalogPrice
            WHERE id = :id';

    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(':id', $wrapped->get('id'), PDO::PARAM_INT);
    $statement->bindValue(':title', $wrapped->get('title'), PDO::PARAM_STR);
    $statement->bindValue(':brandName', $wrapped->get('brandName'), PDO::PARAM_STR);
    $statement->bindValue(':catalogPrice', $wrapped->get('catalogPrice'), PDO::PARAM_INT);
    $statement->execute();
}

public function delete(Product $product): void
{
    $wrapped = new ReflectionWrapper($product);

    $sql = 'DELETE FROM products WHERE id = :id';
    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(':id', $wrapped->get('id'), PDO::PARAM_INT);
    $statement->execute();
}
```

### Reading from the Database

For reading data, you need methods that convert database rows back into domain objects:

```php
public function findById(int $id): Product
{
    $sql = 'SELECT * FROM products WHERE id = :id';
    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);
    $statement->execute();

    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return $this->convertRowToObject($row);
}

private function convertRowToObject(array $row): Product
{
    return new Product(
        (int) $row['id'],
        $row['title'],
        $row['brand_name'],
        (int) $row['catalog_price']
    );
}

protected function convertRowsToProducts(array $rows): ProductCollection
{
    $products = new ProductCollection();

    foreach ($rows as $row) {
        $products->add($this->convertRowToObject($row));
    }

    return $products;
}
```

## Advanced Query Patterns

As your application grows, you'll need more flexible querying. There are several approaches, each with trade-offs.

### Specific Finder Methods

The simplest approach is to add specific methods for each query you need:

```php
public function findByTitle(string $title): ProductCollection
{
    $sql = 'SELECT * FROM products WHERE title = :title';
    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(':title', $title, PDO::PARAM_STR);
    $statement->execute();

    return $this->convertRowsToProducts($statement->fetchAll(PDO::FETCH_ASSOC));
}

public function findExpensiveBrandedProducts(string $brandName): ProductCollection
{
    $sql = 'SELECT * FROM products
            WHERE brand_name = :brandName
            AND catalog_price >= :priceLimit';

    $statement = $this->pdo->prepare($sql);
    $statement->bindValue(':brandName', $brandName, PDO::PARAM_STR);
    $statement->bindValue(':priceLimit', 1000, PDO::PARAM_INT);
    $statement->execute();

    return $this->convertRowsToProducts($statement->fetchAll(PDO::FETCH_ASSOC));
}
```

The advantage is clarity — each method name describes exactly what it does. The downside is that you'll end up with many methods as filtering combinations grow.

### Abstracted Finder Methods

For highly filterable listing pages, abstracted methods reduce method count:

```php
public function findBy(array $criteria): ProductCollection
{
    // Build WHERE clause dynamically from $criteria
    // e.g., ['brandName' => 'Adidas', 'title' => 'Ultraboost']
}
```

You've seen this pattern if you've used Doctrine's repository API. It's flexible but requires careful implementation to avoid SQL injection.

### Query Builder Exposure

Some mappers expose a query builder to the calling code. This gives maximum flexibility but risks leaking persistence details into your domain layer:

```php
$q = $this->createQueryBuilder('p')
    ->where('p.brandName = :brandName')
    ->setParameter('brandName', $brandName)
    ->andWhere('p.catalogPrice >= :priceLimit')
    ->setParameter('priceLimit', 1000)
    ->getQuery();

$results = $q->getResult();
```

This approach should be used sparingly. It couples your application code to the ORM and obscures the intent of the query.

## Real-World Use Cases

**Enterprise E-Commerce Platforms** — Large product catalogs with complex filtering, inventory management, and multi-tenant data isolation benefit from clean mapper boundaries.

**SaaS Applications** — When each tenant has isolated data but shares schema, a Data Mapper can inject tenant-scoping logic into every query without contaminating domain models.

**High-Volume Batch Processing** — Data Mappers let you optimize bulk operations (imports, exports, migrations) without polluting domain objects with pagination or chunking logic.

**Legacy System Migration** — When migrating from one database to another, the mapper layer absorbs the change. Domain code stays untouched while only the mapper's SQL changes.

## Best Practices

**One Mapper Per Aggregate Root** — Each aggregate root gets its own mapper. Don't create a monolithic mapper that handles every entity.

**Keep Domain Objects Pure** — Resist the temptation to add persistence concerns to domain classes. If you need lazy loading, implement it in the mapper or a proxy layer.

**Use Interfaces for Testability** — Program to a `ProductMapperInterface` so you can swap implementations (in-memory, SQL, API) without changing domain code.

**Transactions at the Application Level** — Manage transaction boundaries in your application services, not inside individual mapper methods. This lets you compose multiple mapper operations in a single transaction.

**Consider Identity Maps** — For long-running processes, an identity map ensures you don't load the same object twice, preventing stale data and reducing database hits.

## Common Mistakes to Avoid

**Putting Business Logic in Mappers** — A mapper should only handle persistence. Business rules, calculations, and validations belong in domain objects or services.

**Exposing Database Schema to the Application** — When your finder method names or parameters match column names exactly, you've coupled your application to the schema. Use domain-language parameter names instead.

**Over-Abstracting From Day One** — Start with simple finder methods. Introduce abstracted criteria APIs or query builders only when you genuinely need them.

**Mixing Active Record and Data Mapper** — Choose one approach per aggregate root. Mixing them in the same domain leads to confusion about where persistence logic lives.

**Skipping Tests for the Mapper Layer** — Data mapper code is I/O-heavy and error-prone. Write integration tests that verify SQL queries produce correct domain objects.

## Frequently Asked Questions

**Q: Should I use Doctrine or build a custom Data Mapper?**

Doctrine is production-ready and handles complex scenarios like inheritance mapping, identity maps, and lazy loading. Build a custom mapper when you need full control or have simple persistence needs.

**Q: Does Data Mapper work with Laravel?**

Yes. While Laravel defaults to Eloquent's Active Record, you can use Doctrine alongside it or build custom mappers with Laravel's query builder or Eloquent's underlying PDO connection.

**Q: How do I handle relationships between objects?**

You have several options: load related objects eagerly in the mapper method, use lazy loading proxies, or implement a separate mapper for each related aggregate and coordinate them in an application service.

**Q: Is reflection slow for property access?**

Reflection has a cost, but for most applications it's negligible compared to database I/O. Cache reflected property handles if performance is critical.

**Q: Can I use Data Mapper without an ORM?**

Absolutely. The pattern is database-agnostic. You can implement it with plain PDO, MySQLi, or any database abstraction layer.

**Q: How do I handle soft deletes?**

Implement a `SoftDeletableProductMapper` that adds a `WHERE deleted_at IS NULL` clause to every query and sets `deleted_at` on delete operations instead of physically removing rows.

## Conclusion

The Data Mapper pattern gives you a clean separation between your domain model and your persistence layer. Your domain objects stay pure PHP classes focused on business logic, while the mapper handles all database concerns.

This separation pays dividends as your application grows. Testing becomes easier because domain objects don't require database setup. Batch operations become faster because you can optimize SQL without touching business code. And architectural changes become safer because the persistence layer is an implementation detail behind a clean interface.

Start small. Pick one aggregate root in your application and implement a custom mapper for it. Experience the difference a clean persistence boundary makes — then decide how far you want to take it.

Ready to clean up your persistence layer? Pick a model in your current project and extract its persistence logic into a dedicated mapper class today.
