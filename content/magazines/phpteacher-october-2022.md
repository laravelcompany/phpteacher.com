---
title: PHP Teacher magazine - October 2022
publishDate: 2023-04-19 00:00 
description:  'This issue includes articles about finite-state machines,  Vim plugins, single-page applications, cybersecurity, PHP releases, creating a webserver, application events, number formatting, and Laracon'
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - october 
  - 2022
---

### Introduction

Welcome to another exciting issue of *The PHP Teacher Magazine*! This month, we're diving deep into a variety of topics, from state management and application architecture to security best practices and the latest developments in PHP. Whether you're a seasoned developer or just starting out, we've got something for everyone. This issue includes articles about finite-state machines,  Vim plugins, single-page applications, cybersecurity, PHP releases, creating a webserver, application events, number formatting, and Laracon.

We'll explore how to build robust and maintainable applications, improve our workflows with powerful tools, and stay ahead of the curve with the latest PHP features. Get ready to enhance your skills and broaden your understanding of the PHP landscape. Let's jump in!

### Articles

#### Finite-state Machines with PHP 8.1

By Scott Keck-Warren

Managing the state of entities in our applications can often become a complex and error-prone task. This article explores how to leverage **finite-state machines (FSMs)** to manage these flows more effectively using PHP 8.1's features.

An FSM is an abstract model that can be in only one state at a time from a finite set of states. The FSM transitions from one state to another based on input. When defining an FSM, you must specify the list of states, the initial state, and the transitions. A traffic light, for instance, can be modelled as an FSM with states like Off, Red, Yellow, and Green. The transitions would be off to red (on boot), red to green (after x seconds), green to yellow (after y seconds) and yellow to red (after z seconds).

```
     +-----+    boot    +-----+   X secs  +-----+   Y secs  +-----+   Z secs  +-----+
     | Off |----------->| Red |--------->|Green|--------->|Yellow|--------->| Red |
     +-----+             +-----+          +-----+          +-----+            +-----+
```

A good use case of FSMs in web applications is managing the states of a blog post in its publishing flow. The states could be Draft, Ready for Review, Scheduled, and Published. Using PHP 8.1's **enumerations (enums)**, you can define these states as valid values, much like a class. Enums enhance type safety, preventing invalid state assignments. For example:

```php
enum PublishedState {
    case Draft;
    case ReadyForReview;
    case Scheduled;
    case Published;
}
```

Without enums, you might use class constants to keep track of states. However, using enums provides better type safety. With enums, you can enforce that function parameters are of the correct state, and prevent invalid transitions. For example, instead of using a string `$newState` as the parameter, you can require the `PublishedState` enum:

```php
function updateState(PublishedState $newState): void {
//...
}
```

To represent states outside of enums, you can use **backed enums** with scalar values. You can associate strings with each case in your enum:

```php
enum PublishedState: string {
    case Draft = "Draft";
    case ReadyForReview = "Ready For Review";
    case Scheduled = "Scheduled";
    case Published = "Published";
}
```

PHP prevents you from duplicating values when using backed enums or from skipping values. The `value` property can then be used to retrieve the associated scalar value. The `from()` method converts a scalar value back to its enum value:

```php
$state = PublishedState::from("Scheduled");
var_dump($state); // enum(PublishedState::Scheduled)
```

The `tryFrom()` function provides a safer way to get the enum from scalar value by returning null instead of throwing an error. Enums can also contain methods, which makes them ideal for validating transitions. The `isValidTransition` function in listing 4 is an example of validating the transitions for the `PublishedState` enum:

```php
enum PublishedState:string {
    case Draft = "Draft";
    case ReadyForReview = "Ready For Review";
    case Scheduled = "Scheduled";
    case Published = "Published";

    function isValidTransition(PublishedState $newState) {
        $transitions = [
            PublishedState::Draft->value => [
                PublishedState::ReadyForReview,
            ],
            PublishedState::ReadyForReview->value => [
                PublishedState::Draft,
                PublishedState::Scheduled,
            ],
            PublishedState::Scheduled->value => [
                PublishedState::Published
            ],
        ];
        return in_array($newState, $transitions[$this->value]);
    }
}
```

With this, you can ensure that the blog post only moves from one valid state to the next. Finite-state machines provide a structured approach to managing state in applications and combined with the power of enums you can create well defined business logic.

#### Universal Vim Part Three: Putting the You in Utility

By Andrew Woods

This article completes the series on crafting a universal Vim experience by introducing several plugins to enhance speed, agility, and efficacy. We will explore several Vim plugins and how they can improve your workflow.

##### Startify

This plugin provides a menu when you start Vim without arguments, listing recently changed files and sessions. It allows you to quickly get back to your previous work, and it is configurable to display relevant information for the user. Startify lists the items in each section with a single character box next to each item. You can move the cursor and press enter or type the character to open an item. The item can be opened in a split (horizontal or vertical) or a tab using the `s`, `v`, or `t` keys. Startify handles sessions in the `~/.vim/sessions` folder, and also offers functions to manage sessions:

```
:SLoad load a session
:SSave save a session
:SDelete delete a session
:SClose close current session
```

Startify helps you begin your Vim session with ease.

```
  _
 /   \
| (oo) |
 \  -- /
  || ||
  ~~ ~~
```

##### Undo Tree

This plugin exposes Vim's change history as a tree, allowing you to navigate back through your history. You can use `:UndotreeToggle` to access your change history and a diff of each change. This allows you to recover content you have edited. Undotree supports branches of changes allowing you to navigate back to an alternative state of the file. You can also customize the layout of the plugin to fit your needs.

```
      +----------------+
      |   Main Branch  |
      +----------------+
          /         \
    +-------+   +--------+
    |Branch 1|   |Branch 2|
    +-------+   +--------+
```

##### Vim Surround

This plugin makes it easy to add, change, or remove quotes from strings, tags, parentheses and brackets, and braces. For example, if you have the text `Eric and John bought PHP Architect from Oscar`, you can put single quotes around `PHP Architect` by putting the cursor anywhere on the word PHP and typing `ys2aw'`. To change the single quotes to double quotes, you can type `cs'"`. To remove the quotes you can type `ds"`. Vim Surround supports visual selection. For example, if you select the text `PHP Architect` by typing `viwe` and then type `S'` the selected text will be enclosed in single quotes.

```
        ____________________
       /        Text       /
      /  Add, Change, Remove/
     /   Quotes, Tags, More/
    /___________________/
```

##### Ultisnips

This plugin is a text expander for Vim that allows you to trigger snippets with a few keystrokes. You can add placeholders in your snippets to inject values, allowing for more complex text expansions than Vim's native abbreviations.  This plugin is useful for writing code and even for more generic tasks, like emails.  UltiSnips is also used for multi-line expansions. Snippets are organized by file type, but can also be grouped in a file called `all.snippets` to transcend file types.

```
  +----------+       +----------+
  |   Code   |  -->  | Expanded |
  | Snippet  |       |   Text   |
  +----------+       +----------+
```

##### Lightline

This plugin is an improved status bar. Lightline provides more information about the file you're currently editing. The settings are kept in the `g:lightline` variable. It allows for easy customization and integrates with your color scheme.

```
+---------------------------------------+
| File: example.php  Line: 12  Col: 35 |
+---------------------------------------+
```

These plugins, among others, will enhance your Vim experience making it more efficient and easier to use.

#### Cheating at SPA with Breeze & Inertia

By Joe Ferguson

This article explores how to quickly build single-page applications (SPAs) using Laravel Breeze, Inertia, and Vue.js. Inertia is a JavaScript library that allows building server-side rendered pages with JavaScript components. It provides the experience of a client-side SPA without building a dedicated API.

To start you create a new Laravel project and install Breeze:

```
$ composer create-project laravel/laravel inertia
$ cd inertia/
$ composer require laravel/breeze --dev
$ php artisan migrate
$ php artisan breeze:install vue
$ npm run dev
```

Breeze gives you a registration form along with other user functionality. The default route uses `Inertia::render` to render a Vue.js template instead of a Blade template. The `/dashboard` route is served by `resources/js/Pages/Dashboard.vue`.

```
   Laravel
     |
     | Inertia
     v
   Vue.js
```

Breeze provides layouts for authenticated users, `AuthenticatedLayout`, and unauthenticated users, `GuestLayout`. The application's components are located in the `resources/js/Components` and `resources/js/Pages` folders.

You can display tasks using a `Task` component within an `Index.vue` file using a `v-for` loop and pass data as `props` to the child component:

```vue
<template>
    <div v-for="task in tasks" :key="task.id" :task="task" />
</template>

<script setup>
    // code ...
</script>
```

The setup section contains the JavaScript logic and the template section displays the information.  The data comes from the controller and is passed to the frontend using `defineProps`. For example:

```vue
<script setup>
    const props = defineProps(['task']);
</script>
```

A Task model is used to store tasks, and you can use artisan to create this model, the migration, and a resource controller. The command `php artisan make:model --migration --resource --controller Task` does this in one step. The resource controller methods can be used to manage database tasks. The `Route::resource` configuration handles the routes. You can then apply the `auth` and `verified` middleware to the routes.

A policy can be created that limits editing and deletion access to the task owner using `php artisan make:policy TaskPolicy --model=Task`. In the policy `update` and `delete` methods can be overridden to allow only the owner of the task to edit it. The Task controller uses these policies to authorize access to the methods:

```php
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);
        //...
    }
```

Inertia handles redirects, and refreshes the relevant content. This allows you to edit and delete tasks without refreshing the entire page. With Inertia, you can build powerful single-page applications without the complexity of APIs. You can add, edit, and delete tasks by building a basic CRUD application while relying heavily on the existing layout provided by Breeze.

#### Cybersecurity Checkup

By Eric Mann

This article provides four actions you can take to enhance your security posture for Cybersecurity Awareness Month.

1.  **Enable Multi-Factor Authentication (MFA):** MFA protects against password breaches by requiring an additional factor beyond your password. Time-based one-time passwords are an easy and secure form of MFA. Use authenticator applications like Google Authenticator or Authy. Physical keys like YubiKey are even more secure. Avoid using SMS-based codes for authentication.
  ```
    +---------+       +----------+      +---------+
    |  User   |  -->  |Password   |  -->|  Second |
    |         |       |   + MFA  |      |  Factor |
    +---------+       +----------+      +---------+
  ```

2.  **Build Strong Passwords:**  A strong password should contain many characters, and it should not use dictionary words.  **Entropy** makes a password secure, not complexity. A passphrase made of memorable dictionary words is more secure than a complex password. You can use password managers like 1Password to generate and store truly secure passwords.
   ```
        +------------------------+
        |  Password Complexity  |
        |      vs Entropy       |
        +------------------------+
   ```

3.  **Recognize and Report Phishing:** Phishing attacks are often the start of a cyber attack. These emails impersonate someone you know to trick you into downloading malicious software. Look for the following to identify a phishing email:  Does the sender know you personally, is the email asking you to do something out of the ordinary, is there a sense of urgency. Do not follow links embedded in suspicious emails.  Contact the sender to confirm, and always report suspicious emails.
    ```
          +--------------------+
          | Identify Phishing  |
          |  - Sender          |
          |  - Requests        |
          |  - Urgency         |
          +--------------------+
    ```

4.  **Update Your Software:**  Outdated software is a major security risk. Update your systems and endpoints regularly to avoid vulnerabilities.  Apple recently released a patch for a remote code execution flaw in the Webkit rendering engine. Install software updates and reboot your computer to ensure you are using the latest patches.
    ```
     +-------------+
     | Software    |
     | Updates     |
     |  - Regular  |
     |  - Critical |
     +-------------+
   ```

By implementing these actions, you can significantly improve your cybersecurity and protect your digital assets.

#### New and Noteworthy

This section covers some of the recent announcements and updates in the PHP community.

*   **PHP Releases:**
    *   PHP 8.2.0 (RC 3) is available for testing.
    *   PHP 8.1.11 and PHP 8.0.24 have been released.

*   **php[tek] is Returning:** The php[tek] conference is returning to Chicago in 2023 and is seeking sponsors.

*   **Asymmetric Visibility:** This concept allows class properties to be publicly read-only while being set privately or protectedly.

*   **Import Laravel Vapor DNS to Cloudflare:** The Cumulus package helps manage DNS records when using Cloudflare.

*   **Learn PestPHP From Scratch:** A free video course from Laracasts teaches how to use Pest PHP.

*   **RFC: `json_validate()`:** A proposal to add a `json_validate()` function to verify if a string contains valid JSON.

*   **RFC: Improve `unserialize()` error handling:**  A new `UnserializationFailedException` will be thrown when unserialization fails.

*   **RFC: StreamWrapper Support for `glob()`:** Implementing StreamWrappers support for the `glob()` function.

*  **RFC: Deprecations for PHP 8.3**: An umbrella RFC that lists features to be considered for deprecation in PHP 8.3 and removal in PHP 9.

These updates highlight the ongoing evolution of the PHP ecosystem and the efforts to make the language more powerful and secure.

```
   +-------------------+
   |  PHP Development  |
   |   - New Features  |
   |   - Performance   |
   |   - Security      |
   +-------------------+
```

#### Making Our Own Web Server: Part 1

By Chris Tankersley

This article discusses how PHP applications differ from other "modern" web languages, and it shows how PHP can process its own requests without a web server. Unlike languages like Node.js, PHP is not a server; instead, it relies on a web server to handle HTTP connections. The typical life cycle of a PHP script is "start, process, die". PHP expects an external server, like Apache's `httpd` or PHP-FPM, to handle requests.

```
 +----------+       +----------+       +----------+
 |  Client  |  -->  |  Web     |  -->  |  PHP     |
 |          |       |  Server  |  -->  | Engine   |
 +----------+       +----------+       +----------+
```

The most common setups for PHP involve using the PHP engine as a module inside Apache's `httpd` web server, or as proxied FastCGI processes using PHP's FPM manager. `httpd` starts a PHP engine in each of its own processes, and when a web request comes in, `httpd` processes the request and then pipes it through the PHP engine. In a FastCGI setup the web server (like nginx) hands the request off to the PHP-FPM, which spawns a number of PHP processes. Regardless of the setup, PHP processes a single request at a time and relies on the web server to return the response.

PHP has a built-in development server which is a very basic, single-threaded web server that can be used to run an application without the need for a full web server, starting with PHP 5.4.0. You can start the built in server using the command line `php -S [:port] [-t /docroot] [/router.php]`. Starting with PHP 7.4 it's possible to run a multi-threaded development server, but it is not recommended for production. While convenient for development, the built in server is not production-ready and has downsides such as only handling a single connection, and lower performance when serving static files.

PHP has a number of lower level C functions which are exposed as PHP methods. One of them deals with network sockets. Using these you can open a listening socket that waits for connections, then it reads data and sends responses. The following functions are used to build this socket server:

*   `socket_create` - Creates a socket
*   `socket_bind` - Binds the socket to an IP address and port
*   `socket_listen` - Start listening on the socket
*   `socket_accept` - Start accepting connections
*   `socket_read` - Read information from the socket
*  `socket_write` - Write information back to the socket
*   `socket_close` - Close the socket

Here is some example code of using these to create a basic socket server:

```php
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, '0.0.0.0', 8080);
socket_listen($socket);

while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);
    echo $buffer;
    socket_write($connection, $buffer);
    socket_read($connection, 1024);
    socket_close($connection);
}
```

Using this basic server will echo back what it receives, but you will need to parse HTTP requests to make a fully functional HTTP server. HTTP requests are strings that include the HTTP request method, the URI, the HTTP version, headers, and a request body. You can use the `laminas-diactoros` package to parse HTTP requests. The following is example code of reading the request data, parsing it, responding with a basic response:

```php
while (true) {
    $connection = socket_accept($socket);
    $buffer = socket_read($connection, 1024, PHP_NORMAL_READ);
    $request = Serializer::fromString($buffer);
    $response = (new Response())->withStatus(200);
    $message = 'You requested ' . $request->getUriString() .
    ' with verb ' . $request->getMethod();
    $response->getBody()->write($message);
    $responseString = Serializer::toString($response);
    socket_write($client, $responseString);
    socket_close($client);
}
```
The server can now parse an HTTP request using the PSR-7 interface, and you can then expand the code to handle the request and provide the correct response.

This approach allows PHP applications to take direct control over the request before it is processed, but the code still needs to be expanded to handle multiple connections.

#### Application Event Walkthrough

By Edward Barnard

This article introduces Application Events and how they differ from Domain Events and includes a code walkthrough. The Application Event feature follows a similar structure as the Domain Event feature, and this article focuses on differences in the implementations.

```
   +---------------+
   | Application   |
   | Event         |
   |  - Local      |
   |  - Publish    |
   +---------------+
         |
         V
   +---------------+
   |   Domain      |
   |   Event       |
   |  - Global      |
   +---------------+
```

The code includes a factory to create an application service. There are two factory methods, `defaultAppEvent()` and `dbStateChangeAppEvent()`. The `dbStateChangeAppEvent()` method is a method to record all database changes. The test harness executes these methods, showing the flow of control of the application:

```php
$appEvent = AppEventFactory::defaultAppEvent($action,$description);
$appEvent->save();
$appEvent->notify();
```

A default application event repository saves to the local table, and this method demonstrates manual database transactions with CakePHP using `prepare()` and `execute()`. This was done to allow the database layer to be interchangeable, for when the application scales to multiple databases. The `save()` method takes a raw MySQL string to insert, and a query to read back the data:

```php
    public function save(string $insert, string $read, array $parms)
    {
        $connection = $this->localAppEventsTable->getConnection();
        try {
            $connection->transactional(
                function ($conn) use ($insert, $read, $parms) {
                    $statement = $conn->prepare($insert);
                    $statement->execute($parms);
                    $statement = $conn->prepare($read);
                    $statement->execute([$statement->lastInsertId()]);
                    $readback = $statement->fetchAll('assoc');
                    if (!(is_array($readback) && array_key_exists(0, $readback))) {
                        throw new DatabaseException('Event readback failed');
                    }
                    $this->readback = $readback;
                }
            );
        } catch (Exception) {
            return [];
        }
        return $this->readback;
    }
```

The raw MySQL strings are provided by the `DefaultAppEvent` class:

```php
class DefaultAppEvent extends BaseAppEvent
{
    protected static string $insert = <<<'QUERY'
        insert into `local_app_events`
        (action, subsystem, description, detail,
        event_uuid, when_occurred, created, modified)
        values (?, ?, ?, ?, ?, now(6), now(), now())
    QUERY;

    protected static string $read =
        'select * from `local_app_events` where id = ? limit 1';
}
```

The `BaseAppEvent` provides the logic, while the `DefaultAppEvent` provides specific configurations. The constructor captures the necessary data to store to the database, and the manual transactions, and runs the database transaction inside a closure. This service also includes a `notify()` method that publishes an event to the domain event system. The `save()` method runs inside the database transaction, but the `notify()` method runs after a successful commit. The Application event is published as a Domain Event. This separation allows the use of messaging queues such as RabbitMQ for production systems:

```
   Application Event --> Message Queue --> Domain Event
```

An interface is used to define the application event and the repository to allow mixing and matching as needed. The interface for the repository includes a `save()` method:

```php
interface IRAppEvent
{
    public function save(
        string $insert,
        string $read,
        array $parms
    ): array;
}
```

By separating constants into a PHP interface, they can be used and auto-completed by IDEs without the need of implementing methods from the same file. The repository interface simplifies testing by enabling test fixtures to implement the `IRAppEvent` interface.

This feature is not recommended for production as-is, but it provides the building blocks for more complex functionality. The legacy version of the application follows a similar pattern as the code above.

#### Converting Float Strings

By Oscar Merida

This article addresses how to correctly convert currency strings to integers representing pennies. It looks at the common pitfalls and how to approach this problem in a more robust fashion.

A common naive approach is to convert a string to a float, multiply it by 100, and then cast it to an integer:

```php
$price = (float) "105.00";
$price = $price * 100;
var_dump($price); // float(10500)
$price = (int) $price;
var_dump($price); // int(10500)
```

To clean up incoming strings, you can remove unwanted characters such as `$` , `,` , and spaces using `str_replace`. This gives a cleaner string ready to be converted to a float:

```php
function priceToFloat(string $input) : float
{
    $output = str_replace(['$', ',', ' '], '', $input);
    return (float) $output;
}
```

It was found that the naive approach of converting to a float, multiplying, and then casting to an integer, lost precision. The float value `2487.47` was converted to integer `248746` instead of the correct value `248747`. To maintain precision, use the BCMath extension with the function `bcmul()` to correctly convert to an integer representing pennies.

```php
function floatToPennies(float $input) : int
{
  return (int) bcmul($input, 100);
}
```

The BCMath extension prevents the loss of precision during floating point math. This ensures that when converting currency strings to an integer representing pennies, you get the correct result.

```
    +-----------------------+
    | Currency Conversion    |
    |    Float String to    |
    |    Integer (pennies)    |
    +-----------------------+
```

#### Laracon Online 2022

By Eric Van Johnson

This article reviews Laracon Online 2022, discussing the major announcements and most interesting talks.

*   **Livewire 3:** Caleb Porzio discussed the complete rewrite of Livewire 3. A significant change is the inclusion of AlpineJS as part of Livewire. Livewire 3 batches network calls and uses an annotation practice.

*   **Community:**  Caneco's talk "The Hitchhiker's Guide to the Laravel Community" highlighted the importance of community for new Laravel developers.

*   **Database Performance:** Aaron Francis presented "Database Performance for Application Developers," covering schema design, B-tree indexes, and queries.

*   **Taylor Otwell:** Taylor Otwell discussed many new features released over the past year. The new yearly release cycle of Laravel means that new features are released as they are completed. New artisan commands such as the `about` and `db:show` commands were also discussed.
  ```
     +-------------+
     |  Laracon     |
     |   Online    |
     |  - Features |
     |  - Community|
     +-------------+
  ```

The entire conference is available on YouTube for free.

#### Beware: Tek iTek is Disruptive

By Beth Tucker Long

This article discusses the impact that conferences like php[tek] can have on a programmer's career and life. The author recalls how her first conference, php:works, provided her with a network of connections and new tools. She had originally been skeptical that she would get anything out of the conference, but it changed the way she looked at programming and the community around it. Attending conferences improves coding skills and helps build a network. Furthermore, the author became more involved in the community and gained opportunities to start speaking at conferences, which helped grow her business and work for herself full-time. Her conference experience helped her to seek therapy and a diagnosis which drastically improved her quality of life. She encourages people to attend conferences to develop skills, build networks, and learn about new tools and frameworks.
```
    +---------------------+
    | Conference Impact   |
    |   - Skills         |
    |   - Network        |
    |   - Growth        |
    +---------------------+
```
### Postface

This month's issue covered a wide range of topics relevant to the PHP ecosystem. From managing states using FSMs and improving your text editing with Vim, to building SPAs with Breeze and Inertia, enhancing cybersecurity practices, and exploring the latest PHP developments, we have covered some great and timely material. We've also seen how PHP can take control of HTTP connections and how to handle application events. Finally, we addressed the pitfalls of number formatting and the power of community. We hope you found this issue insightful and useful. Please join us again next month.

#### Index of Technology Words

*   Finite-state machines (FSMs)
*   Enumerations (enums)
*   Backed enums
*   Vim
*   Startify
*   Undo Tree
*   Vim Surround
*   Ultisnips
*   Lightline
*   Single-page applications (SPAs)
*   Laravel Breeze
*   Inertia
*   Vue.js
*   Multi-Factor Authentication (MFA)
*   Entropy
*   Phishing
*   PHP-FPM
*   FastCGI
*   Network sockets
*   PSR-7
*  PSR-15
*   Application Events
*   Domain Events
*  BCMath extension
*  Laracon Online
*  AlpineJS
*  Artisan
*  RabbitMQ


