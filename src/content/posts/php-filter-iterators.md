---
title: "PHP FilterIterators - Master Collection Filtering With SPL"
description: "Learn how to use PHP FilterIterators to cleanly filter collections. Master SPL iterators, write maintainable code, and ace technical interviews with real examples."
pubDate: "2022-01-15 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "SPL", "Design Patterns", "Collections", "Iterators", "FilterIterator", "SOLID", "Clean Code"]
selected: false
---

Every PHP developer hits *that* wall. You're staring at a nested loop that's been patched six times, conditions are piling up like Jenga blocks, and the PM just asked for **one more filter**. The array is 50,000 records. Your colleague's solution? `array_filter` with a 200-line closure. Your solution? Something better.

Welcome to PHP's SPL FilterIterators — the tool that turns spaghetti filtering into clean, testable, single-responsibility classes.

Whether you're cramming for a senior dev interview, maintaining a legacy codebase, or architecting the next big SaaS platform, mastering iterator filtering will change how you think about collections forever.

## What You'll Learn

- Why raw arrays are a liability and collections are the upgrade
- How SPL iterators decouple storage from behavior (thank you, SRP)
- Building reusable `FilterIterator` subclasses that make your codebase sing
- Real e-commerce filtering — price ranges, vendor IDs, product prefixes
- `array_filter` vs. iterators: the honest trade-offs
- Composing multiple filters without turning your code into pasta
- PHP 8.1+ patterns: readonly properties, named arguments, typed everything

## The Problem With Plain Arrays

Arrays in PHP are absurdly flexible. That's both their superpower and their kryptonite. They double as lists, maps, sets, and sometimes even query builders when you're feeling reckless.

```php
$products = [
    ['id' => 1, 'title' => 'Widget', 'price' => 29.99, 'vendor_id' => 101],
    ['id' => 2, 'title' => 'Gadget', 'price' => 99.99, 'vendor_id' => 102],
];
```

The minute you start threading business logic through array operations, things get ugly:

```php
$filtered = array_filter($products, function ($product) use ($minPrice, $maxPrice, $vendorId) {
    return $product['price'] >= $minPrice
        && $product['price'] <= $maxPrice
        && $product['vendor_id'] === $vendorId
        && str_starts_with($product['title'], 'Widget');
});
```

This works — until it doesn't. You can't unit test that closure. You can't reuse it. You can't compose it. And when the PM says "we need a new filter for stock levels," you're rewriting the whole thing.

Arrays violate the **Single Responsibility Principle** by mixing storage and iteration logic. SPL iterators fix this by giving each concern its own class.

## What Are SPL Iterators?

The Standard PHP Library (SPL) provides a family of iterator classes that wrap traversable data sources. They separate **what you iterate** from **how you iterate**.

Core SPL iterators include:

- `ArrayIterator` — wraps an array in an object, enabling mutation during iteration
- `AppendIterator` — chains multiple iterators into one sequential pass
- `LimitIterator` — restricts how many items you see (pagination, anyone?)
- `InfiniteIterator` — loops forever (great for circular queues)
- `FilterIterator` — the star of this show: skip items that don't match
- `IteratorIterator` — any `Traversable` becomes an iterator

The key insight? You **compose** them. Need the first 10 items from three merged sources that match a price filter?

```php
$merged = new AppendIterator();
$merged->append(new ArrayIterator($source1));
$merged->append(new ArrayIterator($source2));
$merged->append(new ArrayIterator($source3));

$priced = new PriceFilterIterator($merged, 10.0, 100.0);
$page = new LimitIterator($priced, 0, 10);

foreach ($page as $item) { /* ... */ }
```

Each piece does one thing. None of them know about each other. That's the iterator pattern in its purest form.

## FilterIterator: The Deep Dive

`FilterIterator` is an abstract class. Subclass it and implement `accept(): bool`. Return `true` to keep the current element, `false` to skip it.

The signature looks like this:

```php
abstract class FilterIterator extends IteratorIterator
{
    public function __construct(Iterator $iterator) { }

    abstract public function accept(): bool;

    public function current(): mixed { /* delegates to inner iterator */ }
    public function key(): mixed { /* delegates to inner iterator */ }
    public function next(): void { /* advances to next accepted element */ }
    public function rewind(): void { /* rewinds and advances to first accepted */ }
}
```

The magic is in `rewind()` and `next()` — they automatically call `accept()` and skip rejected items. Your filter class only worries about the boolean decision.

### Building a Price Filter

Let's start simple. An e-commerce site needs to filter products by a minimum price.

```php
readonly class Product
{
    public function __construct(
        public int     $id,
        public string  $title,
        public float   $price,
        public int     $vendorId,
        public ?string $description = null,
    ) {}
}
```

```php
class PriceFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly float $minPrice,
        private readonly float $maxPrice,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $product = $this->current();

        return $product->price >= $this->minPrice
            && $product->price <= $this->maxPrice;
    }
}
```

Usage:

```php
$products = new ArrayIterator([
    new Product(id: 1, title: 'Widget', price: 29.99, vendorId: 101),
    new Product(id: 2, title: 'Premium Widget', price: 149.99, vendorId: 101),
    new Product(id: 3, title: 'Budget Gadget', price: 5.99, vendorId: 102),
]);

$filtered = new PriceFilterIterator($products, minPrice: 10.0, maxPrice: 100.0);

foreach ($filtered as $product) {
    echo $product->title . PHP_EOL;
}
// Output: Widget
```

### Adding More Filters

The real power emerges when you need multiple filters. Each gets its own class with a single responsibility.

```php
class VendorIdFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly int $vendorId,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        return $this->current()->vendorId === $this->vendorId;
    }
}
```

```php
class ProductTitlePrefixFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly string $prefix,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        return str_starts_with($this->current()->title, $this->prefix);
    }
}
```

### Composing Filters

Now chain them together. Each filter wraps the previous one:

```php
$products = fetchProductsFromApi();

$filtered = new PriceFilterIterator(
    new VendorIdFilterIterator(
        new ProductTitlePrefixFilterIterator(
            $products,
            prefix: 'Premium',
        ),
        vendorId: 101,
    ),
    minPrice: 20.0,
    maxPrice: 200.0,
);

foreach ($filtered as $product) {
    printf(
        "[%d] %s — $%.2f (Vendor: %d)%s",
        $product->id,
        $product->title,
        $product->price,
        $product->vendorId,
        PHP_EOL,
    );
}
```

The composition reads inside-out. The innermost iterator fetches all products. Each layer wraps and filters. The `foreach` loop only sees items that passed every filter.

### A Complete CLI Example

Here's a real-world CLI script that reads products from a JSON API and applies multiple filters:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

class Product
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $title,
        public readonly float   $price,
        public readonly int     $vendorId,
        public readonly ?string $description = null,
    ) {}
}

class PriceFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly float $minPrice,
        private readonly float $maxPrice,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $product = $this->current();

        return $product->price >= $this->minPrice
            && $product->price <= $this->maxPrice;
    }
}

class VendorIdFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly int $vendorId,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        return $this->current()->vendorId === $this->vendorId;
    }
}

class ProductTitlePrefixFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly string $prefix,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        return str_starts_with($this->current()->title, $this->prefix);
    }
}

function fetchProducts(string $url): ArrayIterator
{
    $response = file_get_contents($url);
    $data = json_decode($response, true, flags: JSON_THROW_ON_ERROR);

    $products = array_map(
        fn (array $item) => new Product(
            id: $item['id'],
            title: $item['title'],
            price: (float) $item['price'],
            vendorId: $item['vendor_id'],
            description: $item['description'] ?? null,
        ),
        $data,
    );

    return new ArrayIterator($products);
}

// Entry point
[$script, $url, $minPrice, $maxPrice, $vendorId, $prefix] = array_pad(
    $argv,
    6,
    null,
);

$products = fetchProducts($url);

$filtered = $products;

if ($minPrice !== null && $maxPrice !== null) {
    $filtered = new PriceFilterIterator(
        $filtered,
        minPrice: (float) $minPrice,
        maxPrice: (float) $maxPrice,
    );
}

if ($vendorId !== null) {
    $filtered = new VendorIdFilterIterator(
        $filtered,
        vendorId: (int) $vendorId,
    );
}

if ($prefix !== null) {
    $filtered = new ProductTitlePrefixFilterIterator(
        $filtered,
        prefix: $prefix,
    );
}

foreach ($filtered as $product) {
    printf(
        "[%d] %s — \$%.2f%s",
        $product->id,
        $product->title,
        $product->price,
        PHP_EOL,
    );
}

printf("Total matching products: %d%s", iterator_count($filtered), PHP_EOL);
```

Run it:

```bash
php filter.php "https://api.example.com/products" 10 100 101 "Premium"
```

Every filter is optional. Every filter is testable. Every filter can be used or removed without touching the others.

## FilterIterator vs. array_filter

`array_filter` has its place. Let's be honest about when each makes sense.

| Criteria | FilterIterator | array_filter |
|---|---|---|
| **Testability** | Each filter is a class — mock it, unit test it | Closure — must test the calling code |
| **Reusability** | Instantiate anywhere | Copy-paste or extract to function |
| **Composition** | Chain iterators: natural decorator pattern | Nested calls or manual combining |
| **Lazy evaluation** | Yes — filters as you iterate | No — allocates new array |
| **Memory with large sets** | O(1) overhead per item | O(n) new array allocation |
| **Cognitive load** | Low per class | Grows with each condition |
| **Setup boilerplate** | More (a class per filter) | Minimal |

**Use `array_filter` when** you have a one-off filter, the logic fits in two lines, and you don't mind the memory allocation.

**Use FilterIterator when** you have multiple filters, the logic is non-trivial, you need testability, or you're processing large datasets.

## The "Self-Filtering Collection" Approach

A pattern gaining traction is pushing filter logic into the collection itself. Combine the best of both worlds:

```php
class ProductCollection
{
    /** @var array<Product> */
    private array $items;

    public function __construct(Product ...$products)
    {
        $this->items = $products;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function filterByPrice(float $min, float $max): static
    {
        return new static(...array_filter(
            $this->items,
            fn (Product $p) => $p->price >= $min && $p->price <= $max,
        ));
    }

    public function filterByVendor(int $vendorId): static
    {
        return new static(...array_filter(
            $this->items,
            fn (Product $p) => $p->vendorId === $vendorId,
        ));
    }
}
```

The fluent API reads beautifully:

```php
$matches = $collection
    ->filterByPrice(min: 10.0, max: 100.0)
    ->filterByVendor(vendorId: 101);
```

But beware — this creates a new collection (and a new array) at each step. Combine it with iterators for the internal representation when memory matters.

## Real-World Use Cases

### Laravel Collections

Laravel's `Collection` class is essentially this pattern on steroids. The `filter()` method accepts a closure, but `LazyCollection` uses generators internally — the same lazy evaluation principle as iterators.

```php
$filtered = collect($products)
    ->filter(fn (Product $p) => $p->price >= 10)
    ->where('vendorId', 101)
    ->values();
```

Under the hood, Laravel's macro system lets you register custom filters, which mirrors the FilterIterator pattern. If you've used `Collection::macro()`, you've already internalized the concept — you're just ready to apply it at the SPL level.

### E-Commerce Product Filtering

Product catalogs are the poster child for FilterIterators. Customers filter by dozens of attributes simultaneously:

```php
$catalog = new ProductFilterChain(
    new CategoryFilterIterator($products, categoryId: $request->category),
    new PriceFilterIterator($products, min: $request->minPrice, max: $request->maxPrice),
    new InStockFilterIterator($products),
    new RatingFilterIterator($products, minRating: 4.0),
    new BrandFilterIterator($products, brandIds: $request->brands),
);
```

Each filter is a class. Each class has tests. You can add "sort by newest" without touching a single filter.

### API Response Filtering

When building REST APIs, clients often want server-side filtering. Instead of one massive query builder, compose iterators:

```php
class ApiResponseFilterIterator extends FilterIterator
{
    public function __construct(
        Iterator $iterator,
        private readonly array $fields,
    ) {
        parent::__construct($iterator);
    }

    public function accept(): bool
    {
        $item = $this->current();

        foreach ($this->fields as $field => $value) {
            if (!isset($item->$field) || $item->$field != $value) {
                return false;
            }
        }

        return true;
    }
}
```

This lets you build a generic `?filter[status]=active&filter[type]=premium` query handler with zero boilerplate per endpoint.

### Reporting Systems

Reports often filter the same dataset by different criteria for different dashboards. Each report gets its own filter chain:

```php
$data = new ArrayIterator($transactions);

$dailyReport = new DateRangeFilterIterator($data, $today, $today);
$monthlyReport = new DateRangeFilterIterator($data, $monthStart, $monthEnd);
$refundReport = new TransactionTypeFilterIterator($data, 'refund');

$dailyTotal = array_sum(iterator_to_array($dailyReport));
$monthlyTotal = array_sum(iterator_to_array($monthlyReport));
// Not a single if statement changed
```

## Best Practices

### SOLID Filtering

Each filter class embodies the **Single Responsibility Principle**: it knows one rule and applies one check. The **Open/Closed Principle** follows naturally — add filters by creating classes, not by modifying existing ones. **Dependency Inversion** means your high-level filtering logic depends on `Iterator` abstractions, not concrete arrays.

### Performance With Large Datasets

FilterIterators are **lazy**. They don't pre-filter; they reject items one at a time during iteration. For a 500,000-row CSV import:

```php
$rows = new CsvIterator($fileHandle); // yields one row at a time
$valid = new ValidRowFilterIterator($rows);
$region = new RegionFilterIterator($valid, region: 'EMEA');
$active = new ActiveUserFilterIterator($region);

foreach ($active as $row) {
    processRow($row);
}
```

Memory stays flat regardless of file size. No temporary arrays. No GC pressure.

### Composition Over Inheritance

Don't create a mega-filter that inherits everything:

```php
// Avoid
class EverythingFilter extends FilterIterator
{
    public function accept(): bool
    {
        return $this->checkPrice()
            && $this->checkVendor()
            && $this->checkTitle();
    }
}
```

Do compose small filters:

```php
$filtered = new PriceFilterIterator(
    new VendorIdFilterIterator(
        $products,
        vendorId: 101,
    ),
    minPrice: 10.0,
    maxPrice: 100.0,
);
```

Each filter is independently testable and independently useful. You can reorder them, skip them, or add new ones without opening an existing class.

### PHP 8.x Features to Use

- **Constructor promotion** — define and initialize properties in one line
- **`readonly` properties** — filter parameters shouldn't change mid-iteration
- **Named arguments** — `new PriceFilterIterator($it, minPrice: 10.0, maxPrice: 100.0)` is self-documenting
- **Match expressions** — `match ($this->current()->type) { 'premium' => true, default => false }`
- **`mixed` type** — `current(): mixed` matches modern PHP signatures

## Common Mistakes to Avoid

**Mutating the inner iterator.** FilterIterator delegates to its inner iterator. If you call `next()` on the inner iterator directly, you'll desync the filter. Always interact through the filter.

**Forgetting `parent::__construct()`.** The FilterIterator constructor requires an `Iterator` argument. If you override `__construct`, you must call `parent::__construct($iterator)` or nothing works.

**Complex accept() logic.** If `accept()` needs more than ~5 lines, extract helper methods or create a separate validator:

```php
public function accept(): bool
{
    return $this->matchesCategory()
        && $this->isInPriceRange()
        && $this->isInStock();
}

private function matchesCategory(): bool { /* ... */ }
private function isInPriceRange(): bool { /* ... */ }
private function isInStock(): bool { /* ... */ }
```

**Ignoring rewind behavior.** FilterIterator calls `accept()` during `rewind()` to find the first valid element. Make sure `accept()` doesn't have side effects that break on multiple passes.

**Mixing array access with iterator access.** `$filtered[0]` won't work on a FilterIterator. Use `iterator_to_array()` if you need random access:

```php
$all = iterator_to_array($filtered);
```

But remember — this defeats lazy evaluation and allocates memory for the entire set.

## FAQ

**Q: Can I use FilterIterator with generators?**

A: Yes — generators implement `Iterator`. Wrap your generator in an `IteratorIterator` or pass it directly. The filter will evaluate lazily.

```php
function generateProducts(): Generator
{
    yield new Product(/* ... */);
}

$filtered = new PriceFilterIterator(
    generateProducts(),
    minPrice: 10.0,
    maxPrice: 100.0,
);
```

**Q: How is FilterIterator different from array_filter?**

A: FilterIterator is lazy (evaluates on iteration) and object-oriented. `array_filter` returns a new array immediately. Use FilterIterator for large datasets, testability, or composable filtering.

**Q: Can I stack unlimited filters?**

A: Technically yes — each filter wraps the previous. Practical limits depend on memory for the object graph, but chains of 10-20 filters are common and perform well.

**Q: Does FilterIterator work with foreach?**

A: Yes — that's the primary use case. `foreach` calls `rewind()`, `valid()`, `current()`, and `next()` under the hood, which FilterIterator handles automatically.

**Q: Can I reuse a FilterIterator instance?**

A: Yes, call `rewind()` manually. But be aware that the inner iterator may not support rewinding (e.g., a network stream). Check your inner iterator's capabilities.

**Q: What about sorting?**

A: FilterIterator does not sort. Use `iterator_to_array()` with a sort function, or compose with an `ArrayIterator` that pre-sorts the data before filtering.

**Q: Is FilterIterator faster than array_filter?**

A: For iteration, yes — it skips items without allocating memory. For full traversal, `array_filter` is often faster in CPU time because it has less object overhead. Profile your specific use case.

**Q: Can I combine FilterIterator with other SPL iterators?**

A: Absolutely. That's the whole point. Wrap filters in `LimitIterator` for pagination, `AppendIterator` for merging sources, `CachingIterator` to avoid re-fetching.

## Conclusion

FilterIterators are one of PHP's most underrated features. They enforce clean architecture where filtering logic belongs in testable, reusable classes. They keep memory flat when processing thousands of records. And they compose naturally, so your codebase grows without turning into a maintenance nightmare.

Start small. Extract your next `array_filter` into a FilterIterator subclass. Feel the difference when you can unit test that filter in isolation. Then chain two. Then three.

Your future self — and the poor soul who inherits your code — will thank you.

**Now go refactor something.** 🚀
