---
title: PHP Teacher magazine - May 2023
publishDate: 2023-04-19 00:00:00
description: They establish a well defined interface for working with HTTP requests and responses. They introduce a reasonable amount of immutability (streams be streams, after all). Together
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - may 
  - 2023
---


Welcome to another insightful edition of *The PHP Teacher*! This month, we delve into a variety of topics crucial for both the budding and seasoned PHP developer. We explore the practicalities of **working with HTTP requests and responses**, dissect the **complexities of AI and its implications for our industry**, and look at ways to structure our code for **better maintainability** and **testability**. We also examine tools that can help us build better command-line applications and explore practical strategies for incident response. Whether you are interested in **clean code principles**, **architectural patterns**, or **harnessing the latest technologies**, we have something for you.

This issue features:

*   A deep dive into **PSR-7** and the **`http-tortilla` library** for handling HTTP requests and responses
*   An examination of the **GIMM program** and how it prepares students for the tech industry.
*   A critical analysis of **Large Language Models (LLMs)**, their capabilities, limitations and legal issues.
*   A practical look at **tabletop exercises** for disaster planning.
*   A guide to building **console applications** using the **Minicli framework**.
*   An exploration of **maze generation** using PHP.
*   A discussion on **refactoring techniques** for improving codebases.
*   An introduction to **PSR-20 Clock interface**.
*   A comparison of **ADR and MVC architectural patterns**.
*   A thought-provoking piece on the current fears and possibilities around AI.

We hope these articles will challenge and inspire you on your journey as a PHP developer.


## HTTP Tortilla: Wrapping PSR-7 for Developer Convenience**

_By Tim Lytle_

In the world of PHP development, **PSR-7 interfaces for request and response messages** provide a valuable standard. They establish a well-defined interface for working with HTTP requests and responses, introducing a reasonable amount of immutability. This results in increased ease when testing and provides confidence and simplicity when integrating with existing libraries that manipulate and extract information from the HTTP life-cycle. However, these interfaces can be minimal, and developers may long for more convenient methods tailored to specific use cases. This article explores the trade-offs and introduces `http-tortilla`, a library designed to add developer-friendly methods to PSR-7 objects.

**The Trade-Off:**

While PSR-7 defines a minimum set of methods for inspecting and building responses or requests, this can sometimes be too basic for internal use. For example, `$request->getQueryParams()` might be sufficient, but `$request->getQueryParam($name, $default = null)` would be more convenient. Similarly, instead of checking if `$member = $request->getAttribute(Member::class)` is an instance of the desired member entity, we might prefer `$request->isAuthenticated()` and `$request->getAuthenticatedMember()`. These improvements can reduce boilerplate code and make development more efficient.

**The Challenge of Custom Requests:**

Creating custom request objects that wrap PSR-7 requests can solve these convenience issues, but it presents integration problems. If you need to integrate with library code that expects a PSR-7 request, your custom request must implement all PSR-7 methods. Also, PSR-15's middleware pattern, which is highly valuable, requires requests to implement PSR-7 interfaces. Extending existing library code might seem like a solution, but it can lead to problems with the `with*()` methods, testing, and coupling to code that may change.

**The Solution: http-tortilla**

The best solution is to create your own request object, but one that implements all the PSR-7 methods. The `http-tortilla` library helps to achieve this by using a trait for wrapping PSR-7 objects. This trait moves all the boilerplate proxy methods into one place, making it easy to see the added methods and modify the behaviour of PSR-7 methods.

```php
trait ServerRequestWrapper
{
    private ServerRequestInterface $wrapped;

    protected function setWrapped(ServerRequestInterface $request): void
    {
        $this->wrapped = $request;
    }

    private function getWrapped(): ServerRequestInterface
    {
        if (!($this->wrapped instanceof ServerRequestInterface)) {
            throw new \UnexpectedValueException('must `setMessage` before using it');
        }

        return $this->wrapped;
    }

    public function getServerParams(): array
    {
        return $this->getWrapped()->getServerParams();
    }
    // ... other proxy methods
}
```

This trait can be used in a custom `ServerRequest` class:

```php
class ServerRequest implements ServerRequestInterface
{
    use ServerRequestWrapper;

    public function __construct(ServerRequestInterface $request)
    {
        $this->setWrapped($request);
    }

    public function getQueryParam($name, $default = null): ?string
    {
        return $this->getQueryParams()[$name] ?? $default;
    }
}
```

**Handling Immutability**

Because PSR-7 objects are immutable, the `with*()` methods return a modified version of the wrapped request, not your custom request. To solve this, the trait can be modified to use a wrapping 'factory' so that the `with*()` requests can pass the returned object to the factory for wrapping. This way, evolving the request continues to return an instance of our custom request object.

```php
trait ServerRequestWrapper
{
    private $wrapped;
    private $factory;
    
    protected function setFactory(callable $factory): void {
        $this->factory = $factory;
    }

    private function viaFactory(MessageInterface $message): MessageInterface {
        if (!$this->factory) {
            return $message;
        }
    
    return call_user_func($this->factory, $message);
    }

    public function withCookieParams(array $cookies): ServerRequestInterface {
       return $this->viaFactory(
            $this->getWrapped()->withCookieParams($cookies)
        );
    }
}
```
**Extending Beyond Requests:**

These techniques can also be applied to other PSR interfaces, allowing the creation of custom responses and URIs. For example, you can create custom responses like JSON, text or API Problem responses. You can also wrap existing objects, such as exceptions, to act as responses. Similarly, route definitions can be wrapped to act as URIs.

**Lazy Responses**

One useful concept is the idea of a lazy response, which contains all the information needed to create a response but only generates it when necessary. This could be especially useful in middleware chains, where the response could be modified further up the chain.
```php
class LazyResponse implements ResponseInterface {
    use ResponseWrapper;

    private $factory;
    private $data;

    public function __construct($factory, $data) {
        $this->factory = $factory;
        $this->data = $data;
        $this->setWrapped(new Response());
    }
    
    public function getBody(): StreamInterface {
         $response = call_user_func($this->factory, $this->data)
        return $response->getBody()
    }
}
```
**Conclusion**

`http-tortilla` provides a set of traits that make it easier to create custom PSR-7 objects, adding convenience without sacrificing interoperability. You can install it with `composer require phoneburner/http-tortilla` and find more examples at [github.com/tjlytle/tortilla-example](http://github.com/tjlytle/tortilla-example).

```
     _  _
    ( `  )
   /     \
  |  ---  |
   \     /
    `---'
  HTTP Tortilla
```

**Technology Index:** PSR-7, PSR-15, HTTP, Requests, Responses, Middleware, Immutability, Traits, `http-tortilla`

***

## Life After GIMM: Preparing for the Tech Industry Post-College**

_By Adam Giles_

The Games, Interactive Media, and Mobile (GIMM) program at Boise State University offers a hands-on approach to technology education, going beyond just game development. It prepares students for the competitive tech market by exposing them to a wide range of emerging technologies and concepts. This article discusses the GIMM program, its goals, and how it prepares students for careers in technology.

**What is GIMM?**

GIMM is an interdisciplinary program that introduces students to various fields within the tech industry. While it includes game development, it also covers full-stack web development, 3D animation, and AR/VR development. This breadth of experience allows students to build a strong foundation of general knowledge that can be expanded upon.

**Hands-On Experience:**

The program emphasises hands-on experimentation and the creation of portfolio artifacts. Students are encouraged to work on projects inside and outside the classroom, demonstrating their skills and dedication. These projects are crucial for showing employers that the student has practical experience and is not just relying on theoretical knowledge.

**The Generalist vs. Specialist Approach**

The GIMM curriculum fosters a generalist mindset, providing a broad understanding of technology. This allows for more flexibility in career choices, with graduates able to take on a variety of roles. However, GIMM doesn't discourage specialisation. Students can still choose to focus on a particular area, building a deeper understanding in that area while having the general knowledge as well.

**Problem-Solving Skills:**

One of the key goals of the GIMM program is to develop students' problem-solving abilities. The tech industry is constantly evolving, so it is crucial to be able to adapt and troubleshoot independently. The curriculum teaches students how to research, understand, and resolve issues effectively. They are taught how to use online resources like Stack Overflow and modify code snippets to fit their specific needs.

**Community and Networking:**

GIMM also provides a strong sense of community, with opportunities for students to work together on projects, attend events like the GIMM Jam.  Alumni also engage with current students via Q&A sessions, offering valuable insights into how they have applied their GIMM knowledge in the tech sector.

**Application of Skills:**

The GIMM program helps students with career planning. Students create custom portfolio websites and use platforms like LinkedIn to present themselves to employers.  The program prioritises securing employment for students, before or soon after graduation.

**The Value of a Tech Degree:**

With the abundance of online resources, some people may wonder if a tech degree is still necessary. However, programs like GIMM provide a structured path for learning, offering a set curriculum to follow, and allowing students to acquire the foundational knowledge they can expand upon. It also helps students gain practical experience which is essential in the tech sector.

**Conclusion**

The GIMM program serves as a model for how universities can prepare students for the tech industry. By offering a wide range of experiences, developing problem-solving skills, and emphasising the creation of tangible portfolio artifacts, programs like GIMM equip students with the tools they need to succeed.

```
     ___
    /   \
   | GIMM |
   \___/
    
  Tech Education
```

**Technology Index**: GIMM, Technology Education, Full-Stack Development, AR/VR, Problem-Solving, Portfolio, Generalist, Specialist

***

## CatAIstrophe: Navigating the Fears and Facts of Large Language Models**

_By Beth Tucker Long_

Recent advancements in Artificial Intelligence (AI), particularly in Large Language Models (LLMs), have sparked a mix of awe and concern. While AI is hailed as revolutionary, there are widespread fears that it could be destructive. This article explores the capabilities and limitations of LLMs and tries to address some of these concerns.

**LLMs as Text Prediction Systems**

At their core, LLMs are sophisticated text prediction systems. They work by taking a sequence of words (tokens) and predicting the most likely next word. This prediction is based on probability matrices derived from vast amounts of training data. Your phone’s autofill works in a similar way but on a much smaller scale.

**Token Size and Training Data**

The effectiveness of an LLM depends on several factors, including the amount of training data, the raw processing power, and the **token size**.  Autofill on a mobile phone is limited by token size (amount of existing data considered), and the amount of training data. In contrast, models like GPT-3 and GPT-4 have access to massive datasets and larger token windows, allowing them to consider more text to determine the next token.

**How LLMs Work**

LLMs like ChatGPT use huge token windows to determine the next token. They also chunk individual words or combine phrases to improve their accuracy. Human trainers also play a role, rating responses and providing manual responses for the AI to learn from. Although the models have additional functionality, they are fundamentally text prediction models.

**The Hallucination Problem**

The issue is that LLMs generate text based on probability rather than knowledge. This often leads to "hallucinations", or convincing but ultimately incorrect responses. These are essentially lies that the AI is presenting as fact. For example, if asked about articles written by a specific author, an LLM might generate a list of fake articles and even fake publication dates. The same is true of research papers, which could lead to the spread of disinformation.

**AI Agents and Artificial General Intelligence (AGI)**

Despite the hype around AI agents, these tools are not yet capable of achieving Artificial General Intelligence (AGI), which is the ability to replicate an intellectual task like a human. Tools like AutoGPT, which claim to perform research and create content, often get stuck in loops and fail to produce useful output. It is safe to say these tools are not very useful for the general public as of yet.

**Legal and Ethical Issues**

Many LLMs are not open-source, despite their names suggesting otherwise. This raises concerns about where they obtain their training data. For example, Microsoft’s Copilot has been accused of using copyrighted code in its training data. There is also concern around where training data comes from, much of which comes from sources like Common Crawl, which archives the internet including copyrighted material. While this is considered fair use, the legal landscape remains uncertain.

**Where AI Can Help**

Despite these limitations, AI tools can still be valuable. They can help with rubber ducking, prompting new ideas and approaches to problems. LLMs are also useful for dealing with boilerplate code, summarising large amounts of text, and generating outlines for various tasks.

**AI Will Not Replace You**

Current AI lacks the intelligence that humans possess. AI cannot learn from mistakes and successes like human brains.  While AI may weed out developers who are not strong problem solvers, it cannot replace those who can adapt to new challenges.

```
     _
    / \
   ( o o )
   \  ~  /
    -----
  CatAIstrophe
```

**Technology Index:** Artificial Intelligence, LLM, Large Language Models, GPT, Token, Training Data, Hallucination, Artificial General Intelligence, AutoGPT, Open Source, Copyright, Copilot.

***

## Tabletop: Planning for Disaster**

_By Eric Mann_

In the world of cybersecurity, being proactive is as important as being reactive. One excellent method of preparing for potential incidents is to conduct tabletop exercises. These exercises simulate cybersecurity incidents in a low-pressure environment, allowing teams to practice their responses and identify weaknesses in their processes. This article explores how to run a tabletop exercise effectively.

**What is a Tabletop Exercise?**

A tabletop exercise is essentially a role-playing game for cybersecurity incident response. It simulates a breach, and the team members work through their response procedures as if it were real. The key is to have representatives from all key stakeholder groups, such as legal, finance, infrastructure, and executive staff. These exercises help teams understand their roles and responsibilities in an incident.

**Creating a Believable Scenario**

The simulation should include a clear what, who, and when of the event. The scenario should be realistic and relatable. For instance, you can simulate an infrastructure engineer downloading a malicious file, a customer service representative installing a keylogger, or an executive installing malware on a personal device.

**Planning the Simulation**

Plan the information you will provide during the simulation based on how you expect the team to react to the incident. If their first step would be to audit network access logs, then plan on providing those network logs for the simulation. Also, be prepared to provide extra information to the team if needed.

**Guiding the Team**

During the exercise, it's important not to intervene too much. Allow the team to navigate the scenario and make their own decisions. If they go down a rabbit hole, gently guide them back to the task at hand. As the team becomes more familiar with the incident response playbook, they will rely less on your guidance.

**Retrospective and Action Items**

After the simulation, allow everyone to take a break before engaging in a retrospective phase. Discuss the team's performance and create a list of action items for further policy or process refinements.

**Regular Repetition**

Tabletop exercises should be repeated regularly, every 3-6 months, to test different attack vectors and to improve your incident response plan. You can also use real-world incident reports as the basis for your scenarios. Regular repetition strengthens the response plan and the team's ability to use it.

**Related Reading**

For further reading, check out these articles by Eric Mann, also in the php[architect] magazine:

*   _Security Corner: The Risks of Free Conference Internet_ [https://phpa.me/security-apr-2023](https://phpa.me/security-apr-2023)
*   _Security Corner: InfoSec 102: Phishing_ [https://phpa.me/security-mar-2023](https://phpa.me/security-mar-2023)
*   _Security Corner: Infosec 101: The Confused Deputy_ [https://phpa.me/security-feb-2023](https://phpa.me/security-feb-2023)

```
    _____
   |     |
  _|     |_
 /  TABLE  \
|   TOP    |
 \_______/
     Incident Planning
```

**Technology Index:** Cybersecurity, Incident Response, Tabletop Exercise, Threat Modeling, Simulation, Security, Incident Response Plan

***

## The Workshop: Minicli - A Minimalist Framework for Building CLI Applications**

_By Joe Ferguson_

Building command-line interfaces (CLIs) with PHP is a common task, but often involves using heavyweight frameworks like Symfony’s Console Component or Laravel’s Commands. Minicli is a minimalist framework for building CLI applications in PHP. This article demonstrates how to use Minicli to create simple and powerful console applications.

**Getting Started with Minicli**

Minicli requires PHP 8.0 or higher and the `ext-readline` extension. To create a new application you can start by creating a new folder and installing minicli using composer:
```bash
mkdir demo; cd demo
touch example
chmod +x example
composer require minicli/minicli
```
Then you create an empty file called example and add the following code:
```php
#!/opt/homebrew/bin/php
<?php
if(php_sapi_name() !== 'cli') { exit;}
require __DIR__ . '/vendor/autoload.php';

use Minicli\App;

$app = new App([
    'app_path' => [__DIR__ . '/app/Command'],
    'theme' => '\\Unicorn', 
    'debug' => false,
]);

$app->registerCommand('demo', function () use ($app) {
    $app->getPrinter()
        ->success('Hello php[architect] :D', false);
    $app->getPrinter()
        ->info('Info message with background', true);
    $app->getPrinter()
        ->error('Error Message :(', false);
});

$app->runCommand($argv);
```
Make sure you update the shebang line to match your PHP install. The first line of the file, the shebang, tells the system that the file needs to be executed using the PHP binary. The code then sets up autoloading, imports Minicli classes and instantiates the application. It creates a `demo` command that outputs three messages to the console. To run the application type `./example demo` into the console.

**Structured Application Layout**

A better way to structure the application is to use the `'app_path'` configuration and use PSR-4 autoloading. You will need to add the following to your `composer.json`:
```json
"autoload": {
    "psr-4": {
        "App\\": "app/"
    }
}
```
Then you create your commands in the `app/Command` directory. You need to regenerate the autoload file by typing `composer dump-auto` into the console. You can create `DefaultController.php` inside the `app/Command/Demo` directory with the following contents:
```php
namespace App\Command\Demo;
use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle(): void
    {
        $this->getPrinter()
            ->success('Hello php[architect] :D', false);
        $this->getPrinter()
            ->info('Info message with background', true);
        $this->getPrinter()
            ->error('Error Message :(', false);
    }
}
```
The output of running `./example demo` remains the same as before. The code will return a non-zero error code if an exception is encountered. It also uses `try/catch` blocks to catch errors, and display them if debug mode is enabled.
```php
try {
    $app->runCommand($argv);
} catch (CommandNotFoundException $exception) {
    $app->getPrinter()->error("Command Not Found.");
    return 1;
} catch (Exception $exception) {
    if ($app->config->debug) {
        $printer = $app->getPrinter();
        $printer->error("An error occurred:");
        $printer->error($exception->getMessage());
    }
    return 1;
}
```

**Sub-Commands**

Minicli also supports sub-commands, which is why the controller was named `DefaultController.php`. This means you can run `./example demo` or `./example demo default` and see the same output. You can also create a sub command by creating `app/Command/Demo/TpsController.php` with the following contents:
```php
namespace App\Command\Demo;
use Minicli\Command\CommandController;

class TpsController extends CommandController
{
    public function handle(): void
    {
        $name = "World";
        if ($this->hasParam('name')) {
            $name = $this->getParam('name');
        }
        $this->getPrinter()->display("Hello $name!");
    }
}
```
This will display Hello World if you just type `./example demo tps` and if you type `./example demo tps --name John` it will display `Hello John!`

**Handling Parameters and Flags**

Command line applications need to handle input and support flags. You can use `$this->hasParam()` to check if a parameter has been passed. The parameter value can be retrieved with `$this->getParam()`. You can also check for flags using `$this->hasFlag()`, such as `--shout`. Alternatively, you can use `$this->getArgs()` to get all command-line arguments in order:

```php
$message = "Hello World";
if ($this->hasFlag('shout')) {
    $message = strtoupper($message);
}
$this->getPrinter()->display($message);
```
You can also access parameters by name instead of array index to improve readability.

**Test Flags**

To help with debugging you should implement a test flag that prevents the command from writing to the database, creating files or otherwise making changes.

```php
$test = false;
if ($this->hasFlag('test')) {
    $this->getPrinter()
        ->display("Operating in test mode!");
    $test = true;
}
// Process data and need to update a DB
if (!$test) { // if we're not using test flag
    // update database!
}
```

**Conclusion**

Minicli is a useful framework for creating console applications because it’s minimalist and it stays out of the way.

```
    _
   / \
  |   |
  \___/
  MiniCLI
```

**Technology Index**: Minicli, CLI, Command-Line Interface, Console Application, Composer, PSR-4, Command Controllers, Sub-Commands, Parameters, Flags.

***

## Maze Rats, Part Two: Generating Random Mazes with PHP**

_By Oscar Merida_

Last month, we discussed how to represent mazes using a 2D array of integers. In this issue, we explore how to generate random and solvable mazes in PHP using the recursive backtracker algorithm.

**Algorithms for Generating Mazes**

Many algorithms exist for generating mazes. This article will focus on the recursive backtracker algorithm, which is known for creating long and winding passages. Other algorithms include:

*   **Eller’s algorithm**: Generates large mazes in linear time by working one row at a time.
*   **Wilson’s algorithm**: Uses uniform spanning trees to generate mazes.

**Rendering the Maze**

Before generating the maze, the first step is to render it. We can use the GD library bundled with PHP to create a PNG image of the maze. The code below is the `Renderer` class that draws the walls of each cell based on the integer representation of each cell:

```php
class Renderer {
    private \GdImage $image;
    private array $cells;
    private int $color1;
    private int $color2;
    private int $lineWidth;
    
    const WEST = 0x1;
    const EAST = 0x2;
    const SOUTH = 0x4;
    const NORTH = 0x8;

    public function __construct(
    private int $height,
    private int $width,
    private int $cellSize,
    ) { }

    public function setCells(array $cells): void {
        $this->cells = $cells;
    }
   
    public function draw(): bool {
        $padding = ceil($this->cellSize * 0.6);
        $this->image = imagecreatetruecolor(
            width: $this->width*$this->cellSize+$padding*2,
            height: $this->height*$this->cellSize+$padding*2,
        );
        $this->lineWidth = $this->cellSize * 0.1;
        $bg = imagecolorallocate(
            $this->image, 0xff, 0xff, 0xff
        );
        $this->color1 = imagecolorallocate(
            $this->image, 0x33, 0x33, 0x33
        );
        $this->color2 = imagecolorallocate(
            $this->image, 0x99, 0x99, 0x99
        );
        imagefill($this->image, 0, 0, $bg);
        imagesetthickness($this->image, $this->lineWidth);
    
        $x = $y = $padding;
        foreach ($this->cells as $row) {
            foreach ($row as $cell) {
                $this->drawCell($cell, $x, $y);
                $x += $this->cellSize;
            }
            $x = $padding;
            $y += $this->cellSize;
        }
        return true;
    }
    //... other methods including drawCell() and save()
}
```
**Recursive Backtracker Algorithm**

The recursive backtracker algorithm works by picking a starting cell and then randomly breaking down walls to visit new cells.  If the algorithm reaches a dead end, it backtracks to a previous cell and tries a different path. The code below shows a working example of the algorithm:

```php
class RecursiveGenerator {
    private const WEST = 0x1;
    private const EAST = 0x2;
    private const SOUTH = 0x4;
    private const NORTH = 0x8;
    private const CLOSED = 15;
    private const BASH = [
        self::WEST => [
        'opposite' => self:: EAST,
        'xOffset' => -1,
        'yOffset' => 0
        ],
        self::EAST => [
        'opposite' => self:: WEST,
        'xOffset' => 1,
        'yOffset' => 0
        ],
        self::NORTH => [
        'opposite' => self:: SOUTH,
        'xOffset' => 0,
        'yOffset' => -1
        ],
        self::SOUTH => [
        'opposite' => self:: NORTH,
        'xOffset' => 0,
        'yOffset' => +1
        ],
    ];
    private array $cells;

    public function __construct(
    private int $maxRows,
    private int $maxCols
    ) {
        $this->cells = range(0, $maxRows - 1);
        $this->cells = array_map(
        function() use ($maxCols) {
        $row = range(0, $maxCols - 1);
        return array_map(fn() => self::CLOSED, $row);
        }, $this->cells);
    }
     public function getCells(): array
    {
        return $this->cells;
    }
    public function generate(): void {
        $startX = rand(0, $this->maxRows - 1);
        $startY = rand(0, $this->maxCols - 1);
        $this->visitCell($startX, $startY);
    }
    //... other methods including visitCell, getNeighborCoords, shuffleWalls
}
```
The code begins by initialising all the cells to have all four walls.  The `shuffleWalls()` function returns a randomised array of available walls in the current cell. The `visitCell()` method recursively tries to break through all the walls.

**Removing Dead Ends**

To make the maze more solvable, we can remove dead ends. The function `removeDeadEnds()` scans the maze for cells with three walls and then breaks down one of the walls.

**Adding Entrance and Exit**

The `addEntranceExit()` function adds an entrance on the north side and an exit on the south side by removing the appropriate wall for a chosen cell. This ensures the maze is traversable.

**Conclusion**

Using the recursive backtracker algorithm with PHP, we can generate a variety of mazes. This approach can be used to generate mazes of different sizes and complexities.

```
       _
     _| |_
   _|     |_
  |   MAZE   |
   |_     _|
     |_|_|
```
**Technology Index**: Maze Generation, Recursive Backtracker, GD Library, Algorithms, Pseudocode, Data Structures.

***

## First, Make it Easy: Refactoring for Rapid Change**

_By Edward Barnard_

Software projects can often become inflexible and hard to change, especially when deadlines are tight. This article explores a pragmatic refactoring technique to make codebases more adaptable to change without extensive rewrites.

**The Challenge of Rigid Code**

When PHP code becomes rigid and changes are needed urgently, you may need to apply a technique that allows for rapid refactoring. Often, it's not possible to do a proper rewrite or add extensive tests. In these situations, the advice "For each desired change, make the change easy (warning: this may be hard), then make the easy change" becomes very useful.

**The Problem: A 1300-Line Class**

In a real-world scenario a 1300-line PHP class, `StripePaymentController`, needed updating. This class handled multiple types of registrations with complex and intertwined logic. This was hard to maintain because the application used different business workflows all in a single place.  The difficulty arose due to an evolving database structure requiring corresponding changes, with different types of registration (regular season, league tournament, national championship and a special case tournament) all using the same class. This mixing of concerns made it hard to change the class safely.

**Applying the Single Responsibility Principle**

The Single Responsibility Principle suggests that a class should have only one reason to change. In this case, the different types of registration were all in the same class, violating this principle. The solution was to separate concerns and make changes easier.

**The Solution: Extracting Methods into Classes**

To make the necessary changes quickly and safely the solution is to extract methods into separate classes. To begin with all `protected` methods should be changed to `private`. This prevents the methods being called from outside the class. Next, each method of the original class was extracted into a new class of its own. For example, a method like `captureFlow()` was extracted into a new class `CaptureFlow`.
```php
class CaptureFlow
{
    public function captureFlow(array $post): string
    {
        $flow = RegistrationWorkflow::captureFlow();
        $_SESSION['registration_flow'] = $flow;
        $_SESSION['stripe_post'] = $post;
        return $flow;
    }
}
```
Then the original class had to be changed to use the new class. The original method was deleted after all references to it had been replaced by the new class. This refactoring method continued for all of the original classes methods. The new `initialize()` method in `StripePaymentController` was used to instantiate all the new classes, including any dependencies.

**The Result: Scattered Code**

This method of refactoring scattered code from one large file into many small files. The original class was reduced