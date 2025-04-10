---
title: PHP Teacher magazine - April 2023
publishDate: 2023-04-19 00:00:00
description: Our monthly PHP Teacher magazine covers various aspects of PHP development and related technologies.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - april
  - 2023
---

Hello readers, and welcome to another edition of *The PHP Teacher Magazine*! This month, we delve into a wide array of topics, from the foundational principles of software design to the cutting-edge applications of AI in code generation. We’ll explore how to build more robust and scalable applications, understand the nuances of API design, and even consider the ethical implications of our work. Whether you are a seasoned developer or just starting your journey, there is something here to enhance your skills and understanding of the PHP landscape. Prepare to be inspired, challenged, and equipped with practical knowledge. Let's get started!

## My Journey into Domain-Driven Design

By Edward Barnard

This month, we open with a reflective piece by Edward Barnard on his experiences with **Domain-Driven Design (DDD)**. Barnard shares his journey, which began 30 years ago with his contribution to the book *Software Inspection*, and how his understanding of DDD has evolved. He notes that while his initial attempt to implement Software Inspection failed due to a lack of collaboration, he continued to explore different methodologies. Barnard's story underscores the importance of team collaboration and shared understanding in adopting new methodologies.

```
      _    _
     / \  / \
    (   \/   )
    \      /
     ------
     |    |
     \____/
```
He dives into his experience with embedded PHP development, where he successfully fixed critical bugs using his PHP skills. However, the culture at the company wasn't open to new ideas. Despite being a skilled embedded C developer, he was let go because his code was too different. He discovered **Test-Driven Development (TDD)** during this time, a methodology that was both useful and contentious in his work.

Barnard also touches upon the concept of a "**Big Ball of Mud**," a term he'd only heard negatively, yet it has some merit. He explains that his team’s rewrite attempts failed twice due to a lack of training and collaboration, highlighting that even failed efforts can offer valuable lessons. He emphasizes the importance of collaboration in DDD and the need for "protective boundaries" using concepts like "bounded context" and the Aggregate pattern.

Barnard returns to his roots, referencing the **Software Inspection** process from his earlier work with Tom Gilb, in which the requested Inspection cannot proceed unless the Entry Criteria are met, ensuring that time is not wasted. This highlights the need for collaborative processes, which are necessary for more complex problems that cannot be solved by one expert.

Barnard's journey highlights the fact that collaboration is crucial when implementing complex software, and it's important to choose practices that align with the team's goals and capabilities.

*   **Key Concepts:** Domain-Driven Design, Test-Driven Development, Big Ball of Mud, Software Inspection, Collaboration.
*   **Code Example:** There are no direct code examples, but the principles are about approach and methodology
*   **References:**

##  The Role of PHP in Ubiquitous Computing

By Jack Polifka

Jack Polifka explores the future of PHP in the face of ubiquitous computing, moving past traditional web development. He posits that while PHP currently powers much of the internet, its future lies in becoming a universal language, powering new technologies beyond the web browser. He notes how technologies such as social media, e-commerce and smartphones have become a facet of daily life, and PHP has been associated with them.

```
     ____
    /    \
   |  ()  |
   \ ____ /
     ||||
     ----
```
Polifka suggests that as technologies like **Augmented Reality (AR), Virtual Reality (VR), and the Internet of Things (IoT)** become more commonplace, PHP will likely transition to a logic-based role, primarily using **Application Programming Interfaces (APIs)**. He advocates for developers to start using PHP for building more API-first systems to accommodate future clients.

He explains the fundamentals of APIs, highlighting that they are interfaces used for exchanging information between different systems. Polifka explains key aspects of the **HTTP protocol** including methods, paths, headers, bodies and status codes that are used in **RESTful APIs**. He outlines how the client-server model works with requests and responses, and he correlates HTTP methods to CRUD SQL commands like INSERT, SELECT, UPDATE and DELETE. He also illustrates a practical example of an API for a music streaming service, showcasing how it can be used with web browsers and future technologies like AR and VR.

Polifka highlights that by focusing on APIs, PHP developers can ensure their systems are adaptable to future technologies. This is in contrast to cross-platform development, which can be limited by its inability to predict future rendering methods. Polifka advocates for API first systems that will send and recive data with JSON or XML. He ends by encouraging developers to prepare for an evolving technological landscape, by using APIs that will be ready for future clients.

*   **Key Concepts:** APIs, RESTful architecture, HTTP Protocol, Client-server Model, CRUD operations, Ubiquitous Computing.
*   **Code Example:** The article includes an example of an API design for a music streaming service as well as a list of elements associated with HTTP requests and responses.
*   **References:**

## GPT: Changing The Way We Code

By Tomas Votruba

Tomas Votruba explores the impact of **Generative Pre-trained Transformer (GPT)** models on software development. He begins by noting how rapidly the technology is changing, rendering tutorials obsolete in just weeks. Votruba emphasises how fast technology is changing, stating that waiting for yearly upgrades to software may become obsolete, with upgrades potentially becoming a matter of a week. He also mentions tools such as PHPStan and Rector, that, with the use of GPT, can integrate feedback and propose fixes with pull requests. He also discusses a very simple implementation of GPT, called Dave GPT, that is only a few lines of PHP.

```
   _____
  /     \
 |  GPT  |
  \_____/
     ||
     ||
```

Votruba challenges the notion that GPT-based startups are complicated, sharing a minimal PHP script using the OpenAI API. He then brainstorms several practical applications of GPT for developers, such as generating code fixes, upgrading PHP versions, and creating unit tests. He provides details on his project testgenai.com, which generates unit tests for PHP code, and also how GPT can create a CLI tool that generates unit tests for public methods. Votruba shares his experience developing this project, emphasizing that the value is not in the AI code itself but in the surrounding ecosystem, which includes marketing, input validation, and background queues. He encourages developers to experiment with GPT to see where it fits into their development workflow.

Votruba also explores the idea of using GPT to "scale" individual developers, by automating their daily tasks and allowing them to focus on higher-level work, thus freeing up time to self-reflect and grow as a human. He notes how tools can automate specific tasks, taking the best parts of a person's skill set and automating it. This allows us to share our know-how and automate it. He also gives the example of a friend who makes patterns for clothes, and how they could use GPT to design trends, thus freeing up time for more projects. Votruba ends with an optimistic view of how AI could change development and create a more beautiful world.

*   **Key Concepts:** Generative AI, GPT Models, OpenAI API, Code Generation, Automated Testing, Software Evolution.
*   **Code Example:** Dave GPT is presented in its minimal form . Examples of how GPT can be used to fix PHPStan errors and automate upgrades are also described.
*   **References:**

## 12 Factor Applications: Parts 7-12

By Chris Tankersley

Chris Tankersley delves into the remaining principles of the **12 Factor Applications** methodology, specifically addressing tenants 7 through 12, which focus more on the architecture of the code. He starts with **Port Binding**, where applications should announce their service through port binding. He notes how containers can listen on a specific port and then be redirected to another port by the orchestration layer. For example, a PHP application might listen on port 9000, the standard PHP-FPM port.

```
   +-----+
   | PORT|
   +-----+
    |||||
    |||||
```
Next, Tankersley discusses **Concurrency**, which should be implemented through scaling out horizontally by adding more processes rather than vertically by adding more resources. He explains how PHP's architecture has always been designed for this, using techniques like CGI, `mod_php`, and PHP-FPM. He contrasts PHP with single threaded languages like Node.js which requires a developer to think about how to handle concurrency. He also touches on how single threaded asynchronous processes will require that developers think about how to scale an application.

Tankersley moves on to **Disposability**, a design that supports the ability to start, stop and destroy an application at any moment. He discusses how traditional PHP applications fit the pattern, as they generally have short lifetimes. But with newer single-threaded applications such as OpenSwoole and ReactPHP, it becomes necessary to implement features such as signal handling, which allow the script to respond intelligently to the system. The author suggests handlers for `SIGTERM` and `SIGHUP` signals to gracefully shutdown or restart an application.

Tankersley addresses **Dev/Prod Parity**, where development, staging, and production environments should be identical to reduce issues relating to differences in the operating environment. He highlights that tools like Puppet, Ansible and Terraform can help with managing these environments. He also notes how containers help maintain parity because every installation can point to the same byte-for-byte replica of an image. He cautions that parity should not be an excuse for bad programming, and developers should design applications to work in different environments.

**Logs**, according to the 12 Factor Application, should be treated as event streams using `stdout` to allow for flexibility in the running environment, and that `stderr` is more appropriate for logging. Tankersley explains how PHP’s `error_log()` function and using a **PSR-3** compatible logger like Monolog, allows us to log to either stream and different log levels. Tankersley gives examples of how `stdout` and `stderr` are meant for different things.

Finally, he discusses **Admin Processes**, highlighting that any administrative tasks should be run as a separate process but in the same environment as the application. It should be a process not part of the startup of the application. He wraps up by noting that while the 12 Factor Applications are not new, they still generate good questions for all developers.

*   **Key Concepts:** Port Binding, Concurrency, Disposability, Dev/Prod Parity, Logging, Admin Processes.
*   **Code Example:** An example of how to create a Monolog handler for writing to `stderr` is shown.
*   **References:**

## The Risks of Free Conference Internet

By Eric Mann

Eric Mann warns of the dangers of using free conference WiFi, highlighting the security risks associated with connecting to untrusted networks. He begins by drawing a comparison with a home WiFi network, and how freely we give out our home WiFi passwords. He states that all the attendees have the same level of access as a guest on your home network, and this can be a catastrophic failure for a work machine.

```
      _
     / \
    |  O  |
    \___/
     ||||
     ----
```

Mann shares how common development setups that use **Docker** can expose vulnerabilities. He explains how a simple command using **Nmap** can be used to identify live hosts and expose their open ports and services, potentially revealing sensitive data. He provides an example of a conference challenge where he used Nmap to find exposed machines and guess default credentials for databases.

Mann provides an example where a WordPress developer had their production database exposed with default credentials. This underscores that even default configurations can pose a risk. He also notes that it is common to find such exposed services. He emphasises the need for internal networking for development environments, as well as not exposing ports without taking proper precautions. He concludes by stressing the importance of proactive network security and taking proper precautions to avoid catastrophic security failures.

*   **Key Concepts:** Network Security, Docker, Nmap, Default Credentials, Internal Networking, Port Exposure.
*   **Code Example:** A sample `docker-compose.yml` file is shown, as well as an example of an Nmap command for finding live hosts.
*   **References:**

## A Grumpy Programmer’s Introduction To NeoVim and PHP

By Chris Hartjes

Chris Hartjes introduces NeoVim, a highly configurable, text-based editor for PHP development. He explains that NeoVim is based on Vim, a modal text editor that is very efficient. He describes how he was introduced to Vim and that he has been relying on muscle memory ever since to guide his keystrokes. Hartjes explains that NeoVim is a rewrite of Vim, with a new API and plugins written in Lua.

```
    +-----+
    |     |
    | VIM |
    |     |
    +-----+
      ||||
      ||||
```
Hartjes shares his experience using NeoVim and how it can be configured into a custom IDE using plugins and tools. He highlights the use of **Language Server Protocol (LSP)** tools, which bring features from an IDE such as syntax highlighting, code completion, and linting. He then lists his current plugins and explains the most helpful.

Hartjes gives examples of general plugins he uses, along with version control, and PHP specific plugins. He explains how **EditorConfig** can be used to respect coding styles in a project. The use of keyboard shortcuts is very important in NeoVim, and Hartjes notes how this keeps his hands on the keyboard without needing a mouse. Hartjes shares how he is always evaluating his tools to see how they fit into his workflow.

Hartjes also notes the benefits of tools like **Telescope**, which helps to find files within a project, as well as using **nvim-cmp** for autocompletion. He gives an example of a plugin that integrates with LSP to show the developer diagnostic and error messages. He concludes by encouraging developers to explore tools and how they can improve their workflows, while also not being afraid to experiment.

*   **Key Concepts:** NeoVim, Modal Editor, Language Server Protocol (LSP), Plugins, Configuration, EditorConfig.
*   **Code Example:** The article includes a configuration for LSP as well as a list of plugins that are helpful when using NeoVim for PHP development.
*   **References:**

## Maze Rats

By Oscar Merida

Oscar Merida explores the topic of maze generation algorithms, using the challenge of trapping treklins in a maze. He introduces the problem as building a square grid maze with four walls, and each wall can be either open or closed. Merida discusses early mazes and references Wikipedia on maze generation algorithms, suggesting they can be represented as connected graphs.

```
   +---+---+
   |   |   |
   +---+---+
   |       |
   +---+---+
```
Merida decides to stick with a grid of squares, and then focuses on representing a single cell, which has four walls that can be either open or closed. He determines that each wall can be represented as a binary value, where a closed wall is 1 and an open wall is zero. He creates a table which lists the possible combinations of walls using decimal and hexadecimal, and he notes that cells with only one wall are a power of 2.

Merida walks through a manual example, showing how he sketches a maze and translates the walls in a cell to a bit value. He then represents this maze as a PHP array, with hexadecimal values for each cell. He then uses bitwise operations to generate a basic visualisation using ASCII characters. He uses the bitwise AND operator (&) to check if a cell has the desired state, and he uses ASCII box drawing entities to tie it all together.

*   **Key Concepts:** Maze Generation, Graph Theory, Bitwise Operations, Grid Representation, ASCII Art.
*   **Code Example:** Includes a visual representation of a maze and the PHP array representation, as well as how to visualise the maze with bitwise operations and ASCII art.
*   **References:**

## Impedance Mismatch

By Edward Barnard

Edward Barnard explores the challenges of adapting software architecture to different needs using the concept of **Impedance Mismatch**. Barnard starts with a description of a whiteboard diagram of their PHP application, which initially looked like a “Big Ball of Mud”. However, with slight changes to the diagram, an interesting modular monolith emerged. Barnard also references an article by Kirsten Westeinde about Shopify creating a modular monolith.

```
       /----\
      /  API \
     /-------\
     ||   ||
     ||   ||
     \____/
```

Barnard provides a description of their application that has three main user types that have completely different requirements: Athletes, Team Coaches and System Administrators. The original application had modules based around the users. However, he notes how the business logic for registration was scattered across the different modules, which is why it was a "Big Ball of Mud".

He then created a new diagram that contains a two-layer API structure with top-level APIs based on user roles (Athlete, Team, Admin) and another set of APIs corresponding to business phases (Local, Regional and National). This matrix has independent components and reveals how different user types might need the exact same data but with different authorisations. He uses the OpenAPI specification (OAS) for defining the APIs and generates Models with Swagger-Codegen.

Barnard realises that he does not need to make internal HTTP calls and can simply make method-to-method calls, passing along the request and response objects. This protects their code base from the third layer, the database schema. This process also creates "seams" in the code, allowing him to create a two layer structure without any actual API calls. He references Michael Feathers, who defines a “seam” as a way to alter program behavior without changing it in place. He references an article that suggests using a pattern of “Transitional Architecture” in order to replace a monolith in several steps.

Barnard also uses Kent Beck's advice “make the change easy, then make the easy change". Barnard ties it together with the concept of “Impedance Mismatch,” noting the need to adapt one shape to fit a different shape, and that internal APIs can place boundaries within different modules. He ends by emphasizing how creating seams can bring the application under control one step at a time.

*   **Key Concepts:** Impedance Mismatch, Modular Monolith, API Design, RESTful APIs, Seams, Transitional Architecture.
*   **Code Example:** There is no code, but the article does refer to API design.
*   **References:**

## PSR-18: HTTP Client

By Frank Wallen

Frank Wallen explores **PSR-18**, the HTTP Client interface. He highlights the increasing use of APIs and the need for standardized interfaces, to improve control of content and resources, and provide improved performance through asynchronous requests. Wallen mentions how this article will focus on the consumers of an API, not the design of the API. He recommends attending some talks at Tek about API design and security.

```
    +------+
    | HTTP |
    +------+
    ||   ||
    ||   ||
```
Wallen describes how PSR-18 provides a design for a common interface for sending PSR-7 messages and responses, without defining asynchronous requests. He notes that this PSR supports the **Liskov Substitution Principle** to swap out HTTP client implementations. He then details the simple interface for the HTTP client, noting how the implementor is responsible for ensuring the response is a valid HTTP 2xx response and that headers are set correctly, as well as any compression / decompression.

Wallen provides a simple implementation of `ClientInterface` using the Guzzle HTTP client. He highlights that a `ClientExceptionInterface` exception should be thrown if the request cannot be sent. Wallen then outlines the `RequestExceptionInterface` which should be thrown when the request is not formed properly, and the `NetworkExceptionInterface` which should be thrown if a network error occurs.

Wallen concludes by emphasising how PSR-18 provides a standardised interface for swapping client implementations. He also encourages developers to use or at least study well-tested libraries. He ends by mentioning PSR-20 (Clock) that will be reviewed next month, and also encourages readers to attend php\[tek] 2023.

*   **Key Concepts:** HTTP Client, PSR-18, PSR-7, Liskov Substitution Principle, HTTP Responses, Client Exception Handling.
*   **Code Example:** An example of how to implement the `ClientInterface` using the Guzzle HTTP client is provided.
*   **References:**

## The Subtle Art of Optimal DaaS

By Matt Lantz

Matt Lantz explores the topic of **DaaS (Database as a Service)** selection, noting that many developers focus more on the code and less on the DevOps and infrastructure. He notes how developers generally have their own cloud provider, and then chose between Forge and Vapor.

```
      ____
     /    \
    | DaaS |
    \____/
     ||||
     ----
```

Lantz touches on **Laravel Forge** as the original platform as a service for deploying PHP applications. He also briefly mentions other options like Kubernetes and Vapor for more advanced scaling. He notes how these scaling options can be very automated, yet require some adjustment regarding cost and resources. He mentions how options such as Laravel Octane can also be used with these scaling options, but with some code changes.

Lantz states that developers should regularly review if their current solution is optimized for their project and team needs. He also highlights that there is a DaaS solution for all skillsets and budgets.

*   **Key Concepts:** DaaS, Laravel Forge, Kubernetes, Laravel Vapor, DevOps, Cloud Infrastructure.
*   **Code Example:** No specific code examples are included
*   **References:** None.

## Stop Waiting

By Beth Tucker Long

Beth Tucker Long encourages readers to stop waiting for the right time to pursue their goals. She notes how she often went to conferences and kept a list of "things to do" but that this list ended up being old and overwhelming. She provides examples of how her "someday" list had goals such as hiring an intern.

```
    _____
   /     \
  |  NOW  |
   \_____/
     ||
     ||
```

Long explains that she finally hired an intern that was struggling to find one and that this solved many of her problems. She shares how her interns learn new skills while setting up tools she had not yet used, thus speeding up her workflow. She challenges the readers to also stop waiting and make their goals happen.

*   **Key Concepts:** Goal Setting, Procrastination, Action, Workflow Improvement.
*   **Code Example:** No specific code examples.
*   **References:** None.

## Postface: Technology Index and Final Thoughts

This month's issue has touched upon various aspects of software development, from the foundations to the most current innovations. It has been shown how important collaboration is for software development, and how AI is poised to disrupt many aspects of our work. We have explored many different approaches, and it is up to us as developers to pick and chose what suits our goals and capabilities. Here is an index of some of the key technology concepts explored in this issue:

*   **Domain-Driven Design (DDD)**
*   **Test-Driven Development (TDD)**
*   **Big Ball of Mud**
*  **Software Inspection**
*   **Application Programming Interfaces (APIs)**
*   **RESTful APIs**
*   **HTTP Protocol**
*   **CRUD operations**
*   **Generative Pre-trained Transformer (GPT)**
*   **Language Server Protocol (LSP)**
*   **PSR-18**
*   **ASCII art**

As always, we hope that this issue of *The PHP Teacher Magazine* has provided you with valuable insights and practical knowledge. Remember to keep learning, experimenting, and pushing the boundaries of what is possible with PHP. Thank you for reading, and see you next month!
