---
title: PHP Teacher magazine - November 2022
publishDate: 2022-11-19 00:00:00
description: "Hello and welcome to the November issue of *The PHP Teacher Magazine*! As the leaves turn and the nights draw in, we've curated a selection of articles designed to both challenge and inspire you in your PHP journey"
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - september 
  - 2022
---

## Welcome to the November Issue of *The PHP Teacher Magazine*!

Hello and welcome to the November issue of *The PHP Teacher Magazine*! As the leaves turn and the nights draw in, we've curated a selection of articles designed to both challenge and inspire you in your PHP journey. This month, we delve into the powerful world of Abstract Syntax Trees (ASTs), explore the nuances of Value Objects, and examine the importance of local development environments. We also look into some critical security concerns, the inner workings of dependency injection, and even venture into the creation of our own web server. Finally, we also have some fun with a look at the world cup draws and our responsibility when designing forms. 

This issue is packed with insights and practical advice, all crafted to enhance your understanding of PHP and its ecosystem. Whether you’re a seasoned developer or just starting out, we’re confident you'll find valuable takeaways in these pages.

Inside this issue:

*   **"The Value of the AST"** by Tomas Votruba - Discover how ASTs can transform your code refactoring and enable powerful code manipulation.
*   **"Bring Value To Your Code"** by Dmitri Goosens - Explore the significance and practical applications of Value Objects in Domain-Driven Design.
*  **"Local Dev with Lando"** by Joe Ferguson - Learn how Lando can simplify your local development workflow, whatever your OS preference.
*   **"Direct Object References"** by Eric Mann - Understand the security risks of direct object references in APIs and how to mitigate them.
*   **"PSR-11: Container Interface"** by Frank Wallen - Grasp the purpose and implementation of the Container Interface (PSR-11) and how it helps with dependency injection.
*   **"Making Our Own Web Server: Part 2"** by Chris Tankersley - Dive deeper into the creation of a custom PHP web server, including handling concurrent connections.
*  **"World Cup Draws"** by Oscar Merida - Use PHP to simulate the world cup draws, complete with all of the constraints and complexities that come with that challenge.
*  **"Security in Drupal 9"** by Nicola Pignatelli - Get the latest tips on securing your Drupal website by leveraging some of the most popular and helpful modules.
*   **"Transactional Boundary"** by Edward Barnard - Understand the importance of transactional boundaries in Domain-Driven Design.
* **"Our Responsibility in Learned Helplessness"** by Beth Tucker Long - Explore the effects of learned helplessness when we create forms and user interfaces and how to address it.

We are incredibly grateful for your continued readership, and we hope that you enjoy this issue of *The PHP Teacher Magazine*.

**Step 2: Individual Articles**

Okay, let's dive into the individual articles.

## The Value of the AST**


In the ever-evolving world of PHP development, the Abstract Syntax Tree (AST) is an increasingly vital tool, allowing for sophisticated code manipulation and analysis. This article will explore the core concepts of ASTs, along with practical applications using the `nikic/php-parser` library, along with some tips to use it effectively with the rector tool.

```
       /\_/\
      ( o.o )
      > ^ <  AST Power!
```

**What is an AST?**

An AST is not a tangible tool, but a conceptual way of viewing code. It's a hierarchical representation of the structure of your code, where each node in the tree corresponds to a specific construct in PHP. Rather than dealing with plain text or tokens, which can be cumbersome, ASTs provide a structured way to understand and modify code.

**Why use an AST?**

The power of the AST comes from its structured nature. Unlike tokens, where spaces and parentheses matter, ASTs focus on the semantic content of the code, making it easier to perform pattern-based refactoring. Imagine you wanted to change every instance of `echo 1;` to `echo '1';`. With an AST, you can define this pattern as a transformation rather than having to manipulate raw text, with this change you could also change all instances where you have integer outputs.  This allows you to automate code changes across your whole project, guaranteeing that changes only happen where you expect them.

**The `nikic/php-parser`**

The `nikic/php-parser` is a PHP package that parses PHP code into an AST. It's a cornerstone for many code analysis and refactoring tools in the PHP ecosystem. In order to use it you need to install it through composer.

```bash
composer require nikic/php-parser:dev-master --dev
```

This library takes your PHP code and transforms it into a structured tree that you can easily traverse and modify.

**Code Example: Changing echo**

Let's look at an example of how to use `nikic/php-parser` to refactor code, using the example of changing `echo 1;` to `echo '1';`:

First, we create two files `refactor-php-pattern.php` and `SomeCode.php`. Inside the `SomeCode.php` file we have `<?php echo 1;`.

```php
<?php // refactor-php-pattern.php
require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard;


$factory = new ParserFactory();
$parser = $factory->createForNewestSupportedVersion();

$file = __DIR__ . '/SomeCode.php';
$fileContents = file_get_contents($file);
$astNodes = $parser->parse($fileContents);

// Create a Node Visitor
final class ChangeEchoNumberToStringNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?Echo_
    {
        if (! $node instanceof Echo_) {
            return null;
        }

        if (! $node->expr instanceof LNumber) {
            return null;
        }

        $node->expr = new String_((string) $node->expr->value);

        return $node;
    }
}
// Run Node Visitor

$nodeTraverser = new NodeTraverser();
$nodeTraverser->addVisitor(
    new ChangeEchoNumberToStringNodeVisitor()
);
$traversed = $nodeTraverser->traverse($astNodes);

// Print Changes

$standardPrinter = new Standard();
$modified = $standardPrinter->prettyPrintFile($astNodes);
file_put_contents(__DIR__ . '/SomeCode.php', $modified);
```

**Explanation:**

1.  We set up the parser using `ParserFactory`.
2.  We read the PHP file we want to modify.
3.  We parse the file content into AST nodes using `$parser->parse()`.
4.  We define a node visitor (`ChangeEchoNumberToStringNodeVisitor`) which extends `NodeVisitorAbstract` and implements the `enterNode()` method which runs through all the nodes in the AST.
5.  Inside `enterNode()`, we check if the current node is an `Echo_` node, then we check if the expression within is a `LNumber` if these tests are correct we create a new `String_` node with the number converted to a string to replace the previous node.
6.  We set up a `$nodeTraverser` and add our node visitor.
7.  We call the `traverse()` function to perform the changes.
8. Finally we output the modified code into our file.
9. The result will be the `SomeCode.php` file will contain `<?php echo '1';`.

**The Role of Rector**

While the `nikic/php-parser` package is incredibly powerful, it can be cumbersome to handle the complexities of maintaining code formatting while making code changes. This is where Rector comes in. Rector is a tool that uses `nikic/php-parser` under the hood and abstracts away many of these details allowing you to write clear, concise refactoring patterns.  Rector can handle changes from simple formatting issues to complete code structure changes.  You can install it using the following composer command:

```bash
composer require rector/rector --dev
```

Rector allows you to define configuration files to perform complex refactoring with ease.

**Downsides of Pure `php-parser`**

The `php-parser` has some limitations. For instance, it doesn't know about variable types; it only sees the raw code values. If your code includes variables like `$value = 1; echo $value;`, the `php-parser` won't change the variable into a string, instead it will skip it. Also, the parser does not maintain formatting. You can maintain your formatting with a format-preserving pretty printer, but this is very complex.

**Conclusion**

ASTs are more than just abstract concepts; they are the key to a new era of code manipulation and analysis in PHP. Tools like `nikic/php-parser` and Rector allow you to write and maintain code more effectively. The ability to perform code-based refactoring is a skill that will help to move any project into the future and allows you to move a codebase forward in time with minimal manual work.

**References:**

*   [Real World AST: https://phpa.me/real-world-ast](https://phpa.me/real-world-ast)
*   [PhpToken::tokenize(): https://phpa.me/php-tokenize](https://phpa.me/php-tokenize)
*   [3v4l.org example: https://3v4l.org/JJp4v](https://3v4l.org/JJp4v)
*    [list of tokens: https://www.php.net/manual/en/tokens.php](https://www.php.net/manual/en/tokens.php)
*   [OpenSource Pledge: https://phpa.me/opensource-pledge](https://phpa.me/opensource-pledge)
*   [php-src core: https://github.com/php/php-src](https://github.com/php/php-src)
*    [PhpParser 5-alpha1: https://phpa.me/php-parser](https://phpa.me/php-parser)
*   [PHPStan: https://github com/phpstan/phpstan](https://github.com/phpstan/phpstan)
*   [Rector Article: https://phpa.me/migrate-legacy-one-month](https://phpa.me/migrate-legacy-one-month)

**Technology Word Index:**

*   Abstract Syntax Tree (AST)
*   `nikic/php-parser`
*   Token
*   Node
*   Refactoring
*   Rector
*   Node Visitor
*   Composer

## Bring Value To Your Code**

**Introduction:**

This article dives into the world of Value Objects, a crucial building block in Domain-Driven Design (DDD), that many developers find confusing and useless until they see the power of these objects to make their codebase better. We will explore what a Value Object is, why they are important and practical applications in PHP.

```
     _______
    |  VAL  |
    | U E   |
    | O B   | Value Object!
    | J C   |
    | E T   |
    -------
```

**What is a Value Object?**

A Value Object is a design pattern where an object's value is defined by its attributes, not by an identity. Unlike entities, which have a unique identifier, Value Objects are considered equal if their attributes are the same. If two objects both have red green blue and alpha values, that are the same they should be considered the same, they represent the same concept and are therefore interchangeable. A good real-world example is the PHP `DateTimeImmutable` object, which represents a specific moment in time but doesn't have an ID or identity. Another example would be an enum, where there are a limited set of values.

**Key Characteristics of Value Objects**

1.  **Immutability:**  Value Objects should be immutable; their values can't change after creation. If you need to change the state, you should create a new Value Object with the new attributes. PHP8.1 introduces the `readonly` keyword, making this pattern easier to implement.
2.  **No Identity:** Value Objects don't have a unique identity. Two Value Objects with the same attributes are considered identical and interchangeable.
3.  **Completeness:** Value Objects represent a whole concept and must be complete in the sense that they are valid and complete. All attributes should be necessary to represent the concept properly.
4.  **Replaceable:** If a Value Object needs to change in its value, you need to replace it with a completely new Value Object, therefore making it immutable.

**Code Examples:**

Let's look at how to implement a Value Object for a `Color`:

```php
<?php
class Color
{
    public function __construct(
        public readonly int $red,
        public readonly int $green,
        public readonly int $blue,
        public readonly float $alpha,
    ) {}

    public static function ofHexColor(string $hexColor, float $opacity): self
    {
        $hexColor = ltrim($hexColor, '#');
        $parts = match(strlen($hexColor)) {
            3 => [
                str_repeat(substr($hexColor, 0, 1), 2),
                str_repeat(substr($hexColor, 1, 1), 2),
                str_repeat(substr($hexColor, 2, 1), 2),
            ],
            6 => [
                substr($hexColor, 0, 2),
                substr($hexColor, 2, 2),
                substr($hexColor, 4, 2),
            ],
            default => throw new InvalidColor(
                sprintf('%s is not a valid CSS color', $hexColor)
            )
        };

        return new self(
            hexdec($parts),
            hexdec($parts),
            hexdec($parts),
            $opacity
        );
    }
}
```

**Explanation:**

*   This code defines a `Color` class with `readonly` properties for `red`, `green`, `blue`, and `alpha`. The `readonly` means once the object is instantiated those properties cannot be changed.
*   The constructor sets these properties.
*   We include a factory method `ofHexColor()` that ensures that the input passed into the color is a valid color and also performs some conversions to allow for hex colours.
*   If we want to change the colour we have to create a new value object with the modified values.
*   We can ensure that our object is always complete and valid using these factory methods.

**Why are Value Objects important?**

1.  **Encapsulation:** Value Objects encapsulate domain logic. Instead of using primitive types directly you encapsulate that logic into a proper value object.
2.  **Immutability and Thread Safety:** Immutability makes Value Objects inherently thread-safe, so they are very useful when running multi-threaded applications.
3. **Domain Language:** Value objects allow you to use the language of the domain to express your concepts in the code.
4.  **Testability:** Since Value Objects are immutable and have well-defined logic, they are easier to test.

**Value Objects with other Value Objects:**

Value objects can be composed of other value objects.

```php
enum Sharpness {
    case VerySharp;
    case Sharp;
    case Regular;
    case Blunt;
}
enum Hardness {
    case OhSoHard;
    case RegularHard;
    case Medium;
    case Soft;
}

class VirtualPencil
{
    public function __construct(
        public Color $color,
        public Sharpness $sharpness,
        public Hardness $hardness,
    ) {}
}

$virtualPencil = new VirtualPencil(
  new Color(0, 0, 0, 1),
  Sharpness::VerySharp,
  Hardness::OhSoHard,
);
```

**Explanation:**

*   The `VirtualPencil` class contains 3 different value objects.
*  `Color`, `Sharpness` and `Hardness` are all value objects.

**Rule 3: Value Objects are whole objects and should be complete.**

Value objects should always be valid and represent a complete concept. Therefore, value objects should always be constructed with the correct value, rather than setting them separately. This ensures that your value objects always represent the correct concepts and are therefore not corrupt in any way.

**Conclusion:**

Value Objects might look simple on the surface, but they're a very powerful tool when implementing Domain-Driven Design in your PHP application. By using them, you can create more maintainable, testable, and robust code. Their immutability, combined with their conceptual clarity, makes them an essential part of any developer’s toolbox. So don't hesitate to introduce value objects in your code.

**References:**

*   Domain-Driven Design.—also called the Blue
*   See Listing 1
*  See Listing 2
*  See Listing 6
*  See Listing 8
* See Listing 9
* See Listing 10

**Technology Word Index:**

*   Value Object
*   Immutability
*   Domain-Driven Design (DDD)
*  Readonly properties
* Factory Methods
* Enums

## Local Dev with Lando**

**Introduction:**

Setting up a local development environment can often be a time-consuming and frustrating process. Lando is a free, open-source tool designed to streamline this experience, supporting various operating systems and PHP frameworks. This article will cover the basics of using Lando to create efficient local development environments for your PHP projects.

```
     _
    | |
   _| |_   Lando!
  |  _  |
  |_| |_|
```

**What is Lando?**

Lando is a local development tool that uses containers to provide a consistent and reproducible development environment for your projects. It allows developers to set up the various services they need like web servers, databases, mail servers and much more. Lando’s flexibility is one of its greatest strengths as it provides this consistent environment regardless of the underlying Operating System, which is crucial for collaborative projects.

**Why use Lando?**

1.  **Consistency:** Lando provides a consistent development environment across different operating systems. Whether you’re on Linux, macOS, or Windows, Lando ensures that everyone on the team is working with the same setup.
2.  **Simplicity:** Lando simplifies the creation and management of local environments. You can set up complex environments with just a few commands.
3.  **Flexibility:** Lando supports multiple PHP frameworks, databases, and services, making it adaptable to various project needs.
4.  **Isolation:** Each Lando environment runs in its own container, isolating it from other projects and preventing conflicts.

**Installation**

Lando is available for download on its official GitHub releases:

[https://github.com/lando/lando/releases](https://github.com/lando/lando/releases)

You'll need to choose the right package for your operating system.  Lando has great support for macOS, Windows, and Linux.

**Basic Lando Workflow**

1.  **Project Setup:**
    *   Navigate to the root directory of your PHP project.
    *   Run `lando init`.
    *   Answer the questions from Lando about your project. Lando will detect the type of application and what it will require to run.
    *   Lando will generate a `.lando.yml` file in your project.
2.  **.lando.yml Configuration:**
    *   You can customize your environment by editing the `.lando.yml` file. Here, you can set up different services and configurations that are unique to your application.
    *   Here you can set things like custom database credentials, the version of PHP you want to run, and much more.
3.  **Starting Lando:**
    *   Run `lando start`. This command builds the containers defined in your `.lando.yml` file and starts your development environment.
4.  **Accessing Your App:**
    *   Lando will provide you with the URL to access your application.
5.  **Using Lando:**
    *   Run commands inside your containers using `lando <command>`.
        For example, `lando artisan migrate` will run your Laravel migrations.
    *   `lando db:seed` will run your database seeders.
6.  **Stopping Lando:**
    *   Run `lando stop`.
7.  **Uninstalling Lando**
   *  Lando can be uninstalled by following the instructions in the documentation.

**Code Examples**

Here’s a basic `.lando.yml` file for a Laravel application with MySQL, mailhog and node services:

```yaml
name: rfid
recipe: laravel
config:
    webroot: public
    database: mysql
services:
  mailhog:
    type: mailhog:v1.0.0
    portforward: true
    hogfrom:
     - appserver
  node:
    type: node:16
    command: npm run dev
```

**Explanation:**

*   `name`: Sets the name of your Lando application.
*   `recipe`: Uses the Laravel recipe. Lando will set up all the correct services for Laravel.
*   `config`: Customizes your webroot and sets the default database.
*   `services`: Adds the `mailhog` service which is very useful for debugging emails. `node` service which sets up node for frontend tooling.

You can further customize your `.env` file in your Laravel app to use the hostnames provided by the database service:

```ini
APP_URL=https://rfid.lndo.site
APP_SERVICE=rfid.lndo.site

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=laravel
```

**Database Connections:**

Lando makes connecting to your databases easy. It exposes each database service, which can be connected to through IDE's like PHPStorm. It also exposes the credentials that you'll need to connect.

**Beyond the basics**

Lando can run more than one application. Lando can also run any php application from Wordpress, Drupal, Symfony, etc. Lando also has many defaults allowing you to configure the minimum needed to run a PHP application. Lando does not create supplemental files like some other dev tools which means that all of your environment settings are kept in just one file, keeping the configuration very clean.

**Using Lando with Multiple Applications**

Lando allows you to manage multiple projects concurrently, using only the required system resources, which is extremely useful for developers. You can start and stop projects and configure them in ways that are completely separate from each other.

**Conclusion:**

Lando is a very valuable tool for any PHP developer, as it provides a simple but very powerful way to create and manage your development environments. Lando allows you to focus on building your applications rather than wrestling with your environment. Whether you're using Laravel, Symfony, WordPress, or any other PHP framework, Lando is an essential tool in your arsenal.

**References:**

*   [Lando: https://lando.dev](https://lando.dev)
*   [GitHub releases: https://github.com/lando/lando/releases](https://github.com/lando/lando/releases)
*   [Uninstall Lando: https://phpa.me/lando-dev](https://phpa.me/lando-dev)
*  [MSSQL plugin: https://docs.lando.dev/mssql/](https://docs.lando.dev/mssql/)
*   [Varnish: https://docs.lando.dev/varnish/](https://docs.lando.dev/varnish/)

**Technology Word Index:**

*   Lando
*   Containers
*   `.lando.yml`
*   Docker
*  Local Development
*  Laravel
*  Mailhog

## Direct Object References**

**Introduction:**

Security is a critical aspect of software development, often overlooked until it’s too late. One common vulnerability is the use of direct object references in APIs, which can expose sensitive data and compromise your application. This article covers what direct object references are, how they pose a threat, and how to secure your APIs against this vulnerability.

```
    .------------------------.
    |  DANGER!               |
    |   Direct Object       |
    |  References Here       |
    '------------------------'
```

**What are Direct Object References?**

A direct object reference is when you expose an internal object identifier directly as part of an API. For example, `/account/{id}`. An attacker could use these to access or modify resources they shouldn't be able to. A well-designed system should only allow a user to interact with the data that belongs to them and should not expose that data to other users. This makes it very easy for malicious users to target the identifiers used by your system.

**Why are Direct Object References a Risk?**

1.  **Information Exposure:** When you expose internal identifiers, it makes it easy for an attacker to understand the structure of your data and how your system operates.
2.  **Easy Exploitation:** An attacker can easily enumerate through object identifiers to find and access data they are not authorised to see or manipulate.
3.  **Security Breaches:** If an attacker finds a way to use direct object references to access or modify data that they should not be able to access it can lead to security breaches.

**Vulnerable Code Example:**

Consider the following insecure API endpoints in Laravel:

```php
<?php

use App\Http\Controllers\AccountController;

Route::controller(AccountController::class)->group(function () {
    Route::post('/account', 'create');
    Route::get('/account/{id}', 'read');
    Route::patch('/account/{id}', 'update');
    Route::delete('/account/{id}', 'delete');
});
```

And the controller:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request)
    {
        $account = new Account;
        // Populate the model
        $account->save();

        return $account;
    }

    public function read(int $id)
    {
        return Account::findOrFail($id);
    }

    public function update(Request $request, int $id)
    {
        $account = Account::findOrFail($id);
        // Populate changes onto the model
        $account->save();
        return $account;
    }

    public function delete(int $id)
    {
        return (Account::findOrFail($id))->delete();
    }
}
```

**Explanation:**

*   This API directly uses the `id` parameter in the URL to fetch the `Account` model.
*   This can expose the data of other accounts if an attacker were to guess the id's of the accounts.
*   This code also does not perform authentication so any user can access any resource.

**Securing Your APIs:**

1.  **Authentication:** Ensure that every request is properly authenticated. Check that your users have logged in. Laravel makes this very easy with the inbuilt auth middleware.

    ```php
    Route::get( '/account/{id}', 'read' )->middleware('auth');
    ```
2.  **Authorization:** Use authorization to check if a user is allowed to perform an action. You should ensure that a user can only perform actions on the resources that belong to them, for example you should only be able to access your own account and not anyone else's.

    ```php
    Gate::define('update-account',
         function (User $user, int $accountId) {
             return $user->id === $accountId;
         }
     );
    ```
    You can then enforce this in your controller using the built in `authorize` method:

        ```php
     public function update(Request $request, int $id)
         {
             $account = Account::findOrFail($id);
             $this->authorize('update-account', [$account->user, $id]);
             // Populate changes onto the model
             $account->save();
             return $account;
         }
        ```
3. **Avoid exposing internal object identifiers** Do not directly expose internal object identifiers, use a UUID instead, or some other form of encoding to obfuscate the identifiers.
4.  **Session Data:** Use session data to associate resource access with the logged-in user.

    ```php
    public function read(Request $request, int $id)
    {
        $userId = $request->session()->get('user_id');
        if ($userId !== $id) {
            abort(403);
        }
        return Account::findOrFail($id);
    }
    ```
    This code checks if the `id` in the URL is the same as the `user_id` in the session. If it isn't it returns a 403 error.
5.  **Regular Code Reviews:** Make security a regular part of your development cycle. Consider the potential threats that your application may face.
6.  **Threat Modeling:** Regularly model the potential threats to your application, especially in new or complex code.

**Conclusion**

Direct Object References are a common security vulnerability that is easy to make in your applications. By following the above techniques, you can better secure your APIs and help to protect your application from malicious users. Security is not a one-time action; it is a continuous process that requires constant monitoring and updating, this includes looking back at old code to ensure no bugs have crept in.

**References:**

*   [Gates: https://laravel.com/docs/9.x/authorization](https://laravel.com/docs/9.x/authorization)
*   [consider and model potential threats: https://phpa.me/articlesecuritycorner](https://phpa.me/articlesecuritycorner)
*  [Security Corner: Broken Authentication, August 2022.](https://phpa.me/security-aug-2022)
*  [Security Corner: Surviving Cybersecurity, September 2022.](https://phpa.me/security-sept-2022)
* [Security Corner: Cybersecurity Checkup, October 2022.](https://phpa.me/security-sept-2022)

**Technology Word Index:**

*   Direct Object References
*   API Security
*   Authentication
*   Authorization
*   Session
*   Threat Modeling
*   Laravel
* Middleware
* Gate

## PSR-11: Container Interface**

**Introduction:**

Dependency Injection is a cornerstone of modern software design, and the Container Interface (PSR-11) provides a standard way to implement it. This article will explain what PSR-11 is, its core principles, and how it facilitates dependency injection in PHP applications.

```
      ______
     |      |
     |  DI  |  PSR-11
     |______|
```

**What is PSR-11?**

PSR-11 defines a common interface for dependency injection containers in PHP. A dependency injection container (or a service locator) is an object that creates and provides all the objects that are needed by your application, these objects are referred to as 'services'. PSR-11 focuses only on providing the interface with two methods: `get()` and `has()` which are the only methods that should be implemented.  This standardized approach allows developers to switch between different container implementations without changing the rest of their code.

**Core Concepts of PSR-11**

1.  **Entry Identifiers:** PSR-11 requires that entries in the container be accessed with unique string identifiers. These identifiers should be strings but it's common practice to use the fully qualified name of the class you intend to retrieve from the container. The identifiers have to be a PHP-legal string with at least one character.
2.  **`get()` Method:** The `get()` method takes an entry identifier and returns an instance of the service. This method must throw a `Psr\Container\NotFoundExceptionInterface` if the service can't be found and a `Psr\Container\ContainerExceptionInterface` if there is any other issue in retrieving the service.
3.  **`has()` Method:** The `has()` method takes an entry identifier and returns a boolean that indicates if a service with the provided identifier exists in the container.
4.  **No Specific Implementation:** The specification does not dictate how the container should store or create objects, just the interface for retrieving them. There are many approaches that a container can take including array access, using a factory, or using autowiring.

**Why is PSR-11 Important?**

1.  **Interoperability:** PSR-11 promotes interoperability by providing a common interface for containers. This means that libraries that require a container can accept any PSR-11 compatible container.
2.  **Loose Coupling:** Using a container encourages loose coupling in your application. This makes the code more maintainable and testable by decoupling the various components of the application.
3.  **Dependency Injection:** PSR-11 is crucial to the process of dependency injection. Instead of creating objects directly, dependencies are injected by a container, making the code more flexible and maintainable.

**Code Example: Service Locator**

Here's an example of a class that uses the service locator pattern with PSR-11:

```php
<?php

use Psr\Container\ContainerInterface;

class ServiceLocatorExample
{
    protected $service;
    public function __construct(ContainerInterface $cont)
    {
        $this->service = $cont->get('service');
    }
}

```