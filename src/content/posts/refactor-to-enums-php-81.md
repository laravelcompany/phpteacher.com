---
title: "Refactor to Enums - Modernizing PHP Code With PHP 8.1 Enumerations"
seoTitle: "Refactor to Enums - Modernizing PHP Code With PHP 8.1 Enumerations"
description: "Modernize your PHP code with PHP 8.1 enums. Learn how to refactor constants and string-based states into type-safe enumerated types."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-07-22 21:00:00"
tags: ["PHP", "PHP 8.1", "Enums", "Refactoring", "Clean Code"]
---

PHP 8.1 introduced native enumerations, one of the most anticipated features in years. Enums let you define a type that is limited to one of a discrete set of possible values, making "incorrect states unrepresentable." This isn't just syntactic sugar—it fundamentally changes how you model domain concepts in PHP.

Before PHP 8.1, developers had several workarounds for enum-like behavior, each with significant drawbacks. This guide walks through real-world refactoring from these older approaches to PHP 8.1 enums, using a Laravel application as the example.

## The Old Ways

### Constants in a Class

```php
<?php

class ProductStatus
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
    const DRAFT = 'draft';
    const ARCHIVED = 'archived';
}
```

The problem: any string can be passed where a "status" is expected. `ProductStatus::ACTIVE` and `'cat'` are indistinguishable at the type system level. There's no validation, no autocomplete support beyond the class constants, and no way to enforce that only valid statuses are used.

### Database-Driven Statuses

Many Laravel applications store statuses in a database table. A `statuses` table has an ID and name, and your models have a `status_id` foreign key.

```php
// migration
Schema::create('statuses', function (Blueprint $table) {
    $table->id();
    $table->string('name');
});

Schema::table('products', function (Blueprint $table) {
    $table->foreignId('status_id')->constrained('statuses');
});
```

This approach works but adds complexity: a whole table and model for what amounts to a few string values. Every environment needs seed data. Queries need joins or eager loading to get the status name. And the "status" concept is still represented as an integer at the database level, losing semantic meaning.

### Third-Party Enum Packages

Packages like `myclabs/php-enum` and `spatie/enum` provided enum-like behavior before native support:

```php
use MyCLabs\Enum\Enum;

class ProductStatus extends Enum
{
    const ACTIVE = 'active';
    const INACTIVE = 'inactive';
}
```

These work but require a dependency, lack native language integration, and the error messages and IDE support aren't as good as native enums.

## Enter PHP 8.1 Enums

### Basic Enum

```php
<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
    case Archived = 'archived';
}
```

This defines a `ProductStatus` type with exactly four possible values. You can type-hint against it:

```php
<?php

namespace App\Models;

use App\Enums\ProductStatus;

class Product extends Model
{
    protected $casts = [
        'status' => ProductStatus::class,
    ];
}
```

Now `$product->status` is a `ProductStatus` instance, not a string. Passing any value other than the four defined cases is a type error at compile time.

### Pure Enums vs. Backed Enums

**Pure enums** have no associated value:

```php
enum PaymentStatus
{
    case Pending;
    case Completed;
    case Failed;
    case Refunded;
}
```

**Backed enums** have a scalar value (string or int):

```php
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
```

Use backed enums when you need to serialize to or deserialize from a database column, API response, or form input.

## Refactoring the Database Approach

When moving from the database-driven status approach to native enums, the migration changes from a foreign key to a simple string column:

```php
// Before
$table->foreignId('status_id')->constrained('statuses');

// After
$table->string('status');
```

Then cast to the enum in the model:

```php
class Product extends Model
{
    protected $casts = [
        'status' => ProductStatus::class,
    ];
}
```

Laravel's Eloquent automatically handles serialization: it stores the backed value (`'active'`) in the database and hydrates the enum case when loading.

## Enum Methods

Enums can have methods, making them self-contained value objects:

```php
<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Draft => 'Draft',
            self::Archived => 'Archived',
        };
    }

    public function isAccessible(): bool
    {
        return $this === self::Active;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }
        return $options;
    }
}
```

In Blade templates:

```blade
<select name="status">
    @foreach(App\Enums\ProductStatus::options() as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

## Real-World Refactoring Examples

### From Constants to Enum

Before:

```php
class OrderService
{
    public function process(Order $order, string $status): void
    {
        if ($status === OrderStatus::COMPLETED) {
            $order->markAsCompleted();
        }
    }
}
```

After:

```php
class OrderService
{
    public function process(Order $order, OrderStatus $status): void
    {
        if ($status === OrderStatus::Completed) {
            $order->markAsCompleted();
        }
    }
}
```

The type system now guarantees `$status` is a valid `OrderStatus`. No runtime validation needed.

### From Multiple Booleans to Enum

Before:

```php
class User
{
    private bool $isAdmin;
    private bool $isEditor;
    private bool $isViewer;
}
```

After:

```php
enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}

class User
{
    private UserRole $role;
}
```

This eliminates invalid state combinations (a user who is both admin and viewer?).

### Enum with Database Queries

Enums work naturally with Eloquent query scopes:

```php
class Product extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Active);
    }

    public function scopeByStatus(Builder $query, ProductStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }
}
```

### Form Request Validation

```php
class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }
}
```

Laravel's `Rule::enum()` validates that the value is a valid backed case of the enum.

## Symfony Integration

In Symfony, enums integrate naturally with the Form component:

```php
use App\Enums\ProductStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('status', EnumType::class, [
                'class' => ProductStatus::class,
                'choice_label' => fn (ProductStatus $status) => $status->label(),
            ]);
    }
}
```

Symfony's serializer also handles enums natively, encoding them as their backed value in JSON responses.

## Testing with Enums

Enums make tests clearer by eliminating magic strings:

```php
class ProductTest extends TestCase
{
    public function test_active_products_are_accessible(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $this->assertTrue($product->isAccessible());
    }

    public function test_draft_products_are_not_accessible(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::Draft,
        ]);

        $this->assertFalse($product->isAccessible());
    }
}
```

## Enum vs. State Pattern

Enums are not a replacement for the State pattern—they're complementary. Use enums for simple value types with a fixed set of options. Use the State pattern when behavior changes based on state in complex ways, and transitions have rules.

A good heuristic: if you find yourself writing `match ($this) { ... }` methods inside an enum that contain significant logic, consider whether a State pattern would be more appropriate. Enums excel at representing a fixed set of values with lightweight behavior.

## Enum Serialization in APIs

When building APIs with Laravel or Symfony, enum serialization requires attention. By default, Laravel's Eloquent API resources serialize backed enums as their scalar value:

```php
// Controller
public function show(Product $product): ProductResource
{
    return new ProductResource($product);
}

// ProductResource
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status, // Serializes to 'active'
        ];
    }
}
```

If you need custom serialization, implement `JsonSerializable` on your enum:

```php
enum ProductStatus: string implements \JsonSerializable
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
    case Archived = 'archived';

    public function jsonSerialize(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }
}
```

In Symfony, the Serializer component handles this automatically for backed enums. For API Platform, enums are natively supported in OpenAPI documentation generation, providing automatic swagger documentation for enum fields.

## Migrating Legacy Database Values

When refactoring from database-driven statuses to enums, you may have existing data. A migration script handles this:

```php
<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add the new column
        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->nullable();
        });

        // Step 2: Migrate data
        Product::with('legacyStatus')->chunk(100, function ($products) {
            foreach ($products as $product) {
                $product->status = match ($product->legacyStatus->name) {
                    'Active' => ProductStatus::Active->value,
                    'Inactive' => ProductStatus::Inactive->value,
                    'Draft' => ProductStatus::Draft->value,
                    'Archived' => ProductStatus::Archived->value,
                    default => ProductStatus::Draft->value,
                };
                $product->saveQuietly();
            }
        });

        // Step 3: Drop the old foreign key and table
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
            $table->string('status')->nullable(false)->change();
        });

        Schema::dropIfExists('statuses');
    }

    public function down(): void
    {
        // Reverse migration logic
    }
};
```

This three-step approach ensures no data loss. The new column is added first (nullable), data is migrated in chunks to avoid memory issues, and only then is the old column and table removed.

## Enums with Doctrine (Symfony)

In Symfony with Doctrine, enum mapping is straightforward using PHP 8.1 enums as types:

```php
<?php

namespace App\Entity;

use App\Enum\ProductStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', enumType: ProductStatus::class)]
    private ProductStatus $status;

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function setStatus(ProductStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
}
```

Doctrine stores the backed value in the database and hydrates the enum case when loading. Repository queries can filter by enum cases directly:

```php
<?php

namespace App\Repository;

use App\Entity\Product;
use App\Enum\ProductStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class ProductRepository extends ServiceEntityRepository
{
    public function findActiveProducts(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', ProductStatus::Active)
            ->getQuery()
            ->getResult();
    }
}
```

## Enums in Validation (Symfony)

Symfony's validation component supports enum constraints out of the box:

```php
<?php

namespace App\Entity;

use App\Enum\ProductStatus;
use Symfony\Component\Validator\Constraints as Assert;

class ProductDto
{
    #[Assert\NotBlank]
    #[Assert\Type(ProductStatus::class)]
    private ProductStatus $status;
}
```

This validates that the provided value is one of the valid enum cases, rejecting invalid values automatically.

## Performance Considerations

Enums are singletons. Each case is instantiated once per request and reused. There is no performance overhead compared to class constants. In fact, because enums participate in type checking at the opcode level, the engine can optimize comparisons more aggressively than string or integer comparisons.

```php
// Fast - compiled to a simple comparison
if ($status === ProductStatus::Active) { ... }

// Also fast - but no type safety
if ($status === 'active') { ... }
```

The type safety advantage comes with zero runtime cost.

## Best Practices

1. **Use backed enums for persistence**. Pure enums can't be stored directly in a database column without serialization.

2. **Keep enums focused**. An enum should represent one concept with a small, fixed set of values. Large enums (20+ cases) may indicate a design issue.

3. **Avoid behavior-heavy enums**. Enums can have methods, but complex business logic belongs in dedicated service or strategy classes.

4. **Name cases in PascalCase**. PHP enum cases follow class naming conventions. Use `Active` not `ACTIVE` or `active`.

5. **Use `tryFrom()` for user input**. When accepting enum values from HTTP requests or CLI input, use `ProductStatus::tryFrom($input)` which returns `null` for invalid values instead of throwing.

6. **Leverage `from()` when the value must exist**. If you're certain the value is valid, `ProductStatus::from('active')` returns the case or throws a `ValueError`.

## Conclusion

PHP 8.1 enums eliminate an entire category of bugs by making invalid states unrepresentable. They replace ad-hoc constant classes, database lookup tables, and third-party enum packages with native language syntax that every PHP developer understands.

The refactoring is straightforward: define the enum type, update model properties and casts, and replace string/integer comparisons with typed enum comparisons. The result is cleaner, safer, more self-documenting code that's easier to test, maintain, and evolve.
