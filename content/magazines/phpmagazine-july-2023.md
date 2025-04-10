---
title: PHP Teacher magazine - July 2023
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - july
  - 2023
---


Welcome to the July edition of php teacher! This month, we're diving into a diverse range of topics, from making your websites more accessible to exploring the cutting edge of PHP performance and deployment strategies. We also delve into database management, ethical considerations in coding, and user experience, ensuring there’s something for every PHP developer. As always, we aim to provide practical insights and actionable knowledge to enhance your skills and projects. This issue is packed with articles to inspire and inform your work, making sure we keep pushing the boundaries of what’s possible with PHP.

##Be Barrier Free

##### Making the Web Accessible to All
 
  
 ```text
       __   _
      /  \ | |
     / /\ \| |
    / /  \ | |
   /_/   \_|_|

```
 Many developers think that accessibility is only a concern for massive websites like Google or Facebook. However, the truth is that every website should be designed with accessibility in mind. Many websites create barriers to usability, often because developers code for the "happy path" and do not consider the needs of all users. Just because nobody has complained doesn't mean there isn't a problem. We need to make more thoughtful decisions based on all the data at our disposal. We at php teacher are committed to helping you make your websites barrier-free. 

 **Understanding the Need** 
 
 Consider the perspective of a user who relies on a screen reader. They need to navigate websites using the tab key or by headings, and every piece of content, including images, must be properly labelled. This is why alternative text tags for images are so important. Proper heading structure and a consistent page layout are crucial for users of assistive technologies. Without these, the website becomes a confusing maze.
 
 **The Prisoner's Dilemma** 
 
 The current situation on the web is similar to the Prisoner's Dilemma. Developers often prioritize getting the job done rather than thinking about the bigger picture of accessibility. This results in websites that are not inclusive, and a failure to consider edge cases and the full range of potential users. It's crucial to consider not only how an application works but also how it will be used. Ethical considerations must be a key part of technical design. This includes anticipating and addressing edge cases, like unexpectedly long surnames or oversized PDFs.

 **Practical Steps** 
 
 Here are some steps that can help you improve the accessibility of your website:
 
 *   **Use alt text for all images**: Ensure that every image has a descriptive alternative text tag.
 *   **Organize content logically**: Use headings consistently to structure your content.
 *   **Ensure keyboard navigation**: Make sure users can access all elements on the page using the keyboard, not just a mouse.
 *   **Avoid clutter**: Keep the page structure simple, with minimal images, links and buttons per page.
 *   **Test with assistive technologies**: Use screen readers and other assistive technologies to ensure that your website is usable by all.
 
 By taking these steps, you can make the web a more inclusive place for everyone.

 **References:**
 *.
 
 ## Symfony Image Uploads With Cloud Static Object Storage
 
 ##### Scaling Your File Storage
 
 ```text
      _    _
     ( \  / )
      \ \/ /
      / /\ \
     (_/  \_)
 
 ```
 In today's world, storing files locally on the web server is becoming a thing of the past. Scalable and automated deployments require that uploaded files be available to all servers. Cloud static object storage services, like AWS S3 and Digital Ocean Spaces, offer cheap, scalable, and reliable alternatives. This article will guide you through using the AWS SDK for PHP and the Flysystem library to manage file uploads in your Symfony application.
 
 **Using the AWS PHP SDK** 
 
 The AWS SDK for PHP provides a way to interact with AWS services. To use the SDK, you first need to install it with composer: `composer require aws/aws-sdk-php`. After setting up an S3 bucket, you can use the SDK to upload, delete, and list objects in the bucket. It's critical that your access keys are not committed to your source code repository.
 
 Here’s a basic example of uploading a file using the SDK:
 ```php
 require 'vendor/autoload.php';
 
 use Aws\S3\S3Client;
 use Aws\Exception\AwsException;
 
 try {
  $s3Client->putObject([
   'Bucket' => 'acme-bucket',
   'Key' => 'profile.jpg',
   'Body' => fopen('/path/to/profile.jpg', 'r'),
  ]);
 } catch (Aws\S3\Exception\S3Exception $e) {
  echo 'Error: ' . $e->getAwsErrorMessage() . PHP_EOL;
 }
 ```
 The key acts as the path and filename for the object, which is used to manipulate and serve the object.
 
 **Abstracting with Flysystem** 
 
 To further abstract the functionality of storing files, you can use the Flysystem library. Flysystem provides an abstraction layer that allows you to swap easily between storing on the filesystem, in memory, or using several cloud storage providers. To set this up in Symfony, install: 
 ```bash
 composer require league/flysystem-bundle
 composer require league/flysystem-aws-s3-v3
 composer require vich/uploader-bundle
 ```
 The `vich/uploader-bundle` is used to handle the file uploads in your forms.
 
 **Configuration** 
 
 You need to set up environment variables in your `.env` files, which should not be committed to git. Here's an example of `.env.dev.local` file:
 ```text
 AWS_ACCESS_KEY="ABC123"
 AWS_SECRET_KEY="abcxyz123"
 AWS_REGION="us-east-1"
 AWS_STORAGE_BUCKET="acme-bucket"
 FLYSYSTEM_ADAPTER_STORAGE="acme.storage.aws"
 ```
 The `FLYSYSTEM_ADAPTER_STORAGE` variable toggles between different storage options. Next, configure the AWS client adapter in `services.yaml`:
 ```yaml
 # config/services.yaml
 services:
  acme.s3_client:
   class: Aws\S3\S3Client
   arguments:
   version: '2006-03-01'
   region: "%env(AWS_REGION)%"
   use_aws_shared_config_files: false
   credentials:
   key: "%env(AWS_ACCESS_KEY)%"
   secret: "%env(AWS_SECRET_KEY)%"
 ```
 Now, configure the storage adapters in `config/packages/flysystem.yaml`:
 ```yaml
 # config/packages/flysystem.yaml
 flysystem:
  storages:
   acme.storage.local:
    adapter: 'local'
    options:
    directory: '%kernel.project_dir%/.../storage/'
   acme.storage.aws:
    adapter: 'aws'
    visibility: public
    options:
    client: 'acme.s3_client'
    bucket: '%env(AWS_STORAGE_BUCKET)%'
    prefix: '%env(APP_ENV)%/uploads/storage/'
    streamReads: true
   acme.storage:
    adapter: 'lazy'
    options:
     source: '%env(FLYSYSTEM_ADAPTER_STORAGE)%'
 ```
 Finally, you can configure your User entity and form type to handle image uploads. Use a Twig filter (`flysystem_asset`) to generate public URLs for your uploaded files.

 **Digital Ocean Spaces** 
 
 Digital Ocean Spaces offers a service compatible with the AWS S3 API, which can be used with the same Flysystem setup. By using Flysystem, switching between storage methods is transparent and easy.
 
 **Important Note** 
 
 Cloud storage is billed via a combination of the volume of data stored and the upload/download bandwidth. Keep an eye on your billing plan for these services to avoid surprises.
 
 ## Exploring PHP 8’s JIT Compiler: Performance Boosts and Limitations
 
 ##### Speeding Up Your PHP Code
 
 ```text
    ___
   /   \
  |  _  |
  | (_) |
   \___/
 ```
 PHP 8 introduced a Just-In-Time (JIT) compiler that can significantly improve the performance of your applications. This feature compiles PHP code directly into machine code, providing a major performance boost, potentially doubling the speed of execution. In this article, we'll examine how the JIT compiler works, what benefits it provides and what its limitations are.
 
 **How the JIT Compiler Works** 
 
 A JIT compiler compiles code into machine code immediately before it is executed. This is different from the traditional approach of converting source code to an intermediary language, which is then pre-compiled. PHP 8’s JIT compiler optimises and compiles the code while it is being executed. The main optimisations it uses include: 
 
 *   **Inline caching**: This process uses a "call" cache to record recent parameters and return values of function calls to avoid repeated executions.
 *   **Guarded optimizations**: This skips redundant operations within single function calls that have already been done, to ensure routines run faster.
 *   **Optimistic type speculation**: This process makes guesses about a given expression and confirms them through type analysis.
 
 By using these optimisations, PHP 8's JIT compiler can provide significant performance improvements between 3x-10x faster than traditional methods. The compiled machine code is stored in memory to be reused in subsequent executions.
 
 **What Benefits from the JIT?** 
 
 The JIT compiler offers notable speed improvements for many types of applications: 
 
 *   **Built-in functions**: Many of PHP's built-in functions, like `strlen()`, `str_replace()`, `abs()`, `round()`, `count()` and `array_merge()`, have been optimised to work with the JIT compiler.
 *   **Loops and conditionals**: The execution of loops (for, while) and conditional logic (if/else, switch/case) in your code is sped up by JIT.
 *   **Exception handling**: The JIT engine can remove unused exception handlers.
 *   **CPU intensive tasks**: The JIT excels at optimising mathematical, looping, and recursion-heavy code.
 
 **What are the Limitations?** 
 
 Unfortunately, not all PHP functionality is currently JIT-compatible. This includes: 
 
 *   Variable variables (`$$var`)
 *   `extract()`
 *   `compact()`
 *   Serialization
 *   Dynamic calls (`call_user_func()`)
 *   Reflection
 *   And more...
 
 The PHP team is continuously working to expand JIT coverage. Code that relies heavily on I/O operations may not see as much improvement. The JIT is most dramatic for specialised use cases, like algorithms and data processing. 
 
 **Best Practices for Using the JIT Compiler** 
 
 Here are some best practices for optimising performance with PHP 8’s JIT: 
 
 *   **Enable OPCache**: Ensure OPCache is enabled and correctly configured in `php.ini`:
 ```ini
 [opcache]
 opcache.enable=1
 opcache.jit_buffer_size=100M
 opcache.jit=1235
 ```
 *   **Use strict typing**: Specifying data types for function arguments and return values can help improve performance.
 *   **Profile your code**: Use tools like Xdebug or Blackfire to identify performance bottlenecks.
 *   **Avoid excessive string concatenation**: Use `implode()` instead of concatenating strings in a loop.
 *   **Reduce array allocations**:  Use `array_map()` instead of manually creating arrays in a loop.
 *   **Optimise the critical path**: Identify and optimise the parts of your code that take the longest to execute.
 
 **Future Developments** 
 
 The JIT compiler still has room for further optimisations, including improved function inlining, escape analysis, and adding support for tracing JITs. Expanded JIT coverage and community contributions will be key to fully realising the potential of the JIT compiler.
 

 
 ## Serverless PHP with Bref
 
 ##### Deploying Without Servers
 ```text
       /\_/\
      ( o.o )
      > ^ <
  ```
 Serverless computing is becoming increasingly popular among developers. It allows developers to focus on writing code without having to worry about the infrastructure. This article will examine how to use Bref and the Serverless Framework to deploy PHP applications to AWS Lambda.

 **Understanding Serverless** 
 
 In a serverless environment, the provider handles the deployment, environments, and scaling of your application. You bundle up your code, upload it to your provider, and the provider does the rest. You only pay for how long your application runs. This is a different approach to Platform as a Service, which provides a traditional hosting environment with a web server and PHP installation. PHP developers have limited serverless options compared to other languages. Some PHP serverless options include: 
 
 *   **Amazon Web Services (AWS)**, using the Custom Runtime API via Bref.
 *   **Digital Ocean**, through their "Functions" product.
 *   **Apache OpenWhisk**, which powers Digital Ocean Functions.
 *   **Appwrite**, through their "Functions" product.
 *   **Microsoft Azure**, using custom handlers for their "Azure Functions" service.
 *   **Google Cloud Functions**.
 
 **Functions vs. Applications** 
 
 In serverless computing, the terms “Function” and “Application” are used to describe how the code is structured. A "Function" is a PHP script comprising a single function, while an "Application" is structured like a traditional PHP application with standard bootstrapping code.
 
 **Bref and the Serverless Framework** 
 
 To deploy to a serverless platform like AWS, we can use Bref, a set of extensions for AWS Lambda that enable PHP support, and the Serverless Framework, a tool that makes it easier to interface with a variety of serverless platforms. 
 
 To start using Bref, install it with composer: `composer require bref/bref`. Then, initialise Bref with: `bref init`. Choose option “0” for a "Web Application." Bref will create an `index.php` file and a `serverless.yaml` file for deployment configuration.

 **AWS Setup** 
 
 To set up AWS, you need to: 
 
 *   Create an AWS account.
 *   Set up an AWS user with the correct IAM permissions by creating a policy called `serverless-cli` with a gist provided by Serverless Framework, as well as adding the `logs:TagResource` permission.
 *   Add this policy to an AWS group and user.
 
 **Deployment** 
 
 The `serverless.yaml` file contains the configuration for deployment. Here's an example: 
 ```yaml
 # serverless.yaml
 service: app
 provider:
  name: aws
  region: us-east-1
 plugins:
  - ./vendor/bref/bref
 functions:
  api:
   handler: index.php
   description: ''
   runtime: php-82-fpm
   timeout: 28
  events:
   - httpApi: '*'
 package:
  patterns:
   - '!node_modules/**'
   - '!tests/**'
 ```
 
 This configuration indicates that the application is deployed to AWS using the Bref plugin and uses the `php-82-fpm` runtime to execute the `index.php` file when an HTTP request is received.

 To deploy your application to AWS, use the command: `serverless deploy`. Bref handles cleanly wrapping PHP so that almost any PHP application will work.

 **Example with Slim Framework** 
 
 Here’s an example of a small application that uses the Slim framework:
 ```php
 // index.php
 use Psr\Http\Message\RequestInterface;
 use Psr\Http\Message\ResponseInterface;
 use Slim\Factory\AppFactory;
 
 require_once __DIR__ . '/vendor/autoload.php';
 
 $app = AppFactory::create();
 
 $app->get('/', function(
  requestInterface $request,
  ResponseInterface $response,
 ) {
  $response->getBody()->write(
   "This is the main route of a Slim application"
  );
  return $response;
 });
 
 $app->get('/hello', function(
  RequestInterface $request,
  ResponseInterface $response,
 ) {
  parse_str($request->getUri()->getQuery(), $args);
  $name = $args['name'] ?? 'world';
  $response->getBody()->write("Hello {$name}");
  return $response;
 });
 
 $app->run();
 ```
 This code will run the same in a serverless platform as on a bare-metal server.

 **Related Reading:** 
 
 *  _The New LAMP Stack is Serverless by Benjamin Smith_.
 *  _Community Corner: A Bref of Fresh Air by Eric Van Johnson_.

 **References:**
 *
 
 ## Prisoner’s Dilemma

##### Ethical Considerations in Software Development
 
 ```text
    ___
   /   \
  |  O  |
  |  _  |
   \___/
 ```
 Every application must be designed with careful consideration of the ethical implications of its use (or misuse). Security in software development extends to the ethics of its development and eventual use. In this article, we will discuss the importance of considering the potential impact of your software, beyond its technical functionality.
 
 **The Need for Ethical Consideration** 
 
 Developers must think about the potential for their tools to be misused. For example, an encryption tool could be leveraged to protect illegal activities, or a generative AI could be used in misinformation campaigns. The impact of the misuse is just as important to consider as how it will be misused. Would someone break a webpage heading, transfer money to the wrong account, lose a legal case, or even die because of it? 
 
 **Edge Cases and Security** 
 
 Consider edge cases when designing your application. A system needs to handle unexpected input, such as a long, hyphenated surname or a PDF that is much longer than expected. Failure to do so could make your application vulnerable. Each edge case needs to be considered by the development team.
 
 **Threat Modeling** 
 
 In software development, it's important to take time to not only threat model your application, but also to threat model its potential use in the world. These discussions are critical to the secure development and operation of your products and systems. This includes considering how it might be used maliciously or unintentionally.

 **The Prisoner's Dilemma in Code** 
 
 The ethical considerations of coding are akin to the Prisoner's Dilemma. Developers often focus on completing the task at hand without considering the wider impact of their code. They often code for the “happy path”. However, as software engineers, our ethical responsibility is to design tools that are used responsibly and safely.

 **Generative AI** 
 
 With the growing popularity of generative AI like ChatGPT and Stable Diffusion, it’s more critical than ever for software engineers to think carefully about how their tools will be used. These tools are being used in political misinformation campaigns and to produce fabricated information. This makes the need for ethical consideration even more important.
 
 **Security Is Ongoing** 
 
 Security is not something to be added right before launching an application; it is an ongoing process. Developers must be aware of the ways a malicious user might hijack their website or API and work proactively to prevent it.
 
 **References:**
 *.
 
 ## PostgreSQL
 
 ##### Diving into a Powerful Database System

 ```text
       _
      (_)
      / \
     |   |
      \_/
 ```
 While MySQL is a popular choice for many PHP developers, PostgreSQL offers a robust, ACID-compliant database management system that is suitable for various applications. It can handle everything from small request workloads on a single machine, to data warehouses scaled across multiple systems, or web services with high-traffic. This article will guide you through the core concepts of PostgreSQL and setting it up for a PHP application.
 
 **ACID Compliance** 
 
 PostgreSQL is an ACID-compliant database. ACID stands for Atomicity, Consistency, Isolation, and Durability, which are the four fundamental properties that guarantee that database transactions are processed reliably. These are defined as: 
 
 *   **Atomicity**: All operations in a transaction are treated as a single unit. Either all succeed or none.
 *   **Consistency**:  Any changes made by a transaction conform to all defined rules, constraints, and relationships, ensuring the database is always in a known state. If a transaction fails at any point, all changes are rolled back.
 *   **Isolation**: Transactions occur independently of one another and should appear to run in isolation.
 *   **Durability**: Once a transaction has been committed, its changes persist even in the event of a system failure, ensured by write-ahead logging (WAL).
 
 **Schemas** 
 
 In PostgreSQL, a database can contain one or more schemas, which are named collections of database objects. A default schema named `public` is created when a database is created. Schemas provide a way to organize database objects into logical groups. Schemas can control access to database objects by granting privileges on a per-schema basis.
 
 **Installation and Setup** 
 
 The article provides instructions for installing PostgreSQL on macOS, Windows, and Ubuntu: 
 
 *   **macOS**: Use Homebrew to install and start PostgreSQL.
 *   **Windows**: Download the PostgreSQL installer from the official website.
 *   **Ubuntu**:  Use the command line to install and start PostgreSQL.
 
 **Creating a New Database** 
 
 After installation, you can create a new database using the `createdb` command in the command-line tool or `psql`:
 ```bash
 createdb phparch
 ```
 You can also connect to the psql command line with:
 ```bash
 psql postgres
 ```
 To create a new user with superuser privilages:
 ```sql
 CREATE ROLE my_super SUPERUSER LOGIN PASSWORD 'secret';
 ```
 You can list the existing roles using: `\du`
 
 **Integrating with PHP Applications** 
 
 In this example, a Laravel application is used. After setting the database connection in `.env`, and running migrations, the tables are created in PostgreSQL. The default migrations create a `users` table. You can inspect the database using `psql`, or with a GUI tool like pgAdmin.
 
 **References:**
 *  .

 ## Site Navigation
 
 ##### Designing for Accessibility
 
 ```text
    /\   /\
   /  \ /  \
  |    V    |
   \    ^    /
    \  / \  /
     \/   \/
  ```
 For many users, navigating the internet is a quick and easy task, but for users of assistive technologies like screen readers, it can be a slow and cumbersome process. This article focuses on how to improve the accessibility of your site's navigation, with insight from the perspective of a screen reader user.
 
 **The Time Factor** 
 
 Screen reader users listen to a lot of information to find what they are looking for. Users of screen magnification often scroll through the page slowly. This increases the time it takes to find and access information. It's important to create an orderly page structure with as few images, headings, links, and buttons as possible, especially on a website’s home screen.
 
 **Screen Reader Limitations** 
 
 Screen readers are not very intelligent. They rely on the developer's labels to read content, especially with images. Without alternative text tags, images do not exist for screen reader users, and neither do the links or buttons connected to them.
 
 **Keyboard Navigation** 
 
 Screen reader users primarily use the keyboard to navigate websites. The tab key moves from top-left to bottom-left, and shift + tab works in reverse. There are also keys to move between headings. Ensure that headings are consistent and in the correct order, as this can greatly improve the experience for screen reader users.
 
 **Page Organization** 
 
 Information should be organised logically and appear in an orderly manner. Consistent page structure is also very important for users of assistive technologies. Adding headings to anything you want a screen reader user to find quickly can be particularly helpful.

 **Mouse-based actions** 
 
 Screen reader users often cannot use a mouse. Any actions that require a mouse click must have a keyboard equivalent.
 
 **Key Takeaways** 
 
 *   **Use alt text for images**:  Ensure all images have descriptive alternative text tags.
 *   **Use headings**: Organise content with consistent heading levels.
 *   **Provide keyboard equivalents**: Any action that requires a mouse click must also be available using the keyboard.
 *   **Organize content logically**: The structure of your site should be organised and consistent.
 
 By understanding the way screen reader users navigate the web, developers can avoid common accessibility mistakes and create more inclusive websites.
 
 **References:**
 *.
 
 ## Maze Directions
 
 ##### Turning Paths into Instructions
 ```text
     _   _   _
    |     |     |
    |___|___|___|
    |     |     |
    |___|___|___|
    |     |     |
    |___|___|___|
 ```
 In this article, we will focus on converting a solution for a maze into a set of simple directions. We will take a list of coordinates that mark a path out of a maze and convert them into instructions like "Turn left, go forward".
 
 **Recap** 
 
 Last month we learned how to generate a maze, draw a map, and overlay a path to the exit. Now, we need to translate that map into directions. The path is a series of cells, represented by row and column coordinates.
 
 **Flattening the Path** 
 
 The first step is to extract the cells we need to visit, maintaining the order in which to traverse them. The entrance will always be on the north wall or row 0, and the exit will be in the last row. The `flattenPath()` method returns an array of row-column coordinates to visit to get from the entrance to the exit. Here’s a part of the `flattenPath()` implementation:
 ```php
  public function flattenPath(): array
 {
  $cells = $this->getCells();
  // get rid of closed cells
  $cells = array_map(function ($row) {
   return array_filter($row,
    fn($cell) => $cell !== self::CLOSED);
  }, $cells);
  // find our entrance cell
  $entrance = array_filter($cells,
   function (int $cell) {
    return ($cell & self::NORTH) === 0;
   });
  $path = [];
  foreach ($entrance as $col => $value) {
   $path[] = [0, $col];
  }
  $current = $path;
  // now, we visit each every cell
  while (!empty($cells)) {
   [$currentRow, $currentCol] = $current;
   $walls = $cells[$currentRow][$currentCol];
   $maybe = [];
   if (($walls & self::NORTH) === 0 &&
    ($currentRow - 1 >= 0)) {
    $maybe[] = [$currentRow - 1, $currentCol];
   }
   if (($walls & self::SOUTH) === 0) {
    $maybe[] = [$currentRow + 1, $currentCol];
   }
   if (($walls & self::EAST) === 0) {
    $maybe[] = [$currentRow, $currentCol + 1];
   }
   if (($walls & self::WEST) === 0 &&
    ($currentCol - 1 >= 0)) {
    $maybe[] = [$currentRow, $currentCol - 1];
   }
   // don't include cells we've already visited
   $maybe = array_filter($maybe,
    function ($c) use ($path) {
     return !in_array($c, $path, true);
    });
   $next = array_shift($maybe);
   $path[] = $next;
   unset($cells[$currentRow][$currentCol]);
   $cells = array_filter($cells);
   $current = $next;
  }
  return $path;
 }
 ```

