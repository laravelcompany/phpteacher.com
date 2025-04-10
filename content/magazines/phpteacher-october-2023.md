---
title: PHP Teacher magazine - October 2023
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - october
  - 2023
---

Welcome, readers, to another edition of *The PHP Teacher*! This month, we delve into a diverse range of topics, from the intricacies of building **GraphQL APIs with PHP** to the importance of **security** and **testing** in modern web development. We'll explore the collaborative world of game development, the nuances of sorting algorithms, and the power of **observability** in complex systems. We'll also touch on front-end development with HTMX, consider accessibility, and rethink how we approach Laravel applications. This issue aims to equip you with knowledge and practical skills to enhance your development journey. Get ready for a deep dive into the heart of PHP and beyond!

---

## GraphQL APIs with PHP: A Guide to Schema Design and Execution**

Introduction:

In today's fast-paced web development world, APIs are the backbone of most applications. **GraphQL** is a powerful query language that offers an alternative to traditional REST APIs by allowing clients to request exactly the data they need. This article will guide you through the process of building efficient, flexible, and secure **GraphQL APIs using PHP**. We'll cover everything from setting up your environment to handling complex queries and mutations.

Getting Started:

Before we begin, you’ll need to set up your development environment. Firstly, ensure **PHP** is installed on your computer. You can download it from the official website or use a package manager like Homebrew for macOS. Next, you'll need a **GraphQL library**. We'll use the excellent `webonyx/graphql-php` library:

```bash
composer require webonyx/graphql-php
```

A basic project structure is crucial for an organised workflow:

```
Your-graphql-project/
├── index.php    # GraphQL entry point
├── schema/
│   ├── types/   # Define GraphQL types
│   ├── queries/ # Create queries
│   ├── mutations/ # Implement mutations
│   └── schema.php # Combine types, queries, and mutations
└── resolvers/
    ├── QueryResolver.php    # Resolver for queries
    └── MutationResolver.php # Resolver for mutations
```

Understanding GraphQL Schemas

A **GraphQL schema** defines the structure and rules of your API. Types are fundamental, and include:

*   **Scalar Types**: Basic data types like `String`, `Int`, `Float`, `Boolean`, and `ID`.
*   **Object Types**: Represent entities with fields that have their own types.
*  **Input Types**: Used for passing arguments to queries and mutations.

Example:

```graphql
input UserInput {
  Name: String!
  Age: Int!
}

type User {
    id: ID!
    name: String!
    posts: [Post] # Relationship
}

type Post {
    id: ID!
    title: String!
    content: String!
    author: User
}
```
Relationships between types are easily established:

Resolvers:

**Resolvers** are PHP functions that fetch and manipulate data based on queries and mutations. Each field in your schema needs a corresponding resolver.

Example:

```php
function resolveUserName($user) {
  return $user->getName();
}

// Linking resolver to the schema:
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

$userType = new ObjectType([
 'name' => 'User',
 'fields' => [
    'name' => [
        'type' => Type::string(),
        'resolve' => 'resolveUserName',
        ],
     // ... other fields ...
    ],
]);
```

Handling Queries and Mutations:

Queries allow clients to request specific data, while mutations allow for data manipulation.

Example Query:

```graphql
type Query {
  getUser(id: ID!): User
}
```

Example Mutation:

```graphql
type Mutation {
  createUser(input: UserInput!): User
}
```

Best Practices:
* Group related queries and mutations logically.
* Use clear and descriptive names.
* Document your schema thoroughly.

Security:
* Implement authentication mechanisms such as **JWT** or **OAuth**.
* Use libraries like `firebase/php-jwt` for JWT handling.

Error Handling:
* Use exceptions to communicate issues to the client.

```php
function resolveUserName($user) {
  if (!$user) {
    throw new \Exception("User not found.");
  }
  return $user->getName();
}
```
Optimization
* Use batching and pagination to efficiently retrieve data.
* Avoid over-fetching and under-fetching.

Testing:
*  Use PHPUnit to write unit tests for your resolvers and types.
*  Use PHPUnit or Postman to write integration tests.

```php
  use PHPUnit\Framework\TestCase;
    class UserResolverTest extends TestCase
    {
        public function testUserNameResolver()
        {
            $user = (object) ['name' => 'John Doe'];
            $result = resolveUserName($user);
            $this->assertEquals('John Doe', $result);
        }
    }
```

Real-World Examples:
* **E-commerce platforms**: Efficiently manage product details and inventory.
* **Content Management Systems (CMS)**: Offer fine-grained control over retrieving data.

Conclusion:
GraphQL in PHP offers a powerful and flexible approach to API development. By understanding schema design, writing resolvers, and implementing best practices, you can build efficient, scalable, and secure APIs.

```
       ___
      /   \
     |  _  |    GraphQL
     | / \ |    in PHP
     |-----|
     \_____/

```

## The Shared Thinking Patterns Between Programmers and Artists**

Introduction:

In the realm of game development, collaboration between programmers and artists is vital for creating immersive and engaging experiences. While these disciplines are often seen as distinct, there are significant overlaps in their tools, workflows, and thought processes. This article explores these similarities to foster better communication and mutual respect between programmers and artists.

Tools of the Trade:

*Programmer Essentials:*

*   **Game Engines**: Software development environments like Unity and Unreal Engine that simplify game creation.
*   **Code Editors**: Tools such as Visual Studio Code, Sublime Text, and IntelliJ IDEA for writing code.
*  **Version Control Systems (VCS)**: Systems like Git for tracking code changes and collaboration.

*Artist Essentials:*

*   **Digital Painting**: Software like Adobe Photoshop and Clip Studio Paint for concept art and texturing.
*   **3D Modelling/Sculpting**: Tools such as Blender and ZBrush for creating 3D assets.

The Pipeline: The System Development Life Cycle (SDLC)

Both programmers and artists follow the SDLC, which consists of five steps:

1.  **Planning**: Programmers collaborate with artists to define the game's scope, mechanics, and visual style. Artists start with initial concepts and sketches.
2.  **Analysis**: Programmers research the technical feasibility of the game mechanics, while artists analyse references to understand the art direction.
3.  **Design**: Programmers start creating basic game mechanics and logic, while artists "block out" their art pieces using basic shapes.
4.  **Implementation**: Programmers integrate all the code, while artists add details to their models and paintings.
5. **Maintenance**: Both teams work to resolve bugs and improve performance or visual quality, and continue to work iteratively

Shared Thinking:

*   **Iterative Approach**: Both disciplines use an iterative approach, constantly refining their work.
*   **Problem-Solving**: Both programmers and artists engage in creative problem-solving.
*   **Attention to Detail**: A shared dedication to detail is essential for both roles.
*   **Continuous Learning**: Both fields are constantly evolving, requiring continuous learning.

Conclusion:
By recognising their shared workflows and thought processes, programmers and artists can foster better communication, stronger collaboration, and greater mutual respect.

```
   ______
  /      \
 /  _  _  \  Programmer
 | / \ / \ |    and
 | \_/ \_/ |  Artist
 \______/
```

## Software Archaeology - Part 2**

Introduction:

This article continues our journey into the depths of legacy PHP projects. We'll explore how to find our way through unfamiliar codebases using various debugging techniques and testing strategies.

Finding Your Way:

*   **Searching Files**: Use IDE searching capabilities and command-line tools like `egrep` to locate specific routes, text, or class names.
*   **Find People with Knowledge**: Talk to those who previously worked on the project. Look at commit histories on platforms like GitHub to find contributors.

Debugging Techniques:

*   **`var_dump()` and `die()`**: Simple methods to print variable contents and halt code execution.

```php
if ($condition) {
  var_dump($variable);
  die('Reached inside the condition.');
}
```
* **Symfony Var Dumper:** Use `symfony/var-dumper` for enhanced debugging with configurable output and output buffering
*   **Xdebug**: A powerful tool for step debugging, allowing you to halt code execution at breakpoints and inspect variables. Install using PECL and configure in `php.ini` or a config file.

```ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
```

*   Set up your IDE (e.g., Visual Studio Code, PHPStorm) to connect with Xdebug. Set breakpoints and use step controls to explore the code.

Testing:
*   **Unit Tests**: Test individual code blocks such as methods. Use testing frameworks like PHPUnit or Pest.

```php
public function testGetParts(): void
{
    $e = CronExpression::factory('0 22 * * 1-5');
    $parts = $e->getParts();

    $this->assertSame('0', $parts);
    $this->assertSame('22', $parts);
    $this->assertSame('*', $parts);
    $this->assertSame('*', $parts);
    $this->assertSame('1-5', $parts);
}
```
*   **Functional Tests**: Verify the interaction of multiple systems. Use tools like Behat or Codeception.
*  **Behavioral Tests**: Document how users are expected to interact with the system.

Legacy Applications:
* When delving into legacy PHP applications, both unit tests and integration tests are vital tools.
* While integration tests provide broader insights and may be more accessible initially, unit tests offer a focused, detailed understanding necessary for software archaeology.

Conclusion:
With the correct tools and strategies, navigating legacy code can be an exciting exploration. Combine searching, debugging, and thorough testing to master even the most complex of codebases.

```
    ____
   /    \
  |  /\  |   Software
  | /  \ |   Archaeology
  |______|
```

## The Meaning of “High Trust”**

Introduction:
Security isn't just about external threats. In many cases, those with internal access to a system pose the most significant risks. This article examines the concept of "high trust" and the importance of monitoring and securing internal operations.

Internal Threats:
*   Employees have deep knowledge of the system and often have direct access to credentials.
*   They can read and edit logs, control duty schedules, and access backups.
*  They could be the source of malware, embezzlement or intellectual property theft

Securing Your System:
*   **Principle of Least Privilege**: Limit control to known, well-scoped actions.
*   **Systematic Tracking**: Track every action performed in the system using an append-only log.
*   **Cryptographic Signing**: Use a signing algorithm to ensure that log records cannot be removed or changed.

Trust, but Verify:
*  Even trusted employees should have their actions logged and audited.

Conclusion:
Do not ignore the risks of internal threats. By implementing appropriate logging, auditing, and access controls, you can protect your system from rogue insiders.

```
    ____
   /    \
  |  /\  |   High
  | /  \ |    Trust
  |______|
```

**Article 5: HTMX: The Simple Markup Extension We’ve Been Waiting For**

By Matt Lantz (Adapted for *The PHP Teacher*)

Introduction:

HTMX, or Hypertext Markup Extension, is an external JavaScript library that allows developers to use **AJAX**, CSS, and **HTML** to manage web interactions without the complexity of traditional JavaScript frameworks. This article reviews the pros and cons of HTMX and how to make the most of it.

How HTMX Works:

*   HTMX allows you to perform AJAX requests using HTML attributes.
*   It reduces the need for JavaScript builds and simplifies code.

Example:

```html
<button hx-post="/clicked" hx-trigger="click" hx-target="#target">
   Click Me
</button>
```
Reduced Complexity:
*   HTMX eliminates the need to perform JavaScript builds and can render HTML directly instead of processing JSON.

Seamless Integration:
*   HTMX is framework agnostic and can integrate with any backend language or framework, offering high versatility.

Performance:
*   HTMX only loads what is necessary for the initial page experience.
*   It performs DOM content swaps rather than reprocessing whole sections of the page.

Security:
*   Review documentation to cover issues like XSS
*   You should include your CSRF token in the header to make sure your application is protected

```html
<body hx-headers='{"X-CSRFToken": "your_csrf_token"}'>
```

Community Support:
*  HTMX's community is still growing, and complex issues may not have immediate solutions or workarounds.

HTMX with Blade components:
* HTMX pairs well with Blade components in Laravel since it has no real JavaScript to load or propagate in the template layering.

Conclusion:
HTMX offers simplicity, reduced complexity, and enhanced performance. If you are building a small to medium-sized application or want to remove maintenance tasks, HTMX is a great solution.

```
  _______
 /   _   \
|  (_)  |   HTMX
 \_____/
```

## Emoticons, Stickers, and GIFs**

By Maxwell Ivey (Adapted for *The PHP Teacher*)

Introduction:

This article explores the accessibility challenges faced by users with visual impairments, focusing on how screen readers interpret emoticons, stickers, and GIFs.

Emoticons and Emojis:
*   Screen readers often provide inclusive descriptions of emoticons and emojis, including details about skin color, gender, and number of people.

Stickers:
*   Descriptions of stickers are less predictable and rely on the designer's effort.

GIFs:
*   GIFs are the most problematic, as screen readers do not typically describe animated content.
*   When adding a GIF always include alternative text.

Best Practices:
*  Use alternative text tags for accessibility.
*  Limit the number of emoticons to three or fewer per post.
*   Be mindful of the descriptions provided for stickers and GIFs.

Conclusion:
By paying attention to the way screen readers interpret visual content, we can make digital communication more accessible to everyone.

```
     ____
    /    \
   |  ()  |   Accessibility
   |______|

```

## Insertion Sort**

Introduction:

This article continues our exploration of sorting algorithms, focusing on the **insertion sort**, an efficient algorithm for small arrays. We'll also analyse its performance compared to the bubble sort and comb sort.

How Insertion Sort Works:
*   Insertion sort builds a sorted array one element at a time. It scans an array from n-1 to the first element shifting to the right until an element smaller is found, then goes to the next loop.

Pseudocode:

```
i ← 1
while i < length(A)
    j ← i
    while j > 0 and A[j-1] > A[j]
        swap A[j] and A[j-1]
        j ← j - 1
    end while
    i ← i + 1
end while
```
PHP Implementation:

```php
function swap_elements(array &$list, int $i, int $j): void {
   if (array_key_exists($i, $list) && array_key_exists($j, $list)) {
      $tmp = $list[$i];
      $list[$i] = $list[$j];
      $list[$j] = $tmp;
      return;
   }
   throw new \InvalidArgumentException("Invalid index specified");
}

function insertion_sort(array &$list): void
{
    $i = 1;
    while ($i < count($list)) {
        $j = $i;
        while ($j > 0 and $list[$j - 1] > $list[$j]) {
            swap_elements($list, $j, $j - 1);
            --$j;
        }
        ++$i;
    }
}
```
Benchmarks:
*   The insertion sort is faster than bubble sort but not as fast as comb sort, because it also makes many comparisons.

Performance Table:

|Sort|1000|5000|10000|
|---|---|---|---|
|Bubble|0.0321|0.8414|3.3420|
|Insertion|0.0249|0.6525|2.7604|
|Comb |0.00873|0.06275|0.1286|

Conclusion:
Insertion sort is an incremental approach to sorting small arrays. While not the fastest, it demonstrates a different strategy for organizing data.

```
   _____
  /  _  \
 | (_) |   Insertion
 \_____/   Sort
```

## DDD Alley: Create Observability, Part 5: Offline Processes**

By Edward Barnard (Adapted for *The PHP Teacher*)

Introduction:

This article concludes our series on creating observability, focusing on offline processes like queue workers and email handling.

Offline Processes:
*   Offline processes, like email notifications, need to be handled outside of the main request flow to avoid "hang time".
*  Design for events that show the running process's perspective.
*  The event name and description are both expressed in the past tense.
*  The "activity" field lists values that should be captured as part of the event.

Email Funnel:
*   Emails should be sent one recipient at a time to allow for individual tracking and customer support.

Process Design:
1.  A business process puts requests into an input queue.
2.  A worker splits the request per recipient (creating “To”, “From”, “Subject”, the twig templates, and parameters).
3.  A worker renders the Twig templates and moves the record to the next queue, including the rendered email.
4.  A worker sends the email and records the gateway status.
* Each worker should emit events to describe their step.
*  Record events in database tables, then consider moving to New Relic or Splunk.
* Each queue should be observed on a log status page.
* The final queue should contain a copy of the sent message to help with auditing and resending.

Separation of Concerns:
*   Separate different parts of the process so changes do not cause issues.
*   You should be able to change the queueing mechanism, email system, and logging tool independently.

Conclusion:
By using flowcharts, small steps, event-driven design, and separation of concerns you can develop robust and easily observed systems for offline processes.

```
   ______
  /      \
 |  () () |    Observability
 |  \__/  |
 \______/
```

## Taking Laravel To The Orchestra; Building A Symfony Inspired Application.**

Introduction:
This article explores how to bring more structured “clean” coding practices to your Laravel application by incorporating principles from the Symfony framework.

From Magic to Structure:

*   Typical Laravel applications often use static calls and magic methods that can be harder to test and maintain.
*   Symfony is more structured and forces a higher level of understanding. Although you lose some of Laravel's magic, the code is often cleaner.

Example: Refactoring a simple controller

*   Initial Controller - Magic Static call
```php
Route::get('/articles', IndexController::class)
    ->name('articles:index');

final class IndexController
{
    public function __invoke(Request $request)
    {
        return Article::all();
    }
}
```

*  Add the JSON Response
```php
Route::get('/articles', IndexController::class)
    ->name('articles:index');

final class IndexController
{
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse(
          data: Article::all(),
          status: Response::HTTP_OK,
        );
    }
}
```
* Using Query Method
```php
Route::get('/articles', IndexController::class)
    ->name('articles:index');

final class IndexController
{
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse(
           data: Article::query()->get(),
           status: Response::HTTP_OK,
        );
    }
}
```

Design Patterns:

*   Implement the **Repository Pattern** to make your code more testable and to utilize the Eloquent ORM.
Example:
```php
interface ArticleRepository
{
    public function all(): Collection;
}

final class EloquentArticleRepository implements ArticleRepository
{
    public function all(): Collection
    {
        return Article::query()->get();
    }
}

final readonly class IndexController
{
    public function __construct(
        private ArticleRepository $articleRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse(
           data: $this->articleRepository->all(),
           status: Response::HTTP_OK,
        );
    }
}
```
*  Add **Service Classes** to separate user-land code from business logic

Example:
```php
Route::get('/articles', IndexController::class)
    ->name('articles:index');

interface ArticleRepository
{
    public function all(): Collection;
}

final class EloquentArticleRepository implements ArticleRepository
{
    public function all(): Collection
    {
        return Article::query()->get();
    }
}

interface LaravelService {}

final class ArticleService implements LaravelService
{
    public function __construct(
        private readonly ArticleRepository $repository,
    ) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }
}

final readonly class IndexController
{
    public function __construct(
        private ArticleService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
       return new JsonResponse(
            data: $this->service->all(),
            status: Response::HTTP_OK,
       );
    }
}
```
*  Refactor HTTP usage in a library to allow mocking and testing.

Testing:
*  Use HTTP fakes to test HTTP calls
```php
    /** @test */
    public function it_can_send_a_http_request(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 'ok'], 200),
        ]);

        $this->postJson('some-internal-url', [
            'foo' => 'bar',
        ])->assertStatus(201);

        Http::assertCount(1);
    }
```

Conclusion:
By using design patterns like the Repository and Service patterns, you can build more structured, testable, and maintainable Laravel applications.

```
      ___
     /   \
    |  _  |   Laravel
    | / \ |   Orchestra
    |_____|
```

## Is Your Plan Extensive Enough To Help Someone Else?**


Introduction:
This article examines how a thorough plan, and understanding customer requirements, contributes to the success of a software development project.

Learning from Mistakes:
*   In the past, coding started too early, without a plan, resulting in a negative customer experience.
*   A thorough planning process results in a successful project.

Good Customer Requirements:
*   Customer requirements should be converted into a high-level phased plan to help manage their expectations.

Customer Plan:

*   Phase 1: Initial development including basic authentication and responsive design
*   Phase 2: Advanced features and customization
*  Phase 3: Reporting and analysis

Developer Plan:

*  Start with a detailed database schema.

Example Schema:
```
users:
 id: UUID (primary key)
 username: VARCHAR (unique)
 password: VARCHAR
 email: VARCHAR
 phone: VARCHAR
 two_factor_enabled: BOOLEAN
 role: VARCHAR

loans:
 id: UUID (primary key)
 user_id: FOREIGN_UUID (foreign key)
 loan_amount: DECIMAL
 interest_rate: DECIMAL
 term: INTEGER
 start_date: DATE
```
*   Establish API routes for the system.
*  Detail third party APIs used

Handoffs:
*    Provide a status of current tasks and deliverables to help the team know how to continue with the project

*   If leaving for a short period include information on:
    * The tasks and deliverables and their progress
    * The tasks and deliverables for the next few days
    *  Dependencies and risks
   *  Contact information
   *  Any documentation
*   If leaving for longer include the above plus:
    * A temporary replacement
    * A review of project plans

*   If leaving permanently include:
   * All the above
   * A permanent replacement

Conclusion:
A thorough plan, that can be used by team members, is an essential part of any software development project to ensure success.

```
     ____
    /    \
   |  /\  |  Extensive
   | /  \ |    Plan
   |______|
```

## The Heart of the Code**

Introduction:

This article explores the human side of software development, reminding us that all code is ultimately about facilitating sharing.

Beyond Technicalities:
*   Software development is more than just code; it's about enabling people to share data, ideas, and time.

Focus on Sharing:

*   The user interface is a gateway for people to share with each other and with you.
*   Security ensures that we control what we share and with whom.
*   The API provides a means for different entities and services to share data.
*   All of these should be viewed through the lens of facilitating sharing

Conclusion:
Remember that the core of any project is the sharing of data. Focus on the human side of development, and share your knowledge with others.

```
   ____
  /    \
 |  <3 |   Heart of
  \____/   Code

```

**Postface**

This month's issue covered a wide variety of topics, designed to help you become a more well-rounded PHP developer. From the practical application of GraphQL to the softer skills of planning and teamwork, we hope you have found value in these articles. Keep exploring, experimenting, and most importantly, keep sharing!

**Index of Technology Words:**

*   AJAX
*   API
*   Authentication
*   Blade
*   Composer
*   CSS
*   Debugging
*   Eloquent
*   Error Handling
*   Framework
*   Functional Testing
*   Git
*   GraphQL
*   HTML
*   HTMX
*   IDE
*   JSON
*   JWT
*   Laravel
*   Legacy Code
*   OAuth
*   ORM
*   Pagination
*   Performance
*   PHP
*   PHPUnit
*   Query
*   Refactoring
*   Repository Pattern
*   Resolver
*   REST
*   Schema
*   Security
*   Service Classes
*   Sorting
*   Symfony
*   Testing
*   Unit Testing
*   Version Control System
*   Xdebug
