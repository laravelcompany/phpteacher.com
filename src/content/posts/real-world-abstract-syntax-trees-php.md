---
title: "Real World Abstract Syntax Trees - Using AST in PHP Development"
description: "Learn how Abstract Syntax Trees work in PHP, how to use nikic/PhpParser to traverse and modify code, and build real-world refactoring tools with AST."
pubDate: "2022-09-10 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["AST", "Abstract Syntax Tree", "PHP", "Parser", "Static Analysis", "PhpParser"]
selected: false
---

A client comes to you with a project. "We have 2000 static method calls," they say. "We need to migrate to dependency injection. Everyone tells us it's impossible."

After a few moments, you can reply: "I see a fairly simple algorithm using AST to achieve that. Let's do it."

That is the power of Abstract Syntax Trees. AST is a technology that changes how you view code. Once you understand it, problems that look like rewrites become mechanical transformations. You don't need a computer science degree. You need to know one pattern.

## What Is an Abstract Syntax Tree?

An Abstract Syntax Tree is a tree structure that represents the structure of a programming language. It belongs in the same category as terms like "dependency injection" — it is a pattern, not a PHP-specific concept. Once you know what to look for, you can apply it in any language.

Most explanations try to draw a picture of an AST representing a PHP expression. That is tautology — explaining the term by using the term itself. Instead, start with something you already understand: English sentence structure.

```
S V O M P T
```

Subject, Verb, Object, Manner, Place, Time. "Tomas wrote a post in a coffee shop today."

This structure splits one branch into more detailed ones:
- Text splits into sentences
- A sentence splits into words — required ones (S, V, O) and optional ones (M, P, T)
- A subject can have detailed adjectives — "extremely lazy Tomas"

When you learn this English sentence structure rule, you suddenly see every sentence through these glasses. If a teacher tells you to find all subjects in a text, you can do it very quickly. If a programmer created English grammar, it would be called ASVOT — Abstract Subject Verb Object Tree.

## Seeing Code as an AST

Find all the variables in this code:

```php
$article = new Article('How anyone can learn AST fast?');

$author = new Author('Tomas');
$author->writeContentForArticle($article);

$magazine = new Magazine('PHP Arch');

$author->deliverFinishedArticleToMagazine($article, $magazine);
```

Three variables — `$article`, `$author`, and `$magazine`. You just used AST directly in your brain without knowing it.

How did you know those are variables? You looked for a pattern: a string starting with `$`, then alphanumeric characters, then `=`. `$<variable_name> = ...;` Anything matching that pattern is a newly created variable. That description is called *grammar* — the same concept as the S V O rules for English.

Did you know PHP itself uses grammar to parse your code? The PHP source code contains a grammar file that defines every valid language construct. `nikic/php-parser` implements this same grammar in pure PHP, turning source code into a tree of node objects.

## Three Basic Elements of an AST

Every AST system — whether for PHP, YAML, TWIG, HTML, or XML — shares three components. Remember the acronym NTV: Nodes, Traverser, Visitors.

### 1. Nodes

Nodes are the building blocks of an AST. Every piece of code becomes a node object. PHP uses roughly 100 different node types. A `$variable` becomes `Expr_Variable`. An `if` statement becomes `Stmt_If`. A method call becomes `Expr_MethodCall`. Each node contains its value and references to its children.

```php
// What the PHP parser sees internally:
// Stmt_Expression
//   ├── Expr_Assign
//   │   ├── Expr_Variable('author')
//   │   └── Expr_New('Author', args: ['Tomas'])
```

Nodes share common data — line positions, file names, metadata — typically provided by a base `Node` class.

### 2. Node Traverser

The traverser visits every node in the tree from top to bottom. It starts at the root, visits all children recursively, and continues until it reaches nodes without children.

```php
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

$code = <<<'CODE'
<?php
$article = new Article('AST is awesome');
echo $article->getTitle();
CODE;

$parser = (new ParserFactory())->createForNewestSupportedVersion();
$ast = $parser->parse($code);

$traverser = new NodeTraverser();
// Add visitors here
$modifiedAst = $traverser->traverse($ast);
```

A generic traverser works because every node exposes its child nodes through `getSubNodeNames()`. The traverser reads these names, iterates the children, and recursively visits each one. This design keeps the traverser closed for modification but open for extension.

### 3. Node Visitors

Visitors are where the real work happens. A visitor receives every node during traversal and decides what to do with it — read information, modify the node, or collect statistics.

```php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MethodCallCounter extends NodeVisitorAbstract
{
    private int $count = 0;

    public function getCount(): int
    {
        return $this->count;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Expr\MethodCall) {
            $this->count++;
        }
    }
}

$visitor = new MethodCallCounter();
$traverser->addVisitor($visitor);
$traverser->traverse($ast);

echo $visitor->getCount(); // Number of method calls in the file
```

## Building a Custom Coding Standard

Static analysis tools like PHPStan and Psalm are the most visible AST consumers. But you can build your own rules for project-specific conventions:

```php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class NoGlobalFunctions extends NodeVisitorAbstract
{
    private array $violations = [];

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Expr\FuncCall) {
            if ($node->name instanceof Node\Name && !$node->name->isFullyQualified()) {
                $this->violations[] = sprintf(
                    'Line %d: Use fully-qualified function names (e.g., \array_map instead of array_map)',
                    $node->getStartLine()
                );
            }
        }
    }
}
```

This visitor enforces a rule that every function call must use a fully-qualified name. Plug it into your CI pipeline and reject code that violates your team's conventions.

## Refactoring Legacy Code with AST

The real superpower of AST is automated code modification. Remember the 2000 static calls that needed dependency injection? Here is the pattern:

```php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

class StaticCallToDI extends NodeVisitorAbstract
{
    private string $serviceClass;
    private string $propertyName;

    public function __construct(string $serviceClass, string $propertyName)
    {
        $this->serviceClass = $serviceClass;
        $this->propertyName = $propertyName;
    }

    public function enterNode(Node $node): null|Node
    {
        // Replace ClassName::method() with $this->service->method()
        if ($node instanceof Node\Expr\StaticCall) {
            if ((string) $node->class === $this->serviceClass) {
                return new Node\Expr\MethodCall(
                    new PropertyFetch(
                        new Variable('this'),
                        $this->propertyName
                    ),
                    $node->name,
                    $node->args
                );
            }
        }

        // Add constructor parameter
        if ($node instanceof Class_ && $node->isAnonymous() === false) {
            $constructor = $this->findOrCreateConstructor($node);
            $constructor->params[] = new Param(
                new Variable($this->propertyName),
                type: new Node\Name\FullyQualified($this->serviceClass),
                flags: Class_::MODIFIER_PRIVATE
            );
        }

        return null;
    }

    private function findOrCreateConstructor(Class_ $class): ClassMethod
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === '__construct') {
                return $stmt;
            }
        }

        $constructor = new ClassMethod('__construct', [
            'flags' => Class_::MODIFIER_PUBLIC,
            'params' => [],
            'stmts' => [],
        ]);

        $class->stmts = array_merge([$constructor], $class->stmts);
        return $constructor;
    }
}
```

Run this visitor across your codebase, and every static call to the specified class becomes a constructor-injected dependency. No manual editing. No missed spots. The algorithm handles all edge cases because it understands the code structure, not just text patterns.

## Real-World Use Cases

### Renaming Methods Across a Codebase

```php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class MethodRenamer extends NodeVisitorAbstract
{
    public function __construct(
        private string $oldName,
        private string $newName
    ) {}

    public function enterNode(Node $node): null|Node
    {
        if ($node instanceof Node\Expr\MethodCall) {
            if ($node->name->toString() === $this->oldName) {
                $node->name = new Node\Identifier($this->newName);
            }
        }

        if ($node instanceof Node\Stmt\ClassMethod) {
            if ($node->name->toString() === $this->oldName) {
                $node->name = new Node\Identifier($this->newName);
            }
        }

        return null;
    }
}
```

### Extracting Interface from Class

```php
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassMethod;

class ExtractInterface extends NodeVisitorAbstract
{
    private array $publicMethods = [];

    public function enterNode(Node $node): null|Node
    {
        if ($node instanceof ClassMethod && $node->isPublic()) {
            $methodName = $node->name->toString();
            $this->publicMethods[$methodName] = new ClassMethod(
                $methodName,
                [
                    'params' => $node->params,
                    'returnType' => $node->returnType,
                    'flags' => 0,
                ]
            );
        }
        return null;
    }

    public function getInterfaceMethods(): array
    {
        return $this->publicMethods;
    }
}
```

### Migrating Configuration Formats

AST is not limited to PHP. The same NTV pattern works for YAML, NEON, TWIG, and even HTML. A service container migration from YAML to PHP attributes uses the same traverser-visitor architecture with a different parser.

## United Traverse Direction

AST always traverses from top to bottom — from the root node down to the deepest child. Two reasons for this:

1. **Recursive calls must have one direction.** If traversal could go up and down arbitrarily, circular changes would crash the script.

2. **The tree is a mutable array of objects.** Modifying a bottom element, then the top, then the bottom again creates chaos. An item in a parent array might be missed because it was already traversed.

Always modify the current node or its children. Never modify parent nodes.

## The NTV Pattern Beyond PHP

The Nodes-Traverser-Visitors pattern transcends PHP:

| Language | Parser Package | Nodes | Use Case |
|---|---|---|---|
| PHP | `nikic/php-parser` | ~100 node types | Refactoring, static analysis |
| Twig | `twig/twig` | ~50 node types | Template analysis |
| YAML | `symfony/yaml` | ~10 node types | Config migration |
| HTML | `domdocument` | ~50 node types | Accessibility checks |
| JavaScript | `babel/parser` | ~150 node types | Codemods, transpilation |

Once you recognize the pattern in one tool, you see it everywhere.

## What the Parser Gives You

The `nikic/php-parser` library provides three things out of the box:

- **Parser** — Converts PHP source code into an AST
- **NodeTraverser** — Visits every node in the tree
- **PrettyPrinter** — Converts a modified AST back into PHP source code

```php
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

$code = file_get_contents('src/Service.php');

// Parse
$parser = (new ParserFactory())->createForNewestSupportedVersion();
$ast = $parser->parse($code);

// Traverse
$traverser = new NodeTraverser();
$traverser->addVisitor(new MethodCallCounter());
$traverser->traverse($ast);

// Print back
$printer = new Standard();
$modifiedCode = $printer->prettyPrintFile($ast);

file_put_contents('src/Service.php', $modifiedCode);
```

## Summary

Abstract Syntax Tree is a way of seeing code. Just as English grammar helps you identify subjects, verbs, and objects, language grammar helps you identify logical elements called nodes.

The three components of AST work together:
- **Nodes** represent code constructs
- **Node traverser** visits every node recursively
- **Node visitors** read or modify nodes for a specific purpose

The parser package provides nodes and traverser. You write visitors. That is the entire pattern.

When a client brings you an "impossible" migration problem, AST gives you the answer. Not a rewrite — an algorithm. Identify the pattern, write a visitor, and let the traverser do the rest.

Happy coding.
