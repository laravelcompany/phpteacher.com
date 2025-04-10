---
title: PHP Teacher magazine - September 2023
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - september
  - 2023
---

Welcome to another exciting edition of *The PHP Teacher Magazine*! This month, we delve into a diverse range of topics that are crucial for every PHP developer, from the foundations of asynchronous programming to the latest trends in full-stack development, and even touching on important considerations for accessibility and data privacy. We’ve got a packed issue that will not only expand your technical knowledge but also challenge you to think differently about how you build and deploy applications.

In this issue, we'll explore how to modernise a monolith application, achieve asynchronous operations without external libraries, and even design a custom ticketing system. We will also delve into the ongoing browser wars and their impact on web development, the implications of AI on data privacy and the need to protect your data. We also look at the need for inclusive design in your websites and how to best sort your data, finally we take a look at tools and techniques that will make your code more performant.

As always, our goal is to equip you with the knowledge and practical skills you need to stay ahead in the fast-paced world of PHP development. So, grab a cup of tea, settle in, and let's get started!



## Getting Modern with Our Monolith**

```
      _    _
     (o  o)
   /   \/   \
  /      /\  \
 /________\/__\
  Monolith Rising
```

In this article, Steve McDougall discusses the challenges of maintaining separate front-end and back-end repositories, highlighting the issues of synchronising releases and keeping momentum equal. He introduces **InertiaJS as a middleware application** that sits between a Laravel application and the chosen front-end framework. InertiaJS allows rendering of front-end components using JavaScript, similar to NextJS or NuxtJS. When a new route is requested, InertiaJS fetches the new component over XHR, re-renders the DOM, and updates the browser’s history API.

McDougall emphasizes that this approach simplifies development compared to traditional Single Page Applications (SPAs), which often require maintaining two separate codebases. With InertiaJS, developers can use a solid front-end framework to interact with a robust back-end ecosystem. He also cautions against the "everything is a SaaS" trend in full-stack JavaScript, which can lead to a complex web of subscriptions and widgets.

The article provides a step-by-step guide to setting up a full-stack modern monolith using Laravel and InertiaJS. The process involves using the Laravel Installer, adding Laravel Breeze for authentication with VueJS and TypeScript, and setting up server-side rendering. A key component is the `HandlesInertiaRequests` middleware class, which allows the sharing of global props with all front-end pages. This middleware is where persistent properties, like the authenticated user, are registered. A common example is using a "meta" model, like countries, cached to avoid repeated database queries.

The article includes a code example showing how to conditionally load data based on the authentication state and how to cache data. It also discusses how controllers are structured in Inertia, using the `Inertia::render` method to load components. McDougall presents a trait, `HasInertiaResponse`, to inject the Inertia Response Factory directly into controllers, avoiding repeated look-ups.

Further, the article details how forms work in Inertia, using the form helper to create reactive forms with two-way binding in VueJS. It demonstrates different ways to handle responses, including returning data on the first visit, optionally including data on partial reloads, and using lazy loading. The article also highlights the testing helpers available in Inertia that help when paired with PestPHP.

In closing, McDougall advises against using anything other than Filament for building internal dashboards and recommends InertiaJS as "the much needed glue to hold it all together" for Laravel and VueJS.

**References:**
*  

**Code Examples:**

*   Laravel Installation

```bash
laravel new github-browser --git --pest
composer require --dev laravel/breeze
```

*   Middleware class

```php
public function share(Request $request): array
{
  $auth = Auth::check();
    return array_merge(parent::share($request), [
    'auth' => [
    'user' => $auth ?
        new UserResource(
            resource: Auth::user(),
        ) : null,
    ],
    'projects' => $auth ?
        ProjectResource::collection(
            resource: Cache::remember(
                key: Auth::id() . '-projects',
                ttl: CacheTime::HOUR->value * 5,
                callback: static fn () =>
                    Project::query()
                        ->where('user_id', Auth::id())->get(),
            )
        ) : null,
    'ziggy' => function () use ($request) {
        return array_merge((new Ziggy)->toArray(), [
            'location' => $request->URL(),
        ]);
    },
  ]);
}
```

*   Controller Example

```php
public function __invoke(Request $request): Response
{
  return Inertia::render('PageName/Component');
}
//with trait
final class IndexController
{
    use HasInertiaResponse;

    public function __invoke(Request $request): Response
    {
        return $this->response
            ->render('PageName/Component');
    }
}
```

## Asynchronous PHP without External Libraries**

```
      (  )   (  )
      \  /   \  /
       \/     \/
   -----   -----
   Async PHP Flow
```

Vinicius Dias explores asynchronous programming in PHP without using external libraries. He defines asynchronous programming as non-blocking I/O, which means the ability to handle messages when possible instead of immediately. This contrasts with synchronous I/O, where the CPU remains idle while waiting for an operation to complete.

The article explains how PHP streams are used to handle I/O operations, such as accessing files, HTTP requests, and sockets. Dias introduces the `stream_select` function, which is used to monitor changes in the status of streams. He uses an example of reading the content of five files to illustrate the use of `stream_select`. The traditional approach would be to use `file_get_contents` sequentially, which blocks the execution until each file is completely read.

Instead, the asynchronous solution opens all the files using `fopen` and sets them to non-blocking mode with `stream_set_blocking`. The `stream_select` function is called in a loop to check which files are ready to be read. The first three parameters of this function are passed by reference, with the first being the list of streams to observe for reading. The return value indicates the number of streams that have updates, and the parameters are modified to contain only those streams. After reading the content, it closes the file, and removes the file from the list of streams being read.

The article details how to use sockets to perform asynchronous HTTP requests, thereby showing that any network operation can be performed asynchronously. The example demonstrates sending an HTTP request to a web server using sockets, and handling the response without blocking the execution.

Dias highlights that though this approach is complex, understanding it is crucial to grasp how tools like ReactPHP and Swoole work behind the scenes. He emphasises the importance of knowing how to create an Event Loop and use it effectively. He states that the main reason for learning "bare metal" PHP is to understand how tools like ReactPHP and even Swoole work behind the scenes.

**References:**
*  

**Code Examples:**

*   Synchronous File Reading

```php
$fileContent1 = file_get_contents('file1.txt');
$fileContent2 = file_get_contents('file2.txt');
$fileContent3 = file_get_contents('file3.txt');
$fileContent4 = file_get_contents('file4.txt');
$fileContent5 = file_get_contents('file5.txt');
```

*   Asynchronous File Reading using `stream_select`

```php
$fileStreamList = [
    fopen(__DIR__ . '/text_files/file1.txt', 'r'),
    fopen(__DIR__ . '/text_files/file2.txt', 'r'),
    fopen(__DIR__ . '/text_files/file3.txt', 'r'),
    fopen(__DIR__ . '/text_files/file4.txt', 'r'),
    fopen(__DIR__ . '/text_files/file5.txt', 'r'),
];

foreach ($fileStreamList as $fileStream) {
    stream_set_blocking($fileStream, false);
}

do {
    $streamsToRead = $fileStreamList;
    $streamsWithUpdates = stream_select(
        $streamsToRead,
        $write,
        $except,
        seconds: 1,
        microseconds: 0
    );
  if ($streamsWithUpdates === false) {
        echo 'Unexpected error';
        exit(1);
    }
    if ($streamsWithUpdates === 0) {
        continue;
    }
    foreach ($streamsToRead as $index => $fileStream) {
        $content = stream_get_contents($fileStream);
        echo $content . PHP_EOL;
        if (feof($fileStream)) {
            fclose($fileStream);
            unset($fileStreamList[$index]);
        }
    }
} while (!empty($fileStreamList));
```

*   Asynchronous HTTP Request via Socket

```php
$fileStreamList = [
    fopen(__DIR__ . '/text_files/file1.txt', 'r'),
    fopen(__DIR__ . '/text_files/file2.txt', 'r'),
    stream_socket_client("tcp://example.com:80", $errno, $errstr),
    fopen(__DIR__ . '/text_files/file3.txt', 'r'),
    fopen(__DIR__ . '/text_files/file4.txt', 'r'),
    fopen(__DIR__ . '/text_files/file5.txt', 'r'),
];

foreach ($fileStreamList as $fileStream) {
    stream_set_blocking($fileStream, false);
}

$httpRequest = 'GET / HTTP/1.1' . "\r\n";
$httpRequest .= 'Host: example.com' . "\r\n";
$httpRequest .= 'Connection: close' . "\r\n";

fwrite($fileStreamList, $httpRequest . "\r\n");
do {
    $streamsToRead = $fileStreamList;
    $streamsWithUpdates = stream_select(
        $streamsToRead,
        $write,
        $except,
        seconds: 1,
        microseconds: 0
    );
    if ($streamsWithUpdates === false) {
        echo 'Unexpected error';
        exit(1);
    }
    if ($streamsWithUpdates === 0) {
        continue;
    }
    foreach ($streamsToRead as $index => $fileStream) {
        $content = stream_get_contents($fileStream);
        $bodyOffset = strpos($content, "\r\n\r\n") + 4;
        if ($bodyOffset !== false) {
            echo substr($content, $bodyOffset);
        } else {
            echo $content;
        }
        if (feof($fileStream)) {
            fclose($fileStream);
            unset($fileStreamList[$index]);
        }
    }
} while (!empty($fileStreamList));
```

## An Overnight Covid Ticketing System**

```
    _      _
   / \    / \
  |   |  |   |
  \___/  \___/
   Ticket System
```

Raja Renga describes the development of a COVID-19 ticketing system built in response to an urgent request from a healthcare client. The system was needed for Bengaluru Metropolitan and had to be operational within a day. Due to time constraints, existing SaaS solutions were not suitable. The team leveraged their prior experience with custom reporting solutions to develop the system quickly.

The ticketing system included patient medical and demographic details, with nearly 50 attributes, 24 masters, conditional informational collection fields, and a dozen departments with approximately 500 users. The workflow involved a toll-free number, call agents, and routing tickets to various experts and public departments.

The system was developed and deployed in a Government server within a day, and handled around 55,000 tickets over the next two months. It was then extended to the entire Karnataka state and updated to meet new needs, handling an additional 15,000 tickets.

Renga introduces Fibenis, a homegrown information system used to build the ticketing application. **Fibenis is an adaptive full-stack base development system evolved from communication patterns and natural language principles**, based on component-based development and continuous improvement. It emphasizes the Browse, Read, Edit, Add, and Delete (BREAD) functions as a core component. The system is designed to handle data through communication patterns similar to human communication, using concise terms based on context.

In Fibenis, structured communication is key, using key-value pairs to define forms and desks. The system uses array-based inputs called definitions that can define a module, either as form or desk. The system is built on a modular approach, using engines and definitions, to improve reusability, scalability, and maintainability. There are several different types of engines like Template Series and Arrow Series to handle different kinds of requests.

The system stores data using flat relational tables, which then evolved to using an Entity-Attribute-Value (EAV) model to handle dynamic scalability. The EAV model allows adding columns without changing the base table structure. The EAV model provides a solution for scaling the column information dynamically and reducing the wastage of memory.

The article also discusses an EAV modeler that helps generate form and desk series definitions using a form add-on sub-engine. The EAV modeler includes an Attribute Builder, Entity Child Management Auto, Entity Child Base, Entity Key Value, and Entity Status Map. This modeler allows for better management of entity-related information in a single interface.

The article details a critical moment when a report on complete ticket information was requested on short notice, and the Fibenis template series capability helped produce the needed report in time. The article concludes with an overview of the steps taken to deploy the system and how they tackled the challenges during implementation. This includes separating different tasks and handling them progressively using the character matrix.

**References:**
*  

**Code Examples:**

*   Minimal Form Input

```php
$F_SERIES = [
    'title' => 'Flat DB',
    'table' => 'demo',
    'key_id' => 'id',
    'fields' => [
        1 => [
            'label' => 'Text',
            'id' => 'text_flat',
            'type' => 'text',
            'is_must' => 1,
            'allow' => 'x30',
        ],
    ],
];
```

*   Minimal Desk Input

```php
$D_SERIES = [
    'title' => 'Demo Flat',
    'table_name' => 'demo',
    'key_id' => 'id',
    'fields' => [
        'tx' => [
            'label' => 'Text ',
            'id' => "text_flat",
            'head' => ' width="80%"',
            'attr' => [
                'class' => 'fbn-h5',
            ],
            'is_sort' => 1,
        ],
    ],
    'is_add' => 1,
    'is_del' => 1,
    'is_edit' => 1,
];
```

*  Pseudo definition for EAV

```php
$F_SERIES = [
    'title' => 'Masters',
    'table' => 'entity_child',
    'key_id' => 'id',
    'default' => ['entity_code' => 'MT'],
    'fields' => [
        1 => [
            'label' => 'Code',
            'child_table' => 'entity_attribute_char',
            'parent_field_id' => 'entity_child_id',
            'child_attr_field_id' => 'attribute_code',
            'child_attr_code' => 'MTCD',
            'id' => 'value',
            'type' => 'text',
            'is_must' => 1,
            'allow' => 'x2',
        ],
    ],
];
```

##  We Are Losing the Browser War**

```
    /----\
   |  ()  |
  /------\
  Browser Battle
```

Chris Tankersley discusses the history of browser wars, drawing parallels between the past and the current dominance of Google Chrome. He highlights the first browser war between Netscape Navigator and Internet Explorer, and how Microsoft's dominance stifled innovation and led to security issues.

The article emphasizes that Netscape Navigator, initially the dominant browser, was eventually overtaken by Internet Explorer. Microsoft used its position to introduce proprietary features that did not adhere to open standards, resulting in a fragmented web experience and stagnated web standards. The deep ties with the Windows operating system made Internet Explorer a target for malware.

Tankersley then examines the rise of Google Chrome, which was introduced in 2008. Chrome's minimalist design, speed, and integration with Google services contributed to its rapid adoption. Google also actively marketed Chrome, which led to its pervasive presence. This dominance has led to concerns similar to those raised during the Internet Explorer era.

He notes that Chrome's market share is so significant that Google has considerable influence over the web's direction, leading to concerns about centralization of power and stifled innovation. Furthermore, Google's revenue model, based on advertising, raises privacy concerns. It is not about blocking third-party cookies but changing the way the information is shared.

The article notes that there is a possibility that innovation could be impacted, with market dominance leading to complacency. This can lead to a situation where developers focus primarily on optimizing for Chrome to the detriment of users of other browsers.

Tankersley presents alternatives to Chrome like Mozilla's Firefox and Apple's Safari, highlighting the different rendering engines and base technologies that the various browsers are built on. Most alternative browsers, however, are based on Blink/Chromium, technology built by Google. The article then emphasises the importance of having multiple implementations for standards, rather than a single entity driving and controlling it.

He concludes by stressing the need for diversity and inclusivity in the web and how it is a shared responsibility for both developers and users. By not focusing on a monolithic web, it ensures that the internet remains a place for innovation and free thought.

**References:**
*  

**Code Examples:**

*   There are no specific code examples in this article

## The Apocalypse Is Now**

```
   _    _
  / \  / \
 |   \/   |
  \  /\  /
   \/  \/
   Data Apocalypse
```

Eric Mann warns of a different kind of "AI Apocalypse," not the fictional killer robots, but the current issues surrounding data ownership and control by companies training AI models. He highlights how user data is being scraped and hoarded to train AI models for various purposes without consent. He then differentiates between data science (DS), machine learning (ML), and artificial intelligence (AI).

DS is the general study of information, involving building deep expertise to identify patterns in data. ML is a subset of AI that involves creating algorithms that allow computers to learn from data to predict outcomes. AI is similar to ML but asks computers to produce inferences without training. The article notes that there is a rise of artificial narrow intelligence with tools like Photoshop's content-aware fill and generative tools like ChatGPT.

Mann uses Zoom as an example of companies updating their terms of service to use customer data to train their AI models. The new terms of service give them extensive rights to use the data without limiting whether they can sell the data to third parties. This lack of control raises serious concerns about data ownership.

The article provides a method to opt out of content scraping by adding a user-agent block in the `robots.txt` file.

```
User-agent: GPTBot
Disallow: /
```

The article emphasises that you are still relying on the honesty of organisations like OpenAI to respect the flag.

Mann states that the best way to avoid becoming a data source for AI models is to keep data out of the system by understanding how data is being mined and investing in keeping private information private. He urges the reader to take responsibility for understanding the use of their data and fighting back at the data science level.

**References:**
*  

**Code Examples:**

*   robots.txt example

```
User-agent: GPTBot
Disallow: /
```

## Unseeable Colors**

```
    * * * * *
   *       *
  *         *
 *           *
  *         *
   *       *
    * * * * *
 Blind View
```

Maxwell Ivey shares his experience as a visually impaired person navigating e-commerce sites, highlighting the difficulties in understanding color descriptions. He notes that many websites lack proper image descriptions and alt text, making it difficult for users with visual impairments to fully understand the products they are browsing. Ivey states that people with vision loss perceive the world differently, and this must be taken into account when designing websites.

The article suggests two ways to make colour names understandable to all users:
*   Use detailed color descriptions in the `alt` text tags for images, which might require additional data entry fields.
*   Code a button that fetches more detailed color descriptions when a user needs them.

Ivey argues that providing more detailed information can boost customer confidence, reduce returns, and improve the overall user experience. By making color descriptions more accessible, e-commerce sites can enhance the user experience for all customers. He emphasizes the importance of building relationships and initiating conversations to find better solutions for creating a more inclusive online world.

**References:**
*  

**Code Examples:**

*   There are no specific code examples in this article.

## Comb Sort**

```
  / \  / \ / \ / \
 |   ||   ||   ||
 \_/  \_/ \_/ \_/
   Comb Sort
```

Oscar Merida explores the Comb Sort algorithm, a more efficient sorting algorithm compared to the Bubble Sort. He begins with a brief recap of the Bubble Sort, noting its drawback of comparing adjacent elements, which results in "turtles"—small elements at the end of the array that require many loops to move to the beginning. Large values near the start of the array are called “rabbits”.

The Comb Sort addresses the limitations of Bubble Sort by comparing non-adjacent elements using a "gap," initially comparing elements far apart and gradually reducing the gap until they are adjacent. This makes it faster at moving both rabbits and turtles.

Merida provides an implementation of the Comb Sort algorithm and discusses the importance of the shrink factor. The shrink factor is used to reduce the gap between compared elements. He notes that a shrink factor of 1.3 is the most ideal based on empirical testing. Values too small slow the algorithm, and values too large make it inefficient at handling turtles.

The article includes a benchmark comparison between Comb Sort and Bubble Sort, showing that Comb Sort performs much better, especially as the array size increases. He emphasizes how a slight change to the Bubble sort makes a huge change in performance.

**References:**
*  

**Code Examples:**
*   Comb Sort Function

```php
function comb_sort(array &$list): void
{
    $max = count($list);
    $shrinkFactor = 2;
    $gap = ceil($max / $shrinkFactor);
    do {
        $swapped = false;
        for ($i = 0; $i < $max - $gap; $i++) {
            if ($list[$i] > $list[$i + $gap]) {
                $tmp = $list[$i];
                $list[$i] = $list[$i + $gap];
                $list[$i + $gap] = $tmp;
                $swapped = true;
            }
        }
    $shrinkFactor = 1.3;
        $gap = ceil($gap / $shrinkFactor);

    } while ($gap > 1 || $swapped);
}
```

##  Create Observability, Part 4: Simple Queue System**

```
    _______
   /       \
  |  Queue  |
  \_______/
   System Flow
```

Edward Barnard describes the design of a simple queue system using MySQL database tables to move offline processes for asynchronous processing. This article builds upon a previous article that redesigned a business process using a flowchart. Here, he discusses moving the "post Reserve Week scores" process offline.

The article discusses the current system of posting Reserve Week scores, which includes sending multiple emails that are slowing down the website. He outlines the process which is to list teams with no scores for the week, and posting scores from the reserve week if required. To address this issue, the process of sending emails will be moved to an offline process by inserting records into the `email_requests` table.

Barnard explains the "assembly line" approach, with workers taking items from an input queue, processing them, and passing new items to the next worker. In his example the first worker will take one item from `email_requests` and break it down into 60 separate requests for emails. The system is designed to scale as the workload increases.

He uses Dave Pomeroy’s design for the simple queue, and the `email_requests` table is the first input queue. The table uses a unique reservation code and timestamp to track the items in the queue. He explains that each worker will attempt to reserve a record, verify the reservation, start the work by setting a started timestamp, complete the work and mark as complete by updating the completed timestamp. Each step will be performed within the worker process.

The article then gives a detailed explanation of the table and how the reservation process works. The article concludes by summarising what was covered and explains that next month will go through the redesign in full, and include metrics for observability.

**References:**
*  

**Code Examples:**
*   `email_requests` Table Schema

```sql
CREATE TABLE `email_requests`
(
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `reservation_code` char(32) DEFAULT NULL,
    `reserved_at` timestamp(6) NULL DEFAULT NULL,
    `started` timestamp(6) NULL DEFAULT NULL,
    `completed` timestamp(6) NULL DEFAULT NULL,
    `queue_parameters` mediumtext NOT NULL
    COMMENT 'PHP serialize()',
    `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reservation_code` (`reservation_code`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;
```

## Exploring Real-World Applications of PHP-FIG’s PSRs**

```
   _     _
  (_)   (_)
 /   \ /   \
|  PSR-11 |
 \     /
   \ /
    V
  PHP Standards
```

Frank Wallen explores the practical implementation of PHP-FIG’s PSRs, particularly PSR-11 (Container Interface), using the Container library from The League of Extraordinary Packages. He emphasizes the importance of PSRs in promoting interoperability and consistency within the PHP ecosystem.

The article explains that PSR-11 aims to standardise how frameworks and libraries use containers to obtain objects and parameters. The minimum requirement is two methods, `get` and `has`. The `get` method returns a value associated with an identifier and throws an exception if not found, and the `has` method returns true or false based on if it has been registered.

The container library goes well beyond the basic requirements of PSR-11, offering methods for configuring and retrieving dependency definitions