---
title: PHP Teacher magazine - July 2022
publishDate: 2023-04-19 00:00:00
description:  'Welcome to another exciting edition of "The PHP Teacher Magazine"! This month, we delve into a variety of topics crucial for any PHP developer looking to enhance their skills and stay ahead of the curve.'
image: /assets/services/security.svg
tags:
  - php
  - magazine
  - july
  - 2022
---

### Introduction to This Month's Issue

Welcome to another exciting edition of "The PHP Teacher Magazine"! This month, we delve into a variety of topics crucial for any PHP developer looking to enhance their skills and stay ahead of the curve. We'll explore practical techniques, best practices, and new technologies, providing you with the knowledge and tools to tackle complex challenges.

From **team building** and **continuous learning** to the intricacies of **database management with MongoDB**, we cover a broad spectrum of topics. We'll also examine how to streamline **Drupal migrations** using custom Feeds Tamper plugins and delve into the world of **version control with Git.** We'll demystify **multifactor authentication**, explore **containerization with Docker**, and navigate the complexities of **HTTP message interfaces with PSR-7.** Finally, we'll touch on important concepts like **domain-driven design** and the creation of **custom Drupal modules**.

Each article in this issue is designed to provide clear, actionable insights, complete with code examples, references, and even some fun ASCII art. Whether you're a seasoned professional or a budding enthusiast, we're confident that this month's issue will provide valuable knowledge to enhance your development journey. Let's dive in!

## An Ode to Team Building

```
      _    _
     ( |  | )
      \ `--' /
       \    /
        `.  .'
          ||
        _ || _
       (______)
```

**Team building** and **continuous education** are often underutilized by companies, yet they can significantly impact a company's success. Investing in these activities can foster a more collaborative, innovative, and ultimately successful workplace. This article explores the importance of team building and continued learning in a professional environment.

#### The Importance of Team Building

Team building goes beyond just social events; it's about creating a cohesive unit where members trust, support, and collaborate effectively. When teams operate well, they can tackle complex problems, generate creative solutions, and enhance overall productivity.

*   **Improved Communication**: Team-building exercises often require communication, which can help break down barriers and improve information flow between team members. This reduces misunderstandings and fosters a more transparent work environment.
*   **Increased Collaboration**: By working together on shared goals during team-building activities, individuals learn to appreciate each other's strengths and weaknesses. This facilitates more effective collaboration on daily projects.
*   **Enhanced Morale**: When employees feel valued and connected, their morale and job satisfaction increase. Team-building activities provide a space for employees to relax, have fun, and build personal connections.
*   **Better Problem-Solving**: A team that can communicate and collaborate well can also solve problems more effectively. Diverse perspectives and ideas lead to creative and robust solutions.

#### Continuing Education

In the ever-evolving landscape of technology, continuous education is essential. It allows developers to stay up-to-date with the latest tools, techniques, and trends, ensuring they can contribute effectively to the team.

*   **Skill Enhancement**: Education programs help developers learn new skills, improving their versatility and ability to adapt to changing project requirements.
*   **Innovation**: Exposure to new ideas and technologies sparks innovation. By understanding cutting-edge developments, developers can apply these to projects to improve efficiency and quality.
*   **Increased Confidence**: Learning new skills boosts a developer's confidence, encouraging them to take on new challenges and contribute more effectively.
*   **Competitive Advantage**: Companies that invest in continuing education equip their teams to maintain a competitive advantage in the market by producing higher-quality, state-of-the-art software.

**Reference:**
*   John Congdon, "An Ode to Team Building," *The PHP Teacher Magazine*, 2023

## MongoDB and PHP: A Perfect Match

```
        ,@@@@@@@,
    @@@@@@@@@@@@@@@
  @@@@@@@@@@@@@@@@@@@
 @@@@@@@@@@@@@@@@@@@@@
 @@@@@@@@@@@@@@@@@@@@@
 @@@@@@@@@@@@@@@@@@@@@
  @@@@@@@@@@@@@@@@@@@
    @@@@@@@@@@@@@@@
       '@@@@@@@'
```

**MongoDB** has become an increasingly popular choice for building **large-scale PHP applications**, offering a flexible and scalable alternative to traditional relational databases. This article explores how MongoDB and PHP work together and the advantages of using a document database over a relational one.

#### Document Databases vs. Relational Databases

Traditional databases like MySQL use a relational model based on tables with predefined schemas. MongoDB, on the other hand, is a document database.

*   **Schema Flexibility**: Unlike relational databases, MongoDB is schemaless, meaning you don't have to define a strict schema upfront. This flexibility is useful for storing data that can vary in structure. You can add or remove fields as needed without altering the database schema.
*   **Scalability**: MongoDB can handle massive amounts of data and concurrent traffic, making it ideal for large applications. It can be scaled horizontally by adding more servers to your cluster.
*   **Data Structure**: Data in MongoDB is stored as JSON-like documents, which closely resemble the objects developers work with in their code. This allows developers to interact more naturally with the data.

#### Using MongoDB with PHP

MongoDB's flexible structure allows for more intuitive data modeling. This allows you to embed related data inside a single document instead of creating multiple related tables.

*   **Installation**: You can install MongoDB on your local machine, use the official Docker container, or use MongoDB Atlas, a cloud-based service. The easiest way to start is with a free cluster on MongoDB Atlas.
*   **PHP Driver**: To interact with MongoDB from PHP, you need to install the MongoDB PHP driver using `pecl`. Once installed, you can start connecting to your MongoDB instance using PHP.

#### Practical Examples: CRUD Operations with MongoDB

This article provides some code examples showing how to interact with MongoDB with PHP.
Here are code examples using `insertOne` for creating a new record, `findOne` for reading from the database, `updateOne` for updating records, and `deleteOne` for deleting documents.

```php
// Create a new speaker
$newSpeaker = [
  'name' => 'Joel Lord',
  'bio' => 'Joel Lord is a developer advocate at MongoDB…',
    'socials' => [
        'Twitter' => '',
        'Github' => ''
        ]
];
$result = $collection->insertOne($newSpeaker);
echo('New user inserted with id '.$result->getInsertedId());

// Read a speaker
$speaker = $collection->findOne();
echo('The first speaker found in the collection is '.
$speaker->name);

// Update the bio for a speaker
$collection->updateOne(
    ['_id' => $speaker->_id],
    ['$set' => ['bio' => 'New bio']]
);

// Delete the speaker
$result = $collection->deleteOne(['name' => 'William Wright']);
```

*   **Optimized Data Schema**: Instead of using multiple tables for speakers, social links, and talks, you can embed social links directly within the speaker document, reducing the need for joins. For example, you can embed the social links into the speaker object, or the talk information into the speaker object.
```php
[
    '_id' => 1,
    'name' => 'Joel Lord',
    'bio' => 'Joel Lord is a developer advocate at MongoDB…',
    'socials' => [
    'Twitter' => '',
    'Github' => ''
    ]
]
```
*   **Aggregation Pipelines**: MongoDB also supports complex data transformations using aggregation pipelines, enabling you to manipulate and analyze data before displaying it. These pipelines use a series of stages to filter, sort, and project the desired data.

**References:**
*   Joel Lord, "MongoDB and PHP—A Perfect Match," *The PHP Teacher Magazine*, 2023


##  Customizing Drupal Feeds for Smooth Migrations

```
       /\_/\
      ( o.o )
      > ^ <
```

**Drupal migrations** can be complex, but the **Feeds module** provides a robust tool for importing content. This article explores how custom Feeds Tamper plugins can be used to manipulate data during migrations, simplifying complex import tasks.

#### Feeds Tamper Plugins

The **Feeds Tamper module** allows you to modify data as it is imported into Drupal. It offers a wide array of built-in plugins for data transformation. However, sometimes, custom plugins are necessary to handle specific data quirks.

*   **Creating a Custom Plugin:** To create a custom Feeds Tamper plugin, you need to create a module, define a plugin class, and add the correct plugin annotation. The plugin class extends `TamperBase` and resides in the namespace `Drupal\my_tamper\Plugin\Tamper`.
*   **Plugin Annotation:** The plugin annotation, denoted by `@Tamper`, includes a unique `id`, a user-friendly `label`, a detailed `description`, and a `category` to place it in the user interface.

Here's an example of a plugin annotation:

```php
/**
 * Plugin implementation for converting URL into Article field_id.
 *
 * @Tamper(
 *   id = "url_to_article_ref",
 *   label = @Translation("Convert URL into Article field_id"),
 *   description = @Translation("Convert URL into Article field_id"),
 *   category = "Text"
 * )
 */
```
*   **Dependency Injection:** Best practice is to inject services using dependency injection by using the `ContainerFactoryPluginInterface`. Dependency injection promotes flexibility and testability by allowing for mocking services in unit tests.
*   **Overriding the `tamper` Function:** The `tamper` function performs the data manipulation. For example, it might convert a path alias into an actual node ID. It's also a good idea to do data cleanup and sanitisation inside the tamper. You may want to skip some values that do not match, or return empty strings depending on your application requirements.

Here is a code example that uses a `PathAliasManager` and a `preg_match` to return a node id:
```php
public function tamper($data, TamperableItemInterface $item = NULL) {
    // Dump any get parameters on the URL for a clean alias.
    $data = strtok($data, '?');
    $path = $this->pathAliasManager->getPathByAlias($data);
    if (preg_match('/node\\/(\\d+)/', $path, $matches)) {
        return $matches;
    }
    return '';
}
```
#### Example: Converting URL to Entity Reference

One common use case is converting a URL field to an entity reference. Suppose you need to import content from Drupal 7 to Drupal 9, where the "Internal Link" field in Drupal 7 stores an aliased URL, and the corresponding field in Drupal 9 must be an entity reference. You could create a custom tamper plugin to:

1.  **Clean up** the URL by removing any GET parameters.
2.  **Use the `PathAliasManager` service** to convert the aliased URL to the Drupal path.
3.  **Extract the node ID** from the path and return the node ID.
4.  **Use this ID** to set the entity reference field.

#### Reusability

The real power of the plugin system is its reusability. You can use the plugin as a template to build your own library of tamper plugins to supplement the already existing plugins.

**References:**
*   Doug Groene, "Customizing Drupal Feeds For Smooth Migrations," *The PHP Teacher Magazine*, 2023


## What is Git Doing?

```
        o
       /|\
      / | \
     /  |  \
    /   |   \
   /____|____\
```

**Git**, the most dominant version control system, is a powerful tool that tracks changes made to files. This article demystifies what Git is actually doing behind the scenes when we write our code.

#### Version Control Systems: A History

Before Git, there were other version control systems like CVS (Concurrent Versions System) and RCS (Revision Control System). These systems helped developers move away from manually backing up files by keeping track of changes made to files.

#### Git: A Decentralized Approach

Git was designed for decentralized collaboration. Unlike systems like SVN and CVS, which have a single, canonical source for a project, Git allows users to download entire copies of a repository. This enables more flexible workflows and branching.

#### Git's Internal Structure

At its core, Git stores a project's state as a series of snapshots, which are referred to as "commits".
*   **Snapshots as Trees**: Git doesn’t track changes between files but stores each snapshot as a tree, which is a complete copy of the directory and files at that moment.
*   **Commits**: These snapshots are turned into commits that contain metadata such as what trees are included in the commit, author details, and parent information. The parent information allows Git to trace the lineage of commits.
*   **Head**: Instead of storing the information as a history, git stores the location where the repository is currently, which is called `HEAD`.
*   **Branches:** Branches in Git are just a reference to a commit that may have child commits.
*  **Merging**: When merging, Git can create a special merge commit that takes two branches, figures out the optimal merge between files, and creates a commit with multiple parents.

#### How Git Works

*   **Branching**: Branching in Git is a very efficient operation, where multiple commits can point to a single parent commit.
*   **History**: When people show Git histories, they are displayed in a way that makes sense to people. However, Git technically stores where the repository is located now, and which commit is its parent.
*  **Commits**:  Commits are just snapshots of the file system at a specific point in time.

**Reference:**
*  Chris Tankersley, "What is Git Doing?", *The PHP Teacher Magazine*, 2023


## Demystifying Multifactor Authentication

```
   _   _
  | | | |
  | |_| |
  |_____|
  |  _  |
  | | | |
  |_| |_|
```

**Multifactor authentication (MFA)** enhances security by adding extra authentication factors to the traditional username and password login process. This article explains what authentication factors are and the trade-offs between different methods.

#### Authentication Factors

Authentication factors are the methods used to verify a user's identity. These factors are generally categorised into:

*   **Something you know**: This includes traditional passwords, PINs, or security questions. This is the most common method but is also the weakest since this information can be phished or guessed.
*   **Something you have**: This includes items that you have on your person, like a smartphone or security key. Using a device for authentication is more secure than relying just on your memory.
*   **Something you are**: This includes biometric data like fingerprints, facial recognition, or voice recognition. These methods are the most convenient but may have privacy implications.

#### Magic Links and Email

Email can be an effective way to use MFA, where a "magic link" is emailed to the user for authentication. However, this method still has some vulnerabilities. If the user has a compromised email account, or if the email gets intercepted, then access can be granted to an unauthorised user.

#### Importance of MFA

Using multiple authentication factors builds a much stronger security approach, making it difficult for attackers to gain access to an account. It protects user accounts from stolen credentials, brute-force attacks, and phishing.

**Reference:**
* Eric Mann, "Demystifying Multifactor Authentication," *The PHP Teacher Magazine*, 2023

## PHP from Virtual Machine to Docker

```
    .---.
   /     \
  |()  ()|
  \  \/  /
   `----'
```

**Containers** are a vital part of modern web development, and **Docker** is a leading platform for containerization. This article guides you through the process of migrating a PHP application from a virtual machine (VM) to containers using Docker.

#### The Need for Containerization

Containerization allows you to package applications and their dependencies into isolated containers that can run consistently across different environments.

*   **Consistency**: Containers ensure consistent application behavior across development, testing, and production environments, eliminating "it works on my machine" issues.
*   **Efficiency**: Containers share the host OS kernel, making them more lightweight and resource-efficient than VMs.
*   **Portability**: Containers can be easily moved between different hosts and cloud providers, simplifying deployment.
*   **Scalability**: Docker makes scaling your application simpler by running multiple instances of the same image.

#### Migrating to Docker

The article walks through the process of migrating a simple PHP application with MariaDB and Redis from a VM to Docker. This involves creating a container image for the PHP application and using existing Docker images for MariaDB and Redis.

*   **Dockerfile**: The `Dockerfile` is the source of a container image and contains instructions on how to provision a container. This includes setting up the necessary packages, copying code, and configuring environments.
*   **Build Process**: Using the command `docker build . -t svpernova09/rfid` will build a docker image from the current directory, and tag it with `svpernova09/rfid`. This image can then be used to spin up multiple containers of your application.
*   **Docker Registry:** A Docker registry stores and manages docker images. Similar to how packagist handles php libraries, Docker Hub and similar platforms track docker images and their versions.
*   **Docker Compose:** `Docker Compose` is an efficient tool for defining and managing multi-container applications. You can use a `docker-compose.yml` file to define your applications services, their configurations, and their dependencies. The `docker-compose up` command then makes it easy to run multiple services.
*   **Volumes**:  By using volumes, you can persist data even when containers are restarted, which is very important for databases and logs.
*   **Health Checks**: Health checks ensure your application is running before starting dependant services. In the provided example, the application has a dependency on `MariaDB`. Setting `condition: service_healthy` in the `docker-compose.yml` file ensures that the application only starts after the health check of the database passes.
* **Service Dependencies**: When using docker compose it's important to specify service dependencies using the `depends_on` keyword.
Here is a docker-compose file example:

```yaml
rfid_app:
  image: svpernova09/rfid:latest
  volumes:
    - ./logs:/var/www/html/storage/logs
  ports:
    - "8000:80"
  depends_on:
    mariadb:
      condition: service_healthy
    redis:
      condition: service_started
  env_file:
    - docker/docker.env
```

#### Best Practices
*   **Private Registries**: Do not push your application images to public registries, as this may reveal source code and configuration secrets.
*   **Hardening Passwords**: Make sure to harden your passwords.
*   **SSL certificates**: Use something like Traefik to route traffic and handle SSL certificates for your application.
*   **Database Backups**: Create a database backup and recovery plan.
*   **Docker Stacks**: Consider using Docker Stacks for stability.

**References:**
*   Joe Ferguson, "PHP from Virtual Machine to Docker," *The PHP Teacher Magazine*, 2023

## PSR-7 HTTP Message Interface

```
      _ _ _
   . (   _   ) .
  (    | |    )
  (  (_/ \_)  )
   '---------'
```

The **PSR-7 HTTP Message Interface** provides a standardized way to handle HTTP messages in PHP. This article explores the rationale behind the interface and how to use it effectively.

#### The Importance of Standardized Interfaces

Before the PSR-7, PHP projects often created their own implementations for handling HTTP requests and responses, leading to incompatibilities and inconsistencies. The purpose of PSR-7 is to provide a common interface for HTTP messages. This allows for more interoperability between libraries and frameworks.

#### Request and Response Objects

The key components in PSR-7 are the `RequestInterface` and the `ResponseInterface`.

*   **RequestInterface**: Represents an incoming HTTP request, including the URI, headers, and body.
*   **ResponseInterface**: Represents an HTTP response, including the status code, headers, and body.

A typical request would look like this:
```
GET /users HTTP/1.1
Host: example.com
Accept: application/json
```
A typical response would look like this:
```
HTTP/1.1 200 OK
Content-Type: application/json
Cache-Control: max-age=100

{
  "users": [
    {"id": 1, "name": "John Doe"}
   ]
}
```
#### Key Concepts

*   **Immutability**:  PSR-7 objects are immutable, which means that the `with*()` methods do not change the original object. Instead, they return a new object with the changes applied. This can help improve the predictability of the system.
*   **URI Objects**: The `Uri` object represents the URI of the request or response. You can manipulate the URI using withPath(), withQuery(), etc..
*   **Body Handling**: The `getParsedBody()` method can return any value, but the structure should depend on the application. In a POST request this would be the values submitted in the POST payload.
*   **Header Handling**: Header names are case insensitive. Any header names and values that contain invalid values such as NUL characters `\r` and `\n` should be rejected by the implementation.
* **Value Objects**: PSR-7 interfaces work with value objects, which are immutable objects that are based on their value rather than their identity.

#### Practical Example
```php
$uri = new Uri('http://foo.dev');
$baseRequest = new Request($uri,null, [
    //headers ...
]);
$newRequest = $baseRequest->withUri($uri->withPath('/list'))->withMethod('GET');
$anotherRequest = $baseRequest->withUri($uri->withPath('/users'))->withMethod('GET');
```
In this example, a `baseRequest` is created, and is then used as a blueprint for other requests that are created by calling the `withUri` method. The `withUri` method then creates a new request with the new URI. Any other values that were set in the `baseRequest` are kept.
#### Benefits of PSR-7

By using the PSR-7 standard you can:

*   **Interoperability**: This allows different libraries and frameworks to work together because they share the same interfaces.
*   **Testability**: The immutability of PSR-7 objects simplifies testing because you can rely on the fact that an object will not change unexpectedly.
*   **Flexibility**: You can use PSR-7 objects in your own applications or within libraries without being tied to any one particular implementation.

**References:**
*  Frank Wallen, "PSR-7 HTTP Message Interface," *The PHP Teacher Magazine*, 2023


## Structure by Use Case

```
     ____
    /    \
   |  ()  |
   \  ||  /
    `----'
```

**Domain-Driven Design (DDD)** emphasizes structuring software around business logic. This article introduces the **Bounded Context pattern** and how to use it to organize code by use cases.

#### The Bounded Context Pattern

The Bounded Context pattern helps to organize large domains by defining explicit boundaries within a system. These boundaries create separation between areas of responsibility, allowing teams to better manage the complexity of the project.

*   **Strategic Design**: DDD involves organizing large domains into a network of Bounded Contexts, preventing any one area from becoming too complex.
*   **Use Cases**: A Bounded Context encapsulates a specific part of the domain, with a clear purpose and boundaries.
*   **Code Organization**: Bounded Contexts are represented by folders within the source tree, each containing the necessary code for that domain.

#### Software Layers within a Bounded Context

Within each Bounded Context, you should include the following folders:

*   `ApplicationServices`: This layer represents the use cases and application logic by coordinating the fulfillment of a use case.
*   `DomainModel`: This layer contains the core domain logic, which includes the entities, value objects, and domain events.
*   `Factory`: This layer is responsible for creating instances of objects in the domain.
*   `Repository`: This layer is an abstraction over the data layer, and it provides a method for interacting with data storage, whether it's a database, file system or other data sources.

#### Implementing a Use Case

1.  **Command-Line Tool**: Use a command-line tool to trigger and exercise use cases.
2.  **Factory**: Use a factory to create the application service responsible for handling the use case.
3.  **Repository**: The repository manages the interactions with the database or any other data sources.
4.  **Application Service**: The application service is responsible for coordinating actions and it contains the business logic for that specific use case.

#### Repository Pattern

The repository pattern helps isolate data access logic, making the application less dependent on the underlying data storage. It provides a way to abstract the data access layer of a framework.

*   **Abstraction**:  The repository is an intermediary layer between your application and the database, it prevents business logic from becoming intertwined with database specific code.
*   **Simplified Queries**: The repository provides explicitly named methods, making the application code more understandable.
*   **Testability**: By isolating the data access layer, the application service becomes easier to test since it will not have framework-specific dependencies.

#### Application Services

An application service should implement a specific use case by delegating to the domain and infrastructure layers. It should remain free of any framework-specific code.
The repository class may include framework specific implementations. By separating these concerns, the application becomes easier to test.

**References:**
*  Edward Barnard, "Structure by Use Case," *The PHP Teacher Magazine*, 2023
