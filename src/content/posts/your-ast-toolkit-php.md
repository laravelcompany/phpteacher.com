---
title: "Your AST Toolkit - Building PHP Refactoring Tools With Abstract Syntax Trees"
description: "Build PHP refactoring tools with Abstract Syntax Trees. Create custom static analysis and automated code transformation utilities using PhpParser."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-10 21:00:00"
tags: ["AST", "PHP", "PhpParser", "Refactoring", "Static Analysis", "Symfony"]
---

Abstract Syntax Trees give you X-ray vision into your PHP code. Once you see code as AST nodes, you can automate massive refactoring projects that would take months of manual work.

## What Is an AST?

An AST — Abstract Syntax Tree — is an approach to seeing code rather than a specific tool or library. PHP code can be understood in several ways:

- **Execution**: You provide input values, the script processes them, and prints results.
- **Reflection**: Analyze metadata about classes, properties, and methods without running code.
- **Tokens**: `PhpToken::tokenize()` breaks code into flat token streams like `T_ECHO`, `T_LNUMBER`, etc.
- **AST**: Code is parsed into a tree of node objects preserving structure, values, and position.

The line `echo 1;` becomes:

```php
new Echo_(new LNumber(1));
```

The power of AST is **pattern refactoring** — defining an A → B transformation and running it across your entire project. The same change with raw tokens would require handling every variation of whitespace, parentheses, and formatting.

## Pattern Refactoring

Pattern refactoring means defining exact code patterns and transforming them deterministically. Every situation must have exactly one valid transformation. This is the foundation of automated code modification.

A simple example: change `echo 1` to `echo '1'`:

```php
-new Echo_(new LNumber(1));
+new Echo_(new String_('1'));
```

Once you understand this basic mechanism, you can scale to real-world refactoring like adding type declarations:

```php
-private $value = 1000;
+private int $value = 1000;
```

Anything you can explain to a colleague in terms of "find X and replace with Y" can be automated with AST.

## Tool 1: nikic/PhpParser

The PHP community already has an excellent PHP parser written in PHP — `nikic/php-parser`. Install it:

```bash
composer require nikic/php-parser:dev-master --dev
```

Start by creating a `refactor-php-pattern.php` file:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpParser\ParserFactory;

$factory = new ParserFactory();
$parser = $factory->createForNewestSupportedVersion();

$file = __DIR__ . '/SomeCode.php';
$fileContents = file_get_contents($file);
$astNodes = $parser->parse($fileContents);
```

### Adding Node Visitors

Node visitors define the changes you want to make. Each visitor extends `PhpParser\NodeVisitorAbstract` and implements `enterNode()`:

```php
<?php
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

final class ChangeEchoNumberToStringNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node): ?Echo_
    {
        if (! $node instanceof Echo_) {
            return null;
        }

        if (! $node->expr instanceof LNumber) {
            return null;
        }

        $node->expr = new String_((string) $node->expr);

        return $node;
    }
}
```

Run the visitor with a `NodeTraverser`:

```php
<?php
use PhpParser\NodeTraverser;

$nodeTraverser = new NodeTraverser();
$nodeTraverser->addVisitor(new ChangeEchoNumberToStringNodeVisitor());
$traversed = $nodeTraverser->traverse($astNodes);
```

Then print the modified code back:

```php
<?php
use PhpParser\PrettyPrinter\Standard;

$standardPrinter = new Standard();
$modified = $standardPrinter->prettyPrintFile($astNodes);
file_put_contents(__DIR__ . '/SomeCode.php', $modified);
```

You've just performed project-wide automated refactoring with a handful of lines.

### Downsides of Pure PhpParser

Vanilla php-parser has limitations. It cannot infer variable types — `$value = 1; echo $value;` would be missed by a visitor looking for `Echo_` with `LNumber`. The missing piece is **PHPStan**, which can analyze types from scalars to unions.

Another issue: php-parser's default printer doesn't preserve original spacing. Lines like:

```php
echo 1;
echo    1;
    echo   1   ;
```

All parse to the same AST nodes but print identically, losing formatting. The format-preserving pretty printer solves this but requires a more complex setup.

### Rector: A Smarter Wrapper

Rector wraps php-parser and PHPStan together, solving the type-analysis and formatting problems. Install it:

```bash
composer require rector/rector --dev
```

Initialize and run:

```bash
vendor/bin/rector init
vendor/bin/rector process
```

Rector ships with pre-built rule sets for upgrading PHP 5.3 → 8.2, PHPUnit 4 → 9, Symfony 2.8 → 6.1, and many more. You write the patterns; Rector handles the complexities.

## Tool 2: YAML Config Parser

PHP projects in 2022 rarely use pure PHP for everything. Configuration files in YAML or NEON format are everywhere. NEON has an AST parser, and in 99% of cases, YAML and NEON syntax are identical.

Install the NEON parser:

```bash
composer require nette/neon
```

Create a `refactor-yaml-pattern.php`:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Nette\Neon\Decoder;

$neonDecoder = new Decoder();

$file = __DIR__ . '/simple.yaml';
$yamlContents = file_get_contents($file);
$neonAstNode = $neonDecoder->parseToNode($yamlContents);

var_dump($neonAstNode);
```

You get a `Nette\Neon\Node\BlockArrayNode` — an AST node for your YAML. Now modify it:

```php
<?php
use Nette\Neon\Node;
use Nette\Neon\Node\ArrayItemNode;
use Nette\Neon\Node\LiteralNode;
use Nette\Neon\Traverser;

$neonTraverser = new Traverser();

$traversedNode = $neonTraverser->traverse(
    $neonAstNode,
    function (Node $node) {
        if (! $node instanceof ArrayItemNode) {
            return null;
        }

        if (! $node->key instanceof LiteralNode &&
            $node->key->value !== 'public'
        ) {
            return null;
        }

        if ($node->value->toValue() === false) {
            return null;
        }

        $node->value = new LiteralNode(false);
        return $node;
    }
);

$yamlContents = $traversedNode->toString();
file_put_contents(__DIR__ . '/simple.yaml', $yamlContents);
```

This changes `public: true` to `public: false` in your YAML config files. Apply the same approach to any key-value transformation.

For other config formats, check Packagist for existing parsers. If none exist (like `*.ini` files), either make manual changes or migrate to a better-supported format with proper tooling.

## Tool 3: AST in Twig Templates

Template files are often the wild west of refactoring. Renaming a method in PHP without updating templates causes runtime crashes. Twig has had a built-in AST parser and traverser since version 0.9 (2010).

Parse a Twig template:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Parser;
use Twig\Source;

$environment = new Environment(new ArrayLoader([]));

$fileName = __DIR__ . '/simple.twig';
$fileContents = file_get_contents($fileName);
$fileSource = new Source($fileContents, 'simple_file');
$tokenStream = $environment->tokenize($fileSource);

$twigParser = new Parser($environment);
$twigAstNode = $twigParser->parse($tokenStream);

var_dump($twigAstNode);
// Twig\Node\ModuleNode
```

Create a node visitor to rename `product.title` to `product.name`:

```php
<?php
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\NodeVisitor\NodeVisitorInterface;

final class ReplaceTitleWithNameNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        if (! $node instanceof ConstantExpression) {
            return $node;
        }

        if ($node->getAttribute('value') !== 'title') {
            return $node;
        }

        return new ConstantExpression('name', $node->getTemplateLine());
    }

    public function leaveNode(Node $node, Environment $env): ?Node
    {
        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }
}

$nodeVisitor = new ReplaceTitleWithNameNodeVisitor();
$twigTraverser = new NodeTraverser($environment, [$nodeVisitor]);
$traversedNode = $twigTraverser->traverse($twigAstNode);
```

Twig's parser lacks a built-in printer to write the AST back to a file, but the ability to traverse and modify template AST nodes is still powerful.

### Other Template Systems

If your project uses different templates:

1. Look for a parser directly in your templating system
2. Exploit similar syntax with an existing parser
3. Migrate to better-supported template syntax for smoother upgrades

## Expanding Your Toolkit

Today you have three tools for project-wide code changes:

- **PHP** via nikic/php-parser and Rector
- **YAML/NEON** via Nette's NEON parser
- **TWIG** via Twig's built-in AST parser

Start small. Experiment with single-pattern changes. Give yourself time to absorb the AST mindset — it took the author six months to internalize php-parser. Once it clicks, you'll automate refactoring at a scale that would terrify you to attempt manually.

Try each tool one at a time on a non-critical project. Soon you'll see code not as text files but as a forest of transformable nodes.
