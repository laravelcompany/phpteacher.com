---
title: PHP Teacher magazine - April 2022
publishDate: 2023-04-19 00:00:00
description: Our monthly PHP Teacher magazine covers various aspects of PHP development and related technologies.
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - april
  - 2022
---

This month collection of articles from the PHP Teacher magazine covers various aspects of PHP development and related technologies. 
One article explores testing the PHP core, guiding readers through the process of writing and running tests. 
Another focuses on choosing appropriate software licenses, comparing open-source and proprietary options, and highlighting their legal implications. 
A further piece details enhancing application security, drawing lessons from past mistakes and offering best practices. 
Finally, practical guides on building web services and applications using PHP and related tools such as Codeception and Smoothie Charts are provided.



# Testing the Core: A Deep Dive into PHP Internals**

Greetings, code warriors! Ever wondered what lies beneath the shiny surface of PHP? Beyond the frameworks and the userland code, there’s a whole other world – the PHP core itself! In this deep dive, we'll explore how to get our hands dirty, write some tests, and maybe even contribute back to the very heart of our beloved language.

Let’s start by cloning the PHP source code.

```bash
$ git clone git@github.com:php/php-src.git
$ cd php-src
```

Now, we need to build it:

```bash
$ ./buildconf
$ ./configure --with-zlib
$ make -j `nproc`
```
   
          _  _
         (o)(o)
      |  /   \  |
      | /     \ |
      |-------|
    _/         \_
   /_____________\

This isn't your average "hello world" compile, my friends. We are compiling the very engine of PHP!  After it builds, you can find the PHP binary in `./sapi/cli/php` and check it with:

```bash
$ ./sapi/cli/php –v
```

Now that we have our freshly compiled PHP binary, it’s time for some testing.  PHP uses `.phpt` files for its tests. A basic test file has three mandatory sections: `TEST`, `FILE`, and `EXPECT`. Think of it like a unit test, but for the core itself.

```
--TEST--strlen() function
--FILE--
<?php
var_dump(strlen('Hello World!'));
?>
--EXPECT--
int(12)
```
Let's break it down:
*   **`--TEST--`**:  A short description of what's being tested.
*   **`--FILE--`**: The actual PHP code to execute. It's very common to see `var_dump` used for creating output.
*   **`--EXPECT--`**: The expected output of the `FILE` section.

The test runner expects the output of the FILE section to match the EXPECT section exactly. There's also the `EXPECTF` section, which lets you use `printf`-style tags for dynamic output.

You can also add other optional sections:

*   **`--DESCRIPTION--`**:  For more verbose descriptions.
*   **`--EXTENSIONS--`**: Specify any required PHP extensions for your test.
*   **`--SKIPIF--`**:  Skip the test based on a condition.
*   **`--XFAIL--`**: Indicate that a test is expected to fail.
*   **`--CLEAN--`**:  Run cleanup code after your tests.

To run the tests, use the `make test` command:

```bash
$ make TEST_PHP_ARGS=-j`nproc` test
```

To write our own test, let’s explore an example with zlib.  Florian Engelhardt found that the `zlib_get_coding_type()` function lacked test coverage. This function returns either "gzip", "deflate", or `false`. You can find out where this function is located in the source code, in this case: ext/zlib/zlib.c. Then, by referring to the PHP documentation for the function, you can then start to create the test scenarios.

The test case must consider all possible scenarios:
*   No `Accept-Encoding` header
*   `Accept-Encoding` set to `gzip`
*   `Accept-Encoding` set to `deflate`
*   `Accept-Encoding` set to anything else

Here's the first test case, for when there is no Accept-Encoding header set:

```
--TEST-- zlib_get_coding_type()
--EXTENSIONS--
zlib
--FILE--
<?php
ini_set('zlib.output_compression', 'Off');
var_dump(zlib_get_coding_type());
ini_set('zlib.output_compression', 'On');
var_dump(zlib_get_coding_type());
?>
--EXPECT--
bool(false)
bool(false)
```

When running the test, it may fail at first, because the output compression setting cannot be changed after any output is sent. To avoid the warning and make it pass, make sure to change the `FILE` section to only create output after calls to `ini_set`.

```php
ini_set('zlib.output_compression', 'Off');
$off = zlib_get_coding_type();
ini_set('zlib.output_compression', 'On');
$on = zlib_get_coding_type();
var_dump($off);
var_dump($on);
?>
```

To test the gzip scenario, we have to use the `ENV` section, which passes environment variables to the script.

```
--TEST-- zlib_get_coding_type() is gzip
--EXTENSIONS--
zlib
--ENV--
HTTP_ACCEPT_ENCODING=gzip
--FILE--
<?php
ini_set('zlib.output_compression', 'Off');
$off = zlib_get_coding_type();
ini_set('zlib.output_compression', 'On');
$gzip = zlib_get_coding_type();
var_dump($off);
var_dump($gzip);
?>
--EXPECT--
bool(false)
string(4) "gzip"
```

To verify code coverage, you need `lcov` and have to recompile PHP with `gcov` enabled.  Afterwards, you can generate an HTML report to see the areas your tests cover.  This is how you can see if you covered all the possible cases of the `zlib_get_coding_type`. 

Writing PHP core tests isn't just about making PHP more robust; it's also about gaining a deep understanding of how PHP actually works. Plus, the skill you acquire will help you write tests for other projects, like PHPUnit. This process may even get you an ElePHPant!

## Hacking Your Home with PHP: Building an Accelerometer Plotting App**

Fellow tech tinkerers, let's dive into the fascinating world of home automation, powered by a Raspberry Pi and, of course, PHP! We're not just building another to-do list app; we're plotting accelerometer data in real time!

Our journey began with setting up the Raspberry Pi, installing a LAMP stack, and connecting an accelerometer. We even created a C++ program to log the accelerometer data to a database, and a Unix service to start and stop the logging process automatically. Now we're building a web service and a plotting application to visualize that data!

First, the web service. We’ll create an API that uses HTTP `GET` requests.  This API will respond with JSON data containing the latest accelerometer readings.
```
    _______
   /       \
  |  O   O  |
  |   \ /   |
  \_______/
     ||||
     ||||
    ------
```
To keep things simple, we'll have one endpoint at: `http://raspberrypi.local/accelerometerservice/`. The first request will return the newest accelerometer data. Subsequent requests can include a `lastMeasurementId` parameter, fetching only new data since the last request. The data will be in JSON, like this:
```json
{
  "accelerationData": {
    "accelerationMeasurements": [
      {
        "axis": {
          "X": "0.199219",
          "Y": "-0.182617",
          "Z": "0.943359"
        },
        "dateTime": "2022-03-02 15:00:46.631"
      }
    ],
    "lastMeasurementId": "171695"
  }
}
```

The PHP code for the web service includes these files:
*   `AccelerometerData.php`: A class representing a row from the `accelerometer_data` table.
*   `AccelerometerDataManager.php`:  A class with methods for reading data from the database and formatting it as a JSON payload.
*   `index.php`: The entry point for our web service.

The `AccelerometerDataManager` has two primary methods:
*   `readLatest()`: Fetches the newest record.
*   `readFromIdToLatest($id)`: Fetches records since the last ID.

The `index.php` file handles the request and returns the appropriate JSON response.
```php
<?php
require_once("AccelerometerDataManager.php");

$httpVerb = $_SERVER['REQUEST_METHOD'];

$accelerometerDataManager = new AccelerometerDataManager();

switch ($httpVerb) {
    case "GET":
        header("Content-Type: application/json");
        if (isset($_GET['lastMeasurementId'])) {
            echo $accelerometerDataManager->readFromIdToLatest($_GET['lastMeasurementId']);
        } else {
            echo $accelerometerDataManager->readLatest();
        }
        break;
    default:
        throw new Exception("Unsupported HTTP request");
        break;
}
?>
```

To install the web service, you download a zip file to your Raspberry Pi, unzip it, and move it to the `/var/www/html/` directory.

Next, the plotting application! This is a client-side web app, using JavaScript to fetch data from our web service every second and plot it using the Smoothie Charts library.
```
     /\
    /  \
   /    \
  /______\
 |        |
 |   --   |
  --------
```

The application includes:
*   `smoothie.js`: The Smoothie Charts JavaScript library.
*   `index.html`:  The main page, displaying the real-time plot.
*   jQuery is used to make the AJAX calls to get data from the web service.

The JavaScript code in `index.html` sets up a `SmoothieChart` object and the time series for the X, Y and Z axes. The `setInterval()` function fetches data every second and updates the chart.

```javascript
function plotAccelerometerData() {
  let x = new TimeSeries();
  let y = new TimeSeries();
  let z = new TimeSeries();

  setInterval(() => {
    getLatestAccelerometerData(x, y, z);
  }, 1000);


  let smoothie = new SmoothieChart({responsive: true, grid: {strokeStyle:'rgb(125, 0, 0)', fillStyle:'rgb(60, 0, 0)', lineWidth:1, millisPerLine: 250, verticalSections:6}, labels:{fillStyle:'rgb(255,255,255)'}});
  smoothie.addTimeSeries(x, {strokeStyle: 'rgb(0, 255, 0)', fillStyle: 'rgba(0, 255, 0, 0.3)',lineWidth: 3 });
  smoothie.addTimeSeries(y, {strokeStyle: 'rgb(255, 0, 255)', fillStyle: 'rgba(255, 0, 255, 0.3)',lineWidth: 3 });
  smoothie.addTimeSeries(z, {strokeStyle: 'rgb(0, 0, 255)', fillStyle: 'rgba(0, 0, 255, 0.3)',lineWidth: 3 });

  smoothie.streamTo(document.getElementById("acceleration_data_plot_canvas"), 1000);
}
```

To install the plotting application, download the zip file to your Pi and move the accelerometer directory to `/var/www/html/`.  With the accelerometer and web service running, you should see a live, interactive plot of the accelerometer data at `http://raspberrypi.local/accelerometer/`.

## Choosing a License: A Guide for the Perplexed Developer**

Ah, licenses... those legal documents that often seem like they were written in a foreign language! But fear not, fellow coders, because understanding licenses is crucial for protecting your work and respecting the rights of others.

At its core, a software license is a legal agreement that outlines how a user can legally use the software. This includes installation, integration into other software, and modification.

If there is no license, the copyright holder retains all rights. The source code can be seen on GitHub, but it doesn’t give you any rights to use it. So, choosing the right license is very important!

Let's start with the concept of the **Public Domain.** If a work is in the Public Domain, it is free for anyone to use for any purpose. You can dedicate your work to the Public Domain, or it enters this domain when the copyright expires. The Creative Commons 0 license, and the Unlicense are two ways to get very close to a Public Domain declaration.
```
     ____
    /   /
   /___/
  /   /
 /___/   
```

Next, let's discuss the **"Don't Be a Dick" license**. This license is very short and basically says don’t be a jerk when using the software. But, the problem with this license is that the copyright holder can decide what is "dickish," making it a legally uncertain option.

The **Proprietary License** is a license that is specific to a piece of software, like an End User License Agreement (EULA). This license controls usage of the software in terms of installation, usage and other legal restrictions. Each software product has its own unique EULA, making it unique to that software. EULAs usually don't differentiate between source code and compiled code.

Now we get to the world of **Open Source**. To be considered Open Source, the license must meet a set of criteria, as defined by the Open Source Initiative. Here’s a few of those guidelines:

*   **Free Redistribution:** Users must be able to distribute the software freely.
*   **Source Code:** The source code must be included, and can be distributed as both source and compiled code.
*   **Derived Works:**  Modifications and derivative works must be allowed.
*  **No Discrimination:** The license cannot discriminate against any person, group, or field of endeavor.
*   **Technology Neutral**: The license must not be tied to a specific technology.

Some licenses look like open-source, but restrict use, like the JSON license which says that the software should be used “for Good, not Evil”. This is a vague and problematic restriction.

Open Source licenses fall into two broad categories:

**Permissive Licenses:** These are licenses that allow the most freedom. Users can use, modify, and distribute the code, even in proprietary software. Some of the most common are the MIT, BSD and Apache 2.0 licenses. These are suitable for libraries that you want everyone to be able to use easily.
```
  ____
 /    \
|  ()  |
 \____/
  ||||
  ||||
 ------
```

**Copyleft Licenses:** These licenses require that derivative works be licensed under the same license as the original. The GPL license is the best example of a copyleft license, and is best for complete applications. There is even a derivative license of the GPL called the Affero GPL which says that anyone who just accesses the code can ask for the source code changes.

So what should you do when releasing software? Think about your goal for the software and whether it should be proprietary or open source. If you chose open source, use an OSI approved license that fits your goals. And if you’re just using open source software, make sure you understand and respect the license requirements.

**Article 4: Operational Security: A Constant Vigilance**

In the digital world, complacency is the enemy of security. Security is an ongoing process, and what was secure yesterday might be compromised tomorrow. We need to constantly learn and adapt!

Operational security means taking a hard look at how you build, deploy, and maintain your applications and systems. It’s not just about firewalls; it's about the whole ecosystem of your tech.
```
    _   _
   /  \ /  \
  |    X    |
  \  / \  /
   \/   \/
  ------
```

Here are a few key aspects of operational security:

*   **Network Security:** Use a strong firewall, and do not open unnecessary ports to the world.
*   **Password Security:** Understand entropy and password strength, and use a password manager instead of relying on your memory. You should use multi-factor authentication (MFA) where possible.
*   **Ongoing Learning:** Never stop learning about new security threats and practices.

A real world security issue was demonstrated when a developer opened port 25 to the entire world, allowing the mail server to be used to spam the internet.
Another security issue occurred when a developer entered `Passw0rd!` as a password and assumed they were safe because the system rated the password as “strong”.

Remember that security is everyone’s responsibility. As developers, we are increasingly becoming involved in operations, making it our responsibility as well.

## Accept Testing with Codeception: Automating Your QA Process**

Acceptance testing: It's the superhero of testing for those legacy applications! It approaches your application from outside the source code, allowing you to greatly increase test coverage without touching a single line of your application's code.

Acceptance testing verifies that an application behaves exactly as expected, often using a web browser to simulate user interactions. This means writing tests that will be acted out by a user using a web browser, like logging in and performing specific tasks.

While slower than unit tests, acceptance tests give confidence that the application works as designed using a real browser. Unit tests test isolated methods, and feature tests test methods working together, but acceptance tests exercise the entire application.

Codeception is a PHP testing framework to help you write acceptance tests. Codeception needs a web driver, like Selenium. To use Selenium, you will need to install the correct web drivers.  You can start the Selenium server with the command: `selenium-standalone start`.

Let’s see how a test is written. This is a basic test to check if a user can login to a website, as seen in Listing 3:

```php
<?php

class LoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToLogin(AcceptanceTester $I)
    {
        $I->wantTo('sign in');
        $I->amOnPage('/login');
        $I->see(trans('auth/general.login_prompt'));
        $I->seeElement('input[type=text]');
        $I->seeElement('input[type=password]');
    }
}
```

*  **`_before()`**: This method can be used to do any setup required for the tests.
*  **`tryToLogin()`**: This is the actual test. It describes the action to be performed, then checks that certain elements are visible on the page.

To continue the test, you need to fill the form and click the login button:

```php
  public function tryToLogin(AcceptanceTester $I)
    {
        $I->wantTo('sign in');
        $I->amOnPage('/login');
        $I->see(trans('auth/general.login_prompt'));
        $I->seeElement('input[type=text]');
        $I->seeElement('input[type=password]');
        $I->fillField('username', 'snipe');
        $I->fillField('password', 'password');
        $I->click('Login');
        $I->see('Success: You have successfully logged in.');
    }
```
        
  
To run the test, use the command `codecept run acceptance`.

If a test fails, Codeception gives you the HTML of the page and a screenshot, allowing you to debug it quickly.  Codeception gives you the ability to test the entire stack from UI elements, database access, and form validations, making the test coverage very high. Codeception was created by Michael Bodnarchuk in Kyiv, Ukraine.

## When the New Requirement Arrives: Refactoring with Confidence**

So, a new requirement lands on your desk. Does this mean jamming it in wherever it fits, making your code harder to work with, or is there a better way? Let’s talk about preparatory refactoring!

Preparatory refactoring, as Martin Fowler calls it, means making the code easy to change before actually making the change. When adding a new feature, we want to do it in a way that does not impact existing code. The Open-Closed principle suggests that code should be open for extension, but closed for modification. This means adding new code without changing the existing code.

In the context of a Domain Driven Design, let’s say you are working on an Athlete Season Registration workflow. A new requirement is to send an email confirmation when an athlete completes registration. The email addresses are stored in the same database tables used by our current “role-based lookups” class. Our first thought might be to add a new method to that class to fetch the email addresses, but that would make the class even more bloated.

We should be aware of bloated classes, where a subset of the methods are being used by the clients. The client using only some methods is a good clue to split the class up.

Here are the steps to take before we implement the actual functionality:
*   Define the public methods needed for the new feature.
*   Create an interface describing those methods.
*   Create an empty class implementing that interface and extending the existing class.
*   All tests should still pass.
*   Develop the new feature using the new class.

The interface, which we extract, is only used by the tests, which decouples the structure of the tests from the structure of the application. Extracting the interface lets the tests and production code to evolve separately. This ensures the tests and the production code to be as separate as possible. The tests can evolve without impacting production code, while the production code can evolve without breaking existing tests.

For example, if the `ILookupRecipientEmail` interface includes the methods that extract email addresses, a class like the following can be implemented:
```php
<?php

declare(strict_types=1);

namespace App\BoundedContexts\AMS\DomainModel\Interfaces\RoleBasedLookupInterfaces;

interface ILookupRecipientEmail
{
   /** covered */
    public function alternateGuardianEmail(): string;

    /** covered */
    public function alternateGuardianExists(): bool;

    /** covered */
    public function isEmailAddressRequired(): bool;

    /** covered */
    public function leagueRequiresGuardian(): bool;

    /** covered */
    public function primaryGuardianEmail(): string;

    /** covered */
    public function primaryGuardianExists(): bool;

    /** covered */
    public function userEmail(): string;

    /** covered */
    public function userIsUnder18(): bool;
}
```
This allows you to implement this interface in a new class that extends the existing `role-based lookups` class, or even create a completely separate class.

By doing this preparatory refactoring, we can add new features without changing the existing code or breaking existing tests. With all the unit tests in place, it's easier to take the next step and refactor more of the code.

## Drupal 9 - Introduction and Installation**

Let’s explore the world of Drupal, one of the most popular Open Source content management systems (CMS)! In this article, we’ll cover the installation process using a command line approach as well as a web based approach.

To install Drupal, we need a computer with Linux Ubuntu, PHP, Apache or Nginx, and a database like MySQL or MariaDB.  Composer is used to manage Drupal's dependencies. You can install Composer like this:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
```

Once Composer is installed, you can install Drupal using the command:
```bash
composer create-project drupal/recommended-project my-drupal-site
```

After the project is created, you can go through the installation steps using your web browser, visiting the newly created directory. Drupal will ask you for things like database credentials and site configuration values.
```
     ____
    /    \
   |  O  |
   \____/
    |  |
    ----
```

You can also install Drupal via the command line, with Drush.  To install Drush, run:
```bash
composer require drush/drush
```

To install Drupal using the command line, use the `drush site:install` command.  This will install the default Drupal profile without any arguments, and ask you for database credentials.

You can also add contributed modules and themes using Composer:
```bash
composer require drupal/module_name
```

If you are using drupal/core and not drupal/core-recommended, use the following to update the core:
```bash
composer update drupal/core --with-dependencies
```

Then run these commands after updating the core:
```bash
drush updatedb
drush cache:rebuild
```

Composer is very important for the core installation and the modules and themes because it takes care of the dependencies for you. The `composer --help list` command will give you a list of all the commands.
  
## Making Some Change: A Dive into Currency Calculations**

Let’s explore a challenge that seems simple at first but quickly reveals the quirks of computer arithmetic: making change! We'll convert an amount of money into its equivalent denominations of bills and coins, while trying to minimise the number of bills and coins.

The “naive” approach involves dividing the amount by the denomination and using the `floor()` function.
```php
<?php
$amount = 267.51;

echo "Starting amount: {$amount}" . PHP_EOL;

if ($amount >= 100.00) {
    $hundreds = $amount / 100.00;
    echo floor($hundreds) . " $100 bill(s)" . PHP_EOL;
    $amount = $amount - (floor($hundreds) * 100.00);
}
//... and so on
?>
```

However, floating-point math isn’t always precise.  This can lead to rounding errors that throw off the calculations. For example, the remaining amount may be slightly less than one cent, causing issues when dividing by the value of a penny.

The fix is to convert the amount to an integer representing the smallest unit of currency. In the case of US dollars, convert it to pennies:
```php
$amount = (int) ($amount * 100);
```
Then, you can use the modulus operator (`%`) instead of floats. The values of all denominations should be converted to pennies for consistency.

To make the code cleaner, you should remove the repeated code and add helper functions for calculations.
```php
<?php
$amount = 267.51;

$amount = (int) floor($amount * 100);

function div(int $dividend, int $divisor): array {
    return [
        (int) ($dividend / $divisor),
        $dividend % $divisor
    ];
}

$denominations = [
    [10000, '$100', 'bill', 'bills'],
    [5000, '$50', 'bill', 'bills'],
    [2000, '$20', 'bill', 'bills'],
    [1000, '$10', 'bill', 'bills'],
    [500, '$5', 'bill', 'bills'],
    [100, '$1', 'bill', 'bills'],
    [25, '', 'quarter', 'quarters'],
    [10, '', 'nickel', 'nickels'],
    [5, '', 'dime', 'dimes'],
    [1, '', 'penny', 'pennies'],
];

echo "Starting amount: {$amount}" . PHP_EOL;

foreach ($denominations as $d) {
    if ($amount >= $d) {
        [$num, $amount] = div($amount, $d);
        echo $num . ' ' . $d . ' '
            . ($num > 1 ? $d : $d ) . PHP_EOL;
    }
}

echo "Remaining amount: ". $amount;
```
This approach not only gives a more precise output, but it also makes your code easier to read and more flexible. If you need to perform sensitive calculations, consider the `bc_math` library which is built into PHP. And remember, when working with money, use a Value Object to represent the currency to perform calculations.  The MoneyPHP library is a good option.

## New and Noteworthy: PHP, Symfony and More**

Stay up-to-date with the latest developments in the PHP world! We'll cover PHP releases, news from the Symfony community, and cool tools that will make us all better developers!

Let’s dive in!

**PHP Releases**

*   **PHP 8.1.4 and 8.0.17:** These are the latest bug fix releases from PHP, and as always, they include a variety of fixes and improvements. Make sure to update your PHP versions regularly.
    ```
      ___
     /   \
    | PHP |
     \___/
    ```

**Symfony News**

*   **SymfonyLive Paris 2022:**  The French-speaking event of Symfony is back! The conference features keynotes and talks from the Symfony community.
*   **Platform.sh is the official Symfony PaaS:** SymfonyCloud is a layer on top of Platform.sh, making it easier to manage Symfony projects.

**Other News**

*   **PhpStorm 2022.1 EAP #6:** JetBrains has released another early access preview of PhpStorm. This release includes new features and improvements.
*   **JetBrains' statement on Ukraine:** JetBrains has condemned the attacks of the Russian government, and is standing with Ukraine.
*   **PHP RFC: Arbitrary string interpolation:** PHP has added a feature to include more types of string interpolation, other than `$foo` or `{$foo}`.
*  **Setup PHP in GitHub Actions**: A great way to setup PHP, code coverage and composer is now available in GitHub actions.
*  **Generics in depth:** A video series is available to explain how to use generics in PHP.
*  **DrupalCon 2022:** If you are a Drupal developer, make sure to check out the latest DrupalCon.

**Article 10: PSR 12 Extended Coding Style Standard: Formatting for Collaboration**

Ah, code style! The never-ending debate between tabs and spaces! But seriously, having a clear code style is important for collaboration.  Let’s talk about PSR-12!

PSR-12 builds on PSR-1 and defines many coding style rules, many of which are supported by modern IDEs. PSR-12 replaces PSR-2: Coding Style Guide, because PHP has changed quite a bit since it was written, and using it could result in a lot of work. Instead, PSR-12 is here to guide us.

Let's look at some of the most important rules in PSR-12:
```
       _
    __| |__
   /      \
  |  PSR  |
  \______/
```
*   **Line Endings:**  All files MUST end with a single blank line with a line feed (LF). This can cause problems with version control when a developer has to add a new line to a file, or if the file does not end with an LF, diff tools may have trouble with it.
*   **Whitespace:** Code MUST use four spaces for each indent level, and MUST NOT use tabs. Although modern IDEs handle tabs and spaces, version control software like Git, treats them differently and might create a cognitive friction when developers are reviewing the code.
*   **No trailing whitespace:** Make sure there is no whitespace at the end of the line, as it might cause issues if a user copies the code and an extra space gets copied.

By agreeing on a style guide for new members, it can prevent wasting time discussing it again. This consistency can be quite important for collaboration, and help in communication with the team.
  
**Article 11: Tech is Taking Sides: The Tech Industry in Times of Conflict**

In times of conflict, most industries try to remain neutral, but the tech. industry is still a very important part of the world. The tech industry is a very complex and complex industry, and it is very hard to keep up with the ever-changing technology.