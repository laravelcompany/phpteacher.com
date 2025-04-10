---

title:  PHP Teacher Magazine - January 2023
description: "Welcome to the January 2023 Edition of The PHP Teacher Magazine!"
date:   2017-05-16 18:00:00
---

Happy New Year from all of us at *The PHP Teacher Magazine*! We hope that 2023 is off to a fantastic start for you and that you're ready for a year of growth and learning in the PHP world. This month’s issue is packed with articles designed to elevate your skills, broaden your perspectives, and keep you on the cutting edge of PHP development. 


This issue dives deep into crucial aspects of PHP development, from architectural patterns to security best practices, and even touches on personal growth as a developer. We are very excited to bring you a collection of articles to support your professional and personal journey this year.

Here's a sneak peek at what you'll find inside:

*   **Data Mapper Pattern:** Delve into the intricacies of this pattern with Alexandros Gougousis and learn how to decouple your domain objects from database concerns.
*   **Domain Logic in One Place:** Stathis Georgiou explores the importance of centralising business rules for cleaner, more maintainable code.
*   **Monolith vs Microservices:** Chris Tankersley weighs the pros and cons of these architectural approaches to help you choose the right fit for your applications.
*   **PCI-DSS: A Beginner’s Guide:** Get up to speed with credit card compliance with Eric Mann's primer.
*   **Upgrading with Reckless Abandon:** Joe Ferguson shares his strategies for upgrading legacy applications to the latest versions.
*  **Any Two Birthdays:** Oscar Merida challenges you with date and time manipulations using PHP’s DateTime library.
*   **Try, or Try Not; There is no Do:** Edward Barnard extends the concept of transactional boundaries to RESTful APIs to ensure consistency.
*   **PSR-14 Event Dispatcher:** Frank Wallen explores how to decouple parts of your application using events and listeners with the PSR-14 standard.
*   **Standing Tall with the Laravel TALL Stack:** Matt Lantz introduces the TALL stack for building interactive applications with Laravel.
*   **Self-worth:** Beth Tucker Long encourages you to think about your mental health and personal wellbeing.

We're committed to being a huge part of your journey this year. We also want to invite you to write for us, and share your knowledge and experience with the community. 

Let’s make 2023 a year of growth, collaboration, and excellent PHP development.
Happy reading!

The Team at *The PHP Teacher Magazine*

***

## Data Mapper Pattern**
   
  **Introduction**
   
  In the world of software development, dealing with data persistence can often become complex, especially when working with Object-Relational Mappers (ORMs). While ORMs simplify things initially, their complexities can bite you as your application grows, particularly when handling batch processing or complex data mappings. The **Data Mapper pattern** provides an alternative, moving the responsibility of persisting data to a dedicated class, thereby decoupling your domain objects from the database.
   
  **The Concept**
   
  The Data Mapper pattern introduces a layer between your domain objects and the database. Unlike the Active Record pattern where domain objects are responsible for their persistence, Data Mappers handle data transfers to and from the database, which allows for a cleaner separation of concerns. This approach can help to make code more maintainable, testable, and flexible.
   
  **Code Example**

   ```php
   <?php
    
   // Listing 1: The Product Model
   class Product {
   private $id;
   private $title;
   private $brandName;
   private $catalogPrice;
    
   public function __construct($id, $title, $brandName, $catalogPrice) {
   $this->id = $id;
   $this->title = $title;
   $this->brandName = $brandName;
   $this->catalogPrice = $catalogPrice;
   }
   }
    
   // Listing 2: The Product Mapper
   class ProductMapper {
   private PDO $pdo;
   
   public function __construct(PDO $pdo) {
   $this->pdo = $pdo;
   }
   
   public function create($title, $brandName, $catalogPrice): Product {
   $sql = 'INSERT INTO products (title, brand_name, catalog_price) VALUES (?, ?, ?)';
   $pdoStatement = $this->pdo->prepare($sql);
   $pdoStatement->execute([$title, $brandName, $catalogPrice]);
   $productId = $this->pdo->lastInsertId();
   return new Product($productId, $title, $brandName, $catalogPrice);
   }
    
   public function update(Product $product) {
   $publicProduct = new ReflectionWrapper($product);
    
   $sql = 'UPDATE products SET title = :title, brand_name = :brandName, catalog_price = :catalogPrice WHERE id = :id';
   $pdoStatement = $this->pdo->prepare($sql);
   $pdoStatement->execute([
   ':id' => $publicProduct->get('id'),
   ':title' => $publicProduct->get('title'),
   ':brandName' => $publicProduct->get('brandName'),
   ':catalogPrice' => $publicProduct->get('catalogPrice'),
   ]);
   }
   
   public function delete(Product $product) {
   $publicProduct = new ReflectionWrapper($product);
   $sql = 'DELETE FROM products WHERE id = :id';
   $pdoStatement = $this->pdo->prepare($sql);
   $pdoStatement->execute([':id' => $publicProduct->get('id')]);
   }
   
   public function findById($id): ?Product {
   $sql = 'SELECT * FROM products WHERE id = :id';
   $pdoStatement = $this->pdo->prepare($sql);
   $pdoStatement->execute([':id' => $id]);
   $row = $pdoStatement->fetch(PDO::FETCH_ASSOC);
   return $row ? $this->convertRowToObject($row) : null;
   }
      
    private function convertRowToObject(array $row): Product {
       return new Product(
           $row['id'],
           $row['title'],
           $row['brand_name'],
           $row['catalog_price']
       );
   }
   
    // ... other methods like findByTitle, etc
    
   }
   // Listing 3: Reflection Wrapper
   class ReflectionWrapper {
        private $object;
        private $reflectedObj;
    
        public function __construct($object) {
            $this->object = $object;
            $this->reflectedObj = new ReflectionClass($object);
        }
    
        public function get($propertyName) {
            $property = $this->reflectedObj->getProperty($propertyName);
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
                $value = $property->getValue($this->object);
                $property->setAccessible(false);
            } else {
                $value = $property->getValue($this->object);
            }
            return $value;
        }
    }
   ```
   
  **ASCII Art**
   ```
   +-----------------+      +-----------------+      +-----------------+
   | Domain Object   | <--> | Data Mapper     | <--> | Database        |
   | (e.g., Product) |      | (e.g., ProductMapper)|      | (e.g., MySQL)   |
   +-----------------+      +-----------------+      +-----------------+
        ^                                  |
        |                                  v
     Application Layer                  Persistence Layer
   ```
   
  **References:**
   *   Alexandros Gougousis, "Data Mapper Pattern".
  *   Code Listings 1,2,3.
   


## Domain Logic in One Place**
   
  **Introduction:**
   
  One of the common issues in application development is **scattered business logic,** where the rules governing your application are spread across multiple files and technologies. This leads to code that is hard to understand, change, and maintain. This article discusses how to centralise your business logic to create code that is more focused, maintainable and easier to work with.
   
  **The Concept:**
   
  Centralising domain logic means putting all your business rules in one place, usually within your entity objects. This approach contrasts with scattering logic across controllers, repositories, or database defaults. By using your entities to encapsulate logic and behavior, you make it clear where to find and modify specific rules.
   
  **Code Example:**
   ```php
   <?php
   // Listing 1: Example of Book entity
   class Book {
       protected string $title;
       protected float $discount = 0;
   
       public function __construct($title, $discount = 0) {
           $this->setTitle($title);
           $this->applyDiscount($discount);
       }
   
       public function setTitle($title) {
           if (strlen($title) < 3) {
               throw new Exception('Title must be more than 2 characters');
           }
           $this->title = $title;
       }
       
       public function applyDiscount($discountAmount) {
           if ($discountAmount < 0) {
               throw new Exception('Discount can\'t be a negative number');
           }
           $this->discount = $discountAmount;
       }
   
       public function getTitle(): string
       {
           return $this->title;
       }
   
       public function getDiscount(): float
       {
           return $this->discount;
       }
   }
    
   // Listing 2: How to use Book entity
    
   // $inputs represents the inputs of the request
   
   try {
        $book = new Book($inputs['title'], 1);
       echo 'Book title:' . $book->getTitle() . PHP_EOL;
        echo 'Discount: ' . $book->getDiscount() . PHP_EOL;
   } catch (Exception $e) {
       echo 'Error:' . $e->getMessage() . PHP_EOL;
   }
   
   ```
   
   **ASCII Art:**
   
   ```
    +--------------------+
    |      Book         |
    |   (Entity)        |
    |--------------------|
    | -title: string    |
    | -discount: float  |
    |--------------------|
    | +setTitle()       |
    | +applyDiscount()  |
    | +getTitle()      |
    | +getDiscount()    |
    +--------------------+
     ^
     | all logic within
   ```
   
  **References:**
  * Stathis Georgiou, "Domain Logic in One Place".
  * Code Listings 1 and 2.
  
***

## Monolith vs Microservices**

  **Introduction**

  The debate between **monolithic and microservices** architectures is ongoing in software development. There’s no clear winner, and each architecture has its benefits and drawbacks. Understanding these differences will help you choose the right approach for your project.

  **The Concepts**

   *   **Monolith:**  A monolithic application is a single, large codebase, with all functionalities contained within. It's like a single large structure that is hard to modify or replace and many times complex to understand.
   *   **Microservices:** A microservices architecture breaks down an application into small, independent services, each responsible for a specific domain. These services are developed, deployed and scaled separately, making it more suitable for complex and large applications.

**Code Example**

*This example is more about architecture rather than specific code. Below is a simplified example of how a monolith and microservice could be set up*

  *Monolith application structure*
  ```
    /app
        /controllers
            UserController.php
            ProductController.php
            OrderController.php
        /models
            User.php
            Product.php
            Order.php
        /views
            /user
            /product
            /order
  ```

  *Microservice application structure*

    *service A (User Service)*
    ```
    /user-service
        /controllers
            UserController.php
        /models
            User.php
        /database
    ```

    *service B (Product Service)*
    ```
    /product-service
        /controllers
            ProductController.php
        /models
            Product.php
        /database
    ```

  **ASCII Art:**

*Monolith*
```
+---------------------------------------------------+
|                    Monolith Application          |
| +-----------------------------------------------+ |
| |  User Management | Product Catalog  | Order System | |
| |  (Controllers/Models) | (Controllers/Models) |(Controllers/Models)  | |
| +-----------------------------------------------+ |
+---------------------------------------------------+
    |
    |
  Database
```

*Microservices*
```
+-----------------+    +-------------------+     +-----------------+
| User Service    |    | Product Service    |    | Order Service   |
| (API)         |    | (API)            |    | (API)         |
+-----------------+    +-------------------+     +-----------------+
      |                      |                        |
      +--------+--------------+----------+-----------+
                    Database              Database
```
  **References:**

  *  Chris Tankersley, "Monolith vs Microservices".
  *   Figure 1.

***

## PCI-DSS: A Beginner's Guide**

   **Introduction**
  
   For developers who deal with customer payment data, understanding and adhering to **PCI-DSS** (Payment Card Industry Data Security Standard) is crucial. It is not just about compliance, it is about building secure applications. This guide will cover the basics of PCI-DSS and what every developer should know.

   **The Concept**
  
   PCI-DSS is a set of security standards designed to ensure that all companies that accept, process, store, or transmit credit card information maintain a secure environment. It was created by the major card providers to protect cardholder data and reduce credit card fraud. Compliance is essential to avoid penalties and maintain customer trust.

  **Code Example**
   *The actual security practices are mostly architectural, and about processes, not code*
  *Here is an example of how a developer can integrate a third-party payment service such as Stripe in their application*
    ```php
     <?php
        // Example of using Stripe to create a charge.
        require 'vendor/autoload.php';
    
        \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY');
        
        try {
            $charge = \Stripe\Charge::create([
                'amount' => 1000, // Amount in cents
                'currency' => 'usd',
                'source' => $_POST['stripeToken'],
                'description' => 'Example Charge',
            ]);
    
           echo "Payment successful! Charge ID: " . $charge->id;
        } catch(\Stripe\Exception\CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            echo 'Payment declined. Code: ' . $e->getHttpStatus() . 'Error: ' . $e->getMessage();
        } catch (\Stripe\Exception\RateLimitException $e) {
             // Too many requests made to the API too quickly
             echo 'Too many requests. Error: ' . $e->getMessage();
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            echo 'Invalid request. Error: ' . $e->getMessage();
        } catch (\Stripe\Exception\AuthenticationException $e) {
           // Authentication with Stripe's API failed
           // (maybe you changed API keys recently)
           echo 'Authentication failed. Error: ' . $e->getMessage();
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
             echo 'Network error. Error: ' . $e->getMessage();
        } catch (\Stripe\Exception\ApiErrorException $e) {
           // Display a very generic error to the user, and maybe send
           // yourself an email
           echo 'An unexpected error occurred. Error: ' . $e->getMessage();
        } catch (Exception $e){
              echo 'An unexpected error occurred. Error: ' . $e->getMessage();
        }
    ?>
    ```
    
   **ASCII Art:**
   
   ```
   +-------------------+    +---------------------+    +--------------------+
   | Customer          | -->| Your Application    | -->| Payment Gateway  |
   | (Browser)         |    |(No PCI Data Here)  |   |(Stripe/Magento)  |
   +-------------------+    +---------------------+    +--------------------+
                       ^                                     |
                       |                                     V
                       |                                   PCI Compliant
   ```
   
  **References:**
   
  *   Eric Mann, "PCI-DSS: A Beginner's Guide".
  *  External Links.

   The best approach to PCI compliance for developers is to avoid handling card payment data directly, leveraging third party services like Stripe. Secure your application and systems, monitor your application, and protect your customers’ data while working with a partner to simplify the path to compliance.

***
  
## Upgrading with Reckless Abandon**

   **Introduction:**

   Upgrading legacy applications can be daunting, but with a strong testing strategy, it can be done effectively and with "reckless abandon". This article explores the approach to upgrading a PHP 7.1 app originally built using Laravel 5.6, showing how having confidence in testing is key for large upgrades.

   **The Concept:**

   Upgrading legacy applications, often means large jumps across framework and PHP versions. While the ideal scenario is to keep your app updated, this is not always possible. When a project is not actively developed, it may use outdated software and become a security liability. A strong test suite allows you to confidently make major structural changes without manual verification, enabling large upgrades with less risk.

   **Code Example**
  *This example is not a single, but rather a collection of tests used to verify functionality in the application.*
   ```php
   <?php
    // Listing 1: Example test case.
    namespace Tests\Feature;
    
    use App\Models\User;
    use App\Models\Referral;
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use Tests\TestCase;
    
    class ReferralTest extends TestCase
    {
        use RefreshDatabase;
    
        public function testCreateReferral()
        {
            $user = User::factory()->create();
            $this->actingAs($user)
                ->withSession(['foo' => 'bar'])
                ->visit('/referrals')
                ->see('Request Gift Card')
                ->type('Recipient Name', 'recipient_name')
                ->type('recipient@email.com', 'recipient_email')
                ->type('Friend', 'patient_relationship')
                // Removed several more fields to fit
                ->type('Treatment Location', 'facility_name')
                 ->type('Gift Card Type', 'giftcard_type')
                ->attach(public_path().'/logo.jpeg','verification')
                ->press('Submit Request')
                 ->seeInDatabase('referrals', [
                     'recipient_name' => 'Recipient Name',
                    'recipient_email' => 'recipient@email.com',
                    'submitter_name' => 'Submitter Name',
                    'submitter_email' => 'submitter@email.com',
                     'patient_relationship' => 'Friend',
                     'address' => '1234 somewhere street',
                    'address2' => '44th floor',
                     'city' => 'Some City',
                     'state' => 'TN',
                    'zip_code' => '90210',
                    'cancer_type' => 'Illness Type',
                    'doctors_name' => 'Doctors Name',
                     'facility_name' => 'Treatment Location',
                    'giftcard_type' => 'Gift Card Type',
                ])
                ->dontSee('Whoops');
        }
    
        public function testUpdateReferral()
        {
           $user = User::factory()->create();
           Referral::factory(2)->create();
           $referral = Referral::all()->random();
    
           $this->actingAs($user)
              ->withSession(['foo' => 'bar'])
              ->visit('/referrals/' . $referral->id . '/edit')
               ->see('Edit Referral')
                ->type('Updated Recipient Name', 'recipient_name')
               ->type('up_recipient@email.com', 'recipient_email')
               ->type('Updated Submitter Name', 'submitter_name')
               ->type('up_submitter@email.com', 'submitter_email')
              ->type('Updated Friend', 'patient_relationship')
               ->type('Updated 1234 somewhere street', 'address')
               ->type('Updated 44th floor', 'address2')
               ->type('Updated Some City', 'city')
               ->type('Updated TN', 'state')
               ->type('Updated 90210', 'zip_code')
               ->type('Updated Illness Type', 'illness_type')
               ->type('Updated Doctors Name', 'doctors_name')
                ->type('Updated Treatment Loc', 'facility_name')
                ->type('Updated Gift Card Type', 'giftcard_type')
                ->press('Update Referral')
                 ->seeInDatabase('referrals', [
                    'id' => $referral->id,
                    'recipient_name' => 'Updated Recipient Name',
                    'recipient_email' => 'up_recipient@email.com',
                     'submitter_name' => 'Updated Submitter Name',
                     'submitter_email' => 'up_submitter@email.com',
                    'patient_relationship' => 'Updated Friend',
                    'address' => 'Updated 1234 somewhere street',
                    'address2' => 'Updated 44th floor',
                   'city' => 'Updated Some City',
                   'state' => 'Updated TN',
                    'zip_code' => 'Updated 90210',
                    'cancer_type' => 'Updated Illness Type',
                   'doctors_name' => 'Updated Doctors Name',
                   'facility_name' => 'Updated Treatment Loc',
                   'giftcard_type' => 'Updated Gift Card Type',
              ])
               ->dontSee('Whoops');
        }
    }
   ```
   **ASCII Art:**
   ```
    +-----------------+    +-----------------+    +-----------------+
    |    Old App      | --> |    Upgrade     | --> |    New App      |
    | (PHP 7.1/L5.6)  |    |    Process     |    | (PHP 8.1/L9)   |
    +-----------------+    +-----------------+    +-----------------+
         |                    |                    |
         +-----------Test Suite--------------------+
   ```
  
  **References:**
   
  *  Joe Ferguson, "Upgrading with Reckless Abandon".
  *   Code Listings 2 and 3.
   * External Links.
    

  A strong test suite is the most important tool for upgrading legacy applications. It allows you to make significant changes with the confidence that you will identify potential breaking changes with less risk and effort. When you are starting on your application make sure that you start by writing as many tests as it takes to provide confidence in major structural changes.

***

##  Any Two Birthdays**

   **Introduction:**

    Working with dates and times can be tricky, and requires a robust approach. This article presents a practice on how to calculate differences, handle leap years, and process other irregularities, showcasing the power of PHP's `DateTime` library.

    **The Concept**
    
   Working with dates and times requires careful attention to detail, particularly with calculations like time differences and leap years. PHP's `DateTime` library offers a reliable way to handle these tasks, providing functionality for parsing, comparing, and manipulating dates.
   
   **Code Example:**

   ```php
   <?php
    
   // Dates definition
   $jose = new DateTimeImmutable('25 March 1946');
   $sandra = new DateTimeImmutable('6/16/2000');
    
   // Same Day of Week
   echo "Jose was born on " . $jose->format('l') . PHP_EOL;
   echo "Sandra was born on " . $sandra->format('l') . PHP_EOL;
    
   if ($jose->format('D') === $sandra->format('D')) {
       echo "They were born on the same day!" . PHP_EOL;
   } else {
       echo "They were not born on the same day." . PHP_EOL;
   }
   
   //  Meteorological Season
    
   function getSeason(\DateTimeImmutable $date): string {
        switch ($date->format('M')) {
            case 'Mar': case 'Apr': case 'May':
              return 'spring';
            case 'Jun': case 'Jul': case 'Aug':
                return 'summer';
            case 'Sep': case 'Oct': case 'Nov':
                return 'fall';
            case 'Dec': case 'Jan': case 'Feb':
                return 'winter';
           default:
                return '';
       }
   }
   echo 'Jose was born in the ' . getSeason($jose) . PHP_EOL;
   echo 'Sandra was born in the ' . getSeason($sandra) . PHP_EOL;
   
   // Odd or Even
    $sum = (int) $jose->format('j') + (int) $sandra->format('j');
    $isEven = (0 === $sum % 2);
   
    if ($isEven){
       echo "They were both born in odd or even day". PHP_EOL;
    } else {
        echo "They were not both born in odd or even day". PHP_EOL;
    }
   
   // Difference in ages
   $diff = $jose->diff($sandra);
   echo "The difference is " . $diff->y . ' years, '
        . $diff->m . ' months, and ' . $diff->d . ' days.' . PHP_EOL;
   
   // Days Between
   $jose22 = new \DateTimeImmutable(
        $jose->format('2022-m-d')
   );
   $sandra22 = new \DateTimeImmutable(
        $sandra->format('2022-m-d')
   );
   
    $daysDiff22 = abs(
       (int)$jose22->format('z') - (int)$sandra22->format('z')
    );
    
    echo "There are " . $daysDiff22 . ' days between ' .
       'their birthdays in a calendar year.' . PHP_EOL;
   ?>
   ```
   **ASCII Art:**
   ```
    +---------------+    +---------------+
    |  DateTime     |    | DateTime      |
    |  (Date 1)     |    |  (Date 2)     |
    +---------------+    +---------------+
          |                   |
          +-------------------+
                    |
                    v
       +-------------------------+
       | DateTime Operations     |
       |  (Comparisons, diff)    |
       +-------------------------+
   ```
  **References:**

  *   Oscar Merida, "Any Two Birthdays".
   *   Code Listings 1,2,3,4,5,6.
   *  External Links.

  **Conclusion:**
   
   The `DateTime` library is essential when working with dates and times in PHP.  It handles timezones, conversions, and irregularities like leap days, ensuring accurate date manipulations.

***

## Try, or Try Not; There Is No Do**

   **Introduction:**
   
   When building robust applications, especially those with APIs, consistent responses are key. This article extends the concept of transactional boundaries to RESTful API requests and responses, ensuring internal consistency in all situations.

   **The Concept:**

   The principle of "all or nothing" should not only be applied to database transactions, but also to API responses. Your API should either return the full, consistent resource with a 200 status code, or a well-defined error with an error status. This principle helps maintain internal consistency for responses, which can be crucial to the applications that consume it.
   
   **Code Example**
   ```php
   <?php
    // Listing 1: CtgoResponse class
    final class CtgoResponse
    {
    private int $statusCode;
    private string $statusText;
    private string $errorSummary;
    private string $errorDescription;
    private array $responseBody;
    
        public function __construct(
            int $statusCode = 200,
            string $statusText = 'OK',
            string $errorSummary = '',
            string $errorDescription = '',
            array $responseBody = []
        ) {
            $this->statusCode = $statusCode;
           $this->statusText = $statusText;
            $this->errorSummary = $errorSummary;
            $this->errorDescription = $errorDescription;
            $this->responseBody = $responseBody;
       }
    
       public function getStatusCode(): int
       {
           return $this->statusCode;
       }
    
       public function getErrorResponseBody(): array
       {
           return [
              'status_code' => $this->statusCode,
               'status_text' => $this->statusText,
               'error_summary' => $this->errorSummary,
               'error_description' =>
              $this->errorDescription,
           ];
       }
    
       public function getResponseBody(): array
       {
           return $this->responseBody;
       }
    
        public function getStatusText(): string
        {
            return $this->statusText;
        }
   }
    
    // Listing 2: CtgoException class
    class CtgoException extends RuntimeException
    {
       private CtgoResponse $ctgoResponse;
    
       public function __construct(
           CtgoResponse $ctgoResponse,
           string $message = '',
            int $code = 0,
            ?Throwable $previous = null
        ) {
            parent::__construct($message, $code, $previous);
            $this->ctgoResponse = $ctgoResponse;
       }
   
       public function getCtgoResponse(): CtgoResponse
        {
            return $this->ctgoResponse;
       }
   }
   //Listing 3: Example of controller
   class TeamsController extends AppController
    {
        public function viewHeadCoach(): ?Response
       {
            $this->request->allowMethod('get');
            $ctgoAuthorize =
            CtgoAuthorizeFactory::forTeamProfile(
                $this->request
            );
            try {
                $ctgoAuthorize->authorize();
                $team = $ctgoAuthorize->getTeam();
                $userRole = $this->loadUserRole($team);
           } catch (CtgoException $e) {
                $this->processCtgoException($e);
                return null;
            }
            
            $results = (new MapTeamStaff())->map($userRole);
            $this->set(compact('results'));
            $this->viewBuilder()
                 ->setOption('serialize', 'results');
            return null;
        }
   }
    
```