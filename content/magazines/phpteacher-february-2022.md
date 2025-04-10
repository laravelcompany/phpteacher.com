---
title: PHP Teacher magazine - February 2022
publishDate: 2022-02-01 00:00:00
description: From Code Review to Async and Beyond, PHP developers can continue to improve their skills and build more effective and resilient applications.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - february
  - 2022
---

# Diving Deep into PHP: From Code Review to Async and Beyond

This post explores several crucial topics for PHP developers, drawing from recent discussions and articles within the PHP community. We'll delve into improving code reviews, understanding asynchronous programming (and its limitations), and leveraging diagram-as-code, among other relevant concepts.

## Elevating Code Reviews for Team Growth and Knowledge Sharing

Code review is a cornerstone of collaborative software development, but it's often approached as a mere gatekeeping process. The article "Teaching Through Code Review" argues for transforming code reviews into opportunities for learning and mentorship.

*   **Consistency is key:** Establish clear coding standards and automate checks using tools like `php-cs-fixer` and `Husky` to free up human reviewers from nitpicking. For example, using `Husky` you can set up a pre-commit hook that runs `php-cs-fixer` before every commit.

    ```bash
    $ mkdir --parents tools/php-cs-fixer
    $ composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer
    $ npx husky-init && npm install # npm
    $ npx husky-init && yarn # Yarn 1
    $ yarn dlx husky-init --yarn2 && yarn # Yarn 2
    ```
    Then, add this line to `.husky/pre-commit`:
    ```bash
    tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src
    ```

*   **Feedback should be constructive:**  Instead of directly pointing out errors, use questions to guide the author toward a solution. For example, rather than saying "This code is wrong", ask, "Would this work better in this instance?". This encourages discussion and learning rather than defensiveness.
*   **Early and frequent reviews**: Reviewing code before it's fully complete allows for early feedback and can prevent wasted effort. Use tags to indicate the status of a branch, such as "needs work," or "ready to review".
*   **Empathy is essential:** Be mindful of reviewer fatigue. Smaller, more frequent reviews can make it easier to fit into schedules and allow for better understanding of changes.  Make sure the author clearly states their intentions and the reasons behind code changes to save time and reduce misunderstanding.
*   **Focus on the code not the person:** Use "I" statements to focus on the code instead of blaming the person who wrote it. Avoid "you" or "your." "I see" statements are helpful.

    ```text
    I see the uasort function modifies the original array. The function could be called on a copy of the array to prevent this.
    ```
*   **Knowledge sharing:** Code reviews are a great way for developers to share their knowledge. The reviewer has to understand what the code does and how it does it. This helps multiple people be able to work on the same projects. This helps with debugging, adding new features, etc.

By adopting these practices, code reviews can become a powerful tool for building a more inclusive and effective development team.

## Understanding "Async" in PHP: The Reality Behind the Hype

Asynchronous programming is often touted as a solution to performance bottlenecks. The article "Async is a Lie" provides a critical perspective on its implementation in PHP.

*   **PHP is single-threaded:** PHP processes requests in a single thread, meaning blocking operations such as disk I/O and database queries can stall the entire application.
*   **The Call Stack:** Understanding the call stack is key.  Functions are added to the stack and executed in a Last-In-First-Out order. Blocking operations in a function will halt the whole call stack.  The program stalls until the operation completes.

    ```php
    function func1() {
      echo 'Hello World';
    }

    function func2() {
      echo 'This is an example.';
      func3();
      echo 'Back in func2()';
    }

    function func3() {
      echo 'Now we are here';
    }

    func1();
    func2();
    ```
*   **Event Loops:** While PHP isn't naturally multi-threaded like some languages, event loops can be used to "fake" asynchrony, by managing different blocks of code and switching context.  However, PHP is single-threaded, it does not have a second thread, so the context switching is not the same.

    ```php
    class EventLoop {
        public $stack = [];

        public function run() {
          while(true) {
            $invokable = array_shift($this->stack);
             if ($invokable) {
                 $response = $invokable();
                 if (!is_null($response) && is_callable($response)) {
                   $this->stack[] = $response;
              }
             }
           }
        }
    }

    $loop = new EventLoop();
    $loop->stack[] = function() {
      echo "I was added to the stack first" . PHP_EOL;
    };
    $loop->stack[] = function() {
      echo "I was added to the stack second" . PHP_EOL;
      return function() {
        echo "I was added from inside a function" . PHP_EOL;
      };
    };
    $loop->stack[] = function() {
      echo "I was added to the stack third" . PHP_EOL;
    };
    $loop->run();
    ```
*   **Coroutines and Generators:** Generators and coroutines offer a way to pause and resume function execution, which can simulate async behaviour.

    ```php
    function myCoroutine(): Generator {
        echo "I did some work and will now wait." . PHP_EOL;
        $value = yield;
        echo "Thanks, I will print " . $value . PHP_EOL;
      }

      $cor = myCoroutine();
      $cor->current(); // Execute up to the yield
      echo "Now we wait" . PHP_EOL;
      sleep(1);
      $cor->send(1); // Finish execution
    ```
*   **Libraries and Extensions:** Libraries like ReactPHP and extensions such as Open Swoole offer more robust async capabilities. Open Swoole ships with its own HTTP and database clients that are context-aware.

While async programming can offer performance benefits, it's not a universal solution. It's crucial to understand the trade-offs and choose the appropriate approach based on the specific needs of your application.

## Diagram-as-Code: Visualizing Your System Through Text

Documentation is a crucial aspect of software development, and "Introduction to Diagram-as-Code" makes the case for integrating technical illustrations directly within your code using textual descriptions.

*   **Diagrams as Code**:  Instead of using manual drawing tools, describe diagrams (e.g. class hierarchies, sequence diagrams) using text-based directives. This approach is similar to Markdown, where human-readable text creates HTML.
*   **Graphviz**: This tool generates graphs from text-based descriptions, emphasizing symbolic representation over precise positioning.

    ```text
    digraph {
      Knowledge -> Magazines
      Knowledge -> Wikipedia
      Magazines -> phparch
    }
    ```
*   **UML**:  Unified Modeling Language (UML) provides standard visual codifications to express software system structures.
*   **Tools:** PlantUML and MermaidJS are tools that facilitate the creation of diagrams via Domain Specific Languages (DSL). These tools interpret the instructions and generate images that can be embedded in documentation.
    *   **PlantUML:** Supports various UML diagrams and customization options.

        ```plantuml
        @startuml
        !theme cerulean

        autonumber

        participant Alice
        box
          participant Bob
          participant Charlie
        end box

        Alice -> Bob ++ : says hello
        Alice -> Bob : asks the time it is
        Alice <-- Bob : replies hello
        Bob -> Charlie ++ : asks the time
        Charlie -> Charlie : looks at wrist watch
        Bob <-- Charlie -- : replies the time it is
        Alice <-- Bob -- : gives the time
        @enduml
        ```
    *   **MermaidJS**: Focuses on client-side rendering and supports some UML and non-UML models, also offering interactivity.

        ```text
        mermaid
          stateDiagram-v2
              [*] --> Still
              Still --> [*]
              Still --> Moving
              Moving --> Still
              Moving --> Crash
              Crash --> [*]
        ```
*   **Kroki.io:**  An online API that supports multiple diagram engines, including PlantUML and MermaidJS.
*   **Integration:** Integrate Diagram-as-Code into development environments via IDE plugins and documentation generators like PhpDocumentor. You can use Kroki.io to render images using a JavaScript file inserted into the PhpDocumentor layout.

Diagram-as-Code provides a way to maintain consistent and up-to-date documentation directly alongside your source code.

## PHP-FPM and Apache Configuration

The article "Configuring PHP-FPM & Apache" discusses the benefits of using PHP-FPM (FastCGI Process Manager) with the Apache webserver.

*   **PHP-FPM:** PHP-FPM executes PHP code as a separate service rather than embedding it in the web server.
*   **Multiple PHP Versions:** Using FPM allows you to run multiple versions of PHP on the same server, something not possible with `mod-php`.
*   **Configuration:**  PHP configuration is located in `/etc/php`, with subfolders for each version, and for each SAPI, which is the way applications interact with PHP.
*   **Virtual Hosts:**  Apache virtual hosts can be configured to use different PHP versions by changing the `SetHandler` directive. The virtual host configuration will pass requests to the FPM service, which will execute the PHP code.

    ```apache
    <VirtualHost *:80>
       ServerAdmin webmaster@localhost
       ServerName paste.test
       DocumentRoot /home/vagrant/paste/public

       <Directory /home/vagrant/paste/public>
         AllowOverride All
         Require all granted
       </Directory>

       SetHandler "proxy:unix:/var/run/php/php7.4-fpm.sock|fcgi://localhost"

       ErrorLog ${APACHE_LOG_DIR}/paste.test-error.log
       CustomLog ${APACHE_LOG_DIR}/paste.test-access.log combined
    </VirtualHost>
    ```
    In the example above, the SetHandler directive tells Apache to use php7.4-fpm.

*   **URL Rewriting:** You can configure URL rewrites and redirections using `.htaccess` files. An example is to redirect from http to https.

  ```apache
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
    ```

Using PHP-FPM allows you more flexibility with running PHP applications in production.

## Other Notable Topics

*   **Cybersecurity:** The article "Getting Started with Cybersecurity" emphasizes the importance of understanding how systems work and how they can be broken. It encourages developers to adopt a "security-focused mindset".
*   **Integer Factors:** The article "Finding Integer Factors" explores algorithms for decomposing an integer into its factors. The code examples illustrate how to find positive integer factors and how to use array mapping and filtering to accomplish this. It also explores finding negative factors.

    ```php
    function findFactors(int $product) : array {
        $factors = range(1, (int) sqrt($product));
        $factors = array_map(
            function($factor) use ($product) {
            if ($product % $factor === 0) {
                return [$factor, $product / $factor];
            }
            }, $factors
        );

        return array_filter($factors);
    }
    ```
*   **Design Patterns:** "When You Know the Pattern" discusses how to apply design patterns, specifically the Registry pattern, within a PHP application. The example uses memoization to optimize lookups by creating a `RoleBasedLookup` class. It keeps track of cached data based on user roles.
*   **PHP Community:** The article "Every Which Way But Loose" discusses the ongoing debate around PHP's inconsistent naming conventions and the difficulties in making changes due to the wide-ranging impact. It highlights the strength of the PHP community and its ability to welcome new members. There are many people who are excited about PHP's future.
*   **Raspberry Pi and LAMP Stack**: The article "How to Hack your Home with a Raspberry Pi - Part 2 - Installing the LAMP Stack on your Pi" provides a detailed guide on setting up a LAMP stack on a Raspberry Pi. It covers updating software packages, installing Apache, MySQL (MariaDB), and PHP, and also how to modify the home page to display the date and time.

    ```bash
    sudo apt update
    sudo apt install apache2
    sudo apt install mariadb-server
    sudo apt install php php-mysql
    ```

By exploring these topics, PHP developers can continue to improve their skills and build more effective and resilient applications.
