---
title: "Harness The Power of the AST - Advanced PHP Code Manipulation"
description: "Harness the power of PHP's Abstract Syntax Tree for advanced code manipulation. Build automated refactoring tools with PhpParser and Rector."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-12-10 21:00:00"
tags: ["AST", "PHP", "Rector", "Refactoring", "PhpParser", "Automation"]
---

The abstract syntax tree is one of the most powerful concepts in programming language tooling. It transforms source code into a tree of node objects that you can analyze, modify, and regenerate. When you understand the AST, you unlock the ability to automate massive codebase transformations that would otherwise take weeks of manual work.

## What Is an Abstract Syntax Tree?

An abstract syntax tree is a tree representation of source code. Each node in the tree represents a construct in the code. The parser takes your PHP source and produces a tree, the node traverser walks through every node, and node visitors perform operations on specific node types.

Consider this simple PHP expression:

```php
$name = 'Tomas';
```

The AST for this would be something like an `Expr_Assign` node with a `Expr_Variable` child for `$name` and a `Scalar_String` child for `Tomas`. The tree structure preserves the meaning of the code while stripping away superficial syntax like whitespace and comments.

## Why Write Your Own AST Parser?

There are three compelling reasons to build your own AST parser:

1. **No existing tool** for the format you need to process (like JSON, YAML, or custom config files)
2. **Automation within CI pipelines** where you need reproducible, testable code transformations
3. **Beyond find-and-replace** when simple string manipulation is insufficient

## A Real-World Problem: Validating City Names

Imagine you work with thousands of JSON files from an external REST service containing coffee shop locations worldwide. One day, the service starts returning a city called "New Yerk" and this incorrect data propagates to your production website.

The business asks for validation: city names must be real locations in the USA. Invalid entries get skipped, logged, and never reach production.

### The Manual Approach

At first glance, you might write:

```php
$jsonArray = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
foreach ($jsonArray['coffees'] as $coffee) {
    $coffeeCity = $coffee['city'];
    // validate here
}
```

This works until the external service changes its structure. Now you need to handle nested groups:

```php
$jsonArray = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
foreach ($jsonArray['coffees'] ?? [] as $coffee) {
    $coffeeCity = $coffee['city'];
}

$coffeeGroups = $jsonArray['coffee-groups'] ?? [];
foreach ($coffeeGroups as $groups) {
    foreach ($groups as $groupName => $coffees) {
        foreach ($coffees as $coffee) {
            $coffeeCity = $coffee['city'];
        }
    }
}
```

This code is fragile, tightly coupled to the JSON structure, and hard to maintain. When the structure changes again, you will get a fatal error first and debug later.

### The AST Node Visitor Approach

Instead of fighting with nested arrays, build an AST parser that converts JSON into a tree of node objects. The algorithm you want is simple:

```php
if (! isset($item['city'])) {
    return;
}

$city = $item['city'];
if (! is_string($city)) {
    return;
}

validate_city_name($city);
```

The node visitor approach lets you write this logic once and apply it to any JSON structure regardless of nesting depth.

## Step 1: Write a Parser

The parser converts input into a generic tree of node objects. JSON has two forms: key-value pairs and nested arrays.

```php
namespace PhpArch\Ast;

final class JsonParser
{
    public function parse(string $inputResponse): array
    {
        $jsonData = json_decode($inputResponse, true, 512, JSON_THROW_ON_ERROR);
        return $this->createNodes($jsonData);
    }

    private function createNodes(array $jsonArray): array
    {
        $nodes = [];
        foreach ($jsonArray as $key => $value) {
            if (is_array($value)) {
                $nestedNodes = $this->createNodes($value);
                $nodes[] = new ChildAwareItemNode($key, $nestedNodes);
            } else {
                $nodes[] = new ItemNode($key, $value);
            }
        }
        return $nodes;
    }
}
```

## Step 2: Define Node Classes

AST tools in PHP conventionally use public properties for easy access and reflection. This makes modification straightforward:

```php
namespace PhpArch\Ast\Node;

abstract class AbstractJsonNode
{
    public function __construct(public string $name)
    {
    }
}

final class ItemNode extends AbstractJsonNode
{
    public function __construct(
        public string|int $name,
        public string $value
    ) {
    }
}

final class ChildAwareItemNode extends AbstractJsonNode
{
    /**
     * @param AbstractJsonNode[] $subNodes
     */
    public function __construct(
        public string|int $name,
        public array $subNodes
    ) {
    }
}
```

## Step 3: Create a Node Traverser

The traverser walks through every node and nested node from top to bottom:

```php
namespace PhpArch\Ast;

use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ChildAwareItemNode;

final class JsonNodeTraverser
{
    /** @var JsonNodeVisitorInterface[] */
    private array $jsonNodeVisitors = [];

    public function addVisitor(JsonNodeVisitorInterface $jsonNodeVisitor): void
    {
        $this->jsonNodeVisitors[] = $jsonNodeVisitor;
    }

    /** @param AbstractJsonNode[] $jsonNodes */
    public function traverse(array $jsonNodes): void
    {
        foreach ($jsonNodes as $jsonNode) {
            foreach ($this->jsonNodeVisitors as $visitor) {
                $visitor->enterNode($jsonNode);
            }

            if ($jsonNode instanceof ChildAwareItemNode) {
                $this->traverse($jsonNode->subNodes);
            }
        }
    }
}
```

## Step 4: Define the Visitor Interface

```php
namespace PhpArch\Ast\Contract;

use PhpArch\Ast\Node\AbstractJsonNode;

interface JsonNodeVisitorInterface
{
    public function enterNode(AbstractJsonNode $jsonNode): void;
}
```

## Step 5: Implement the Validation Visitor

Now the code that solves our original problem. This visitor is reusable on any JSON input regardless of structure:

```php
namespace PhpArch\Ast\JsonNodeVisitor;

use PhpArch\Ast\Contract\JsonNodeVisitorInterface;
use PhpArch\Ast\Node\AbstractJsonNode;
use PhpArch\Ast\Node\ItemNode;

final class ValidateCityJsonNodeVisitor implements JsonNodeVisitorInterface
{
    public function enterNode(AbstractJsonNode $jsonNode): void
    {
        if (! $jsonNode instanceof ItemNode) {
            return;
        }

        if ($jsonNode->name !== 'city') {
            return;
        }

        validate_city_name($jsonNode->value);
    }
}
```

The early returns are key. We only care about `ItemNode` instances with the name "city". Everything else is skipped.

## Putting It All Together

```php
$jsonParser = new JsonParser();
$jsonNodes = $jsonParser->parse($inputResponse);

$jsonNodeTraverser = new JsonNodeTraverser();
$jsonNodeTraverser->addVisitor(new ValidateCityJsonNodeVisitor());
$jsonNodeTraverser->traverse($jsonNodes);
```

This script handles any JSON input with any number of nesting levels. Cities in coffee shops, restaurants, or pubs; single or ten-level nesting; grouped or flat. The same code works for all of them.

## Beyond Validation: Transforming Data

The same pattern lets you correct data inline:

```php
if ($jsonNode->value === 'New Yerk') {
    $jsonNode->value = 'New York';
}
```

## Advanced AST Tools in the PHP Ecosystem

While building your own parser is educational and useful for custom formats, the PHP ecosystem already has powerful AST tools for PHP code itself.

### nikic/php-parser

This is the foundation library for PHP AST manipulation. It parses PHP source code into an AST and can pretty-print the AST back to PHP code. Every major AST tool in PHP builds on this library.

### Rector

Rector is an automated refactoring tool that uses nikic/php-parser to transform PHP codebases. It comes with hundreds of predefined rules for upgrading PHP versions, framework migrations, and code quality improvements.

Upgrading from PHP 7.4 to PHP 8.1 across a legacy codebase with thousands of files is tedious. With Rector, you configure sets:

```php
// rector.php
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        SetList::PHP_74,
        SetList::PHP_80,
        SetList::PHP_81,
    ]);
};
```

Run it, and Rector handles typed properties, match expressions, named arguments, readonly properties, and more.

### Creating Custom Rector Rules

When you need a transformation that doesn't exist yet, Rector lets you write custom rules. Consider renaming a method across your entire codebase:

```php
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class RenameSendToDispatchRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'send')) {
            return null;
        }

        $node->name = new \PhpParser\Node\Identifier('dispatch');
        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Rename send() to dispatch()',
            [new CodeSample(
                '$service->send($data);',
                '$service->dispatch($data);'
            )]
        );
    }
}
```

This rule matches every `MethodCall` node, checks if the method name is "send", renames it to "dispatch", and returns the modified node. When it returns `null`, the node is left unchanged.

## Practical AST Automation Workflows

### CI Pipeline Validation

Parse your `composer.json` in CI to enforce consistent dependency versions:

```php
$jsonParser = new JsonParser();
$jsonNodes = $jsonParser->parse(file_get_contents('composer.json'));

$versionValidator = new class implements JsonNodeVisitorInterface {
    public function enterNode(AbstractJsonNode $jsonNode): void
    {
        if (! $jsonNode instanceof ItemNode) {
            return;
        }

        if ($jsonNode->name === 'php') {
            $version = $jsonNode->value;
            if (version_compare($version, '8.1', '<')) {
                throw new \RuntimeException(
                    "PHP version $version is too low. Require 8.1+"
                );
            }
        }
    }
};

$traverser = new JsonNodeTraverser();
$traverser->addVisitor($versionValidator);
$traverser->traverse($jsonNodes);
```

### Bulk Code Modifications

Need to add a return type to every method in a directory? A Rector rule handles this in seconds. Need to replace a deprecated method call? Same approach. The AST gives you surgical precision across thousands of files.

## The Power You Now Hold

You have written your first JSON AST parser, node traverser, and node visitor. This pattern generalizes to any structured data format. You can validate, transform, and analyze code in ways that simple string manipulation cannot match.

The next time you face a thousand-file change or need to enforce a cross-cutting concern, reach for AST tools. Your colleagues will appreciate the automated approach that eliminates tedious manual work. You will be the most popular person on the team because you have the power to save so much time and boring work.

Happy coding!
