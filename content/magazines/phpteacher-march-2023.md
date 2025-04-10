---
title: PHP Teacher magazine - March 2023
publishDate: 2022-03-01 00:00:00
description: Welcome to this issue of monthly PHP Teacher magazine, a magazine dedicated to helping PHP developers hone their skills and build more effective and resilient applications. This month, we delve into several key areas essential for any modern developer.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - march
  - 2023
---

Welcome to another exciting edition of *The PHP Teacher Magazine*! This month, we've curated a diverse collection of articles designed to enhance your PHP development skills and broaden your understanding of key industry concepts. From practical coding techniques to insightful security practices, we aim to equip you with the knowledge you need to excel in the ever-evolving world of web development.

In this issue, we delve into the power of **PHP FFI (Foreign Function Interface)**, exploring how it enables you to interact with code written in other languages, and we examine how to leverage it with DuckDB, a fast and efficient database system. We also tackle the often-challenging subject of **debugging**, presenting an intuitive approach to help you systematically identify and resolve coding problems. Our security column this month introduces the concept of **phishing** and how to avoid its traps, arming you with the means to protect your systems and data.

Furthermore, we explore the principles of **12-Factor Applications**, a methodology for building robust, scalable, and maintainable software. For those interested in the new features of **Laravel 10**, we've got you covered with a detailed look at the latest enhancements and how they can impact your projects. We also take a look at **PSR-17 (HTTP Factories)**, essential interfaces for standardising the creation of HTTP objects in PHP. Finally, we explore the mathematical concept of **standard deviation** and how to apply it to calculate grades. We also have an article that delves into the often-overlooked topic of **temporary solutions**, providing insights into when to embrace them and when to strive for a more comprehensive approach.

Our goal is to provide you with a wealth of practical and thought-provoking content. Whether you're a seasoned developer or just starting your journey, we hope that you find this issue both informative and inspiring. Thank you for being a valued reader, and we hope you enjoy this month's edition!


## A Guide to Practical Usage of PHP FFI

By Bohuslav Šimek

```
     __
    /  \
   |    |   PHP FFI
   \__/
```

The Foreign Function Interface (FFI) in PHP is a powerful tool that allows developers to interact with code written in other languages. This opens up a world of possibilities, from reusing existing libraries to optimising performance-critical sections of code. In this article, we’ll explore the practical applications of FFI, particularly in the context of the **DuckDB** database.

**What is FFI?**

At its core, FFI is a mechanism by which a program written in one language can call routines or use services written in another. This means that instead of rewriting code in PHP, you can leverage existing libraries written in C, C++, or other languages, which can save time and resources, or even allow you to tap into hardware or memory that would otherwise be inaccessible.

**Why Use FFI?**

While PHP extensions can accomplish similar tasks, FFI has several advantages:

*   **Easier Usage:** FFI can be used directly in PHP code without needing to set up a compilation toolkit.
*   **Maintenance:** FFI is easier to maintain because everything is done in plain PHP.
*   **Deployment:** FFI simplifies deployment by avoiding the need for changes to your deployment processes.
*   **Portability:** FFI offers better portability across different PHP versions.

**Getting Started with FFI**

To illustrate how FFI works, let’s begin with a simple example. We will rewrite the `abs()` function from the C standard library, which returns the absolute value of a given number.
```php
$ffi = FFI::cdef(
    'int abs(int j);',
    'libc.so.6'
);

var_dump($ffi->abs(-42)); // int(42)
```
This code creates a proxy object that allows us to call the C `abs()` function directly from PHP. The `cdef()` function takes two arguments: a C function declaration and the name of the library.

If you need to use a lot of functions, you can also use the `FFI::load()` function to load definitions from a header file. A header file contains function declarations, structures, and macro definitions.

**Practical Example: DuckDB**

**DuckDB** is an in-process, column-based, SQL OLAP database written in C++ that is distributed as a single file. It is known for its performance in analytical queries, and while it doesn't have direct support for PHP, we can use FFI to integrate with it.

**Steps for Integrating DuckDB with FFI**

1.  **Download DuckDB:** Download the prebuilt library for your operating system from the DuckDB website.
2.  **Prepare the Header File:** The header file from DuckDB cannot be used directly because it contains macros that PHP FFI does not support, and include directives must be removed. You must use a C++ compiler like GCC to resolve these macros. Here are the commands to prepare the header file:
```bash
echo '#define FFI_LIB "./libduckdb.so"' >> duckdb-ffi.h
cpp -P -C -D"attribute(ARGS)=" duckdb.h >> duckdb-ffi.h
```
    The first command adds a directive to tell PHP the location of the library and the second command resolves macros.
3.  **Load the Header File:** Load the prepared header file using `FFI::load()` and create structures for the database and connection:
```php
$dbFFI = FFI::load('duckdb-ffi.h');
$database = $dbFFI->new("duckdb_database");
$connection = $dbFFI->new("duckdb_connection");
```
4.  **Open and Connect to DuckDB:** Use the `duckdb_open` and `duckdb_connect` functions to open and connect to the database:
```php
$result = $dbFFI->duckdb_open(
    null,
    FFI::addr($database)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot open database');
}

$result = $dbFFI->duckdb_connect(
    $database,
    FFI::addr($connection)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot connect to database');
}
```
    Note that we must use `FFI::addr()` to pass pointers to C functions and ensure we handle errors.
5.  **Execute Queries:** Execute SQL queries using the `duckdb_query` method. Below we create a table and insert data:
```php
$result = $dbFFI->duckdb_query(
    $connection,
    'CREATE TABLE integers(i INTEGER, j INTEGER);',
    null
);
$result = $dbFFI->duckdb_query(
    $connection,
    'INSERT INTO integers VALUES (3,4), (5,6), (7, NULL)',
    null
);
```
6.  **Retrieve Results:**  To retrieve data, we must provide a pointer to the `duckdb_result` structure as the third parameter of the `duckdb_query()` method. After the query executes, this structure will contain the results. We also retrieve the row and column counts and the values using various methods. We have to convert them to php strings using `FFI::string()` and free the memory allocated by the function:
```php
$queryResult = $dbFFI->new('duckdb_result');
$result = $dbFFI->duckdb_query(
    $connection,
    'SELECT * FROM integers; ',
    FFI::addr($queryResult)
);

$resultAddr = FFI::addr($queryResult);
$rowCount = $dbFFI->duckdb_row_count($resultAddr);
$colCount = $dbFFI->duckdb_column_count($resultAddr);

for ($row = 0; $row < $rowCount; $row++) {
    for ($column = 0; $column < $colCount; $column++) {
        $value = $dbFFI->duckdb_value_varchar(
            $resultAddr,
            $column,
            $row
        );
        echo ($value !== null ?
            FFI::string($value) :
            '')." ";
        $dbFFI->duckdb_free($value);
    }
    echo "\n";
}
```
7.  **Handle Memory Management:** When working with FFI, it's crucial to manage memory manually. PHP has a garbage collector but this does not apply when calling the functions of a library from another language. Data created with the FFI `new()` method is managed by PHP but when dealing with pointers obtained through `FFI::addr()` we need to make sure we free the allocated memory using the appropriate functions provided by the external library. For example in our DuckDB code, we must free the memory used by the results:
```php
$dbFFI->duckdb_destroy_result(FFI::addr($queryResult));
$dbFFI->duckdb_disconnect(FFI::addr($connection));
$dbFFI->duckdb_close(FFI::addr($database));
```

PHP FFI is a potent tool for integrating with code written in other languages. By using FFI, we can make use of other powerful libraries like DuckDB while still writing code in PHP. However, it is important to be aware of the differences in how memory is handled in C and PHP to avoid errors and memory leaks.

##  Problem in a Box

By Edward Barnard

```
      _______
     /       \
    |  BUG  |
    \_______/
```
Debugging is a critical skill for any software developer, yet it is often approached without a clear methodology. This article presents an intuitive approach to problem analysis and debugging, emphasizing the importance of creating theories, and then setting out to prove or disprove them.

**The Intuitive Approach**

The method described in this article relies on intuition and a systematic approach to problem-solving. The technique involves the following steps:

1.  **Diagram the Problem**: When faced with a bug, start by diagramming or describing the issue on a whiteboard. This makes the problem clearer and provides a point of reference.
2.  **Generate Ideas**: List out all possible causes of the problem, no matter how far-fetched they may seem. The more ideas the better for analysis.
3.  **Prove or Disprove Each Idea**: Systematically go through each idea and attempt to prove or disprove its validity. This process eliminates possibilities and focuses on the most likely cause.
4.  **Match to Code**: Trace the possible paths in the code to find the path which may lead to the reported problem. Try to prove that each path was traversed.
5.  **Adjust Code**:  If a path is likely, adjust code to capture information to help you find the necessary information next time.

**Example: The Case of the Corrupted Memory**

The author shares an experience from his time at Cray Research where he and his colleague, Alex, were debugging a system crash. They followed the process above.

*   **The Problem**: The operating system crashed and a system dump printout was given to them with the state of the system at the time of the crash.
*   **The Theory**: The author suspected the crash was related to the “work to be done” flag in the operating system. It appeared that the system was executing code that it should not have.
*   **Proving/Disproving**: He checked the memory location containing the “work to be done” flags and found something odd: part of the memory word had ASCII text. After checking his location he confirmed that he was looking at the right location.
*   **Further Investigation**: He knew that the first few bytes of the Cray operating system tables contain the table name (as an abbreviation) as ASCII text. This ASCII text looked like a partial table name but it was corrupt.
*   **Refined Theory**: The author theorised that the ASCII text was placed intact but interpreted as a set of “work to be done” flags and that the task main loop was flipping bits. The main loop only clears the flags, which led him to believe that the flips would only be from one to zero.
*   **More Proof**: He compared the ASCII text that should have been there to what he saw in the dump. He found that only bits were subtracted, confirming his new theory.
*   **Root Cause**: The author found a task that was creating the in-memory table but was writing it to the wrong place in memory, overwriting the “work to be done” location. The root cause was a line of code that cleared the offset to zero, causing the wrong memory location to be used.
*   **The Solution**: The author had found that the operating system was blindly following instructions and that its input data was invalid and it could not safely proceed. The problem now "fit into the box" with no loose ends.

**The "Box" Analogy**

The author uses the concept of “fitting the problem into a box”. This means that if the solution explains everything observed, and there are no loose ends, then the correct solution has been found.

**Intuition and “Feel”**

Your intuition plays a key role in debugging. When a solution "feels right" or "doesn't feel quite right," this is your intuition guiding you. Trust those feelings, but always confirm them with proof.

**Lessons Learned**

*   **Systematic Approach**: Intuition, when coupled with a systematic approach of proving or disproving theories, is a useful debugging strategy.
*   **Collaboration**: Working with a colleague and arguing passionately is very useful when debugging.
*   **Complete Control**: Having control of the codebase and the ability to add diagnostic information is a luxury that can greatly aid debugging.
*   **Document Everything**: Keep track of all the theories and steps taken.
*   **Trust Your Intuition**: Let your feelings guide you but back them up with evidence.

By using the systematic approach described here, you’ll be able to enhance your debugging skills and more efficiently resolve complex issues.

##  InfoSec 102: Phishing

By Eric Mann

```
     _.--""--._
    .'          `.    PHISHING
   /   O      O   \
  |    \  ^^  /    |
  \     `----'     /
   `.__________.'
```

Phishing is a prevalent threat that can affect any organisation. Understanding what phishing is and how it works is important for protecting your team from being exploited. This article defines phishing and related concepts, providing advice on how to recognise and avoid these attacks.

**What is Phishing?**

Phishing is a type of cyber attack where criminals try to trick individuals into giving away sensitive information. It involves using deceptive emails, websites, or other methods that appear legitimate to lure victims into clicking links, downloading files, or filling out forms. The goal is to get access to their systems or information.

**Social Engineering**

Phishing attacks leverage social engineering, manipulating people into taking actions that benefit the attackers. These attacks are often designed to create a sense of urgency or fear to pressure individuals into acting quickly without thinking clearly.

**Types of Phishing**

*   **Email Phishing:** This is the most common form of phishing where attackers send emails that look like they came from legitimate organisations such as banks, social media sites or ecommerce platforms. The emails often contain links that direct the user to fake websites designed to steal their credentials or to install malware.
*   **Spear Phishing:** A more targeted attack where the attacker researches the victim or their company and sends specific and tailored emails to get the victims to act on their behalf.
*   **Smishing:** Phishing attacks that use SMS messages. Attackers use these messages to get you to visit a fake website or call a phone number.
*   **Vishing:** Phishing attacks using voice messages or phone calls to trick the victim into providing sensitive information or purchasing items on behalf of the attacker.

**Recognising Phishing Attacks**

Phishing attacks often have several red flags that you should be aware of:

*   **Urgent Requests:** Phishing messages often create a sense of urgency, asking you to do something quickly. Attackers want you to respond before you think about it.
*   **Generic Greetings:** Legitimate emails often include your name. Phishing emails may use generic terms like “Dear Customer” or “Dear User.”
*   **Suspicious Links**: Hover over links in emails before you click on them to see the real destination, often a fake or malicious website. If the link looks suspicious, do not click.
*   **Unusual Attachments**: Be wary of email attachments, especially from unknown senders. Attachments may contain malicious software.
*   **Grammatical Errors**: Phishing emails often have spelling and grammar mistakes. Legitimate emails will often be proofread.
*   **Requests for Personal Information**: Be cautious of emails asking for sensitive information such as passwords, social security numbers, or bank account details.

**Avoiding Phishing Attacks**

*   **Be Alert and Vigilant:** Stay alert and cautious when you receive emails or messages, especially if they appear unusual or urgent.
*   **Verify the Sender**: Always check the sender’s email address. If it seems suspicious, it probably is.
*   **Go Directly to the Source**: If you receive a message from a company, open the website directly by typing the URL into your browser rather than clicking on a link in the message.
*   **Use Strong Passwords**: Choose strong, unique passwords for all your accounts and do not reuse passwords.
*   **Enable Multi-Factor Authentication**: Turn on multi-factor authentication when it is available. This will add an extra layer of security to protect your account.
*   **Keep Software Updated**: Make sure that your antivirus software and operating systems are up to date with the latest security updates.
*   **Train Your Team**: Employees should be educated on the importance of security awareness and how to spot phishing emails or messages.
*   **Do Not Give out Information**: Never give out information via email, sms, or phone call unless you have verified the authenticity of the request.


Phishing attacks can have significant consequences for your organisation, so it is important to be aware of the different techniques and methods that the attackers use. By staying vigilant, training your team and using the techniques described above, you can protect your organisation from these harmful attacks.

## 12 Factor Applications: Parts 1-6

By Chris Tankersley

```
      / \
     / | \   12 Factor App
    /  |  \
   /   |   \
  /____|____\
```

The 12 Factor Application methodology provides a valuable set of principles for building robust, scalable, and maintainable software. In this article, we explore the first six of these factors, demonstrating their relevance to modern PHP development and highlighting the benefits of following these patterns.

**What are 12-Factor Applications?**

The 12-Factor Application methodology was presented by Adam Wiggins in 2011 at Heroku, a Platform-as-a-Service (PaaS) company. The idea behind it was to make it easier for developers to create applications that are deployable, scalable, and maintainable, particularly for software as a service applications. While the methodology was initially designed for PaaS environments, it's principles remain relevant in microservice architectures and containerised environments.

**Factor 1: Codebase**

The first factor specifies that your application should be stored as a single codebase in a single repository. This repository should be the source of truth for all versions and deployments of the application.

*   **Benefits**:
    *   Facilitates the other factors of the 12 Factor methodology.
    *   Provides a single source of truth for application status.
    *   Simplifies the deployment process across different environments.
*   **Microservice Architecture**: In a microservice architecture, treat each service as an individual system with its own repository.
*   **Shared Code**: Extract shared code into new packages and treat them as independent dependencies. This prevents code coupling between services.
*   **Monorepos**: Monorepos are a structure where all codebases are kept in a single repository. They can create maintenance issues if there is tight coupling between the various sub projects and can violate the "one codebase to one repository" rule, so should be used with caution.
*   **Alternatives**: If using a monorepo is still the best choice, you can use git submodules to create a single repo that pulls in information from other repositories. Another option is a repo that stores only package dependencies to be pulled in by sub projects.

**Factor 2: Dependencies**

This factor emphasises the need to explicitly declare and isolate dependencies. The application must specify what the dependencies are and the versions that the application needs.

*   **Benefits**:
    *   Allows developers to download the required versions of dependencies.
    *   Ensures build process can be reproducible.
    *   Enables package managers to keep dependencies up to date.
    *   Keeps third party code separate from the application codebase.
*   **PHP**: Modern PHP applications use Composer to manage dependencies, placing them in a `vendor/` folder. This helps satisfy the isolation aspect of this methodology.

**Factor 3: Config**

The third factor dictates that configuration should be stored in the environment, and not in the application code. Things that are mutable between installs such as database credentials or access keys should be provided by the environment.

*   **Benefits**:
    *   Separates configuration from the application, promoting flexibility and adaptability across environments.
    *   Allows you to easily change between different database servers by changing environment variables.
*   **PHP**: You can access environment variables using PHP’s `getenv()` function. Use environment variables for sensitive data.
*   **Database**: Connect to databases using Data Source Names (DSNs) to isolate the database from implementation details. The application should not care about the number of instances of the database or the underlying system.
*   **Fallback**: Applications should use fallback connections in case the primary systems are unavailable.

**Factor 4: Backing Services**

This factor will be discussed in next months article.

**Factor 5: Build, Release, Run**

The fifth factor emphasises the need to strictly separate the build, release, and run stages of application deployment.

*   **Build**: The build process checks out the source code from version control, downloads dependencies, and compiles any needed assets into a build artifact.
*   **Release**: The release stage takes the build artifact and combines it with the configuration for that specific deployment and is assigned a release ID.
*   **Run**: The run stage executes the released application in the target environment.
*   **Triage Bugs**: Since releases can be traced back to builds, this allows developers to debug the exact code that is running in production.

**Factor 6: Processes**

The sixth factor states that applications should execute as one or more stateless processes.

*   **Microservices**: If you are building a microservice architecture, you are already following this factor. Treat each service as its own process that can be scaled independently.
*   **Stateless**: Applications should be stateless to allow for horizontal scaling. If an application needs to store data for the user session, it must use a distributed storage mechanism.
*   **PHP**: Most PHP applications run inside a web server (like Apache httpd) or a FastCGI process. Some PHP applications use long-running daemons that bypass the normal web server or FastCGI setup and need additional considerations for internal architecture and horizontal scaling.


The 12 Factor Application methodology provides guidelines for building robust and scalable software. By implementing these practices, your PHP applications will become more maintainable and easier to deploy. Next month, we will continue by looking at the final six factors of this methodology.

## Laravel 10: New Features & Upgrade Impacts

By Matt Lantz

```
   ____  ____  ____
  |    ||    ||    |
  |    ||    ||    |  Laravel 10
  |____||____||____|
```

Laravel 10 introduces many improvements and features that continue to enhance the developer experience, as is expected with a new release of this popular PHP framework. This article will highlight the impact of upgrading to Laravel 10 and its most significant features.

**Upgrade Considerations**

Upgrading a Laravel application is usually a smooth process. However, there are some notable requirements for Laravel 10:

*   **PHP Version**: Laravel 10 requires PHP ^8.1, allowing access to new features like Enums and Readonly properties, and also providing performance improvements.
*   **Composer Version**: Laravel 10 requires Composer ^2.2 which may require you to update your local system or server.
*   **Dependency Updates**: Ensure that you upgrade the required dependencies, including `laravel/sanctum, spatie/laravel-ignition, nunomaduro/collision` and `phpunit/phpunit`.

**Notable Changes**

*   **AuthServiceProvider**: The `registerPolicies()` call should be removed from the `AuthServiceProvider`.
*   **$dates**: The use of `$dates` has been deprecated in favour of `$casts` in Laravel 9.
*   **DBAL**: Laravel now offers native support for column modifying, dropping the need to use `doctrine/dbal`.
    *   If you have `doctrine/dbal` installed, you can add `Schema::useNativeSchemaOperationsIfPossible()` to the `boot` method of your `AppServiceProvider`.
*   **Laravel Shift**: For larger applications, consider using Laravel Shift which automates the upgrade process and provides a clear git comparison of the changes.
*   **Lifecycle**: Laravel 10 will receive bug support until Aug 6, 2024, and security fixes until Feb 14, 2025.

**New Features**

*   **Native Types**: Laravel 10 uses native types and removes docblocks when possible in favor of native types. This is a trend in modern PHP applications.
    *   For example:
```php
/**
* Show the form for editing the Note.
*/
public function edit(Note $note): View
{
    return view('notes.edit')->withNote($note);
}
```
*   **Password Generator**: The new `Str::password()` method within Laravel’s Str helper class can generate secure random passwords, using `random_int()` internally by default, which include numbers, symbols, and letters.
*   **Process Service**: Laravel 10 has a new process service that allows you to start external processes. You can also use methods such as `successful()`, `failed()`, `exitCode()`, `output()` and `errorOutput()` to interact with them:
```php
$result = Process::run('ls -la');
$result->successful();
$result->failed();
$result->exitCode();
$result->output();
$result->errorOutput();
```
*   **Test Profiling**: The `phpunit` command now has the `--profile` flag to show the time each test takes to help identify slow tests.

**Laravel Pennant**

Laravel 10 includes a new package called Pennant which is a feature flag management system.

*   **Feature Flags**: Feature flags help to control the release of new features and support A/B testing.
*   **Usage**: Define features in a service provider, for example:
```php
use Laravel\Pennant\Feature;
use Illuminate\Support\Lottery;

Feature::define('new-onboarding-flow', function () {
    return Lottery::odds(1, 10);
});
```
*   **Implementation**: Check if features are enabled using `Feature::active()` or blade directives such as `@feature()` and `@endfeature`.
*   **Custom Logic**: Use the class based method to define features and use custom logic to determine if the feature is enabled, for instance, for specific users:
```php
public function resolve(User $user): mixed
{
    return match (true) {
        $user->isInternalTeamMember() => true,
        $user->isHighTrafficCustomer() => false,
        default => Lottery::odds(1 / 100),
    };
}
```

Upgrading to Laravel 10 is generally a smooth process that brings many new features and improvements. The new Process service, Schema support and Pennant package will be very useful to developers in day-to-day work.

## PSR-17: HTTP Factories

By Frank Wallen

```
     _______
    /       \
   |  HTTP   |  PSR-17
    \_______/
```

PSR-17 is a set of interfaces that provides a standard way of creating HTTP objects in PHP. This article will detail the core interfaces of PSR-17, their importance, and show how to implement them in various scenarios.

**Purpose of PSR-17**

PSR-17 was created to support PSR-7 (Message Interfaces) by defining interfaces for factories that can create HTTP objects. The factory design pattern enables standardisation of components that do not implement PSR-7 interfaces directly. PSR-7 defines how requests and responses should be handled, and PSR-17 defines how these objects should be created in a standard manner.

**Key Interfaces**

PSR-17 defines several interfaces for creating various HTTP objects:

***Request Factory (`RequestFactoryInterface`)**: This interface is responsible for creating `RequestInterface` objects:

```php
namespace Psr\Http\Message;

interface RequestFactoryInterface
{
    public function createRequest(
        string $method,
        $uri
    ): RequestInterface;
}
```

The `$uri` parameter accepts either a string or a `UriInterface` object. The implementation of creating a `UriInterface` object is secondary.
***Response Factory (`ResponseFactoryInterface`)**: 
This interface is responsible for creating `ResponseInterface` objects:

```php
namespace Psr\Http\Message;

interface ResponseFactoryInterface
{
    public function createResponse(
        int $code = 200,
        string $reasonPhrase = ''
    ): ResponseInterface;
}
```
The `$reasonPhrase` is optional to support `ResponseInterface::withStatus()` where the status code and reason phrase are related.
***Stream Factory (`StreamFactoryInterface`)**: 
This interface creates `StreamInterface` objects which are used for representing the bodies of HTTP messages:
```php
namespace Psr\Http\Message;

interface StreamFactoryInterface
{
    public function createStream(
        string $content = ''
    ): StreamInterface;

    public function createStreamFromFile(
        string $filename,
        string $mode = 'r'
    ): StreamInterface;

    public function createStreamFromResource(
        $resource
    ): StreamInterface;
}
```
The stream factory supports creation from strings, files, and resources. Streams created from a string should be temporary (e.g. using `fopen('php://temp', 'r+')`).
***Uploaded File Factory (`UploadedFileFactoryInterface`)**: 
This interface creates `UploadedFileInterface` objects, which represents a single file that has been uploaded via an HTTP request:
```php
namespace Psr\Http\Message;

interface UploadedFileFactoryInterface
{
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface;
}
```
The uploaded file stream should come from a `Stream factory` to ensure consistency with PSR-7 recommendations.
***Uri Factory (`UriFactoryInterface`)**: 
This interface is used for creating `UriInterface` objects:
```php
namespace Psr\Http\Message;

interface UriFactoryInterface
{
    public function createUri(
        string $uri = ''
    ): UriInterface;
}
```
The URI factory must be able to create a URI for both client and server requests.
***Server Request Factory (`ServerRequestFactoryInterface`)**: 
This interface creates `ServerRequestInterface` objects, which represent an incoming HTTP request from a server:

```php
namespace Psr\Http\Message;

interface ServerRequestFactoryInterface
{
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface;
}
```
The `$serverParams` are optional, as the `ServerRequestInterface` does not have a method to update the server parameters. Therefore, these must be provided when the server request is created.
There is no specific method for creating a server request from superglobals. This functionality is implementation-specific as it is not always guaranteed that superglobals are available.


The `ServerRequestInterface` is the interface that represents an incoming HTTP request from a server.
Server Request Factory
The `ServerRequestFactoryInterface` is the interface that creates `ServerRequestInterface` objects, which represent an incoming HTTP request from a server.

```php
namespace Psr\Http\Message;
interface ServerRequestFactoryInterface
{
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface;
}
```
Server Request Factory Implementation
The `ServerRequestFactory` class implements the `ServerRequestFactoryInterface` and is used to create `ServerRequestInterface` objects.

```php
namespace Psr\Http\Message;
class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function createServerRequest(
        string $method,
        $uri,
        array $serverParams = []
    ): ServerRequestInterface;
    public function createServerRequestFromGlobals(): ServerRequestInterface;
}
```