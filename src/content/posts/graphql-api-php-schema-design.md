---
title: "GraphQL APIs With PHP - Schema Design and Execution Guide"
description: "Learn to build GraphQL APIs with PHP using webonyx/graphql-php. Covers schema design, resolvers, mutations, authentication, error handling, and query optimization."
pubDate: "2023-10-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "GraphQL", "API", "Schema Design", "Webonyx", "Lighthouse", "Backend", "Web Development"]
selected: false
---

REST APIs have served us well, but they come with inherent friction. A mobile app needs different data than a web dashboard. A product listing page needs different fields than an admin panel. With REST, you either build multiple endpoints for each client or accept that every response contains data the client does not need.

GraphQL solves this by putting the client in control. The client specifies exactly which fields it needs, and the server responds with only those fields. No over-fetching. No under-fetching. No versioning headaches.

This guide walks through building a complete GraphQL API in PHP using the `webonyx/graphql-php` library. You will design schemas, write resolvers, handle authentication, manage errors, and optimize queries for production.

## What You'll Learn

- Setting up graphql-php with Composer and project structure
- Designing GraphQL schemas with types, relationships, and input types
- Writing resolver functions that fetch real data
- Implementing queries and mutations for CRUD operations
- Adding authentication and authorization to GraphQL endpoints
- Error handling and structured logging
- Query optimization techniques including batching and pagination
- Testing GraphQL APIs with PHPUnit

## Setting Up the GraphQL Environment

Start by installing the library:

```bash
composer require webonyx/graphql-php
```

Organize your project structure to separate concerns:

```
graphql-project/
├── index.php
├── schema/
│   ├── types/
│   ├── queries/
│   ├── mutations/
│   └── schema.php
└── resolvers/
    ├── QueryResolver.php
    └── MutationResolver.php
```

This layout keeps type definitions separate from resolver logic. As your schema grows, this separation prevents files from becoming unmanageable.

## Designing GraphQL Schemas

The schema is the contract between your API and its consumers. Every query, mutation, and data type is defined here.

### Defining Object Types

```php
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

$userType = new ObjectType([
    'name' => 'User',
    'fields' => [
        'id' => Type::id(),
        'name' => Type::string(),
        'email' => Type::string(),
        'age' => Type::int(),
        'isActive' => Type::boolean(),
        'posts' => [
            'type' => Type::listOf($postType),
            'resolve' => fn($user) =>
                $postRepository->findByUserId($user['id']),
        ],
    ],
]);

$postType = new ObjectType([
    'name' => 'Post',
    'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'content' => Type::string(),
        'author' => [
            'type' => $userType,
            'resolve' => fn($post) =>
                $userRepository->findById($post['author_id']),
        ],
    ],
]);
```

Object types map directly to your domain entities. The `resolve` key in each field is optional — if omitted, the resolver looks for a matching key in the parent data array. This means simple field mappings need no resolver at all, while computed or related fields can specify custom resolution logic.

### Input Types for Mutations

Input types define the shape of data accepted by mutations. They use the `InputObjectType` class:

```php
use GraphQL\Type\Definition\InputObjectType;

$userInputType = new InputObjectType([
    'name' => 'UserInput',
    'fields' => [
        'name' => Type::nonNull(Type::string()),
        'email' => Type::nonNull(Type::string()),
        'age' => Type::int(),
    ],
]);
```

Notice the `Type::nonNull()` wrapper. This makes the field required. If a mutation omits a non-null field, GraphQL returns a validation error before the resolver is even called.

### Relationships Between Types

GraphQL's relationship handling is where it truly shines over REST.

```php
$blogType = new ObjectType([
    'name' => 'Blog',
    'fields' => [
        'id' => Type::id(),
        'title' => Type::string(),
        'authors' => [
            'type' => Type::listOf($userType),
            'resolve' => fn($blog) =>
                $userRepository->findByBlogId($blog['id']),
        ],
        'posts' => [
            'type' => Type::listOf($postType),
            'resolve' => fn($blog) =>
                $postRepository->findByBlogId($blog['id']),
        ],
    ],
]);
```

A single GraphQL query can traverse from blog to authors to posts without multiple API calls:

```graphql
{
    Blog(id: 1) {
        title
        authors { name email }
        posts { title content }
    }
}
```

In REST, this would require at least three requests: `GET /blogs/1`, `GET /blogs/1/authors`, and `GET /blogs/1/posts`. With GraphQL, it is one request, and the client decides how deep to traverse.

## Writing Resolvers

Resolvers are the functions that fetch data for each field. They can be defined inline in the type definition or as standalone functions.

```php
namespace App\Resolvers;

class UserResolver
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function findById($root, array $args): ?array
    {
        return $this->repository->find((int) $args['id']);
    }

    public function findAll($root, array $args): array
    {
        return $this->repository->findAll(
            limit: $args['limit'] ?? 10,
            offset: $args['offset'] ?? 0
        );
    }
}
```

Resolvers receive four arguments: the root value from the parent resolver, the field arguments, the context (shared across all resolvers in a request), and the resolve info (schema metadata).

```php
// Context can carry authentication state
$context = [
    'user' => $authenticatedUser,
    'db' => $databaseConnection,
];
```

## Handling Queries and Mutations

Queries fetch data. Mutations change data. The distinction is enforced at the schema level.

```php
$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [
        'getUser' => [
            'type' => $userType,
            'args' => [
                'id' => Type::nonNull(Type::id()),
            ],
            'resolve' => fn($root, $args) =>
                $userResolver->findById($root, $args),
        ],
        'users' => [
            'type' => Type::listOf($userType),
            'args' => [
                'limit' => Type::int(),
                'offset' => Type::int(),
            ],
            'resolve' => fn($root, $args) =>
                $userResolver->findAll($root, $args),
        ],
    ],
]);

$mutationType = new ObjectType([
    'name' => 'Mutation',
    'fields' => [
        'createUser' => [
            'type' => $userType,
            'args' => [
                'input' => Type::nonNull($userInputType),
            ],
            'resolve' => fn($root, $args) =>
                $mutationResolver->createUser($args['input']),
        ],
    ],
]);
```

Executing queries and mutations:

```php
$schema = new Schema([
    'query' => $queryType,
    'mutation' => $mutationType,
]);

$query = '
    mutation {
        createUser(input: {
            name: "John Doe"
            age: 30
        }) {
            id
            name
            age
        }
    }
';

$result = GraphQL::executeQuery(
    $schema,
    $query,
    null,
    $context
)->toArray();
```

## Authentication and Authorization

GraphQL does not specify how authentication should work — that is left to the implementation. Two common approaches are JWT and OAuth.

### JWT Authentication

```php
use Firebase\JWT\JWT;

// Generate token
$token = JWT::encode(
    ['user_id' => $user->id, 'role' => $user->role],
    $secretKey,
    'HS256'
);

// Verify token in middleware
$decoded = JWT::decode($token, $secretKey, ['HS256']);
$context = ['user' => $userRepository->find($decoded->user_id)];
```

### Authorization at the Resolver Level

```php
function resolveSensitiveData($root, $args, $context): array
{
    $user = $context['user'] ?? null;

    if (!$user || !$user->canAccessSensitiveData()) {
        throw new \Exception(
            'No permission to access this data.'
        );
    }

    return $user->getSensitiveData();
}
```

For role-based access control, libraries like `spatie/laravel-permission` integrate cleanly with resolver-level authorization checks.

## Error Handling and Logging

GraphQL returns errors in a structured format alongside the data. This means partial success is possible — some fields resolve while others return errors.

```php
function resolveUserName($root, $args, $context): string
{
    $user = $context['userRepository']->find($args['id']);

    if (!$user) {
        throw new \Exception('User not found.');
    }

    if ($user->isBlocked()) {
        throw new \Exception('This user is blocked.');
    }

    return $user->getName();
}
```

Errors from resolvers appear in the response's `errors` array, each with a message, locations, and path. The `data` section contains the fields that resolved successfully.

### Structured Logging

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('graphql');
$logger->pushHandler(
    new StreamHandler('var/log/graphql.log', Logger::DEBUG)
);

try {
    $result = GraphQL::executeQuery(
        $schema, $query, null, $context
    );
    $logger->info('Query executed', [
        'query' => $query,
        'errors' => $result->errors,
    ]);
} catch (\Throwable $e) {
    $logger->error('Query failed', [
        'error' => $e->getMessage(),
    ]);
}
```

## Optimizing GraphQL Queries

### Batching

Use DataLoader-style batching to avoid the N+1 query problem:

```graphql
{
    users(limit: 50) {
        name
        posts { title }  # 50 separate queries without batching
    }
}
```

A batching resolver collects all parent IDs and fetches related data in a single query:

```php
function resolvePostsBatched(array $userIds): array
{
    $posts = $this->postRepository
        ->findByUserIds($userIds);

    $grouped = [];
    foreach ($posts as $post) {
        $grouped[$post['user_id']][] = $post;
    }

    return $grouped;
}
```

### Pagination

Never return unbounded lists. Use pagination arguments:

```graphql
{
    posts(first: 10, after: "cursor") {
        edges {
            node { title content }
            cursor
        }
        pageInfo {
            hasNextPage
            endCursor
        }
    }
}
```

### Caching

Cache frequently accessed data with Redis or Memcached:

```php
function resolveExpensiveData($root, $args, $context): array
{
    $cacheKey = 'expensive:' . md5(serialize($args));

    return $context['cache']->remember(
        $cacheKey,
        3600,
        fn() => $this->repository->fetchExpensive($args)
    );
}
```

## Testing GraphQL APIs

### Unit Testing Resolvers

```php
use PHPUnit\Framework\TestCase;

class UserResolverTest extends TestCase
{
    public function testUserNameResolver(): void
    {
        $user = (object) ['name' => 'John Doe'];
        $result = resolveUserName($user);
        $this->assertEquals('John Doe', $result);
    }
}
```

### Integration Testing

```php
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;

class GraphQLApiTest extends TestCase
{
    public function testGetUser(): void
    {
        $client = new Client();
        $query = '{ getUser(id: 123) { name email } }';

        $response = $client->post(
            'https://your-api/graphql',
            ['json' => ['query' => $query]]
        );

        $data = json_decode(
            $response->getBody()->getContents(), true
        );

        $this->assertEquals(
            'John Doe',
            $data['data']['getUser']['name']
        );
    }
}
```

## Real-World Use Cases

**E-Commerce Platforms.** GraphQL allows product listing pages to request only the fields they need — name, price, image URL — while the admin panel requests inventory levels, supplier data, and revision history from the same endpoint.

**Content Management Systems.** Blog platforms use GraphQL to let content creators fetch custom post types with related media, author profiles, and comment threads in a single query.

**Mobile Applications.** Mobile clients benefit most from GraphQL's selective fetching. A news app fetches article titles and excerpts for the list view, then full content and metadata for the detail view, all from the same schema.

## Best Practices

**Use non-null types aggressively.** If a field should always have a value, mark it as non-null. This shifts error detection from runtime to request validation.

**Name mutations as verbs.** `createUser`, `updatePost`, `deleteComment` — not `addUser`, `modifyPost`, `removeComment`. Consistent naming reduces cognitive load.

**Version through deprecation, not endpoints.** Add the `@deprecated` directive to old fields and let clients migrate at their own pace.

**Limit query depth.** Malicious clients can craft deeply nested queries that overwhelm your server. Implement depth limiting to prevent abuse.

## Common Mistakes to Avoid

**Over-fetching in resolvers.** Just because the client can request every field does not mean you should eagerly load everything. Use lazy resolution and batching.

**Ignoring the N+1 problem.** Without batching, a list of 50 items with related data triggers 51 separate queries. Always batch related data.

**Exposing internal IDs.** Use opaque global IDs (base64-encoded type and ID) rather than exposing database primary keys.

**Skipping input validation.** GraphQL validates types but not business rules. Validate inputs in your resolvers before persisting data.

## Frequently Asked Questions

**Should I use GraphQL or REST for my next project?**
Use GraphQL when clients need flexible data fetching and you want to avoid versioning. Use REST for simple CRUD APIs, public APIs with stable contracts, or when HTTP caching is critical.

**Does GraphQL replace database queries?**
No. GraphQL is a query language for your API, not your database. Resolvers still execute SQL queries, call microservices, or fetch from caches.

**How does file upload work in GraphQL?**
GraphQL mutations can accept file uploads using the multipart request specification. Libraries like `webonyx/graphql-php` support this through custom scalar types.

**Is GraphQL slower than REST?**
It can be, if not optimized. Without batching and caching, GraphQL resolvers make more database queries than a hand-tuned REST endpoint. With proper optimization, the difference is negligible.

**Can I use GraphQL with Laravel?**
Yes. The Lighthouse PHP package provides Laravel-specific GraphQL integration with schema-first design, auto-generated resolvers, and Eloquent relationship handling.

## Conclusion

GraphQL transforms API development by giving clients control over the data they receive. PHP, combined with `webonyx/graphql-php`, provides a mature platform for building GraphQL servers that handle complex data relationships, authentication, and performance optimization.

The key to a successful GraphQL implementation is disciplined schema design. Invest time in defining types that mirror your domain. Write focused resolvers that handle one responsibility. Test your resolvers in isolation and your endpoints through integration tests.

Start with a single query type. Add mutations as you need them. Iterate on your schema as you learn what your clients actually need.
