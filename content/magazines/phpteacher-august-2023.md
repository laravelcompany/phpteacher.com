---
title: PHP Teacher magazine - August 2023
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - august
  - 2023
---


Welcome, dear readers, to another edition of *The PHP Teacher Magazine*! This month, we delve into a wide range of topics, from crafting fantastic SDKs to understanding JavaScript memory management and the intricacies of legacy code. We’ll also explore practical tools like Rector for code refactoring, the fundamentals of sorting algorithms, and how to create robust business processes. Finally, we'll offer a look into the PHP RFC process and discuss anti-patterns in Laravel, all with the aim of making you a more well-rounded and effective PHP developer. We hope you find this month’s issue insightful and beneficial in your coding journey.

##  Fantastic SDKs and How To Build Them

(ASCII art: a toolbox with a hammer, wrench and a gear)
```
      ____
     /    \
    |  _  |
    | | | |
    |____|
   /______\
  |________|
  /________\
  |________|
 /________\
```

Have you ever found yourself frustrated with auto-generated SDKs that just don’t feel right? Steve McDougall explores the common pitfalls of auto-generated SDKs and demonstrates how to build a better SDK, focusing on developer experience.

Many companies automatically generate their SDKs, leading to poor developer experiences and quick abandonment of the service. This is a common problem, as seen with SDKs like those from AWS and Google.

Let's consider a common scenario: instantiating a Google client.
```php
// The original code
$client = new Google\Client();
$client->setApplicationName("Client_Library_Examples");
$client->setDeveloperKey("YOUR_APP_KEY");
```
This can be simplified using named parameters, a more modern language feature.
```php
// Our new implementation
$client = new Google\Client(
    name: 'Client_Library_Examples',
    key: 'YOUR_APP_KEY',
);
```
The same improvements can be made to service creation and method calls to make the SDK more fluid and easier to use.

**Building an SDK:**

When building an SDK, the main entry point should be the SDK client. This avoids the need to instantiate multiple classes. Consider a simple note-taking API with folders and notes endpoints:

*   `GET /folders`
*   `POST /folders`
*   `DELETE /folders`
*   `GET /notes`
*   `POST /notes`
*   `GET /notes/{note}`
*   `PUT /notes/{note}`
*   `DELETE /notes/{note}`
*  `PUT /notes/{notes}/tags`

The SDK client can be built to work with the native Laravel implementation using Guzzle. The client can be registered as a singleton in a service provider, so it's injected into the application. The SDK should provide methods for accessing the different services, such as folders and notes. This allows easy injection of service classes using a container.
```php
final class Client
{
    public function __construct(
        private readonly PendingRequest $request,
    ) {}

    public function folders(): FolderService
    {
        return new FolderService(
            client: $this,
        );
    }

    public function notes(): NoteService
    {
        return new NoteService(
            client: $this,
        );
    }
}
```
Service classes like `FolderService` and `NoteService` are used to call endpoints on the API. The constructor should take a `Client` instance, allowing the service to make requests.
```php
final readonly class FolderService
{
    public function __construct(
        private Client $client,
    ) {}
}
```
Methods on service classes should handle potential exceptions and return objects representing the API responses.
```php
final readonly class FolderService
{
    public function __construct(
        private Client $client,
    ) {}

    public function all(): Collection
    {
        $response = $this->client->get('/folders');

        if ($response->failed()) {
            throw new FailedToFetchFolders(
                response: $response,
            );
        }

        return $response->collect('data')->map(
           fn (array $folder) =>
            Folder::fromRequest($folder)
        );
    }
}
```
When creating new resources, validate data before sending API requests.
```php
public function create(NewFolder $payload): Folder
{
    if ($payload->validate()) {
        // throw a validation exception here
    }
    $response = $this->client->post(
        '/folders',
        $payload->toArray()
    );
}
```
**Testing:**
Use the HTTP Facade's fake method to globally mock HTTP requests.

**Sub-resources:**
Create additional service classes for sub-resources, such as tags, within the primary service class.
```php
final readonly class NoteService
{
    public function __construct(
        private Client $client,
    ) {}

    public function tags(string $note): TagService
    {
        return new TagService(
            client: $this->client,
            note: $note,
        );
    }
}
```
Building SDKs requires a focus on the developer experience, making them easy to use and integrate with existing systems.

*   **Key points:**
    *   Avoid auto-generated SDKs.
    *   Use named parameters and modern language features.
    *   Use service classes to organize API calls.
    *   Validate data before sending requests.
    *   Use value objects for responses.
    *   Use a proxy or dependency injection for service calls.
    *   Write tests using the HTTP Facade.


## How I Learned to Stop Worrying: A Pragmatic Approach to JavaScript Memory Management

(ASCII art: a brain with a light bulb above it)
```
     _.--""--._
    .'          `.
   /   O      O   \
  |    \  ^^  /    |
  \     `----'     /
   `. _______ .'
     //_____\\
    (( ____ ))
     `------'
```

Rahul Kumar shares his journey to understanding JavaScript memory management, highlighting key concepts and practical techniques to avoid memory leaks.

Memory management in JavaScript can seem overwhelming, but understanding the core concepts makes it more manageable.

**Core Concepts:**

*   **The Stack**: Stores primitive types like strings, numbers, and booleans. It is fast to access and allocate.
*   **The Heap:** Stores objects.
*   **Garbage Collection:** Checks for objects on the heap without references and frees up memory.

**Common Memory Leaks and How to Avoid Them:**

*   **Forgotten timers or event listeners:** Always remove timers and event listeners when they are no longer needed.
```javascript
let el = document.querySelector('.some-element');
el.addEventListener('click', handleClick);

// When the event listener is no longer needed
el.removeEventListener('click', handleClick);
```
*   **Closures:** Make sure closures do not maintain references to unused objects.
*   **Global variables:** Avoid assigning objects to global variables.
*   **DOM references:** Remove unused DOM references.

**Managing Memory with Primitives vs. Objects:**

Primitives are stored on the stack, while objects are stored on the heap. When objects are no longer needed, dereference them to allow garbage collection to reclaim memory.
```javascript
function createObject() {
  let obj = { name: "Rahul" };
  obj = null; // Dereferencing the object
}
```
**The Tricky Parts:**

*   **Callbacks:** Avoid passing unnecessary references to callbacks or release them when done.
*   **Reference Cycles:** Break circular references by having objects release their references.
```javascript
var obj1 = {
    obj2: {
        obj1: obj1,
        releaseObj1: function() {
            obj1 = null;
        }
    }
};
```
**Tools for Diagnosing Memory Issues:**

*   **Chrome DevTools Memory Panel**: Tracks memory usage, heap snapshots, and identifies memory leaks.
*   **Node.js Heapdump:** Generates heap snapshots for Node.js backends.
*   **Visual Studio Code Memory Viewer:** Analyzes heap snapshots on the fly.

**Optimizing Code for Memory Efficiency:**

*   **Reuse variables and objects:** Instead of creating new objects each time, reuse them.
```javascript
// Less Efficient: Creating new objects within each iteration
for (let i = 0; i < 10; i++) {
    let obj = {
        prop: i
    };
}

// More Efficient: Reusing a single object across iterations
let obj = {};
for (let i = 0; i < 10; i++) {
    obj.prop = i;
}
```
*   **Clean up event listeners and timers**.
*   **Minimize DOM queries**: Cache DOM elements in variables.

**Best Practices for Responsible Memory Usage:**

*   Avoid memory leaks by cleaning up event listeners, timers, and nulling out variables.
*   Break circular references.
*   Minimize DOM manipulation.
*   Be mindful of variable scope using `let` and `const`.
*   Check third-party libraries' documentation for memory management considerations.

By understanding these core concepts and applying these practices, you can write cleaner and more efficient JavaScript code.

*   **Key points:**
    *   Understand the stack, heap, and garbage collection.
    *   Avoid common memory leaks.
    *   Use tools to diagnose memory issues.
    *   Optimize your code for memory efficiency.
    *   Follow best practices for responsible memory usage.

## Software Archaeology - Part 1


(ASCII art: a shovel, pick and a brush)
```
     ____
    /   /
   /  _/
  /  /
 /__/___
|       |
 \_____/
   | |
   |_|
  /   \
```

Chris Tankersley introduces the concept of Software Archaeology, exploring the challenges of working with legacy code and strategies for understanding and improving it.

Software Archaeology goes beyond reading code; it involves understanding the decisions and quirks of previous developers. Legacy code is a living history of PHP, with many applications built on various versions and paradigms.

**Challenges of Legacy Code:**

*   **Loss of Expertise**: Developers who initially crafted the code may have moved on, leaving behind undocumented wisdom.
*   **Outdated Practices**: Legacy code might be riddled with insecure or inefficient practices compared to modern PHP standards.
*   **Spaghetti Code**: Logic, presentation, and data handling are tangled together.
```php
function fetchDataAndRender($id) {
    global $db;
    $query = "SELECT * FROM users WHERE id = $id";
    $result = $db->query($query);
    echo "";
    while($row = $result->fetch_assoc()) {
        echo "
" . $row['name'] . "";
        logActivity($id); // Side effect
    }
    echo "";
}
```
*   **Deeply Nested Conditionals**: Complex logic with nested if-else blocks.
```php
if ($user) {
    if ($user->isActive()) {
        if ($order->hasItems()) {
            // ...
        } else {
            // ...
        }
    } else {
        // ...
    }
} else {
    // ...
}
```
*   **God Objects and Methods**: Objects that know or do too much, leading to increased side effects.
*   **Cyclic Dependencies**: Objects that rely on each other, making code hard to understand.
```php
class Order {
    private $customer;

    public function __construct(Customer $customer) {
        $this->customer = $customer;
        $this->customer->setOrder($this);
    }
}

class Customer {
    private $order;

    public function setOrder(Order $order) {
        $this->order = $order;
    }
}
```
*   **Magic Numbers and Strings**: Hardcoded values that can be difficult to change.

**Starting an Investigation:**

*   **Dig Through What is Left Behind**: Check documentation, source control logs, and code comments.
*   **Engage with Previous Developers or Users**: First-hand insights can be invaluable.
*   **Understand the Project Structure**: Look for entry points and core files, such as index.php, and match the URL structure with the file structure.
*   **Analyze Configuration Settings**: Identify configuration files or global arrays.
*   **Make Notes**: Document your findings and share with the team.

Understanding legacy code is an opportunity to enhance analytical thinking and appreciate the history of software development.

*   **Key points:**
    *   Legacy code can be complex and challenging.
    *   Loss of expertise is a major issue with legacy code.
    *   Look for spaghetti code, nested conditionals and God Objects.
    *   Start by documenting existing knowledge.
    *   Understand the structure of the application.
    *   Document everything.

**References:**

## Vulnerability Management 101

(ASCII art: a shield with a checkmark)

```
     /\_/\
    ( o.o )
    > ^ <  \
   /     \  |
   |  O  | /
   \_____/
```

Eric Mann explains the importance of vulnerability management, emphasizing that every piece of code is susceptible to vulnerabilities.

Every published code will eventually suffer a vulnerability. Recognizing this truth is the first step in vulnerability management.

**Risk Awareness:**
It's essential to understand the risks inherent in your application and its dependencies. This includes the risks to your product, your customers' accounts, and any sensitive data.

**Risk Management:**
The only truly secure product is one that never launches. Every product carries risk, so balance these risks against the rewards.

**Vulnerability Management Strategies:**

*   **Static Code Analysis**: Use tools like Psalm to perform static analysis.
*   **Dynamic Application Security Testing (DAST)**: Scan applications while they are running to identify potential problems with APIs and behavior.
*   **Automated Dependency Updates**: Use tools like GitHub’s Dependabot to automatically update dependencies.

**Key Points:**

*   Vulnerabilities are inevitable.
*   Prioritise risk awareness and risk management.
*   Use static analysis, DAST, and automated dependency updates to manage vulnerabilities.
*   Establish a solid vulnerability management program.

*   **Key points:**
    *   Vulnerabilities are inevitable.
    *   Be aware of the risks in your application.
    *   Use tools for static analysis, DAST, and dependency updates.
    *   Establish a vulnerability management program.

**References:**

## Small Changes

(ASCII art: A person using a screen reader)
```
    _
   / )
  / /_
 | _  \_
 |/ \ | |
 |  |/  |
  \  / /
  \ /_/
   `-'
```

Maxwell Ivey discusses the impact of small changes on disabled users, urging developers to be mindful of how updates affect accessibility.

Small changes can drastically affect the user experience of people with disabilities, particularly those using adaptive technology like screen readers and magnifiers.

**Examples of Frustration:**

*   **URL Changes**: Changing hyphens to underscores in URLs can force users to start over when navigating a website.
*   **Process Changes**: Adding extra steps to common tasks, like sharing posts on LinkedIn, can be frustrating.

**Recommendations for Developers:**

*   **Evaluate Need**: Ask if the proposed changes are absolutely necessary for the function, safety, or enjoyment of the project.
*   **Ask for Input**: Reach out to people using adaptive technology to ask how changes will affect their experience.
*   **Communicate Changes**: Announce changes to users, explaining what is changing and why, and how the site will behave going forward.

*   **Key points:**
    *   Small changes can create major issues for people using adaptive technologies.
    *   Evaluate if changes are necessary.
    *   Get feedback from disabled users.
    *   Always communicate changes effectively.

**References:**

## Rector Refactoring


(ASCII art: A wrench turning a gear)
```
    _____
   /     \
  |  _|_  |
  | |   | |
  |-------|
   \_____/
     | |
     | |
   /     \
  |_______|
```

Joe Ferguson explores Rector, a tool for automatic code refactoring in PHP, and its uses for improving code quality, removing dead code, and enforcing coding styles.

Rector is a tool for automatic code refactoring in PHP. It can be used to improve code quality, remove dead code, and enforce coding styles.

**Installation and Configuration:**
Install Rector with `composer require rector/rector --dev` and initialise with `php vendor/bin/rector init`. This creates a `rector.php` file where you can configure rules.
```php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->rule(
        TypedPropertyFromStrictConstructorRector::class
    );

    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
    ]);
};
```
**Basic Refactoring:**
Rector can be run on a specific directory with `vendor/bin/rector process src --dry-run`, where the `--dry-run` flag outputs changes without modifying the files.
Rector can remove redundant docblocks, remove dead code, and add type hints.
```php
// Example before Rector
/**
* Sum 2 numbers
* @param float $x
* @param float $y
* @return float
*/
public function add($x, $y)
{
  return $x + $y;
  return "Example Dead Code";
}
// Example after Rector
public function add(float $x, float $y): float
{
  return $x + $y;
}
```
**Applying More Complex Rules:**
You can add `SetList::CODING_STYLE` to enforce coding style rules.
You can skip rules that don't fit your preferences.
```php
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
$rectorConfig->skip([
  UnSpreadOperatorRector::class,
]);
```
Rector may make unexpected changes. You must consider if the changes are good for your codebase.
You can test your code after each change to make sure that you have not broken any code.
**Rector with Real-World Applications:**
Rector can be used to upgrade larger projects like Snipe-IT, starting by adding `SetList::CODE_QUALITY, SetList::DEAD_CODE, SetList::CODING_STYLE`.
Start by processing a small section of code, such as the test suite, to understand how Rector is going to make changes.
You can see how `StaticArrowFunctionRector`, `RemoveUnusedVariableAssignRector`, and `NullToStrictStringFuncCallArgRector` can improve code. You can also see how `StaticClosureRector`, and `CatchExceptionNameMatchingTypeRector` are also applied.
You can use `LevelSetList::UP_TO_PHP_82` to identify issues that prevent a code base from running on PHP 8.2.
Rector can infer problems from code but does not understand intentions.

*   **Key points:**
    *   Rector automates code refactoring.
    *   Use sets of rules to improve code quality and coding styles.
    *   Configure and skip rules based on project needs.
    *   Run Rector incrementally on sections of your codebase.
    *   Rector can be used to upgrade a code base to a newer version of php.


## Bubble Sorting

(ASCII art: two bubbles going up)
```
   ,  ,
  (  )  )
   `--'
    ,  ,
   (  (  )
    `--'
```

Oscar Merida discusses the bubble sort algorithm, its performance characteristics, and provides a PHP implementation.

Sorting algorithms are important in computing, and bubble sort is a simple one.

**The Algorithm:**
Bubble sort works by comparing adjacent elements and swapping them if they are in the wrong order.
The algorithm makes multiple passes through the array, and the largest element “bubbles” to the end with each pass.
The average performance of the bubble sort is N², which is not practical for real world sorting.

**Swapping Elements:**
A function is required to swap two elements in an array. You should pass the array by reference to avoid unnecessary copies.
```php
function swap_elements(array &$list, int $i, int $j): void {
    if (array_key_exists($i, $list) && array_key_exists($j, $list)) {
      $tmp = $list[$i];
      $list[$i] = $list[$j];
      $list[$j] = $tmp;
      return;
    }
    $message = "Invalid index specified";
    throw new \InvalidArgumentException($message);
}
```

**Scanning and Swapping:**
The bubble sort implementation involves looping through the array, swapping elements until no more swaps are needed.
```php
function bubble_sort(array &$list): void
{
    $max = count($list);
    do {
        $swapped = false;
        for ($i = 0; $i < $max -1; $i++) {
            if ($list[$i] > $list[$i + 1]) {
                $swapped = true;
                swap_elements($list, $i, $i + 1);
            }
        }
    } while ($swapped);
}
```

**Testing Performance:**
A function to make test lists quickly uses PHP's array functions to make a list of random numbers.
```php
function make_test_list(int $size, int $min=0, int $max=20000): array {
    $list = array_fill(0, $size, 0);
    array_walk($list,
        function(&$item) use ($min, $max): void {
            $item = random_int($min, $max);
        }
    );
    return $list;
}
```
Performance testing shows that as the size of the list increases, the performance of bubble sort drops significantly.

*   **Key points:**
    *   Bubble sort compares and swaps adjacent elements.
    *   The algorithm has a performance of N².
    *   Pass arrays by reference to avoid making unnecessary copies.
    *   Performance degrades significantly as list size increases.


## Create Observability, Part 3: Rewrite Business Process

(ASCII art: a flowchart diagram)
```
   +-------+     +---------+
   | Start | --->| Process |
   +-------+     +---------+
      |              |
      v              v
   +-------+     +---------+
   |  Yes  |<--- | Decision|
   +-------+     +---------+
         \            |
          \           v
           \       +---------+
            +--->|  End  |
                  +---------+
```

Edward Barnard discusses the importance of flowcharts to document business processes and the need to rewrite a business process when it is not handling errors adequately.

Flowcharts are used to capture and communicate “tribal knowledge” about a business process.

**Current Business Process:**
The existing “Add Team” process was convoluted, making it difficult to understand and modify. The existing process had the sequence of steps out of order and verified form data after inserting records into the database.

**Desired Business Process:**
The new business process is:

1.  Validate form data (Head Coach phone and email).
2.  If the prospective Head Coach is not in the database, insert a new user record (find or create user).
3.  Create team records (team record, initial permissions, connect Head Coach to team).
4.  Send out Welcome email to Head Coach.

Each step runs within a try/catch block and database transaction for consistency.
The new process does not alter existing user records if the prospective Head Coach is already in the system.

**Tribal Knowledge:**
When the order of processing is changed, “tribal knowledge” is altered. The flowchart documents the business process, including:

*   The order of steps.
*   The two use cases.
*   The database records that are created.
*   The failure paths.

The implementation does not have to look like a flow chart but the comments should represent it.
```php
/** Submitted email address in users table? */
if ($existingUser) {
  /** User have active coach role on active team? */
  $schools = $controller->getExistingCoachRoles($UserEmail);
  $hasOtherCoachRoles = !empty($schools);
  /** Fall through to render with "are you sure?" */
} else {
  /** Submitted email address NOT in users table */
  /** Sendgrid validate email? */
  if ($controller->validateEmail($UserEmail)) {
    /** Twilio validate phone? */
    if ($controller->validatePhone($Phone)) {
      /** Create new user */
      $addUserResponse = $controller
        ->createNewUser($addTeamRequest);
      if ($addUserResponse->isSuccess()) {
        $UserID = $addUserResponse->getUserId();
      } else {
        $message = $addUserResponse->getErrorMessage();
      }
    } else {
      /** Twilio did NOT validate phone */
      $message = 'Invalid phone number';
    }
  } else {
    /** Sendgrid did NOT validate email */
    $message = 'Invalid email address';
  }
}
```
The flowchart serves as a description of the business process, not the implementation details.

*   **Key points:**
    *   Flowcharts are a good way to document business processes.
    *   Existing business processes need to be rewritten when not handling errors properly.
    *   Implement changes within try/catch blocks and database transactions.
    *   Use comments from your flowcharts to document the process.

