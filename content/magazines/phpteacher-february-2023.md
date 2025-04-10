---
title: The PHP Teacher Magazine - February 2023
publishDate: 2023-04-19 00:00:00
description: Welcome to my premier Cybersecurity company based in London, United Kingdom.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - february
  - 2023
---

Welcome, fellow PHP enthusiasts, to another exciting edition of "The PHP Teacher"! This month, we delve into a diverse range of topics, from the intricacies of debugging with insights from the world of cryptanalysis to the architectural patterns of microservices and the practicalities of securing your applications. We explore the world of headless CMS solutions, the importance of observability, and how to leverage queues and events to build responsive applications. Whether you're a seasoned developer or just starting your journey, this issue has something to enhance your skills and broaden your perspective.

We'll start with **Edward Barnard**'s intriguing piece, "What Can the NSA Teach Us About Debugging?", which draws parallels between code-breaking and problem-solving in software. Then, **Ivo Lukač** guides us through "Headless Possibilities for PHP," exploring the world of decoupled content management. **Chris Tankersley** will educate us about "The Why and How of Building Microservices", which is a useful guide for application architecture. In our "Security Corner," **Eric Mann** sheds light on "Infosec 101: The Confused Deputy," a critical concept for understanding security vulnerabilities. **Joe Ferguson** takes us on a wild ride with "Upgrading with Reckless Abandon: Part 2," demonstrating how to upgrade a legacy Laravel application while trusting the tests. For those who like puzzles **Oscar Merida** presents "Stats 101 Grade Book," a look at calculating averages and their variations in code. **Edward Barnard** returns with "Observability Lab" to discuss how to create a practical experimentation setup with a Raspberry Pi. **Frank Wallen** explores "PSR-15: HTTP Server Request Handlers," offering insights into modern request handling and middleware implementations. **Matt Lantz** explores the world of “Events, Listeners, Jobs, and Queues”, which are a core concept in modern PHP applications. Finally, **Beth Tucker Long** tackles the pressing issue of "Making the Cut," offering advice on improving the hiring process for developers.

So, grab your preferred beverage, settle in, and let's dive into another month of learning and growth together!


## What Can the NSA Teach Us About Debugging?**

By Edward Barnard

_Introduction:_

Debugging, at its core, is a process of deciphering the hidden logic of a system to fix errors. In this article, we'll explore how techniques used by cryptanalysts, particularly those developed by William F. Friedman, can inform our approach to debugging software. Just like breaking codes, debugging requires patience, attention to detail, and a deep understanding of context.

```
        _    _
       / \  / \
      (   \/   )
       \  /\  /
        \/  \/
     Code-Breaking Mindset
```

_Key Concepts:_

*   **Context is King:** Cryptanalysis emphasizes understanding the historical and situational context of a message. Similarly, in debugging, understanding the environment, the data flow, and the user journey is crucial. **Friedman compared ciphers to codebooks** to illustrate this point. If your word or phrase is not in the codebook, it cannot be used, so, understanding what is within the system is crucial for code debugging.
*   **Intuition:** Friedman's work recognised the importance of intuition in the decryption process. A debugger must have an ability to spot the odd things and make educated guesses about errors. This intuition grows from deep experience. He notes that cryptograms with known correspondents and subjects are much easier to solve than isolated ones with unknown variables.
*   **Systematic Analysis:** Friedman stressed the need for meticulous analysis and perseverance. Effective debugging requires methodically ruling out possible causes of errors, step by step.
*   **The Power of Observation:** The Bacon cipher, with its binary code represented by facing or not facing a camera, highlights the importance of spotting hidden patterns. Similarly, in code, understanding the system as a whole, not just in isolated pieces is essential.

_Code Example:_

Here’s an example showing how to debug a very simple PHP function. You might think that the code will always return an integer value but if the input is not numeric it will throw an error.

```php
<?php
function calculateAverage(array $numbers): int {
   if (!is_array($numbers)) {
     throw new InvalidArgumentException('Input is not an array');
   }

   $sum = array_sum($numbers);
    $count = count($numbers);
    if($count === 0){
      throw new InvalidArgumentException('Array can not be empty');
    }
   return (int)($sum/$count);
}
try {
    $result = calculateAverage([1, 2, 'a',4]);
    echo "Average: " . $result;
} catch (InvalidArgumentException $e){
    echo "Error: " . $e->getMessage();
}
?>
```

_References:_

*   William F. Friedman
*   Military Cryptanalytics
*   Bacon cipher
*   Codebook

_Postface:_

Debugging is not just about applying tools, it also requires the mindset of an analyst. Friedman’s teachings on systematic methods and the need for intuition in cryptanalysis offer valuable lessons for PHP developers in their everyday tasks.

_Technology Words:_ Cryptanalysis, Codebook, Cipher, Bacon Cipher, Debugging, Intuition

## Headless Possibilities for PHP**

By Ivo Lukač

_Introduction:_

The concept of "headless" systems, especially headless CMS, has grown in importance in recent years. It refers to a system that provides an API interface for delivering content, decoupling the content management from the presentation layer. This article delves into the pros, cons, and various options for PHP developers working with headless CMS solutions.

```
       _
      (_)
     /   \
    |     |
    \ API /
     -----
     Headless CMS
```

_Key Concepts:_

*   **Headless vs. Traditional CMS:** Traditional CMSs typically handle both content management and presentation. Headless CMSs, on the other hand, only handle content, providing an API for other applications to consume. This decoupling allows for multi-channel content delivery and greater flexibility.
*   **Hybrid/DXP Solutions:** Some traditional vendors have adopted a hybrid approach, adding APIs to their systems, evolving into Digital Experience Platforms (DXPs). However, integrating a sound API with existing systems is not straightforward and might expose technical debt.
*   **Pros of Headless CMS:**
    *   Multi-channel content delivery: "Create Once, Publish Everywhere".
    *   Improved performance with decoupled frontend using JAMstack approaches.
    *   Clear separation of concerns.
*   **Cons of Headless CMS:**
    *   Lack of built-in “head” features like search, media management, and workflows.
    *   Need for custom "head" implementation or use of 3rd-party solutions.
    *   Customizations are limited, especially in cloud-based solutions.
*   **Market Categorization:** The CMS market can be divided into: traditional web-building CMS, monolithic CMS, decoupled CMS, and pure headless CMS solutions. Many CMS solutions have both SaaS and on-premise versions, with APIs available through RESTful, SOAP, or GraphQL.

_Code Example:_

Example of fetching content from a headless CMS (Contentful) using a PHP SDK:

```php
<?php
require 'vendor/autoload.php';
use Contentful\Delivery\Client;

$client = new Client('YOUR_API_KEY', 'YOUR_SPACE_ID');

$query = new \Contentful\Delivery\Query();
$query->setContentType('blogPost');
$entries = $client->getEntries($query);

foreach ($entries as $entry){
    echo "Title: " . $entry->getTitle() . "\n";
    echo "Content: " . $entry->getContent() . "\n";
}
?>
```

_References:_

*   DXP
*   JAMstack
*   RESTful API
*   GraphQL
*   Contentful PHP SDK
*   WordPress as headless

_Postface:_

The move towards headless CMSs offers great potential for PHP developers, especially in building multi-channel content delivery systems. However, it requires careful evaluation of the need for custom "head" features and integrations.

_Technology Words:_ Headless CMS, API, Traditional CMS, DXP, JAMstack, RESTful, GraphQL, PHP SDK, Decoupled Architecture

## The Why and How of Building Microservices**

By Chris Tankersley

_Introduction:_

Microservices have become a popular architectural pattern that breaks down complex applications into smaller, manageable units. This article explores the rationale behind adopting a microservices architecture, its benefits, and provides practical examples of implementation. It also discusses the communication challenges associated with large teams.

```
      [  ]     [  ]    [  ]
      /  \   /  \  /  \
     /    \ /    \/    \
    | MS1  | | MS2 | | MS3 |
     \    / \    /\    /
      \__/   \__/ \__/
     Microservices Architecture
```

_Key Concepts:_

*   **Microservice Definition**: Microservices break a large application into smaller, independent applications focused on specific problem domains. This contrasts with monolithic applications, which handle all functionality within a single codebase.
*   **Team Independence:** Each microservice is maintained by a small, independent team, allowing for parallel development and deployment. This also reduces communication overhead.
*   **Technology Diversity:** Microservices allow teams to choose the best technology stack for each specific service.
*   **Reduced Communication Overhead:** By breaking down teams, a microservice approach reduces the number of communication lines, and makes it easier for teams to stay informed of changes. The number of communication channels is calculated by *n(n - 1) / 2*.
*   **Practical Example:** The article demonstrates building a basic blog using microservices for posts and users. It also includes the use of service discovery, in this case a very basic json file to register and retrieve services.
*   **Encapsulation:** The examples show how functionality is encapsulated as a service rather than using classes from a PHP framework. Microservices are accessed via API requests between different applications.
*   **Scalability:** Microservices can be independently scaled based on their specific demands.

_Code Example:_

Snippet showing a service discovery implementation and consuming the service

```php
<?php
// Service Discovery Client Class
class ServiceDiscoveryClient{

   protected string $serviceDiscoveryUrl = 'http://localhost:8082/api/services';

   public function getServiceAddress(string $serviceName): string {
       $guzzle = new GuzzleHttp\Client();
       $url = $this->serviceDiscoveryUrl . "/" . $serviceName;
       $response = $guzzle->get($url);
       $body = $response->getBody()->getContents();
       $data = json_decode($body, true);
       return $data['address'];
   }
}
// Posts Service Microservice
class PostService{

  protected string $userServiceUrl;

  public function __construct(){
   $serviceDiscovery = new ServiceDiscoveryClient();
   $this->userServiceUrl = $serviceDiscovery->getServiceAddress('users');
  }

  public function createPost(array $postData): array {
    $guzzle = new GuzzleHttp\Client();
    $url = $this->userServiceUrl  . '/api/users/' . $postData['author_id'] .'/validate';
      $response = $guzzle->post($url, [
        'json' => [
           'token' => 'fakeToken'
           ]
      ]);
      if($response->getStatusCode() === 200){
        // save post in database
        return ['message' => "post created"];
     }
     else {
       // error
      return ['message' => 'not authorized'];
     }
  }
}
$post = new PostService();
$result = $post->createPost(['author_id' => 1, 'content' => "example post"]);
print_r($result);
?>
```

_References:_

*   Monolith vs Microservices
*   Service Discovery
*   etcd
*   API Gateway

_Postface:_

Microservices offer a powerful way to manage complex applications, enabling teams to work autonomously and use the best technology for each service. It encourages clear separation of concerns while allowing independent scalability. However, the architecture also requires careful consideration for implementation.

_Technology Words:_ Microservices, Monolithic, API, Service Discovery, API Gateway, Containerisation, Scalability

## Infosec 101: The Confused Deputy**

By Eric Mann

_Introduction:_

The "confused deputy" is a security concept that describes a scenario where a trusted program or system is tricked into misusing its authority. This article dives into the details of this issue and offers practical advice on how to avoid common security vulnerabilities. The "confused deputy" problem is a well-known issue in computer security.

```
         /  \
        |    |
        |    |
      [  Trusted  ]
      | Program |
       \    /
        |  \/
    Malicious Request
```

_Key Concepts:_

*   **The "Confused Deputy"**: A system or program is exploited by an attacker into misusing its own authority or access rights. The system is “confused” about who is requesting the action, a legitimate user or an attacker.
*   **Trust Relationships**: A system that trusts many external systems may be vulnerable if one of those systems becomes compromised. For example, relying on third party providers for things like login, or email.
*   **Cross-Site Request Forgery (CSRF):** An example of a confused deputy attack where a user is tricked into sending a request to a website they did not intend to. This is frequently achieved by using iframes or stolen session tokens.
*   **Threat Modeling:** It’s crucial to anticipate possible attacks and edge cases. Threat modeling is the practice of identifying vulnerabilities and designing additional security controls.
*   **Defense-in-Depth:** A crucial security approach to layer multiple security measures to prevent edge cases. A strong defence prevents confused deputies and minimises damage.
*   **Phishing:** Never reply to suspicious or unexpected email messages. Verify them with a new message directly to the original sender.

_Code Example:_

Example of a simple CSRF implementation using a hidden field:

```php
<?php
session_start();
if (!isset($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<form method="post" action="process.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"/>
    <input type="text" name="data"/>
    <button>Submit</button>
</form>

// process.php
<?php
session_start();
if(hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
    // process data
} else{
    // error
}
?>
```

_References:_

*   Confused deputy problem
*   Cross-site request forgery
*   Defense-in-depth

_Postface:_

Understanding the concept of a "confused deputy" is essential to building secure applications. By thinking through possible attacks, implementing robust security controls, and being aware of edge cases, developers can mitigate the risks associated with this type of vulnerability.

_Technology Words:_ Confused Deputy, CSRF, Threat Modeling, Defense-in-Depth, Security Vulnerability, Phishing

## Upgrading with Reckless Abandon: Part 2**

By Joe Ferguson

_Introduction:_

This article is the second part of a guide on how to upgrade a legacy PHP application. It focuses on a strategy that involves moving code to a fresh project and trusting the tests. The article covers techniques for migrating an application from an older version of Laravel to the latest one.

```
    _______
   /       \
  | Old App |
   \_______/
       |
       V
    _______
   /       \
  | New App |
   \_______/
    Upgrade Process
```

_Key Concepts:_

*   **Reckless Upgrade Strategy:** Creating a new application and migrating the old codebase, leveraging tests as a safety net. This approach is not for meticulous developers and requires confidence in the test suite.
*   **Dependency Management:** Ensuring all application dependencies are compatible with the target framework version. It is essential to verify the compatibility of all packages to avoid issues.
*   **Configuration:** The `config` folder is essential for settings. Modifying `app.php`, `mail.php`, and `services.php` to update mail transport and external services is essential.
*   **Test Suite Migration:** Migrating or re-creating tests to work with the new structure. Model factories and references to models require updating to match the latest framework.
*   **Database Migrations:** Copying migrations to ensure data structure is set up correctly. This also includes handling data transformations that were needed for the old application but not in the new one.
*   **Routes and Controllers:** Copying routes, updating to new controller syntax, and updating model references.
*   **Views and Front-End:** Copying the front end over to the new application. Although a refactor is recommended, for a reckless approach, copying the old one can be sufficient.
*   **Version Control**: The process ends with making a large commit and a messy pull request.

_Code Example:_

Code illustrating migration of a model factory from older Laravel to newer versions:

```php
<?php
// old factory
$factory->define(App\Gift::class, function (Faker $faker) {
    $referrals = \App\Referral::all();
    return [
        'description' => $faker->sentence,
        'amount' => $faker->randomFloat(2, 0, 10000),
        'referral_id' => $referrals->random()->id,
    ];
});
// new factory
class GiftFactory extends Factory
{
     public function definition(): array
    {
         $referrals = \App\Models\Referral::all();
          return [
             'description' => fake()->sentence,
             'amount' => fake()->randomFloat(2, 0, 10000),
            'referral_id' => $referrals->random()->id,
        ];
    }
}
?>
```

_References:_

*   Laravel Daily’s post

_Postface:_

The approach of "reckless abandon" might not be for everyone, but it illustrates the value of a comprehensive test suite in modern PHP development. It is important to always have a safety net when upgrading an application.

_Technology Words:_ Laravel, Framework Upgrade, Dependency Management, Test Suite, Model Factories, Database Migrations, Version Control, Composer

##  Stats 101 Grade Book**

By Oscar Merida

_Introduction:_

Understanding basic statistical concepts is important for any developer who is trying to understand and interpret data. This article revisits fundamental methods for measuring central tendency: the mean, median, and mode. It will also use code to produce graphs and histograms.

```
      (  )   ( )  (   ) (  )  ( )   (   )
      |  |  | |  |   | |  |  | |  |   |
      -----  -----  ----- ----- ----- -----
      Statistics: Mean, Median, Mode
```

_Key Concepts:_

*   **Mean:** The sum of all values divided by the number of values, also called the average.
*   **Mode:** The number, or numbers, that occur most frequently in a dataset.
*   **Median:** The middle value in a sorted data set. It is calculated differently if there are an odd or even number of items.
*   **Histogram:** A way to visualise the distribution of data, showing frequency of observed values.

_Code Example:_

PHP code for calculating mean, mode and median of a list of values:

```php
<?php
$input = '91, 86, 70, 81, 92, 80, 73, 85, 70, 87, 74, 82, 77,83, 90, 90, 87, 83, 93, 72, 84, 87, 83, 73, 86, 81, 86, 77,75, 89, 77, 80, 79, 95, 69, 78, 89, 84, 70, 72, 89';
$grades = array_map(
    fn($i) => (int) trim($i),
    explode(',', trim($input, ','))
);

if ($grades) {
    $mean = bcdiv(array_sum($grades), count($grades));
    echo "The mean is $mean" . PHP_EOL;
}
$freq = array_count_values($grades);
$maxFreq = max($freq);
$modes = array_filter($freq, fn($i) => $i == $maxFreq);
$modes = array_keys($modes);
sort($modes);
echo "The mode(s) with a frequency of $maxFreq are\n" .
    implode(', ', $modes);
sort($grades);
if (count($grades) % 2 === 1) {
    $midKey = floor(count($grades) / 2);
    $median = $grades[$midKey];
} else {
    $lowKey = count($grades) / 2;
    $highKey = $lowKey;
    $median = ($grades[$lowKey-1]+$grades[$highKey]) / 2;
}

echo "The median is $median";
?>
```

_References:_

*   Tab-separated values gist

_Postface:_

These simple statistical measures offer insight into the distribution of data. Knowing how to apply them is essential for any data driven applications. The example also shows some of PHP array functions that help to achieve these results.

_Technology Words:_ Mean, Median, Mode, Histogram, Data Analysis, Statistics, Array Functions

## Observability Lab**

By Edward Barnard

_Introduction:_

This article explores the benefits of creating a dedicated testbed environment using a Raspberry Pi to experiment with various infrastructure tools. It explains why this approach is useful and provides a practical guide on how to set it up with some of the most essential tools for PHP developers.

```
       _
      (_)
     / | \
    |  |  |
   \   |  /
    ----
 Raspberry Pi Testbed
```

_Key Concepts:_

*   **Need for a Testbed:** Having a safe "laboratory" environment to test different technologies and configurations, without impacting the development environment.
*   **Hardware Selection:** Using a Raspberry Pi for its low cost, flexibility, and safety for experimentation.
*   **Operating System:** Installing a full Ubuntu Desktop on the Raspberry Pi to allow easier integration with other tools.
*   **External Storage:** Adding a USB hard drive to store the large volumes of data for indexing and testing of applications. The disk partition and format needs to be checked before use, because some older USB drives use the MBR partitioning scheme that is limited to 2GB.
*   **Infrastructure Focus:** The article discusses the focus on infrastructure for observability, including the ELK stack (Elasticsearch, Logstash, Kibana).
*   **Deployment:** A reproducible deployment process that can also be used for production.

_Code Example:_

A basic example of how to get the disk information of the connected USB hard drive

```php
<?php
function getDiskInfo(string $diskName): array
{
    $command = "sudo fdisk -l | grep " . $diskName;
    exec($command, $output);
    return $output;
}
$diskInfo = getDiskInfo("MyBook");
print_r($diskInfo);
?>
```

_References:_

*   Raspberry Pi 4B
*   Minnesota State High School League Trap Shooting Championship
*   Raspberry Pi Beginner’s Guide
*   Build a LAMP Web Server with WordPress
*   Ubuntu Desktop on Raspberry Pi

_Postface:_

Having a dedicated testbed allows developers to explore infrastructure and create a reliable setup that can be deployed to production. Using a Raspberry Pi also provides an opportunity to learn about the ARM architecture, and other technologies in a safe environment.

_Technology Words:_ Raspberry Pi, Testbed, Observability, Elasticsearch, Logstash, Kibana, Infrastructure, Ubuntu, ARM Architecture

##  PSR-15: HTTP Server Request Handlers**

By Frank Wallen

_Introduction:_

This article introduces the PHP Standard Recommendation (PSR)-15, which defines standards for handling and responding to HTTP requests using request handlers and middleware. The main goal of PSR-15 is to standardise the implementation of middleware for HTTP applications.

```
     [Request]
        |
      [Middleware 1]
        |
      [Middleware 2]
        |
    [Request Handler]
        |
     [Response]
  PSR-15 Middleware Flow
```

_Key Concepts:_

*   **PSR-15 Overview:** This PSR focuses on standardising how HTTP requests are processed through handlers and middleware. It proposes standards for interfaces for both of these items.
*   **Request Handler:** An object that processes a server request and returns a response. It relies on PSR-7 for the HTTP message definition.
*   **Middleware:** Components that intercept requests and responses before they reach the application’s main handler. Middleware can be arranged in a chain to modify or filter requests.
*   **Double-Pass and Single-Pass**: Two ways that the Middleware implementation is defined in the PSR-15 documents.
*   **Middleware Implementation:** Various ways in which middleware can be implemented, including passing a middleware array to the constructor of the request handler.
*   **Flexibility**:  The PSR allows the developer to make their own decision in the implementation of both middleware and the request handlers.

_Code Example:_

Example of a Request Handler and Middleware implementation using PSR-15:

```php
<?php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
class AuthenticationMiddleware implements MiddlewareInterface {
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    $authHeader = $request->getHeaderLine('Authorization');
    if ($authHeader !== 'valid-token'){
      $response = new \Laminas\Diactoros\Response();
      return $response->withStatus(401);
    }
     return $handler->handle($request);
  }
}
class RequestHandler implements RequestHandlerInterface
{
   public function __construct(protected AuthenticationMiddleware $middleware, protected ResponseFactory $responseFactory){}
   public function handle(ServerRequestInterface $request): ResponseInterface
   {
    return $this->middleware->process($request, $this->responseFactory->createResponse(200));
   }
}
$factory = new \Laminas\Diactoros\ResponseFactory();
$handler = new RequestHandler(new AuthenticationMiddleware(), $factory);
$request = (new \Laminas\Diactoros\ServerRequest())->withHeader('Authorization', 'invalid-token');
$response = $handler->handle($request);
echo $response->getStatusCode();
?>
```

_References:_

*   PSR-15
*   PSR-7
*   Server Request Handlers for Middleware
*   HTTP Server Middleware
*   Wikipedia Middleware definition
*   PSR-15 meta document

_Postface:_

PSR-15 provides the standards for building flexible and reusable middleware for PHP applications. Using a common standard promotes interoperability across frameworks and allows the developer to decide the implementation details according to the application.

_Technology Words:_ PSR-15, Request Handler, Middleware, PSR-7, HTTP Request, HTTP Response, Double-Pass, Single-Pass

## Events, Listeners, Jobs, and Queues Oh my!**

By Matt Lantz

_Introduction:_

This article explores Laravel’s event system and queue capabilities, and how to use them to create more responsive and maintainable PHP applications. The use of events, listeners, queues, and jobs allows the developer to decouple different parts of the application and achieve better separation of concerns.

```
     [Event]
       |
    [Listener 1] [Listener 2]
       |          |
     [Queue]    [Queue]
       |          |
    [Job 1]    [Job 2]
   Event-Driven Architecture
```

_Key Concepts:_

*   **Events and Listeners:** Events signal something has happened in the application and listeners perform some action in response. Events can trigger multiple listeners, and listeners can also be queued for later execution.
*   **Observers**: Observers can be used to keep all actions related to Model Events in one file.
*   **Queues:** Queues enable deferring the processing of tasks, usually to enhance application response times.  Queue workers run in the background, monitor queues and handle jobs as they appear.
*   **Jobs:** Jobs encapsulate a specific task that will be placed in a queue. These are particularly useful when complex processing needs to occur without delaying the response time for a request.
*   **Deferred Processing:** Sending emails, generating reports, or pushing to external APIs, can be put into queues and managed outside of the request-response cycle.

_Code Example:_

Laravel code showing how to define events and listeners and dispatching a job:

```php
<?php
// Event
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
class ReportGenerated{
  use Dispatchable;
    public function __construct(public $bookmark) {}
}
// Listener
namespace App\Listeners;
use App\Events\ReportGenerated;
use App\Jobs\GenerateReport;
class ReportEventListener {
   public function handle(ReportGenerated $event) {
     GenerateReport::dispatch($event->bookmark);
   }
}
// Job
namespace App\Jobs;
use App\Models\Bookmark;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class GenerateReport implements ShouldQueue
{
 use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
   public function __construct(public Bookmark $bookmark) {}
   public function handle(){
     // do something here
   }
}
// EventServiceProvider
namespace App\Providers;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
class EventServiceProvider extends ServiceProvider{
 protected $listen = [
  ReportGenerated::class => [
      ReportEventListener::class
    ]
  ];
}
//dispatch event and process queue
event(new ReportGenerated($bookmark));
?>
```

_References:_

*   Laravel Documentation

_Postface:_

Events, Listeners, Jobs, and Queues are important concepts for building modern PHP applications and improving user experience. They also promote a clean separation of concerns and allow developers to create more scalable and responsive systems.

_Technology Words:_ Events, Listeners, Queues, Jobs, Laravel, EventServiceProvider, Observers, Asynchronous Processing, Separation of Concerns

## Making the Cut**

By Beth Tucker Long

_Introduction:_

This article discusses the challenges of hiring developers and offers practical advice for improving the hiring process. It emphasizes the importance of evaluating a developer’s soft skills and understanding their problem-solving capabilities, in addition to their technical abilities.

```
   [  Interviews ]
      /   |   \
   [Skills] [Soft Skills] [Practical]
        Hiring Process
```

_Key Concepts:_

*   **Developer Shortage:** The article highlights the current global shortage of developers.
*   **Re-evaluate Hiring Practices:** Current practices, such as asking candidates to build projects at home, and focusing on theoretical knowledge are not useful for determining the candidates suitability.
*   **Focus on Soft Skills:** Emphasizing a candidate’s ability to solve problems creatively, work within a team, and communicate well.
*   **Practical Skills:** Asking practical questions or evaluating candidates on their real-world skills, such as securing a form, or setting up a blog.
*   **Hiring Junior Developers:** The need to provide opportunities to newer developers to allow them to become the senior developers of the future.

_Code Example:_

Not a specific code example, but some suggested questions for a practical interview:

*   How would you secure a contact form to prevent common vulnerabilities?
*   Describe the process of setting up a basic blog application.
*   How would you debug a problem in a large codebase?
