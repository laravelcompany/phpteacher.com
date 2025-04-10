---
title: PHP Teacher magazine - December 2022
publishDate: 2023-04-19 00:00 
description:  'This issue includes articles about finite-state machines,  Vim plugins, single-page applications, cybersecurity, PHP releases, creating a webserver, application events, number formatting, and Laracon'
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - december 
  - 2022
---

## Introduction to the Monthly Issue


Welcome to another exciting edition of "The PHP Teacher Magazine"! This month, we delve into a wide array of topics essential for any PHP developer, from mastering the abstract syntax tree (AST) to understanding the nuances of value objects, and even considering the crucial aspects of career growth and content ownership.

In this issue, we'll explore the power of the **AST and how it can be used to create powerful code analysis and modification tools**. We’ll move on to discuss value objects, emphasizing how they can lead to more robust, maintainable, and testable code. We will also look into the essential soft skills, with a focus on career progression and understanding your place in the developer ecosystem. We will explore the concept of technical debt, and how to manage it.

We'll also dive into the practicalities of creating your own blog and ensuring you **own your content**, alongside explorations of PSR-13, which defines interfaces for links. Finally, we will provide practical approaches for problem solving via sticker swapping and implement invariants with a transactional boundary with exception reporting.

This issue is packed with in-depth articles, code examples, and practical advice to elevate your PHP development skills. Whether you’re a seasoned professional or just starting your journey, this edition has something for everyone. Let’s dive in and enhance our knowledge together!

---


# Harnessing the Power of the Abstract Syntax Tree (AST)**

```
     /\_/\
    ( o.o )
    > ^ <   AST Power Unleashed
```

**Introduction:**
In this article, we will explore the power of Abstract Syntax Trees, or ASTs, and how they can be used to analyse and modify code. Abstract Syntax Trees are tree-like representations of the structure of source code. By understanding how ASTs work, developers can create powerful tools for code analysis, automated refactoring, and much more. This article is based on the last of a three-part series by Tomas Votruba.

**Core Concepts:**
An AST is a hierarchical structure that represents the syntactic structure of code. It is created by parsing the source code using an AST parser. Each element of the AST is called a node, representing different aspects of the code such as statements, expressions, and variables. Node visitors allow developers to traverse and modify this structure.

**The Manual Approach vs. The Node Visitor Approach:**
The article initially explores a manual approach to validating a "city" field in a JSON response, using nested `foreach` loops. This method is hard to maintain, specific to the data structure and prone to errors. The Node Visitor approach, using AST, provides a generic, reusable and maintainable way to perform this task. The first step is to write a parser, that converts a code to a generic tree of node objects.

**Creating a Parser:**
The first step in the Node Visitor approach is to create a parser that turns the JSON input into a structured, traversable form. A simple JSON parser is developed in the article, which converts JSON data into nested arrays.

```php
namespace PhpArch\Ast;

final class JsonParser
{
    public function parse(string $inputResponse): array
    {
        return json_decode($inputContents, true); //Basic parsing
        // or including validation
        // return json_decode($inputContents, true, 512, JSON_THROW_ON_ERROR);
    }
}
```

The parser is enhanced to convert the array into a tree of Node objects. This involves creating classes for different types of nodes, such as `ItemNode` for simple key-value pairs and `ChildAwareItemNode` for nested arrays.

```php
namespace PhpArch\Ast\Node;

final class ItemNode extends AbstractJsonNode
{
    public function __construct(
        public string|int $name,
        public string $value
    ) {
    }
}

namespace PhpArch\Ast\Node;
final class ChildAwareItemNode extends AbstractJsonNode
{
     /**
     * @param ItemNode[] $subNodes
     */
    public function __construct(
        public string|int $name,
        public array $subNodes
    ) {
    }
}
```

**Node Traversal:**
A Node Traverser is then introduced to recursively visit all nodes in the AST. The traverser uses the concept of recursion to process any number of nested elements.

```php
namespace PhpArch\Ast;

use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ChildAwareItemNode;

final class JsonNodeTraverser
{
    /**
     * @param AbstractJsonNode[] $jsonNodes
     */
    public function traverse(array $jsonNodes): void
    {
        foreach ($jsonNodes as $jsonNode) {
            // @todo add node visitors
            // traverse all the children
            if ($jsonNode instanceof ChildAwareItemNode) {
               // @todo add node visitors
                $this->traverse($jsonNode->subNodes);
            }
        }
    }
}
```

**Node Visitors:**
Node visitors act as the mechanism to perform actual operations on the AST. A `JsonNodeVisitorInterface` is defined, and specific visitor classes, such as `ValidateCityJsonNodeVisitor`, are created to perform the validation.

```php
namespace PhpArch\Ast\Contract;

use PhpArch\Ast\Node\AbstractJsonNode;

interface JsonNodeVisitorInterface
{
    public function enterNode(AbstractJsonNode $jsonNode);
}

namespace PhpArch\Ast\JsonNodeVisitor;

use PhpArch\Ast\Contract\JsonNodeVisitorInterface;
use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ItemNode;

final class ValidateCityJsonNodeVisitor implements JsonNodeVisitorInterface
{
    public function enterNode(AbstractJsonNode $jsonNode)
    {
        if (! $jsonNode instanceof ItemNode) {
            return;
        }

        if ($jsonNode->name !== 'city') {
            return;
        }

        validate_city_name($jsonNode->value); // Validation Logic
    }
}
```

**Putting It All Together:**
The article shows how to combine the parser, traverser, and visitor to validate data in a flexible and maintainable way. The final step involves using the AST to perform modifications, such as correcting city names.

**Code Example (Full example not possible due to length):**

```php
use PhpArch\Ast\JsonParser;
use PhpArch\Ast\JsonNodeTraverser;
use PhpArch\Ast\JsonNodeVisitor\ValidateCityJsonNodeVisitor;

$inputResponse = '{ "coffees": [ { "name": "Coffee Atrium", "city": "New Yerk" } ] }';
$jsonParser = new JsonParser();
$jsonNodes = $jsonParser->parse($inputResponse);
$jsonNodeTraverser = new JsonNodeTraverser();
$jsonNodeVisitor = new ValidateCityJsonNodeVisitor();
$jsonNodeTraverser->addVisitor($jsonNodeVisitor);
$jsonNodeTraverser->traverse($jsonNodes);
// After traversal, 'New Yerk' will be validated
```

**References:**
*   [Tomas Votruba's Blog](https://phpa.me/real-world-ast)
*   [Nikic PHP Parser](https://github.com/nikic/PHP-Parser)
*  [PHPStan](https://phpstan.org/)

**Postface:**

This article provided an introduction into the complex topic of abstract syntax tree. AST tools help to perform a variety of functions on your codebase, including but not limited to code analysis, automated refactoring, and more.

*   **Index of Technology Terms:**
    *   Abstract Syntax Tree (AST)
    *   AST Parser
    *   Node
    *   Node Traverser
    *   Node Visitor
    *   JSON
    *   Recursion
    *   Object-Oriented Programming

---

# Bring Value To Your Code - The Power of Value Objects**

```
   _.-._
  |  _  |  Value Objects: Solid Foundations
  | | | |
  '-'-'-'
```

**Introduction:**
In the world of software development, Value Objects play a critical role in creating robust and maintainable systems. This article delves into the benefits of using Value Objects, emphasizing how they encapsulate data and associated behavior, leading to more expressive and reliable code. Based on the work of Dmitri Goosens, this article offers a close look at the application of Value Objects in PHP.

**Core Concepts:**
A Value Object is an object that represents a domain concept by its attributes alone, and has no identity. The main benefits of using Value Objects are that they are immutable, always valid, and they are great to be used in caching mechanism. In addition to storing values, Value Objects can encapsulate the domain logic that is associated with the value.

**Value Objects Are Valid Objects:**
Value objects, when implemented correctly, are always valid, this is a major advantage over using primitive types. The article demonstrates this by using an example of an email address class where validation rules are applied in the constructor, throwing an exception if the value is not valid.

```php
class PhpArchEmailAddress
{
    public function __construct(public readonly string $value)
    {
        $regex = '.*@phparch\.com';
        if (preg_match("/$regex/m", $value) !== 1) {
           $txt = '<%s> is not a valid email address.';
           $message = sprintf($txt, $value);
           throw new InvalidPhpArchEmailAddress($message);
        }
        //More validations here
    }
}
```

**Specialized & Rich Models:**
Value Objects allow developers to create more specialised and richer models by encapsulating complex business logic within the objects. The article provides an example of replacing primitive types in a Price class with `Money`, `VatRate`, and `Discount` Value Objects, and implements methods associated with them.

```php
class Price
{
    public function __construct(
        public readonly Money $price,
        public readonly VatRate $vatRate,
        public readonly Discount $discount,
    ) {
    }
     public function total(): Money
    {
        return $this->vatRate->applyVat(
            $this->discount->applyDiscount($this->price)
        );
    }
}

class VatRate
{
    public function __construct(
        public readonly float $rate,
    ) {
    }

    public function calculateVat(Money $money): Money
    {
        return $money->multiply($this->rate);
    }

    public function applyVat(Money $money): Money
    {
        return $money->add($this->calculateVat($money));
    }
}
interface Discount
{
    public function asRate(Money $money): float;
    public function asMoney(Money $money): Money;
    public function applyDiscount(Money $money): Money;
}

class RateDiscount implements Discount
{
    public function __construct(
        public readonly float $rate,
    ) {
    }

    public function asRate(Money $money): float
    {
        return $this->rate;
    }

    public function asMoney(Money $money): Money
    {
        return $money->multiply($this->rate);
    }

    public function applyDiscount(Money $money): Money
    {
        return $money->subtract($this->asMoney($money));
    }
}
```

**Testing:**
Value objects help to write simpler tests, as there is no need to mock the objects. Value objects are always valid and encapsulate the business logic.

**Caching:**
Due to their immutability, Value Objects are excellent candidates for caching. They can be cached for long periods, improving performance.

**Storing Value Objects in the Database:**
The article also discusses various ways to store value objects in a database, from storing them along with the entity they encapsulate, to denormalizing and storing the value object properties directly, or by serializing them into JSON strings. The article also suggests avoiding the use of database enums due to their limitations.

**Code Example:**

```php
$email = new PhpArchEmailAddress("test@phparch.com"); //Valid email creation
// $email = new PhpArchEmailAddress("test@gmail.com"); // Invalid email will throw Exception

$price = new Money(1000, new Currency('USD'));
$vatRate = new VatRate(0.2);
$discount = new RateDiscount(0.1);
$priceObject = new Price($price, $vatRate, $discount);

echo "Total Price: " . $priceObject->total()->getAmount();
```

**References:**
*  [Money for PHP Library](https://www.moneyphp.org/)
*  [Chris Komlenic Blog Post on MySQL Enums](https://phpa.me/mysqls-enum-data-evil)
*  [Access Private Class](https://phpa.me/access-private-class)

**Postface:**
Value Objects provide a way to organize code and encapsulate domain logic, resulting in code that is more expressive, easier to test and maintain. This article demonstrates the value of using Value Objects and provides practical approaches for incorporating them in your projects.

*   **Index of Technology Terms:**
    *   Value Object
    *   Immutability
    *   Domain Logic
    *   Constructor Validation
    *   Caching
    *   Denormalization
    *   Serialization
     *  Database Enums

---

# Refactoring Yourself - Navigating the Stages of a Developer Career**

```
    (  )
  (    )   Career Growth: Levels and Leaps
   \  /
    \/
```

**Introduction:**
This article explores the different stages of a developer's career, drawing from Chris Tankersley's insightful perspective on the distinctions between an apprentice, journeyman, and master craftsman. The goal of this article is to provide the information needed to evaluate your current position in the developer career journey and provide the necessary guidance to continue improving.

**Core Concepts:**
The article introduces different developer career stages, each with its own set of responsibilities and learning goals. These stages are: Apprentice, Journeyman, and Master Craftsman. The article also discusses the Dunning-Kruger effect and Imposter Syndrome, as mental pitfalls during career development.

**Apprentice Developer:**
An apprentice developer is someone new to the field, needing clear tasks with explicit goals. An apprentice should be focused on asking questions and learning the ropes. The focus should be on understanding the fundamentals and reproducing solutions provided by others rather than creating novel solutions.

**Journeyman Developer:**
A Journeyman developer has experience, can handle more complex tasks, and requires less oversight. They begin to notice patterns and apply previous solutions to new problems. At this stage, they are still learning new things but also finding new ways to use the existing skillset. They are more confident in the tools they learned and use them for new solutions.

**Master Craftsman:**
A Master Craftsman not only has strong programming and problem-solving skills, but is also involved in product design and decision making. They often take on leadership and mentoring roles. They act as the glue in the organization, working to ensure the project's success. At this stage, a developer is not only adding new tools to their toolbox, but also improving the ability to work with people and the organization.

**Dunning-Kruger Effect and Imposter Syndrome:**
The article highlights the Dunning-Kruger effect, a cognitive bias where individuals with low competence overestimate their abilities. It also discusses Imposter Syndrome, a feeling that one lacks useful skills when they are more than capable.

**Continuous Learning and Mentorship:**
The article emphasizes the need for continuous learning and mentorship throughout the career journey. The developer needs to be both a mentee and mentor. Mentors help guide career growth and provide valuable guidance and support.

**Code Example:**
This article does not include code examples, since the main content is related to career growth and soft skills. However, here is an example of how a Journeyman might approach structuring a plugin:

```php
<?php
/**
 * Plugin Name: Example Plugin
 * Description: Example plugin for demonstration purposes
 */

 // Define constants
 define('EXAMPLE_PLUGIN_DIR', plugin_dir_path(__FILE__));

 //Include necessary files
 require_once EXAMPLE_PLUGIN_DIR . 'includes/functions.php';
 require_once EXAMPLE_PLUGIN_DIR . 'includes/classes/ExampleClass.php';

 // Register hooks
 add_action('wp_enqueue_scripts', 'example_enqueue_scripts');

 // Initiate classes
 $example_class = new ExampleClass();
 add_action('init', [$example_class, 'init']);
```

**References:**
* [Wikipedia Article on Imposter Syndrome](https://phpa.me/wikip-impostor-syndrome)

**Postface:**
Understanding the different stages of a developer's career is essential for personal and professional growth. This article has provided a framework for developers to assess where they are in their journey and to guide their continuing career development.

*   **Index of Technology Terms:**
    *   Apprentice Developer
    *   Journeyman Developer
    *  Master Craftsman Developer
    *   Dunning-Kruger Effect
    *   Imposter Syndrome
    *   Mentorship
    *   Career Growth

---

#  Sticker Swapping - A Practical Exercise in Algorithm Design**

```
   (  )
  /  \  Sticker Swap: Array Algorithms
 /____\
```

**Introduction:**
This article, inspired by the familiar practice of sticker swapping for the FIFA World Cup, illustrates practical problem-solving techniques in PHP. Drawing from Oscar Merida’s work, it demonstrates how to approach data manipulation and array comparison challenges. This article aims to provide practical ways to solve the problem of finding common elements from multiple arrays and strings.

**Core Concepts:**
The article begins with the problem of comparing two lists of stickers: one a list of needed stickers and another a list of duplicate stickers. The goal is to find the stickers that are present in both lists. The challenge involves handling various input formats, converting them into a usable structure, and then performing the comparison.

**Data Cleanup and Transformation:**
The first step in the solution is to clean up the input data. The article introduces the `cleanInput()` function, which standardizes input by converting it to uppercase, replacing commas with spaces, and removing extra whitespace using regular expressions.

```php
function cleanInput(string $in): string
{
    $out = strtoupper($in);
    $out = str_replace(',', ' ', $out);
    return preg_replace('/\s+/', ' ', $out);
}
```

Next, the cleaned data needs to be transformed into an array of sticker strings. A regular expression is used to match a country code followed by associated numbers, which are then combined into an array of sticker strings.

```php
function buildArray(string $input): array
{
    preg_match_all('/([A-Z]+)\s(+)/', $input, $match);
    $result = [];
    foreach ($match as $i => $m) {
        $country = $match[$i];
        $numbers = explode(' ', trim($match[$i]));
        foreach ($numbers as $number) {
            $result[] = $country . ' ' . $number;
        }
    }
    sort($result, SORT_NATURAL);
    return $result;
}
```

**Combining Cleanup and Array Building:**
The `cleanInput()` and `buildArray()` functions are combined into a single function called `toStickerList()`, which performs all the necessary data cleanup and array building logic.

```php
function toStickerList(string $input): array
{
    // clean input
    $input = strtoupper($input);
    $input = str_replace(
        ',', ' ', $input
    );
    $input = preg_replace(
        '/\s+/', ' ', $input
    );
    // make country+number array
    preg_match_all(
        '/([A-Z]+)\s(+)/',
        $input,
        $match
    );
    $result = [];
    foreach ($match as $i => $m) {
        $country = $match[$i];
        $numbers = explode(
            ' ',
            trim($match[$i])
        );
        foreach ($numbers as $number) {
            $result[] = $country . ' ' . $number;
        }
    }
    sort($result, SORT_NATURAL);
    return $result;
}
```

**Finding Common Elements:**
Finally, the PHP `array_intersect()` function is used to find the common elements between the two arrays, giving the list of stickers that can be swapped.

```php
$need = <<<'EOM'
FWC 2, 3, 7 9, 13, qat 5, qat 6 10 qat 11, 13, 14, qat 15,
qat 18, 19, ECU 1 2 3, 5, 6, 10, 13 19, SEN 7, 8, sen 11,
sen 19, NED 2, 3, 13, ENG 7, ENG 10, ENG 19,
IRN 2, 3, 5, 7, 10 12 17 19, USA 2, USA 12, USA 14 15 16,
USA 17, USA 19, WAL 2, WAL 5, WAL 16, ARG 4 8 9 10
Arg 11, Arg 14, Arg 18, ECU 14 15, ECU 18 Ned 17
EOM;

$dupes = <<<'EOM'
00, FWC 1,4, FWC 12, FWC 17, QAT 1, QAT 3,4 ECU 18 19
GER 2,8, 10, 13,14, 15, JPN 3, JPN 10, JPN 14 15 19,
JPN 20, BEL 3 9 12 14 15 16 19, Can 5,7,17,19, USA 4,
MAR 4, MAR 14, MAR 16, SEN 11, SEN 19, NED 2,3,13,ger 20,
CRO 6, CRO 18, BRA 1, BRA 8, BRA 13, SRB 5,10 14, SRB 16,
SRB 18, 20, SUI 4, SUI 6, SUI 9, SUI 11, SUI 15, SUI 20,
CMR 7 9,11,13,15,16, POR 3, 5, 8, POR 10,11,12, WAL 16
por 15,16, 18, Wal 2,5, USA 15,16,17
EOM;

$need = toStickerList($need);
$dupes = toStickerList($dupes);

$swap = array_intersect($need, $dupes);
echo "You can swap the following: " . implode(', ', $swap);
```

**Code Example:**
```php
$need = "FWC 2, 3, 7 9, 13, QAT 5, QAT 6 10";
$dupes = "FWC 1,4, FWC 12, QAT 1, QAT 3, 4";

$needList = toStickerList($need);
$dupesList = toStickerList($dupes);

$swap = array_intersect($needList, $dupesList);
print_r($swap); //Output: Array ( => FWC 4 )
```

**References:**
* [Regular Expression 101](https://regex101.com/)
* [PHP Array Intersect](https://php.net/array_intersect)

**Postface:**
This article demonstrated how real-world problems can be translated into practical code solutions. By combining data cleanup, regular expressions, and PHP's array functions, we were able to find common elements in two lists in an efficient manner.

*   **Index of Technology Terms:**
    *   Regular Expressions
    *   Array Intersection
    *   String Manipulation
    *   Data Transformation
    *   Algorithm Design

---

# PSR-13: Link Definition Interfaces - Managing Links Effectively**

```
   _    _
  | |  | |   Link Interface: Connecting Resources
  |_|  |_|
```

**Introduction:**
This article discusses PSR-13, which provides a set of interfaces for defining and managing links in web applications. Based on the work of Frank Wallen, we delve into how PSR-13 can help developers handle links more effectively, addressing both anchor tags and link tags. This standardization helps avoid typos and errors, and also improves accessibility for screen readers.

**Core Concepts:**
PSR-13 defines interfaces for handling links, offering methods for serializing and providing links. The two basic types of links are the **link tag** and the **anchor tag**. An anchor tag redirects the user to another resource, whereas the link tag references a resource. Link tags are used to load assets and to identify resources, such as RSS feeds.

**The `LinkInterface`:**
At minimum, a Link consists of a URI and a relationship defining how it relates to the source. The `Psr\Link\LinkInterface` defines how a link object should behave. It includes methods to get the link's URI (`getHref()`), its relationship (`getRels()`), attributes (`getAttributes()`), and whether the URI is templated (`isTemplated()`).

```php
namespace Psr\Link;

interface LinkInterface
{
    public function getHref();
    public function isTemplated();
    public function getRels();
    public function getAttributes();
}
```

**`EvolvableLinkInterface`:**
The `EvolvableLinkInterface` extends `LinkInterface` and allows for the modification of link objects. It includes methods like `withHref()`, `withRel()`, `withoutRel()`, `withAttribute()`, and `withoutAttribute()`. These methods return a new modified instance of the link object, since the link object itself is immutable.

```php
namespace Psr\Link;

interface EvolvableLinkInterface extends LinkInterface
{
    public function withHref($href);
    public function withRel($rel);
    public function withoutRel($rel);
    public function withAttribute($attribute, $value);
    public function withoutAttribute($attribute);
}
```

**`LinkProviderInterface` and `EvolvableLinkProviderInterface`:**
The `LinkProviderInterface` defines an interface for objects that provide lists of links. It includes methods like `getLinks()` to get all links and `getLinksByRel()` to filter links by relationship. The `EvolvableLinkProviderInterface` extends the previous one and provides methods to modify the list of links using `withLink()` and `withoutLink()`.

```php
namespace Psr\Link;

interface LinkProviderInterface
{
    public function getLinks();
    public function getLinksByRel($rel);
}
namespace Psr\Link;

interface EvolvableLinkProviderInterface
    extends LinkProviderInterface
{
    public function withLink(LinkInterface $link);
    public function withoutLink(LinkInterface $link);
}

```

**Handling Links:**
The article discusses the importance of standardizing how links are handled to improve the maintainability and accessibility of web applications. PSR-13 provides a standard for working with links and their related information.

**Code Example:**
```php
use Psr\Link\LinkInterface;
use Psr\Link\EvolvableLinkInterface;

class MyLink implements EvolvableLinkInterface
{
    private string $href;
    private array $rels = [];
    private array $attributes = [];
    private bool $templated = false;

    public function __construct(string $href) {
        $this->href = $href;
    }
    public function getHref(): string {return $this->href;}
    public function isTemplated(): bool {return $this->templated;}
    public function getRels(): array {return $this->rels;}
    public function getAttributes(): array {return $this->attributes;}
    public function withHref($href): EvolvableLinkInterface
    {
         $new = clone $this;
         $new->href = $href;
         return $new;
    }
     public function withRel($rel): EvolvableLinkInterface
    {
         $new = clone $this;
         $new->rels[] = $rel;
         return $new;
    }
    public function withoutRel($rel): EvolvableLinkInterface
    {
         $new = clone $this;
         $new->rels = array_diff($new->rels, [$rel]);
         return $new;
    }
    public function withAttribute($attribute, $value): EvolvableLinkInterface
    {
        $new = clone $this;
         $new->attributes[$attribute] = $value;
         return $new;
    }
    public function withoutAttribute($attribute): EvolvableLinkInterface
    {
        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }
}

$link = new MyLink("https://example.com/page");
$link = $link->withRel('stylesheet')->withAttribute('type','text/css');

echo "Href: " . $link->getHref() . "\n";
echo "Rels: " . implode(', ', $link->getRels()) . "\n";
print_r( $link->getAttributes());
```

**References:**
* [PSR-13](https://www.php-fig.org/psr/psr-13/)
* [RFC 5988](https://www.rfc-editor.org/rfc/rfc5988)
*  [PHP-FIG/Link on GitHub](https://github.com/php-fig/link)
*   [IANA Link Relations](https://phpa.me/iana-link-relations)
*   [RFC 6570 on URI Templating](https://www.rfc-editor.org/rfc/rfc6570)

**Postface:**
This article has presented an overview of PSR-13, a set of PHP interfaces that help developers manage links. By implementing these interfaces developers can handle links more efficiently, as well as provide benefits in terms of accessibility.

*   **Index of Technology Terms:**
    *   PSR-13
    *   Link Tag
    *   Anchor Tag
    *   URI
    *   Link Relation
    *   Link Attributes
    *   Templated URI

---
# Get a Blog - Owning Your Content and Sharing Your Knowledge**

```
   /\\   
  /  \\    Blog Post: Share Your Thoughts
 /____\\
```

**Introduction:**
This article explores the importance of owning your content and creating your own blog, drawing on Joe Ferguson’s insights into the value of sharing knowledge and having control over your online presence. This article provides a pragmatic approach to setting up a blog and controlling the content you create.

**Core Concepts:**
The article emphasizes the importance of owning your content, which means controlling where your content is stored and how it is displayed. The article encourages the use of platforms that allow for complete control over the content. Having control means having the freedom to move the content to a different hosting provider, domain, or technology if needed.

**Starting from Scratch:**
The first step towards owning your blog is registering a domain name with a registrar and selecting a web host for the blog. The article suggests following the path of least resistance and using a boring setup to make maintenance simple.
The article suggests to decouple the content from any hosting provider or service, and not rely on any platform to be in control of your content.

**Choosing a Hosting Solution:**
The article discusses choosing the right hosting for a blog. For WordPress, a host within the WordPress ecosystem is recommended. However, it suggests that a simple, boring, static site generator is easier to maintain, and easier to keep up to date. A static site generator is a solution which does not need any server side code, and simply translates static files into HTML.

**Connecting Domain Name and Hosting:**
Once you have a hosting account, the domain name needs to be connected to the hosting account. This is done by changing the DNS servers at the Registrar. This is crucial to make your blog accessible using your domain name.

**Static Site Generators: Sculpin and Jigsaw:**
The article introduces two PHP-based static site generators: **Sculpin** and **Jigsaw**. Sculpin uses the Twig template engine, and Jigsaw uses the Laravel Blade template engine. The article provides basic instructions to set up both platforms and emphasizes their ease of use. These platforms allow developers to focus on the content, rather than dealing with complex server configurations.

```
$ composer create-project sculpin/blog-skeleton phparch-sculpin
$ cd phparch-sculpin
$ vendor/bin/sculpin generate --watch --server

$ cd phparch-jigsaw
$ composer require tightenco/jigsaw
$ vendor/bin/jigsaw init blog
$ vendor/bin/jigsaw build
```

**Code Example:**
Here's an example of a basic Markdown file used in Sculpin:

```markdown
---
title: My Own Blog
categories:
  - news
---
