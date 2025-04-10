---
title: PHP Teacher magazine - June 2023
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - june
  - 2023
---

Welcome to another edition of *The PHP Teacher Magazine*! This month, we delve into a range of topics designed to enhance your skills and keep you up-to-date with the latest trends in PHP development and related technologies. In this issue, we will cover **Value Objects, the evolving landscape of the Internet and the next generation of developers, serverless computing, token types, web accessibility, database management as a service, maze-solving algorithms, code timing, coding styles, and defensive programming techniques in Laravel**. Each article is crafted to offer practical insights and hands-on knowledge, complete with code examples and visual aids. We hope you find this issue both informative and inspiring as you continue your journey in the world of PHP.

## Value Objects**

In this article, we explore **Value Objects**, a design pattern that promotes encapsulation and immutability in object-oriented programming. Value objects are objects that are defined by their attributes, not their identity. This is different from entities which have unique identities. We will delve into the characteristics of Value Objects, including immutability, replaceability, value equality, side-effect-free behavior and data validation.

```
      _
     / \
    |   |
    \ _ /
     \_/
```
   
    
We will implement a `Person` class, representing basic data, using the `lbacik\value-object` library. This library simplifies the creation and management of value objects, providing an easy way to handle immutability and value comparisons. We will use the `phpspec` framework for testing and discuss the concepts behind TDD and BDD. The practical implementation is as follows:
   
```php
<?php

namespace App;

use Sushi\ValueObject;
use Sushi\ValueObject\Exceptions\InvariantException;
use Sushi\ValueObject\Invariant;

class Person extends ValueObject
{
    private const MIN_AGE_IN_YEARS = 18;

    public function __construct(
        public readonly string $name,
        public readonly int $age,
    ) {
        parent::__construct();
    }

    #[Invariant]
    protected function onlyAdults(): void
    {
        if ($this->age < self::MIN_AGE_IN_YEARS) {
            throw InvariantException::violation(
                'The age is below ' .
                self::MIN_AGE_IN_YEARS
            );
        }
    }
}
```

This article will cover the following:
*   Setting up the project with `composer`.
*   Installing `phpspec` for testing.
*   Creating a basic Value Object with a constructor.
*   Implementing immutability using the `readonly` keyword in PHP 8.1.
*   Using the `set` method from the `lbacik\value-object` library to create new instances with modified values.
*   Understanding Value Equality, and comparing two Value Objects.
*   Implementing invariants to enforce data validation.
*   Using assertions for data validation.
*   Using the `isEqual` method to compare the values of Value Objects.
*   Ensuring side-effect-free behaviour by creating a new object instead of modifying an existing one.

This article will provide you with a comprehensive understanding of Value Objects and how to implement them effectively in your PHP applications using the `lbacik/value-object` library. 
**References:**
*   [lbacik\value-object](https://phpa.me/packagist)
*   [phpspec](http://phpspec.net/en/stable/)
*   [GitHub](https://github.com/lbacik/value-object-spec)
*    [Wikipedia](https://en.wikipedia.org/wiki/Value%5C_object)
*   Domain-Driven Design, Eric Evans
*   Implementing Domain-Driven Design, Vaughn Vernon
*   Patterns of Enterprise Application Architecture, Martin Fowler

## The Next Generation of Developers: Where is the Internet Going?**

This article explores the evolving landscape of the internet and the key technologies shaping the future, focusing on the **Internet of Things (IoT) and Extended Reality (XR)**. We examine how these technologies are becoming increasingly prevalent in daily life and the skills the next generation of developers will need.

```
    .  .  .
    .      .
   .        .
    .      .
     .  .  .
```
   
The article will cover:
*   An introduction to the IoT and its impact on global connectivity, highlighting examples from smart homes to smart cities.
*   The increasing accessibility of tools for creating virtual and augmented reality experiences.
*   The importance of IoT devices and their reliance on sensors, internet connectivity, and standardization.
*   A discussion of Extended Reality (XR), including Virtual Reality (VR) and Augmented Reality (AR) and their applications beyond gaming.
*   The use of digital twins in urban planning for optimizing services and increasing public involvement.
*   The role of educational programs like the Games, Interactive Media & Mobile Technology (GIMM) program at Boise State University in preparing students for the real world.
*   Practical training in IoT using Amazon Echo Frames and Alexa applications.
*   Hands on experience creating VR games and accessibility apps using RealityKit.
*   The importance of security and standardization for the future of IoT systems.

This article aims to provide readers with a clear view of the technologies that are driving the internet forward and how the next generation of developers is being trained to meet these challenges.
**References:**
*   Games, Interactive Media & Mobile Technology (GIMM): [https://www.boisestate.edu/gimm/about/](https://www.boisestate.edu/gimm/about/)
*   Barcelona Planning: [https://phpa.me/barcelona-planning](https://phpa.me/barcelona-planning)
*   Virtual Reality Society: [https://www.vrs.org.uk/virtual-reality/history.html](https://www.vrs.org.uk/virtual-reality/history.html)
*   Industrial augmented reality: [https://en.wikipedia.org/wiki/Industrial_augmented_reality](https://en.wikipedia.org/wiki/Industrial_augmented_reality)
*   Number of connected IoT devices: [https://iot-analytics.com/number-connected-iot-devices/](https://iot-analytics.com/number-connected-iot-devices/)
*   Smart toilet: [https://www.youtube.com/watch?v=QRFo5C3XNUU](https://www.youtube.com/watch?v=QRFo5C3XNUU)

## Dude, Where’s My Server?**

This article delves into the evolution of software deployment, from **bare metal servers to virtual machines, containers, and serverless computing**. We explore how the focus has shifted from hardware to software and how developers no longer need to worry about the underlying infrastructure.

```
   _____
  /     \
 |  _ _  |
 | /   \ |
  \_____/
```
    
We will discuss the following:
*   The history of software deployment, from the Analytical Engine to modern computers.
*   How early software was tightly coupled to the hardware it ran on.
*   The evolution of higher-order languages like Assembly.
*   The introduction of virtualization and how it allowed one machine to act as multiple machines.
*   The development of hypervisors to control access to hardware in virtualized environments.
*   The transition from virtual machines to container technologies like Docker and LXC.
*   How containerization isolates processes and allows for more efficient resource utilization.
*   The advent of serverless computing, where developers deploy code rather than virtual machines or containers.
*   The benefits and challenges of serverless computing, including metered billing and the ability to focus solely on code.
*   The current state of serverless computing for PHP developers with tools like Bref and the Serverless Framework.
*   The evolution of deployment, from caring about the platform, to almost having to worry about the platform like writing in assembly.

This article aims to provide a comprehensive understanding of the historical progression of software deployment and the future of serverless technologies for PHP developers.
**References:**
*   Analytical Engine: [https://phpa.me/analytical-engine](https://phpa.me/analytical-engine)
*   Charles Babbage: [https://en.wikipedia.org/wiki/Charles_Babbage](https://en.wikipedia.org/wiki/Charles_Babbage)
*   Ada Lovelace: [https://phpa.me/ada-lovelace](https://phpa.me/ada-lovelace)
*   Bernoulli number: [https://phpa.me/bernoulli-number](https://phpa.me/bernoulli-number)
*   Language history: [https://phpa.me/lang-history](https://phpa.me/lang-history)
*   Time-sharing: [https://en.wikipedia.org/wiki/Time-sharing](https://en.wikipedia.org/wiki/Time-sharing)
*    IBM M44/44X: [https://en.wikipedia.org/wiki/IBM_M44/44X](https://en.wikipedia.org/wiki/IBM_M44/44X)
*   CP/CMS: [https://en.wikipedia.org/wiki/CP/CMS](https://en.wikipedia.org/wiki/CP/CMS)
*   Virtual machine: [https://en.wikipedia.org/wiki/Virtual_machine](https://en.wikipedia.org/wiki/Virtual_machine)
*   BCPL: [https://en.wikipedia.org/wiki/BCPL](https://en.wikipedia.org/wiki/BCPL)
*   VMware Workstation: [https://phpa.me/vmware-workstation](https://phpa.me/vmware-workstation)
*   Xen: [https://en.wikipedia.org/wiki/Xen](https://en.wikipedia.org/wiki/Xen)
*   VPS: [https://phpa.me/vps](https://phpa.me/vps)
*   Vagrant: [https://www.vagrantup.com](https://www.vagrantup.com)
*   Laravel Homestead: [https://laravel.com/docs/10.x/homestead](https://laravel.com/docs/10.x/homestead)
*   DigitalOcean: [https://www.digitalocean.com](https://www.digitalocean.com)
*   AWS: [https://aws.amazon.com](https://aws.amazon.com)
*  Chroot: [https://en.wikipedia.org/wiki/Chroot](https://en.wikipedia.org/wiki/Chroot)
*   LXC: [https://en.wikipedia.org/wiki/LXC](https://en.wikipedia.org/wiki/LXC)
*    Docker, Inc.: [https://en.wikipedia.org/wiki/Docker,_Inc.](https://en.wikipedia.org/wiki/Docker,_Inc.)
*   Serverless: [https://phpa.me/serverless](https://phpa.me/serverless)
*   Bref: [https://bref.sh/](https://bref.sh/)
*   Serverless Framework: [https://www.serverless.com](https://www.serverless.com)
*   Apache OpenWhisk: [https://openwhisk.apache.org](https://openwhisk.apache.org)
*   Appwrite: [https://appwrite.io](https://appwrite.io)

## Types of Tokens**

This article defines different types of **tokens** used in security, emphasizing the importance of precise terminology. We will explain how each term is used, to help avoid missteps when working with different systems and tools.
  
```
     .
    / \
   /   \
  -----
 /     \
/_______\
```
   
We will explore:
*   The difference between a nonce, a CSRF token, an access token, and a refresh token.
*   How **Cross-Site Request Forgery (CSRF)** tokens prevent malicious attacks by ensuring that form submissions are coming from the server.
*   The functionality of a random token as a nonce to prevent replay attacks.
*   The purpose of **access tokens** in OAuth and how they represent a valid authenticated session with the server.
*   The use of **refresh tokens** to obtain new access tokens after existing ones expire.
*  The critical need to keep access and refresh tokens secret.
*   The importance of communication and removing avenues for misunderstanding in software engineering.
*   How WordPress nonces are not strictly numbers and have a limited "lifetime".

This article will provide a clear understanding of different types of tokens and their specific roles in securing web applications.
**References:**
*   Nonces: [https://phpa.me/nonces](https://phpa.me/nonces)
*   Access tokens: [https://phpa.me/access-tokens](https://phpa.me/access-tokens)
*   Refresh tokens: [https://phpa.me/refresh-tokens](https://phpa.me/refresh-tokens)

**Article 5: Welcome to Barrier-Free Bytes**

This article introduces a new column focused on **accessibility and inclusion**. We aim to provide insights and tools to improve coding practices for users of all abilities. 

```
  _ _ _
 /  |  \
|   |   |
 \_|_/_
```
   
We will discuss:
*   An introduction to the author, Maxwell Ivey, also known as the Blind Blogger, and his journey from carnival owner to accessibility expert.
*   The importance of making products, services, and marketing content more inclusive of people with disabilities.
*   The goal of expanding understanding about the needs of people with disabilities to improve coding practices.
*   The importance of communication and collaboration in building an accessible online world.
*   The author's personal experience in building websites and facing accessibility challenges as a blind person.
*  The importance of feedback and open communication about accessibility issues.
*   The journey from a simple website to becoming an author, speaker, and accessibility expert.
*   The invitation for readers to ask questions and engage in discussions about accessibility.

This article aims to foster a better understanding of accessibility and inspire developers to make their work more inclusive and user-friendly for all.
**References:**
*   www.midwaymarketplace.com: [http://www.midwaymarketplace.com](http://www.midwaymarketplace.com)
*   www.theblindblogger.net: [http://www.theblindblogger.net](http://www.theblindblogger.net)

## Databases as a Service**

This article explores the benefits of using **Database as a Service (DBaaS)** and how to migrate to a DBaaS host. We will cover the benefits of separating the database from the application layer.

```
  _    _
 /_\  /_\
|   ||   |
\ /  \ /
 -----
```
We will discuss:
*   The advantages of separating the database from the PHP application, including increased scalability and improved security.
*   The use of DBaaS to offload the database service to a managed host, reducing the burden of managing your own database server.
*   The ability to scale each layer independently and the benefit of isolating the cause of any problems.
*   The optimization of database and application layers for their specific needs, using resources such as CPU, memory, and storage.
*   The ease of upgrading databases and how the DBaaS provider handles many maintenance tasks.
*  The potential to migrate to a new DBaaS provider or self-managed server without affecting the application layer.
*   Popular database servers among PHP developers: MariaDB and MySQL.
*   Key features of DBaaS providers such as uptime, reliability, scalability, security and transparent pricing.
*   An overview of key players in the DBaaS market, including Microsoft Azure Database, Amazon RDS, and Digital Ocean Managed Databases.
*   A step by step example of migrating an application by creating a MySQL database and configuring a connection to the application.

This article aims to provide readers with a practical understanding of how to leverage DBaaS to improve their PHP application's scalability, security, and maintainability.
**References:**
*   SOC type 2: [https://phpa.me/cloud](https://phpa.me/cloud)

## Maze Rats, Part Three**

This article focuses on **maze-solving algorithms**, specifically the **dead-end filling** method. It builds on previous articles in the series about maze generation.

```
       _
      / \
     /   \
    |     |
     \   /
      \_/
```
We will explore:
*   A recap of maze generation and the representation of maze cells using hex values.
*   Two common approaches to solving a maze: random mouse and dead-end filling.
*   Identifying dead-ends as cells with only one exit.
*   The process of "filling in" dead-ends by closing off those paths.
*  A brute-force approach to finding and filling dead-ends.
*   A more efficient recursive approach to filling in dead-end paths and avoiding repeated scans of the maze.
*   The presentation of the path in red using a renderer and image editing software.
*   The challenges of generating a text based output for directions from the entrance to exit.

This article aims to provide a practical understanding of maze-solving algorithms using PHP, focusing on efficient techniques.
**References:**
*   maze-solving algorithms: [https://phpa.me/maze-algo](https://phpa.me/maze-algo)

## Create Observability, Part 1: Local Timing**

This article explores a simple method for creating **observability** in PHP code using the `microtime()` function. This is a quick and easy method for measuring performance during development.

```
    _______
   / _____ \
  | /   \ |
  | \___/ |
   \_____/
```
We will discuss:
*   The use of a 30-line timing library based on the built-in `microtime()` function.
*   The reasons to use this tool: to verify that code is working as expected and to assess the performance of code blocks.
*   The differences between using this type of tool versus other existing logging applications and framework tools.
*   The low overhead of this method and why it isn't a production solution.
*   A practical implementation of the `Timing` class using the `microtime()` function.
*   How to insert `measure()` calls inside code blocks to get timing details.
*   The way to track execution times and total run times.
*  The method of limiting the output in non-development environments.

This article provides a quick and straightforward method for PHP developers to implement basic timing and observability tools in their code.
  
**Article 9: PER: Coding Style**

This article introduces the PHP Evolving Recommendation (PER) for **Coding Style**, which builds on and expands PSR-1 and PSR-12. We explore how the PER is a living document that will evolve with the PHP community.

```
   _____
  / ____\
 | (____)
  \____/
```
   
We will discuss:
*   The differences between a static PSR and a dynamic PER.
*   The purpose of PER Coding Style as a replacement of PSR-12.
*   How PER uses semantic versioning to track changes and improvements.
*   The reasoning for making PER a living document due to the rapid evolution of PHP.
*   The process of how PHP-FIG gather community opinions to help form decisions.
*   Some discussions and challenges of deciding on aspects that belong in the coding standard versus the coding style.
*   The results of surveys from PHP-FIG Project Representatives and general non-representatives.
*   The importance of standards in improving communication, workflow, and integrations in the PHP ecosystem.
*   Links to the PER coding style documentation and Github repository.

This article will provide readers with an understanding of the latest standards in PHP coding and help them adopt best practices in their own projects.
**References:**
*   PHP-FIG Discord server: [https://discord.gg/php-fig](https://discord.gg/php-fig)
*   PER: Coding Style: [https://www.php-fig.org/per/coding-style/](https://www.php-fig.org/per/coding-style/)
*   PSR-1: [https://www.php-fig.org/psr/psr-1/](https://www.php-fig.org/psr/psr-1/)
*    PSR-12: [https://www.php-fig.org/psr/psr-12/](https://www.php-fig.org/psr/psr-12/)
*   PHP-FIG Project Representatives: [https://phpa.me/fig-cs](https://phpa.me/fig-cs)
*   General Non-Representatives: [https://phpa.me/git-cs-nrv](https://phpa.me/git-cs-nrv)
*   Declare statements in PHP files: [https://phpa.me/fig-cs-declare](https://phpa.me/fig-cs-declare)
*   Github repository: [https://phpa.me/fig-cs-releases](https://phpa.me/fig-cs-releases)

## Defensive Programming for Laravel**

This article covers **defensive programming techniques in Laravel**, ensuring that applications are secure and robust. We will explore how to protect data and prevent potential attacks using best practices.

```
     ____
    /   /
   /   /
  /___/
 /   /
/____/
```
We will discuss:
*   The importance of a defensive mindset in building secure applications.
*   The need to sanitize and validate user inputs to prevent cross-site scripting (XSS) attacks.
*   The use of database abstraction tools to avoid SQL injections.
*   The importance of using a framework's core components over custom-built components.
*   The concept of trusting tooling rather than developers, including aggressive QA and penetration testing.
*   The importance of following SOLID code principles.
*   How to defend against SQL injection attacks by using Laravel's ORM, Eloquent.
*   How to protect against broken authentication attacks by implementing rate limiting and denying compromised passwords.
*   The concept of drive-by downloads and how a good DevOps policy can help prevent them.
*   How to protect against DDoS attacks by limiting requests at the server or application level.
*   The prevention of man-in-the-middle attacks by using HTTPS for all traffic.
*   How to prevent directory traversal attacks with a sound directory structure.
*  The need to inform customers about data breaches and to have plans in place to restore your system and data when an attack occurs.

This article provides a comprehensive guide to defensive programming techniques that all Laravel developers should follow to ensure their applications remain secure.
**References:**
*   https://endoflife.date/

**Article 11: Do We Deserve to Be Here?**

This article is a reflective piece on the **PHP community's journey** and how the language has overcome criticism to become a strong and reliable platform.

```
  ____
 /    \
|      |
\____/
```
We will discuss:
*   The history of jokes and criticisms directed at PHP and its developers.
*   The need to constantly justify PHP's existence and how this has affected the community.
*   The internal arguments and toxicity within the PHP community when trying to imitate other languages.
*   The community's burnout and how it led to a lack of focus.
*  The community's recovery and collaboration that helped revitalize the language.
*   The steady releases, standards group, and a foundation for the continued development of the language.
*  The strength of the PHP community and the need to focus on its authenticity rather than comparing to other languages.
*   The message that PHP is a strong language that should not have to prove its worth.

This article aims to celebrate the PHP community's resilience and provide encouragement for developers to focus on being authentically PHP.
**References:**
*   Treeline Design: [http://www.treelinedesign.com](http://www.treelinedesign.com)
*   Exploricon: [http://www.exploricon.com](http://www.exploricon.com)
*   Madison Web Design & Development: [http://madwebdev.com](http://madwebdev.com)
*   Full Stack Madison: [http://www.fullstackmadison.com](http://www.fullstackmadison.com)

**Postface with Index of Technology Words**

Thank you for reading this month’s issue of *The PHP Teacher Magazine*! We hope these articles have provided valuable insights and practical knowledge that you can use to improve your PHP development skills. Here is a list of key technology terms covered in this issue:
*   Value Objects
*   Immutability
*   Replaceability
*   Value Equality
*   Side-Effect-Free Behavior
*   Data Validation
*   phpspec
*   TDD
*   BDD
*   Internet of Things (IoT)
*   Extended Reality (XR)
*   Virtual Reality (VR)
*   Augmented Reality (AR)
*   Smart Devices
*   Digital Twins
*   Bare Metal Servers
*   Virtual Machines
*   Hypervisor
*   Containers
*   Docker
*   LXC
*   Serverless Computing
*   Tokens
*   Nonce
*   Cross-Site Request Forgery (CSRF)
*   Access Token
*   Refresh Token
*   Accessibility
*   Database as a Service (DBaaS)
*   MariaDB
*   MySQL
*   Dead-End Filling
*   Observability
*  Microtime
*   PER (PHP Evolving Recommendation)
*   PSR (PHP Standard Recommendation)
*  Defensive Programming
*   Laravel
*   Cross-Site Scripting (XSS)
*   SQL Injection
*   Broken Authentication
*   DDoS (Distributed Denial of Service)
*   MiTM (Man-in-the-middle)

We look forward to bringing you more exciting content in our next issue. Keep coding!
