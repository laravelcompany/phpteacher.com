---
title: "Microservices and Their Challenges from a laravel developer perspective"
publishDate: 2025-02-08 00:00:00
description: Microservices are presented as an extension of service-oriented architecture, with each microservice representing a smaller set of functions hosted over a network with a well-defined interface. These services communicate through remote procedure calls (RPCs).
image: /assets/services/security.svg
tags:
  - articles
  - php
  - february
  - 2025
  - microservices
  - architecture
---
  





This article will delve into the concept of Domain-Oriented Microservice Architecture (DOMA), as practiced at Uber from a Laravel Developer Perspective.


# Introduction to Microservices and Their Challenges**
The article begins by acknowledging the widespread discussion around microservices architecture. Microservices are presented as an extension of service-oriented architecture, with each microservice representing a smaller set of functions hosted over a network with a well-defined interface. These services communicate through remote procedure calls (RPCs).  The key feature is how the code is hosted, called and deployed, which contrasts with monolithic applications where components with well-defined interfaces are called directly within the process.  While microservices offer advantages like flexibility, independent deployment, and clear ownership, they also introduce complexities such as increased system overhead. Uber, having grown to around 2,200 core microservices, experienced these trade-offs.

## Uber's Motivation for Microservices**
Uber adopted a microservices architecture to address issues with its monolithic services around 2012-2013. These issues included:

*   **Availability risk:** A single failure in the monolithic code could bring down the entire system.
*   **Risky deployments:** Deployments were painful, time-consuming, and required frequent rollbacks.
*   **Poor separation of concerns:** Maintaining clear boundaries between logic and components was difficult, especially with rapid growth.
*   **Inefficient execution:** These issues hindered team autonomy and independent execution.

The move to microservices improved system reliability, enabled better separation of concerns, clarified code ownership, and allowed for autonomous execution and faster development. However, as Uber scaled, the increased complexity became apparent, leading to challenges such as difficult debugging, deep call hierarchies, and the need for extensive collaboration.

## Domain-Oriented Microservice Architecture (DOMA)**
To address the challenges of increasing complexity, Uber developed DOMA, which leverages established design principles from domain-driven design, clear architecture, service-oriented architecture, and object-oriented design patterns.  The core idea is to organize microservices into **domains**—collections of related services. These domains are further organized into **layers**, defining dependencies between domains. **Gateways** provide clear interfaces to domains, and **extensions** allow for adding logic or data without modifying core services. DOMA aims to transform a complex microservice architecture into a structured, flexible, and reusable layered component.

## Key Principles of DOMA**

###   **Domains:** A domain represents a logical grouping of one or more microservices. The size of a domain is flexible, but the key consideration is the logical role of each collection.  Examples include map search, fare services and matching platforms. Domains do not necessarily follow the company's organizational structure. For example, Uber Maps is divided into three domains, three gateways and 80 microservices.
###   **Layers:**  Layered design determines which services can call other services, enabling separation of concerns and dependency management at scale.  Layers also help in managing failure blast radius and product specificity. Lower-level layers represent generic business functions with more dependencies and a larger failure blast radius, while upper layers are more specific to user experiences. Uber uses five layers:
    *   Infrastructure layer: Provides functionality usable by any engineering organization.
    *   Business layer: Provides core functionality across Uber but not specific to any product category.
    *   Product layer: Functionality related to a specific product or line of business.
    *   Presentation layer: Functionality for consumer-facing applications.
    *   Edge layer: Securely exposes Uber services to the outside world.
###  **Gateways:** A gateway serves as a single entry point to a domain, abstracting internal details. Gateways provide benefits in terms of migration, discoverability and overall system complexity, reducing the number of service dependencies. Gateways allow flexibility when dealing with underlying service implementations.
###   **Extensions:** Extensions provide a mechanism to expand the functionality of a service without changing its core implementation, enabling multiple teams to work independently. Uber uses two extension modes:
    *   **Logic extensions:**  Use a provider or plugin pattern to extend service logic, where an interface is defined on a service basis and the extension team implements this logic in an interface-driven manner without modifying the platform's core code.
    *    **Data extensions**:  Leverage Protobuf's Any capability or JSON to attach data to an interface, keeping platform data models uncluttered.
    *    **Custom Extensions:** Teams can develop their own extensions to meet specific needs

DOMA has significantly impacted most major areas at Uber, particularly the business layer. Platform support costs have dropped and product teams have faster development due to clear boundaries.   For example, one early adopter was able to reduce feature integration time from three days to three hours.  DOMA also reduced the time for code review and planning.  By reducing the number of services required to launch new features, the platform reduced launch time by 25-50%. 

Uber divided 2,200 microservices into 70 domains, with around 50% implemented and planned for future adoption. Gateways help to avoid "migration hell" as underlying services can change without affecting upstream consumers. Platforms designed with DOMA have proven to be more scalable and easier to maintain.

## Practical Advice for Adopting DOMA**
We advises that a well-thought-out microservice architecture should evolve over time, rather than being a one-time effort.  The approach is akin to "pruning the hedge" - it is a dynamic and incremental process.

*   **Startups:** May not need microservices right away. Microservices can add complexity that may not be worth the operational benefits for smaller teams. Microservices also require dedicated resources that early-stage companies may lack.  If microservices are adopted, the core business functions will likely become the most important and longest lasting services.
*   **Medium-sized Companies:**  As teams grow, the need for separation of concerns becomes more important.  Dependency management becomes more critical as some services become business-critical.  Early investments in platformization and avoiding product-specific logic in core services will help avoid technical debt. Extensions can be useful at this stage.  Although the number of microservices will be small, thinking in a “domain-oriented” way can still be helpful.
*   **Large Companies:** Can benefit fully from DOMA. Large numbers of microservices can be grouped into domains with gateways. Legacy services may need refactoring and the gateway will enable easier service migration. A clear hierarchy is needed to keep product and platform logic separate.

## Laravel Code Examples**
The provided source does not include specific Laravel code examples. However, we can explore how the principles of DOMA can be applied in a Laravel environment. (Please note these examples are not directly from the provided source).

Let's imagine an application that manages bookings for a service.

**Domains**
We can define domains like 'Users', 'Bookings' and 'Payments'.

**Users Domain**
This might include services for user authentication, profile management etc.

**Bookings Domain**
This might include the logic for scheduling, creating and managing bookings.

**Payments Domain**
This might include all things payment related

**Layers**
We can define layers such as 'Infrastructure', 'Business', and 'Presentation'.

**Infrastructure Layer**
This layer would contain services for database access, caching, and logging

```php
// Example of an infrastructure service (Database access)
namespace App\Infrastructure\Database;

use Illuminate\Support\Facades\DB;

class BookingDatabaseService
{
   public function getBooking($id)
   {
      return DB::table('bookings')->where('id', $id)->first();
   }

     public function createBooking(array $data)
   {
      return DB::table('bookings')->insert($data);
   }
}
```

**Business Layer**
The business layer would implement the application's core logic

```php
// Example of a business layer service (booking logic)
namespace App\Business\Booking;

use App\Infrastructure\Database\BookingDatabaseService;

class BookingService
{
    private $bookingDatabaseService;

   public function __construct(BookingDatabaseService $bookingDatabaseService)
    {
        $this->bookingDatabaseService = $bookingDatabaseService;
    }
  public function createBooking(array $data)
   {
        // Logic for creating a booking
      $this->bookingDatabaseService->createBooking($data);
   }
     public function getBooking($id)
   {
        // Logic for retrieving a booking
        return $this->bookingDatabaseService->getBooking($id);
   }

}
```

**Presentation Layer**
The presentation layer would expose this to a front-end application.

```php
// Example controller (presentation layer)
namespace App\Http\Controllers;

use App\Business\Booking\BookingService;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private $bookingService;
   public function __construct(BookingService $bookingService)
    {
       $this->bookingService = $bookingService;
    }
    public function store(Request $request)
    {
        $data = $request->all();
         $this->bookingService->createBooking($data);

        return redirect('/bookings');
    }
}
```

**Gateways**
Gateways would provide access points to each domain's functionalities. In Laravel, this could be API endpoints that access a booking service in a domain:

```php
// Example of an API controller (gateway)
namespace App\Http\Controllers\Api;

use App\Business\Booking\BookingService;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{

    private $bookingService;

   public function __construct(BookingService $bookingService)
    {
       $this->bookingService = $bookingService;
    }
   public function show($id)
   {
        $booking = $this->bookingService->getBooking($id);
        return response()->json($booking);
   }
}

```

**Extensions**
Extensions could be used to add new features or modifications.  An example of using a "plugin pattern" for a logic extension is shown below.

```php
//Example implementation of a plugin pattern interface
namespace App\Business\Booking\Extensions;

interface BookingExtensionInterface {
    public function beforeCreate(array $data) : array;
    public function afterCreate(int $id);
}
```

```php
//Example implementation of a logic extension
namespace App\Business\Booking\Extensions;

class BookingValidationExtension implements BookingExtensionInterface{
  public function beforeCreate(array $data) : array{

        //Custom validation Logic
         return $data;
   }

   public function afterCreate(int $id)
    {
      //Custom logic
    }

}

```

```php
//Modified Business layer to use extensions
namespace App\Business\Booking;

use App\Infrastructure\Database\BookingDatabaseService;
use App\Business\Booking\Extensions\BookingExtensionInterface;

class BookingService
{
    private $bookingDatabaseService;
    private $extensions = [];

    public function __construct(BookingDatabaseService $bookingDatabaseService)
    {
        $this->bookingDatabaseService = $bookingDatabaseService;
    }
     public function addExtension(BookingExtensionInterface $extension)
   {
        $this->extensions[] = $extension;
   }

  public function createBooking(array $data)
   {
      foreach($this->extensions as $extension)
        {
              $data = $extension->beforeCreate($data);
        }
      $bookingId =  $this->bookingDatabaseService->createBooking($data);

        foreach($this->extensions as $extension)
        {
           $extension->afterCreate($bookingId);
        }
   }
     public function getBooking($id)
   {
        // Logic for retrieving a booking
        return $this->bookingDatabaseService->getBooking($id);
   }

}
```
You can also implement the Data extension in similar ways to append additional data as needed.

**Conclusion**
Domain-Oriented Microservice Architecture offers a structured approach to managing the complexities of large-scale microservices systems. By organizing services into domains, layers, gateways, and extensions, Uber has significantly improved its developer experience and reduced system complexity. The practical advice given in the article emphasizes the importance of a gradual and iterative approach to adopting microservices, tailored to the size and needs of the organization.

