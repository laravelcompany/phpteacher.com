---
title: PHP Teacher magazine - June 2022
publishDate: 2023-04-19 00:00:00
description: "Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you"

image: /assets/services/security.svg
tags:
  - php
  - magazine
  - june
  - 2022
---

Welcome to this month's issue of PHP Teacher Magazine! We've got a diverse range of articles to explore, covering everything from **home automation with PHP** to **advanced application architecture** and **cybersecurity**. Let’s delve into what this issue has in store for you.


## Illuminating Smart Homes with PHP: Controlling Philips Hue**

```
      _   _             _       _
     | | | | ___   ___ | | ___ | |_   _
     | |_| |/ _ \ / _ \| |/ _ \| __| (_)
     |  _  | (_) | (_) | | (_) | |_   _
     |_| |_|\___/ \___/|_|\___/ \__| (_)
```

PHP, traditionally known for web development, is expanding its reach into the realm of the Internet of Things (IoT). This article delves into using PHP to control Philips Hue smart light bulbs, demonstrating how a seemingly unconventional language can manage home automation devices. We'll explore a PHP library, **Phue**, and its capabilities to manipulate these smart lights, moving beyond basic on/off controls to more sophisticated colour adjustments and effects.

**The Rise of PHP in IoT**

PHP’s increasing popularity in IoT stems from its accessibility, ease of use and the growing availability of libraries that bridge the gap between the web and physical devices. While languages like Python might be more commonly associated with IoT, PHP's versatility makes it a viable option for developers familiar with its syntax. The ability to leverage existing PHP skills in a new domain allows for faster prototyping and development of innovative solutions.

**Introducing Phue: A PHP Library for Philips Hue**

The **Phue** library, originally created as sqmk/Phue and later forked and updated by `neoteknic/Phue`, serves as the foundation for our exploration. This article uses a further fork by syntaxseed/phue. This library simplifies interactions with the Philips Hue Bridge. A PHP developer can use this library to control and manage Hue lights without needing to delve into the complexities of the Hue API.

*   **Installation:** The library is installed using Composer, a dependency manager for PHP.
    ```
    composer require syntaxseed/phue
    ```

*   **Autoloading:** Composer's autoload script needs to be included in the project's bootstrapping.
    ```php
    require_once '../vendor/autoload.php';
    ```

**Establishing a Connection**

Before controlling the light bulbs, it's essential to connect to the Philips Hue Bridge. This bridge acts as the central hub, communicating with the light bulbs.

1.  **Finding the Bridge:** A script included with the **Phue** library assists in finding the bridge's IP address.

    ```
    ./bin/phue-bridge-finder
    ```

    The output reveals the bridge’s internal IP address. For example: `192.168.1.2`.
2.  **Creating a User:** A user must be authorised on the Hue bridge by pressing the button on the device, before commands can be sent using PHP. The following script will create the user.

    ```
    ./bin/phue-create-user 192.168.1.2
    ```

    Upon success, a unique username is returned. This username, along with the bridge's IP address, is crucial for subsequent steps.

**Controlling Lights and Groups**

Hue lights can be grouped into 'rooms', allowing for simultaneous control. The **Phue** library enables listing and controlling these lights and groups.

*   **Creating a Client:** To start, a `Client` object must be created, using the Bridge IP address and username.

    ```php
    use \\Phue\\Client;
    $client = new Client('192.168.1.2', 'abcdefg123456');
    ```

*   **Listing Lights and Groups:** Using the `Client` object, the application can list the connected lights and their associated groups.

    ```php
    $lights = $client->getLights();
    $groups = $client->getGroups();

    foreach ($lights as $lightId => $light) {
        echo "Id #{$lightId} - {$light->getName()} \n";
    }

    foreach ($groups as $group) {
        $groupLightIds = implode(', ', $group->getLightIds());
        echo "Id #{$group->getId()} - {$group->getName()} (Type: {$group->getType()}) Lights: {$groupLightIds} \n";
    }
    ```

**Basic Light Manipulation**

Turning lights on and off is fundamental. The `setOn()` method is used to toggle the light's state. The library simulates a transition effect of fading on and off rather than an instant change.

```php
$lights = $client->getLights();
$light = $lights;
$light->setOn(true);
sleep(2);
$light->setOn(false);
```

**Colour, Brightness and Effects**

The colour and brightness of the bulbs can also be controlled programmatically. The `setBrightness()` function adjusts the brightness from 0 to 255. For coloured bulbs, methods such as `setRGB()` allow setting the light to any colour from the RGB spectrum.

```php
$light->setOn(true);
$light->setBrightness(255);
$light->setRGB(255, 0, 0); // Red
sleep(2);
$light->setRGB(0, 255, 0); // Green
sleep(2);
$light->setRGB(0, 0, 255); // Blue
```

**Advanced Effects**

The library also supports more complex effects like the 'colorloop', which cycles the light through colours or the creation of a flickering candle effect. The candle effect simulates the flickering through the use of random brightness and colour temperature, demonstrating the potential of the **Phue** library in creating dynamic lighting environments.

```php
// Flickering candle effect.
for ($i = 1; $i < 100; $i++) {
  $brightness = rand(20, 50);
  $colorTemp = rand(420, 450);
  $transitionTime = rand(0, 3) / 10;
  $waitTime = $transitionTime;

  $command = new SetLightState(1);
  $command->brightness($brightness)
          ->colorTemp($colorTemp)
          ->transitionTime($transitionTime);

  $client->sendCommand($command);
  usleep($waitTime * 1000000 + 25000);
}
```

**Scenes**

Scenes allow the saving of particular combinations of light settings to be easily restored. This is useful for thematic or mood-based lighting. The code demonstrates the creation of a scene called "PHP Architect" that sets different lights to orange and purple, using the `CreateScene` and `SetSceneLightState` methods.

```php
$client->sendCommand(new CreateScene('phparch', 'PHP Architect',));
$command = new SetSceneLightState('phparch', 1);
$command = $command->brightness(200)->rgb(220, 90, 33); // Orange
$client->sendCommand($command);
$command = new SetSceneLightState('phparch', 2);
$command = $command->brightness(200)->rgb(60, 70, 175); // Purple
$client->sendCommand($command);
```

**Extending the Possibilities**

The **Phue** library, despite being somewhat dated, provides a strong foundation for using PHP with Philips Hue. There is further functionality in the library that isn't documented, but by exploring the source code further a programmer can extend this. The library can be further expanded or an entirely new library created that utilises the latest Hue API. The ability to control smart lighting through PHP opens up creative possibilities from flashing colours on notification, through to changing the colour based on other data.

**References**

*   PHP Teacher magazine - June 2022
*   `neoteknic/Phue: https://github.com/neoteknic/Phue`
*  `fork: https://github.com/syntaxseed/phue`
*   `autoload script: https://getcomposer.org`
*  `project’s wiki: https://phpa.me/github-neoteknic-phue-wiki-bridge`
*   API reference website: `https://developers.meethue.com/develop/get-started-2/`


## Building Robust PHP Applications with DDD, Messaging, and Ecotone**

```
  ___   _  _    _     _   ___   ___
 / _ \ | \| |  /_\   | | / _ \ | __|
| (_) || .` | / _ \  | || (_) || _|
 \___/ |_|\_|/_/ \_\ |_| \___/ |_|
```

This article explores the principles of Domain-Driven Design (DDD) and messaging, and how they can be implemented in PHP with the **Ecotone framework**. DDD is a strategic approach focusing on aligning software development with the problem domain, while messaging introduces asynchronous communication and helps build more resilient applications. We will examine how these concepts can be applied to create more maintainable and flexible systems, and the **Ecotone framework** which provides structure and technical capabilities to support this.

**Understanding Domain-Driven Design**

DDD emphasizes understanding the problem space (how the business works) and translating it into the solution space (the code). It promotes building software that reflects the business's actual processes and flows. Central to DDD is having a well-defined domain model free of external tooling, where code focuses on the business logic without being tied to specific technology or frameworks.

*   **Problem Space vs. Solution Space:** The problem space involves understanding the business requirements, whereas the solution space is about writing the code that addresses those requirements.
*   **Domain Model:** A domain model is a representation of the problem domain within the software, containing business logic and rules.

**General Guidelines for Maintainable Code**

Before diving into DDD, it's crucial to adopt practices that improve overall code maintainability. This includes having clear, well-structured code that is easy to understand and modify.

**Ecotone Framework**

The **Ecotone framework** is designed to facilitate DDD principles by providing glue code and taking care of technical aspects, allowing developers to focus on business logic. It can be used in a standalone version or within other PHP frameworks like Symfony or Laravel.

*   **Flexibility:** Ecotone can be used with a standalone version or with other frameworks like Symfony or Laravel.
*   **Focus on Business:** The framework’s design keeps code free from external tooling by annotating with attributes, allowing the developer to focus on the business rules.

**Problem Solving Approaches**

When developing a system, there are several options for a high-level approach:

1.  **CMS:** Using a CMS provides a quick way to market, however, it may be less adaptable to rapid change.
2.  **Custom Software:**  Building custom software from scratch provides more flexibility and control, but may take longer to get to market.
3.  **Hybrid approach:** utilising a CMS along with some custom software provides a middle ground between these two options.

**First Version: SQL All the Things**

The initial version of a project uses a SQL-based approach, interacting directly with the database. In this implementation, business rules (invariants) are validated within services which leads to a complex situation to track all the requirements. This approach may be good to quickly create a product but makes it difficult to maintain long term.
*   **Invariants:** The business rules such as "the price must be higher or equal to 0" are called invariants.
*   **Example:** A simple shop with ebook registration and ordering demonstrates this approach, the `EbookController` handles the data, using a method `validateData()` within `EbookService`.

**Limitations of the SQL Approach**

Directly using SQL has limitations:

*   **Lack of Consistency:** There is no guarantee that validated data remains valid as it is passed between services.
*   **Technical Fixes:** Technical problems are addressed directly, making code less focused on business logic.
*   **State Management:** Managing the state of objects directly can be problematic and hard to debug.

**Value Objects**

To address some of these limitations, Value Objects should be used to encapsulate invariants, validating data upon construction. Value Objects are immutable, meaning that they are not able to be changed after construction, making it easier to manage state.

**Aggregates**

Aggregates are a collection of objects that can be treated as a single unit. An Aggregate will maintain the overall state of an object, and will only allow changes that keep the object in a valid state, and this behaviour is part of the Domain Model.

*   **API for Changes:** Aggregates become the API for changes, meaning all modification should go through the Aggregate.

**Second Version: Model**

The second version introduces a Domain Model, using Value Objects, Aggregates, and Repositories. This approach includes:

*   **Price Value Object:** Encapsulates the price logic.

    ```php
    final class Price {
        public function __construct(public int $amount) {
            if ($amount < 0) {
                throw new \InvalidArgumentException("Price must be greater than 0");
            }
        }
        //Additional Methods
    }
    ```

*   **Ebook Aggregate:** Encapsulates the logic for the Ebook object and the business rules about how it is created.

    ```php
    class Ebook {
      public function __construct(array $data) {
        if (empty($data["title"])) {
            throw new \InvalidArgumentException("Title must not be empty");
        }
        if (strlen($data["content"]) < 10) {
            throw new \InvalidArgumentException("Content must be at least 10 characters");
        }
          //Additional code
       }
    }
    ```
*   **Ebook Repository:** Provides an interface for storing and retrieving Ebook Aggregates.

    ```php
      interface EbookRepository
      {
          public function save(Ebook $ebook): void;
          public function getById(string $ebookId): Ebook;
      }

    ```
*   **Converter:** A converter provides the way the **Ecotone framework** converts data types for storing objects in a repository.

    ```php
    class PriceConverter
    {
        #[Converter]
        public function convertFrom(Price $price): int
        {
            return $price->amount;
        }

        #[Converter]
        public function convertTo(int $price): Price
        {
            return new Price($price);
        }
    }
    ```

**Third Version: Messaging**

The third version introduces messaging, where actions are communicated between services via messages. This provides resilience, and asynchronous processing.

*   **EventBus:** A way of passing messages between different parts of the code base.
*   **Event Handler:** A listener that will be called when an event occurs.
*   **Message Channel:** An inbox that messages are stored in until they are retrieved.

**Benefits of Messaging**

Using messages decouples parts of the application and makes the overall system more stable. It addresses limitations of a synchronous approach by:

*   **Resilience:** If a service is down, messages are stored and processed later.
*   **Decoupling:** Services don’t depend on each other, allowing them to scale independently.

The following code demonstrates setting up the **Ecotone framework** to use the database as a message channel:

```php
class MessagingConfiguration
{
    #[ServiceContext]
    public function registerMessageChannel() {
        return DbalBackedMessageChannelBuilder::create("order_channel");
    }
}
```

**Command Handlers and Queries**

Command Handlers are a way of exposing methods within a project, and can be called from anywhere in the project through messaging. Query handlers expose a method that can be used to query the application for information using messaging.

**Conclusion**

This article has demonstrated how to evolve an application from a basic SQL-driven system, to one that implements the principles of Domain Driven Design and messaging. DDD provides structure by mapping the business needs to the software, while messaging makes it more resilient. This gives us a foundation to build more robust, and adaptable, systems.

**References**

*   PHP Teacher magazine - June 2022
*   `github repo: https://github.com/dgafka/php-architect-ebook-shop-demo`
*   `documentation: https://docs.ecotone.tech`

## Event-Driven Programming in PHP: The Observer Pattern and Beyond**

```
     _    _       _   _       _
    | |  | | ___ | |_| |_  _ | |_
    | |  | |/ _ \| __| __|| || __|
    | |__| | (_) | |_| |_ | || |_
    |______| \___/ \__|\__||_||__|
```

Event-driven programming offers an alternative approach to traditional procedural and object-oriented paradigms. This article will explore event-driven architecture, focusing on the **Observer pattern** and how it can be implemented in PHP, and also demonstrate how to move beyond the basics using dispatchers and listeners. We will also examine the concepts behind event driven architecture and contrast it to other architectural concepts.

**Patterns in Software**

In software development, patterns represent proven solutions to common design problems. These patterns provide developers with a shared vocabulary, making it easier to understand and discuss code. The use of design patterns allows developers to understand the "why" behind certain design choices.

*   **Design Patterns:** These are ways to structure object-oriented code. The most well-known examples are cataloged in *"Design Patterns: Elements of Reusable Object-Oriented Software"*.
*   **Architectural Patterns:** These are patterns at a higher level. A common pattern is **Model-View-Controller (MVC)**.

**PHP Paradigms**

PHP typically uses architectural patterns such as **Action-Domain-Responder (ADR)**, which is similar to **MVC**. Due to PHP’s nature as a non-concurrent language, variations such as **Web MVC** have emerged. Within the model portion of these architectural patterns, design patterns such as **Active Record** or **Data Mappers** are found. These patterns deal with the problem of how to persist data.

**Event-Driven Programming**

Event-driven programming involves a system that reacts to events. When something happens, the system sends an event that triggers a reaction in other parts of the system. This differs from **Event Sourcing**, which is about storing historical data.

*   **Key Concept:** The system watches for something to happen and executes code accordingly..

**Observer Pattern**

The **Observer pattern** implements the event-driven paradigm, where multiple **Observers** are notified when a **Subject** changes its state. PHP provides native support for this pattern through the `SplSubject` and `SplObserver` interfaces.

*   **Subject:** An object that is being observed.
*   **Observer:** An object that wants to know when the subject has changed.

**Example Implementation**

The following code shows an example using the `SplSubject` and `SplObserver` interfaces:

1.  **Subject Class:** The subject class must implement the `SplSubject` interface.

    ```php
    class UserService implements \SplSubject
    {
      protected \SplObjectStorage $observers;
      public ?string $state;
      public User $user;

        public function __construct()
        {
            $this->observers = new \SplObjectStorage();
        }

        public function createUser(array $data): User
        {
          $user = new User($data);
          $user->save();
          $this->state = 'new';
          $this->user = $user;
          $this->notify();
          $this->state = null;
          return $user;
        }

        public function attach(\SplObserver $observer): void
        {
            $this->observers->attach($observer);
        }
         public function detach(\SplObserver $observer): void
        {
            $this->observers->detach($observer);
        }

        public function notify(): void
        {
            foreach ($this->observers as $observer) {
                $observer->update($this);
            }
        }
    }
    ```

2.  **Observer Class**: The observer class must implement the `SplObserver` interface.

    ```php
    class UserObserver implements \SplObserver
    {
        public function update(\SplSubject $subject): void
        {
          if($subject->state === 'new')
          {
             //do something
          }
        }
    }
    ```

In this implementation, the `UserService` will notify all of the attached `SplObserver` objects every time a new user is created. Each of these observers can respond to a new user being created in different ways.

**Limitations**

The default implementation of the `SplSubject` interface only has a single `notify()` method with no arguments, limiting each observer to watch for a single event. To be able to react to multiple different events, a more flexible system is needed, known as an event dispatcher.

**Event Dispatching**

Event dispatching involves creating **Events**, **Listeners** and **Emitters**. This allows a system to watch for a variety of events, and respond in different ways to each of them.
*   **Event:** A message that is produced by the Emitter, an object representing a change in the state of an application.
*   **Listener:** Any PHP callable that expects an Event. An Event can have zero or more Listeners attached to it.
*   **Emitter:** Any part of the code that wishes to dispatch an event.

To implement event dispatching, the system needs to track listeners for each event, and have a dispatcher that invokes those listeners.

1.  **Listener Provider:** A class that manages listeners for specific events and implements the `Psr\EventDispatcher\ListenerProviderInterface` interface.
    ```php
      class ListenerProvider implements ListenerProviderInterface
      {
          protected array $listeners = [];

          public function addListener(string $eventType, callable $listener): void
          {
              $this->listeners[$eventType][] = $listener;
          }

          public function getListenersForEvent(object $event): iterable
          {
              return $this->listeners[$event::class] ?? [];
          }
      }
    ```
2.  **Dispatcher:** This class is responsible for sending the event to all of the listeners, and implements the `EventDispatcherInterface` interface.
    ```php
      class Dispatcher implements EventDispatcherInterface
      {
          public function __construct(protected ListenerProviderInterface $listeners) { }

          public function dispatch(object $event): void
          {
              $listeners = $this->listeners->getListenersForEvent($event);
              foreach ($listeners as $listener) {
                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped())
                 {
                  return;
                 }
                 $listener($event);
              }
          }
      }
    ```
3.  **Event:** This class will contain the data related to the event that will be passed to the listeners.
   ```php
      class CreateUserEvent
      {
          public User $user;
      }
    ```

Using this more complex implementation makes the code more flexible, and allows a system to support multiple events.

**Reacting Versus Extending**

Event-driven design provides a different way to expand the capabilities of an application and provides a system that is more flexible than using inheritance and composition.

**Conclusion**

Event-driven programming is a powerful tool in software architecture. The patterns and concepts outlined here can be useful in building modular applications that are easy to extend. By using dispatchers and listeners, the design can be made more flexible and versatile.

**References**

*   PHP Teacher magazine - June 2022
*   `PSR-14: Event Dispatcher: https://www.php-fig.org/psr/psr-14/`


## Handling Random and Rare Failures with Domain Events**

```
  ___   __  __    _   _   ___   _  _
 | __| |  \/  |  /_\ | | /_  | | \| |
 | _|  | |\/| | / _ \| |  / /  | .` |
 |___| |_|  |_|/_/ \_\_| /___| |_|\_|
```

In software development, planning for every single failure is impossible. This article will explore how to handle "random and rare" failures by introducing a system that tracks the domain events of an application. We will explore the issues that result from blind optimism, and how Domain Events can help address these.

**The Challenge of Designing for Failure**

It is impractical to plan for every possible thing that could go wrong in software development.  A common approach is to rely on a general error page when something goes wrong, leading to a loss of understanding about the issues in the system. It is often stated that "if it never fails, we never need to worry about the failure", however, if the error does occur the issue is hard to prevent as there is no information available about it.

*   **Blind Optimism:** Assuming everything will work correctly may lead to problems such as data corruption.
*   **Foreign Key Constraints:** These can be impossible to enforce on large databases due to data corruption.

**Importance of History Traces**

A history or event trace can be used to see the exact steps that lead to an error. Recording a trace of what happened in the system is important as it answers the question "what happened?" when an error occurs.

**Experimentation**

Experimentation should be used to explore potential solutions, rather than stopping at the first solution. This is a key concept within Domain-Driven Design and Agile methodologies.

*   **Spikes:** A method originating from Extreme Programming that uses simple programs to test potential solutions, to understand how much effort will be required to solve an issue.
*   **Domain Events** these are things that happen that domain experts care about, that trigger a change in the state of an application.

**Overkill**

Domain events should be stored in the same transaction as any other changes in the system. These events should only be published if the changes have been successful. A sequence of steps that ensure this are:

1.  Begin transaction
2.  Update the database
3.  Insert the Domain Event into a table in the same database within the same database transaction
4.  Commit transaction if successful, rollback otherwise.

At first glance, storing the event both locally and globally can appear to be overkill, but it provides an extremely useful mechanism for capturing random and rare failures.

**Domain Event Tables**

Each local application can store domain events within their own database. This allows each section of the application to store a trace of activity. Each of these tables would have a different schema but a common structure. The following code shows a typical schema:

```sql
CREATE TABLE `local_app_events`
(
 `id` bigint unsigned NOT NULL AUTO_INCREMENT,
 `action` varchar(255) NOT NULL DEFAULT '',
 `subsystem` varchar(255) NOT NULL DEFAULT '',
 `description` varchar(255) NOT NULL DEFAULT '',
 `detail` json DEFAULT NULL,
 `event_uuid` char(36) NOT NULL,
 `when_occurred` timestamp(6) NOT NULL,
 `created` datetime NOT NULL,
 `modified` datetime NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `event_uuid` (`event_uuid`)
) ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;
```

Each of these fields provide important context of the event that occurred.

**Exception Reports**

When an event fails, it is important to capture the context and provide a detailed exception report. When a random or rare failure occurs, this context is stored in an `exception_reports` table.

```sql
CREATE TABLE `exception_reports`
(
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `description` varchar(255) NOT NULL DEFAULT '',
    `detail` json DEFAULT NULL,
    `created` datetime NOT NULL,
    `modified` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;
```

The values stored will provide context as to what happened and enable the developer to understand and fix the issue.

**Event Counts**

An `event_counts` table is used to trigger failures while developing the system. By triggering failures, the ability to understand and resolve issues can be addressed more quickly and effectively.

```sql
CREATE TABLE `event_counts`
(
 `id` int unsigned NOT NULL AUTO_INCREMENT,
 `action` varchar(255) NOT NULL DEFAULT '',
 `when_counted` datetime NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `when_counted` (`when_counted`)
) ENGINE = InnoDB
DEFAULT CHARSET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;
```

**Conclusion**

"Random and rare" failures are often ignored, but these can be captured by storing domain events in the same transaction as any other changes. By tracking all domain events, detailed information about the system can be observed, allowing the identification of problems that may not be obvious. The event tables, exception reports and event counts tables provide the required information for a developer to understand what happened, why it happened and to prevent it in the future.

**References**

*   PHP Teacher magazine - June 2022
*   `ewbarnard/strategic-ddd: https://github.com/ewbarnard/strategic-ddd`
*   `MySQL binary and relay logs: https://dev.mysql.com/doc/refman/8.0/en/replica-logs.html`
*    `spike: https://w.wiki/4XP6`
*    `Domain Event: https://martinfowler.com/eaaDev/DomainEvent.html`
*  `data description language: https://w.wiki/5BBy`
*   `source code repository: https://github.com/ewbarnard/strategic-ddd`

## Assessing Cybersecurity Risks: STRIDE and DREAD**

```
  _____   _      _      _   _     _
 /  ___/ | |    | |    | | | |   | |
 \ `--.  | |    | |    | | | |   | |
  `--. \ | |    | |    | | | |   | |
 /____/ /_|    |_|    |_| |_|   |_|
```

Every application faces cybersecurity risks. This article delves into how to categorize and rate those risks using the STRIDE and DREAD frameworks. These are used to provide a structured approach to understanding and managing security threats.

**The Inevitability of Risk**

Every application will, eventually, be exposed to a security risk. It is important to understand and assess the different types of security risk.

**Threat Categorization with STRIDE**

STRIDE provides a useful mnemonic for categorizing threats. The categories are:

*   **Spoofing:** Questions whether you can prove that a certain action came from the party that is claiming to be the author of that action.
*   **Tampering:** Questions whether the content of a communication can be changed in transit between two parties.
*   **Repudiation:** Questions whether a party can deny that they carried out a given action.
*   **Information Disclosure:** Questions whether confidential information is kept private.
*   **Denial of Service:** Questions whether the service is available for legitimate users.
*   **Elevation of Privilege:** Questions whether administrative and privileged operations are correctly authorised.

After categorizing threats using STRIDE, the critical nature of each type of threat can be evaluated. If, for example, a system contains no sensitive data, 'Information Disclosure' may not be as significant of a concern as other types of threat.

**Risk Grading with DREAD**

The DREAD framework provides a way to rate or grade a threat on a scale from 0 to 10. The categories are:

*   **Damage:** This is an assessment of the worst possible outcome. 0 being trivial and 10 being full system control.
*   **Reproducibility:** How easy is it to reproduce an attack. 0 being very difficult and 10 being trivial, with an exploit being easily used or created.
*   **Exploitability:** How much skill is needed to carry out an attack. 0 requiring expert knowledge, and 10 being something a novice programmer could exploit.
*   **Affected Users:** How many users are affected by the threat. 0 being very few users, and 10 being every user.
*   **Discoverability:** How easy is the threat to find in the first place. 0 being almost impossible to discover, and 10 being publicly disclosed.

The scores from each dimension are averaged together, producing a risk score on a scale from 0 to 10. A score of 0 is no risk, and a score of 10 should trigger an incident immediately. Scores between these two values require further analysis.

**Practical Application**

By using both STRIDE and DREAD, development teams can have a structured way to manage security threats and focus on the issues that have the biggest impact.

**Conclusion**

Not all risks need immediate attention. It is important to prioritize threats by categorising them, and then assessing their level of risk. This allows the developer to focus their efforts on the most important activities, and not be paralyzed by the existence of risks and issues.

**References**

*  PHP Teacher magazine - June 2022
*  `vulnerability