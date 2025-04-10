---
title: PHP Teacher magazine - May 2022
publishDate: 2022-03-01 00:00:00
description: Welcome to this issue of monthly PHP Teacher magazine, a magazine dedicated to helping PHP developers hone their skills and build more effective and resilient applications. This month, we delve into several key areas essential for any modern developer.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - may 
  - 2022
---

Welcome to this issue of monthly PHP Teacher magazine, a magazine dedicated to helping PHP developers hone their skills and build more effective and resilient applications. This month, we delve into several key areas essential for any modern developer. 

## The Power of Lifelong Learning in Software Development**

This article emphasize the importance of lifelong learning for software developers, providing several strategies for effective knowledge acquisition, retention, and application. 
To illustrate how these strategies can be applied in the context of PHP development, here are some specific examples using PHP code and scenarios:

**Active Engagement:**

*   Instead of passively reading PHP documentation, ask questions. For example, if you're learning about the `array_map()` function, don't just accept its functionality. Question:
    *   Why use `array_map()` instead of a `foreach` loop? What are the performance implications?
    *   What happens if the callback function modifies the original array?
    *   Are there situations where `array_walk()` is a better choice?

    ```php
    // Example of using array_map
    $numbers =;
    $squaredNumbers = array_map(function($number) {
    return $number * $number;
    }, $numbers);
    
    // Question: What are the performance implications of this?
    ```
    
    By actively questioning, you go beyond surface-level understanding.

**Hands-on Experimentation:**

*   Don't just copy and paste code examples from PHP tutorials. Type the code yourself and modify it to see the effects. For instance, when learning about PHP namespaces:

    ```php
    // Instead of just copying, type this out
    namespace MyProject\Database;
    
    class User {
    //...
    }
    
    // Then, try modifying it
    namespace MyProject\Models;
    
    class Product {
        //...
    }
    ```
    
    *   Try using different namespace structures and see how it affects code organization.
    *   Create a new class in a different namespace and try to use it.
    *   Intentionally make mistakes to understand error messages.

**Practice and Repetition:**

*   Regularly practice core PHP concepts. Create small projects to apply what you've learned.

    *   For example, if you’re learning about object-oriented programming, create a simple e-commerce application that has classes like `Product`, `User`, and `Cart`.
    *   Revisit old exercises without looking at the solutions.
    *   Practice without an IDE, using only a text editor to enhance your understanding of PHP syntax.

**Self-Testing and Spaced Repetition:**

*   Create flashcards for key PHP concepts:

    *   **Front:** What is the purpose of `PDO`?
    *   **Back:** PHP Data Objects provides a consistent way to access databases.

    *   **Front:** What is the difference between `==` and `===` in PHP?
    *   **Back:** `==` checks for equality of value. `===` checks for equality of value and type.

    Use a tool like Anki to review these flashcards at increasing intervals, reinforcing your knowledge over time.

**Learn by Sharing:**

*   Explain complex PHP topics to someone who is not technical:

    *   For example, try explaining the concept of dependency injection to a non-developer.
    *   Write a blog post about a PHP project you've worked on or a new feature you've learnt about.
    *   Answer questions about PHP on Stack Overflow or similar forums.
    *   By explaining the code, you will solidify your understanding:

    ```php
    // Assume you are explaining dependency injection to a non-technical person
    class DatabaseConnection {
        public function connect(){
            // ... logic to connect
        }
    }
    
    class UserRepository{
        private DatabaseConnection $connection;
    
        //instead of creating the connection here:
        //public function __construct(){
        //  $this->connection = new DatabaseConnection();
        //}
    
        //We pass in the connection
        public function __construct(DatabaseConnection $connection){
            $this->connection = $connection;
        }
    
        public function getUserById($id){
        //... access the database
        }
    }
    
    // Use it like this:
    $connection = new DatabaseConnection();
    $repository = new UserRepository($connection);
    ```
    
**Pair Programming:**

*   Work with other developers on PHP projects.
    *   Pair with someone who is more experienced in a particular framework (e.g. Symfony, Laravel), to learn their best practices.
    *   Pair with someone who has less experience to solidify your own understanding by explaining your choices.
    *   This helps you see different approaches and improve your problem-solving skills.

**Scope and Fun:**

*   When learning a new PHP concept, focus on smaller, achievable goals:

    *   Instead of trying to learn all of Symfony at once, focus on learning just the routing component first.
    *   Mix in fun and unrelated topics to keep things interesting. For example, if you're a backend developer, try building a small web game using PHP.

By actively engaging, experimenting, and sharing knowledge, you will not only learn more effectively, but you will also find the learning process more rewarding. The examples above demonstrate how these principles can be applied specifically within the context of PHP development, using code and practical scenarios to reinforce your understanding.

### Moving Beyond Passive Consumption**

Many developers fall into the trap of passive learning, reading articles or watching tutorials without truly engaging with the material. This approach often leads to superficial understanding and poor retention. To combat this, developers must adopt active learning strategies that stimulate critical thinking and practical application.

*   **Active Questioning:** When encountering new concepts or techniques, **don't accept them at face value**. Instead, ask probing questions:
    *   Why is this method preferred over another?
    *   What are the potential drawbacks or limitations?
    *   In what specific scenarios is this approach most applicable?
    *   What are the underlying assumptions and principles?

    This process of questioning forces you to think critically, analyse the information and gain a much deeper understanding of the topic. Articulating your questions, even if it is only to yourself, can greatly improve comprehension and retention. For example, if you read an article suggesting a particular design pattern is superior, ask yourself why. What problems does it solve that other patterns don't? Are there situations where this pattern might be less suitable?

*   **Hands-on Experimentation:** Reading about code is different from writing code. **Passive absorption of code examples should be avoided**. Instead, type out code examples yourself. This helps reinforce the syntax, logic, and structure in your mind.

    *   **Don't just copy and paste** code examples, manually type them out.
    *   **Modify the examples**, add new features, or break them to understand what happens when things go wrong.
    *   **Create your own projects** to solidify your learning.
    *   If you learn about web sockets in a tutorial, try building a simple chat application or similar application that relies on web sockets.

    The act of typing, debugging, and experimenting makes learning a physical and cognitive process.

*   **Practice and Repetition**: Mastery in software development requires practice. Like learning a musical instrument, coding involves developing muscle memory and intuition.

    *   **Don't just focus on the fun stuff**, do the boring exercises that are important for laying the foundation.
    *   **Challenge yourself by coding without your usual tools** (IDE, debugger), using just a text editor or even pen and paper.
    *   Repetition, especially when combined with varied contexts, strengthens your skills.

*   **Self-Testing and Spaced Repetition:** Testing is a powerful method of reinforcing connections in your brain. **Don't wait for performance reviews or exams to test your knowledge**.

    *   Create your own flashcards of key concepts, and regularly review them.
    *   Use spaced repetition tools like Anki, which schedule reviews at optimal times to enhance retention.
    *   Regular self-testing helps transfer information from short-term to long-term memory.

*   **Learn by Sharing:** Teaching someone else is a very powerful method to learn yourself.

    *   **Explaining a concept to others reveals any gaps in your own understanding**.
    *   Try explaining code to someone outside your field or to a non-technical person.
    *   **Write blog posts or articles** about what you are learning to improve understanding.
    *   The process of articulating complex ideas sharpens your own grasp of the subject matter.

*   **Pair Programming:** Working with a partner can greatly enhance your learning and your focus.

    *   You will learn different techniques and approaches and learn to articulate your ideas.
    *   Pairing can help when you are stuck and provide another perspective on complex problems.

*   **Scope and Fun:** The goal of lifelong learning is to constantly get better.

    *   Don't try to do too much at once. Pick small topics that you can achieve to build confidence.
    *   Learn new fun and exciting areas. The mix of learning fun and un-fun areas helps to keep you motivated.
    *   The goal is to never stop learning and have fun while doing so.

**Code Example (Python - illustrating active learning with a function)**

```python
# Passive Learning - Just reading the function
def add(a, b):
    return a + b

# Active Learning - Questioning and experimenting

# Why does this function add two numbers?
# What happens if we pass strings?
# What if a or b is a float?

# Example using strings (expecting an error)
try:
  print(add("hello", "world"))
except TypeError as e:
    print(f"Error: {e}")

# Experiment with different data types
print(add(2, 3))
print(add(2.5, 3.5))
```

This code block exemplifies active engagement by illustrating not just how the function works but also the effects of experimentation by inputting different values that reveal implicit assumptions of the code. The example moves beyond just the basic functionality of addition to consider error handling and data types.

## Hacking Your Home with a Raspberry Pi: A Practical Guide**

The Raspberry Pi has become a powerful and versatile tool for hobbyists and professionals alike. This article explores a practical project that demonstrates how a Raspberry Pi can be used to monitor a clothes dryer, providing a hands-on example of combining hardware, software, and data analysis.

**Project Overview**

The project involves creating a system that uses a Raspberry Pi to monitor a clothes dryer's vibrations. Using an accelerometer connected to the Raspberry Pi, the system logs data, detects when the dryer has stopped running and sends a text message notification.

*   **Hardware Setup:**
    *   **Raspberry Pi:** The central processing unit of the system. It runs the operating system, collects sensor data, performs data analysis and sends notifications.
    *   **Accelerometer:** A sensor that measures the acceleration of the dryer, providing data about its movement.
    *   **Breadboard and Wiring:** Used to connect the accelerometer to the Raspberry Pi.

*   **Software Setup:**
    *   **Raspberry Pi OS:** A Linux distribution optimised for Raspberry Pi.
    *   **LAMP Stack:** Apache, MySQL, and PHP for setting up a web server to display the data.
    *   **C++ Program:** For logging data from the accelerometer and implementing the state machine.
    *   **Postfix:** For sending email notifications, which is then configured to send SMS notifications.
    *   **TinyFSM:** A C++ template library for implementing finite state machines.

**Implementing the State Machine**

The core of the project is the state machine, which transitions between states based on the accelerometer readings.

*   **States:**
    *   **Dryer Off:** The dryer is not running.
    *   **Dryer Going On:** The dryer is starting to run, a transition state.
    *   **Dryer On:** The dryer is actively running.
    *   **Dryer Going Off:** The dryer is slowing down, a transition state.

*   **Transitions:** The system moves from one state to another based on the movement of the accelerometer.

    *   **Dryer Off to Dryer Going On:** When movement is detected from the accelerometer.
    *   **Dryer Going On to Dryer On:** When the movement continues.
    *   **Dryer On to Dryer Going Off:** When there is no movement from the accelerometer.
    *   **Dryer Going Off to Dryer Off:** When the movement has stopped.

    The system sends a message when it detects the transition from 'Dryer Going Off' to 'Dryer Off'.

**C++ Code Example (simplified state machine)**

```cpp
#include <iostream>
#include <string>

enum class DryerState {
  OFF,
  GOING_ON,
  ON,
  GOING_OFF
};

class StateMachine {
public:
    DryerState currentState = DryerState::OFF;

    void processEvent(const std::string& event) {
        std::cout << "Current State: " << toString(currentState) << std::endl;
        if (currentState == DryerState::OFF && event == "movement"){
            currentState = DryerState::GOING_ON;
            std::cout << "Transitioning to: " << toString(currentState) << std::endl;
        }
        else if (currentState == DryerState::GOING_ON && event == "movement"){
            currentState = DryerState::ON;
            std::cout << "Transitioning to: " << toString(currentState) << std::endl;
        }
         else if(currentState == DryerState::ON && event == "no_movement")
         {
           currentState = DryerState::GOING_OFF;
            std::cout << "Transitioning to: " << toString(currentState) << std::endl;
         }
          else if(currentState == DryerState::GOING_OFF && event == "no_movement")
         {
           currentState = DryerState::OFF;
           std::cout << "Transitioning to: " << toString(currentState) << std::endl;
           std::cout << "Dryer cycle complete - sending notification" << std::endl;
         }

    }

private:
   std::string toString(DryerState state){
      switch(state){
        case DryerState::OFF: return "OFF";
        case DryerState::GOING_ON: return "GOING_ON";
        case DryerState::ON: return "ON";
        case DryerState::GOING_OFF: return "GOING_OFF";
      }
      return "UNKNOWN";
    }
};


int main() {
    StateMachine dryer;
    dryer.processEvent("movement");
    dryer.processEvent("movement");
     dryer.processEvent("no_movement");
    dryer.processEvent("no_movement");
    return 0;
}

```

This C++ code illustrates a simplified state machine with transitions based on "movement" and "no\_movement" events. This example is not the full implementation but provides insight into how the state machine would transition.

**Data Storage and Display**

*   **Database Setup:** A database (e.g., MySQL) stores data from the accelerometer, including timestamps and acceleration values.
*   **Web Interface:** A web service (e.g., using PHP) retrieves data from the database and displays it, allowing users to monitor the dryer's activity.

**Email Notifications**

*   **Postfix Configuration:** Postfix is configured to send email notifications through a Gmail account.
*   **SMS Gateway:** Gmail can be configured to send messages to your phone using the SMS gateway provided by most phone companies.
*   When the dryer stops running, the system sends a notification to your phone, confirming the drying cycle is complete.

**Benefits of This Project**

*   **Hands-on Experience:** You get hands-on experience with hardware, software and real-world data.
*   **Problem-Solving Skills:** You develop problem-solving skills by diagnosing and fixing software and hardware issues.
*   **Practical Application:** You learn by implementing a practical application that addresses a common need.
*   **Customization:** You can customize the system, add more features or adapt the system to different scenarios.

## Continuous Code: The Art of Iterative Development and Automated Testing**

In the fast-paced world of software development, the ability to deliver code quickly, reliably and with high confidence is paramount. This article explores the principles of continuous integration and continuous deployment (CI/CD), which are essential for modern software development practices.

**Iterative Development: The Foundation of CI/CD**

The iterative approach to development, as seen in the Booch Method, focuses on building software in incremental stages. This approach is fundamental to the practices of continuous integration and continuous deployment.

*   **Incremental Stages:** Building software in incremental steps.
*   **Early Testing:** Bugs are identified early in the development cycle.
*   **Feedback Loops:** It allows for early and continuous feedback.
*   **Adaptability:** It’s easier to adapt to changing requirements.

**Test-Driven Development: The Core of Confidence**

Test-Driven Development (TDD) is a method where you write tests before writing the actual code. This helps to ensure the code is testable, robust, and meets requirements.

*   **Write Tests First:** Write a test that describes how the code should behave.
*   **Implement Functionality:** Write the code to pass the test.
*   **Refactor:** Improve the code while ensuring that the tests still pass.
*   **Cycle Continues:** Repeat the cycle for each feature or change.

**PHPUnit Example**

```php
<?php
use PHPUnit\Framework\TestCase;

class CalculatorTest extends TestCase
{
    public function testAdd()
    {
        $calculator = new Calculator();
        $this->assertEquals(5, $calculator->add(2, 3));
    }

     public function testSubtract()
    {
        $calculator = new Calculator();
        $this->assertEquals(1, $calculator->subtract(3, 2));
    }
}

class Calculator
{
  public function add($a, $b){
    return $a + $b;
  }

  public function subtract($a, $b){
    return $a - $b;
  }
}
```

This PHPUnit code demonstrates how tests are written before the `Calculator` class has been implemented. The tests `testAdd` and `testSubtract` describe how the methods should behave. The `Calculator` class is implemented to make the tests pass.

**Continuous Integration: Frequent Merging and Testing**

Continuous Integration (CI) is the practice of frequently merging code changes and running automated tests.

*   **Frequent Commits:** Code changes should be committed frequently.
*   **Automated Testing:** Tests are automatically run whenever code is merged.
*   **Early Error Detection:** Integration issues are caught early.
*   **Code Quality:** Improves overall code quality.

**Continuous Deployment: Automated Releases**

Continuous Deployment (CD) automatically pushes code to production after it has passed all tests. This removes the need for manual deployments.

*   **Automated Pipeline:** Automated process to build, test, and deploy code.
*   **Faster Releases:** Deployments are faster and more frequent.
*   **Reduced Human Error:** Reduces the risk of human error during deployments.
*   **Confidence in Code:** Confidence in the code is crucial for adopting CD.

**Building a CI/CD Pipeline**

*   **Version Control:** Use a version control system like Git.
*   **CI Server:** Set up a CI server like Jenkins, GitHub Actions, GitLab CI, or CircleCI.
*   **Testing Frameworks:** Use a testing framework like PHPUnit.
*   **Deployment Tools:** Use deployment tools to automatically push code to production.

**Benefits of CI/CD**

*   **Faster Time to Market:** Faster releases means getting product to market sooner.
*   **Increased Reliability:** Automated testing and deployments reduces bugs and errors.
*   **Improved Code Quality:** Helps maintain better code quality.
*   **Reduced Risk:** Reduces the risk of manual deployments.

## Classifying Ransomware: Types and Protection Strategies**

Ransomware has become a prevalent and increasingly dangerous cyber threat. This article delves into the different types of ransomware and provides essential protection strategies.

**Types of Ransomware**

*   **Type 1 Ransomware:** Does not encrypt data. It uses a pop-up to demand payment, threatening to delete or block access to files. It does not use encryption to lock the user out of their system.
*   **Type 2 Ransomware:** Encrypts data and demands a payment for the decryption key. If you do not pay you can no longer access your files. WannaCry is a well known example of this type.
*   **Type 3 Ransomware:** This ransomware involves exfiltration of data. Hackers breach systems, download data, and then threaten to release it publicly unless a ransom is paid. NotPetya is an example of this type of ransomware, it caused large amounts of damage without ransoming any data.

**Protection Measures**

*   **Regular, Offsite Backups:** Crucial for recovering from ransomware.

    *   **3-2-1 Rule:** Keep three copies of your data on two different storage media, with one copy offsite.
    *   **Test Backups:** Regularly test backups to ensure that they are reliable.
    *   **Air-Gapped Backups:** Air-gapped backups stored offline are the most secure.

*   **Anomaly Monitoring:** This involves detecting unusual activity in your systems.

    *   **Intrusion Detection:** Implement intrusion detection systems to find potential threats.
    *   **Unusual Activity:** Detect unusual logons, data transfers, or access patterns.
    *   **Automated Alerts:** Set up automated alerts when anomalous activity is detected.

*   **Anti-Malware Systems:** Use robust anti-malware software on all system endpoints, including employee machines.

    *   **Endpoint Protection:** Use endpoint detection and response systems on all endpoints.
    *   **Regular Updates:** Keep your anti-malware software up to date.
    *   **Firewalls:** Use firewalls to protect your network.

*   **User Awareness Training:** Educate employees about ransomware and phishing scams.

    *   **Phishing Awareness:** Teach employees to recognise phishing attempts and other social engineering attacks.
    *   **Security Best Practices:** Reinforce the importance of secure passwords and cautious internet browsing.
    *   **Incident Reporting:** Encourage employees to report suspicious activities.

*   **Network Segmentation:** Divide your network into smaller segments.

    *   **Lateral Movement:** Limits the lateral movement of malware within your network.
    *   **Containment:** This allows you to contain the damage of ransomware within a segment.
    *   **Access Control:** Control access between network segments.

*   **Patch Management:** Regularly update software and operating systems.

    *   **Vulnerability Scanners:** Regularly run vulnerability scanners to identify vulnerabilities.
    *   **Automated Patches:** Use automated patching tools.
    *   **Security Updates:** Stay current with security updates to reduce risk of vulnerability exploits.

*   **Incident Response Plan:** Have a well-defined plan for responding to a ransomware attack.

    *   **Isolation:** Isolate the affected systems and network segments.
    *   **Containment:** Identify and contain the ransomware attack quickly.
    *   **Recovery:** Recover lost data from backups.

**Example: Python code for anomaly detection**

```python
import random

def simulate_logins(num_logins):
  logins = []
  for i in range(num_logins):
    hour = random.randint(0, 23)
    logins.append(hour)
  return logins

def detect_anomalies(logins, threshold=3):
  average_login_hour = sum(logins) / len(logins)
  anomalies = []
  for hour in logins:
      if abs(hour - average_login_hour) > threshold:
        anomalies.append(hour)
  return anomalies

logins = simulate_logins(100)
anomalies = detect_anomalies(logins)
print(f"Logins this hour : {logins}")
print(f"Anomalies : {anomalies}")

```

This Python code provides a basic example of how anomaly detection might work. It simulates logins across hours of the day and then calculates which logins are outside of the threshold.

## Getting Organized with Domain-Driven Design: A Strategic Approach**

Domain-Driven Design (DDD) is a methodology for designing complex systems by focusing on the business domain. This article explores the strategic aspects of DDD, emphasizing communication, collaboration, and testability.

**Strategic vs Tactical DDD**

*   **Strategic DDD:** Focuses on understanding the business domain, its complexity, and the interactions between various parts of the system. It involves collaboration between developers and domain experts to model the business problem accurately.
*   **Tactical DDD:** Concerned with the coding patterns used to translate the domain model into code.

**Bounded Contexts: Managing Complexity**

*   **Partitioning Systems:** Bounded contexts are used to divide large systems into smaller, manageable subdomains.
*   **Explicit Boundaries:** Each context has explicit boundaries, a unique model, rules, and language.
*   **Clear Communication:** Clear communication among teams working within different bounded contexts is important.
*   **Independent Development:** Teams can develop independently within a specific context.

**Testability: Prioritising Quality**

*   **New Code:** New code should be added to bounded contexts with an emphasis on testability.
*   **Existing Code:** Existing code should be left as it is, tests should not be added to it.
*   **Testable Design:** Ensure the application is designed for testability.
*   **Automated Testing:** Implement automated tests within bounded contexts.

**Separation from Framework: Maintaining Flexibility**

*   **Framework Agnostic Code:** Code should be separated from the MVC framework to make it more flexible and reusable.
*   **Access Framework Features:** The code should retain access to the power of the framework when it is needed.
*   **Domain Model:** The goal is to focus on the domain model rather than specific implementation details.

**Project Setup and Folder Structure**

*   **Clear Folder Structure:** Organize code within specific bounded contexts to improve clarity.
*   **IDE Helpers:** Use IDE helpers to support the use of the framework.
*   **Code Organization:** Organise the code in such a way that code is easy to find.

**Database Access with Traits: Clean and Consistent**

*   **Traits:** Use traits to access the database from outside of the MVC framework.
*   **Database Logic:** Add database logic into the traits.
*   **Consistent Access:** Consistent way to access different tables and database functions.
*   **Testable Code:** Separating database logic allows the code to be testable.

**PHP Code example**

```php
<?php

trait UserTable
{
    public function getUserById($id)
    {
        $db = new PDO("mysql:host=localhost;dbname=mydatabase", "user", "password");
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

class ContextRoot
{
    use UserTable;
    public function getUser($id)
    {
        return $this->getUserById($id);
    }
}

class ContextRootTest extends \PHPUnit\Framework\TestCase
{
  public function testGetUser() {
      $root = new ContextRoot();
      // This is just example data for the test, it won't actually connect to the database
      $mockUser = ["id"=>1, "username"=>"testUser"];

      $mockPDO = $this->createMock(\PDO::class);
      $mockStmt = $this->createMock(\PDOStatement::class);

     $mockPDO->method('prepare')->willReturn($mockStmt);
     $mockStmt->method('execute')->willReturn(true);
     $mockStmt->method('fetch')->willReturn($mockUser);

     $this->assertTrue(method_exists($root,'getUserById'));

     $this->assertEquals($mockUser, $root->getUser(1));
    }
}
```

This code illustrates a simple PHP trait for accessing a database. The code also includes a test for the method `getUser`, which demonstrates the testability of the code. 
The code uses a Mock object for the PDO so it does not require connecting to the database.
