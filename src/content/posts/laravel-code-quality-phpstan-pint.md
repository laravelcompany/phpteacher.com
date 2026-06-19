---
title: "Controlling Laravel Code Quality: PhpStan and Pint"
description: "Master Laravel code quality with PhpStan static analysis and Laravel Pint code styling. Complete configuration guide for strict, clean PHP code."
pubDate: "2023-12-20 22:00:00"
category: "laravel"
banner: "/logo.svg"
tags: ["Laravel", "PhpStan", "Laravel Pint", "Code Quality", "Static Analysis", "PHP CS Fixer", "PSR-12", "PHP 8.x"]
selected: false
---

Code quality isn't about preference — it's about preventing bugs before they happen. In 2023, Laravel developers have access to powerful tools that catch type errors, enforce coding standards, and ensure consistency across teams.

The tools have matured significantly. Static analysis can detect entire classes of bugs that would previously require runtime testing. Automated code styling eliminates formatting debates in code reviews. Together, they create guardrails that let you focus on solving business problems rather than fixing preventable mistakes.

## What You'll Learn

- Configuring PhpStan at maximum strictness (level 9) for Laravel
- Setting up Laravel Pint with custom PSR-12 rules
- Integrating type coverage checks with Pest PHP
- Building CI/CD pipelines for automated quality checks
- Creating a personal workflow that enforces quality before commits

## Static Analysis with PhpStan

Static analysis examines your code without running it. It understands type relationships, detects impossible conditions, and catches bugs that would only surface at runtime.

### Why PhpStan?

PhpStan analyzes your code's type flow and reports inconsistencies. It knows that if a method returns `string|null`, you can't pass its return value to a function expecting `string`. It catches calls to undefined methods, mismatched type hints, and unreachable code.

### Configuration

Start with this baseline configuration for Laravel applications:

```yaml
parameters:
  paths:
    - app/
  level: 9
  checkGenericClassInNonGenericObjectType: false
```

**Level 9** is PhpStan's strictest setting. It enforces complete type safety, requiring you to handle every possible type scenario. The `checkGenericClassInNonGenericObjectType: false` prevents false positives on Eloquent model relationships, which use magic methods PhpStan can't fully analyze.

Add a composer script for convenience:

```json
{
  "scripts": {
    "stan": [
      "./vendor/bin/phpstan analyse --memory-limit=3g"
    ]
  }
}
```

The explicit memory limit prevents out-of-memory failures on larger projects.

### GitHub Actions Integration

Set up a dedicated quality check workflow:

```yaml
jobs:
  quality:
    name: "Code Quality Checks"
    steps:
      - name: 'Run PhpStan'
        run: composer run stan
```

Keep this separate from your main test suite. Run it on pull requests and main branch pushes, not on every commit to draft PRs.

## Code Styling with Laravel Pint

PHP CS Fixer has been the standard for PHP code styling for years. Laravel Pint is a Laravel-first wrapper that simplifies configuration and provides friendlier output.

### Configuration

Here's an opinionated but practical Pint configuration that enforces strict typing and clean code:

```json
{
  "preset": "per",
  "rules": {
    "declare_strict_types": true,
    "final_class": true,
    "fully_qualified_strict_types": true,
    "method_chaining_indentation": true,
    "ordered_traits": true,
    "protected_to_private": true,
    "yoda_style": true,
    "concat_space": {
      "spacing": "one"
    },
    "ordered_imports": {
      "sort_algorithm": "alpha"
    },
    "ordered_class_elements": {
      "order": [
        "use_trait",
        "case",
        "constant",
        "constant_public",
        "constant_protected",
        "constant_private",
        "property_public",
        "property_protected",
        "property_private",
        "construct",
        "destruct",
        "magic",
        "phpunit",
        "method_abstract",
        "method_public_static",
        "method_public",
        "method_protected_static",
        "method_protected",
        "method_private_static",
        "method_private"
      ]
    },
    "no_superfluous_elseif": true,
    "no_useless_else": true,
    "strict_comparison": true,
    "ternary_to_null_coalescing": true,
    "use_arrow_functions": true,
    "void_return": true
  }
}
```

### Key Configuration Choices

**declare_strict_types: true** — This is the single most impactful quality setting. It makes PHP throw `TypeError` when you pass an argument of the wrong type instead of silently coercing it. Every file in your application should begin with `declare(strict_types=1)`.

**final_class: true** — Making classes `final` by default forces you to explicitly think about inheritance. Instead of extending a class, you compose with it. This leads to more maintainable code because composition creates explicit contracts while inheritance creates implicit dependencies.

**protected_to_private: true** — Properties and methods that don't need to be accessed by subclasses should be private. This reduces the surface area of your class and makes refactoring safer.

**yoda_style: true** — Writing `null !== $variable` instead of `$variable !== null` prevents the common mistake of typing `$variable = null` instead of `$variable === null`. The parser catches the assignment-in-condition error.

### Composer Script and CI

```json
{
  "scripts": {
    "pint": [
      "./vendor/bin/pint"
    ]
  }
}
```

```yaml
jobs:
  quality:
    name: "Code Quality Checks"
    steps:
      - name: 'Run Laravel Pint'
        run: composer run pint
```

## Type Coverage with Pest PHP

Type coverage is a relatively new quality metric. It measures what percentage of your code has explicit type annotations for parameters, return types, and properties.

Pest PHP includes a type coverage plugin that integrates seamlessly:

```json
{
  "scripts": {
    "types": [
      "./vendor/bin/pest --type-coverage"
    ]
  }
}
```

This scans your entire codebase and reports the percentage of typed declarations. Unlike PhpStan, which verifies type correctness, type coverage measures type completeness. Together, they ensure that not only are your types correct, but you haven't missed any declarations.

## The Complete Workflow

A disciplined quality workflow follows these steps:

1. Write code and tests
2. Run static analysis (`composer run stan`)
3. Apply code styling (`composer run pint`)
4. Check type coverage (`composer run types`)
5. Run tests (`composer run test`)
6. Commit

This order matters. Run static analysis before styling because PhpStan provides more actionable feedback. Style after fixing type issues to avoid formatting code that you'll immediately change.

## PhpStan Beyond Level 9

While level 9 is the maximum strictness, PhpStan offers features beyond the baseline level check.

### Custom Rules

Write custom rules for project-specific conventions:

```php
<?php

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;

class NoDumpRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name instanceof Node\Name
            && in_array($node->name->toString(), ['dump', 'dd', 'var_dump'], true)
        ) {
            return ['Remove debug call before committing'];
        }

        return [];
    }
}
```

### Baseline Management

For existing codebases, generate a baseline to track known issues:

```bash
vendor/bin/phpstan analyse --generate-baseline
```

The baseline file lists current errors. As you fix them, remove them from the baseline. This lets you adopt strict analysis immediately without fixing every issue at once.

### Laravel-Specific Extensions

The Larastan extension adds Laravel-specific knowledge to PhpStan:

```bash
composer require --dev larastan/larastan
```

It understands Eloquent model relationships, facade method calls, and macro definitions that vanilla PhpStan would flag as errors.

## Laravel Pint in CI/CD

Integrate Pint into your CI pipeline for automated style enforcement:

```yaml
jobs:
  lint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
      - name: Check Code Style
        run: vendor/bin/pint --test
```

The `--test` flag makes Pint check style without modifying files. It returns a non-zero exit code if any files don't match the configured style.

## Type Coverage for Complete Safety

Type coverage measures the percentage of your codebase with explicit type annotations. Pest PHP's type coverage plugin makes this straightforward:

```bash
composer require --dev pestphp/pest-plugin-type-coverage
```

Configure a minimum coverage threshold:

```json
{
  "scripts": {
    "types": [
      "./vendor/bin/pest --type-coverage --min=100"
    ]
  }
}
```

Enforcing 100% type coverage on new code prevents entire categories of bugs. It forces you to declare return types, parameter types, and property types — making your code self-documenting and tool-friendly.

## Real-World Use Cases

- **Open source packages**: Maintainers enforce quality standards across contributions from dozens of developers
- **Enterprise teams**: Onboarding new developers is faster with automated guardrails that prevent common mistakes
- **Agency projects**: Consistent code style across multiple client projects makes maintenance predictable
- **SaaS products**: Type safety prevents production incidents caused by unexpected null values or type mismatches
- **Legacy migrations**: Static analysis identifies type issues before they cause runtime failures during refactoring

## Best Practices

- **Start at a lower PhpStan level and work up**: Level 9 can be overwhelming for existing codebases. Start at level 5 or 6 and increase gradually
- **Run styling before commit**: Configure a pre-commit hook to run Pint automatically
- **Document baseline exceptions**: When you must suppress a PhpStan error, document why in the code
- **Treat CI failures as blocking**: A PR that fails quality checks should not be mergeable
- **Review quality rules quarterly**: As your team's standards evolve, update your configurations

## Common Mistakes to Avoid

- **Running static analysis without a baseline**: New projects can go straight to level 9. Existing projects need a baseline to track improvements
- **Ignoring type coverage**: Type declarations prevent an entire category of bugs. Don't skip them because they require more typing
- **Applying styling without review**: Automated styling can make unexpected changes. Review the diff before committing
- **Over-relying on docblocks**: PhpStan can parse `@param` and `@return` annotations, but native type hints are always preferred
- **Not updating the baseline**: When you fix a PhpStan error, remove it from the baseline. Stale baselines defeat the purpose

## Building a Quality Culture

Tools alone don't ensure code quality. The team culture around quality matters more.

### Establishing Standards

Write down your team's quality standards in an ADR (Architecture Decision Record):

- What PhpStan level is required?
- What Pint rules are enforced?
- What type coverage minimum is acceptable?
- When can exceptions be made and who approves them?

### Gradual Enforcement

Introduce strict checks gradually. Start with PhpStan level 5 and increase quarterly. Apply Pint formatting to new code only at first, then expand to the full codebase.

### Blame-Free Enforcement

Quality checks catch system issues, not individual failures. A CI pipeline that rejects code for a missed type hint isn't accusing anyone — it's protecting the team. Frame quality enforcement as collaborative improvement.

### Continuous Improvement

Review your quality configuration every quarter. As PHP evolves (new typed properties, readonly classes, enum support), your quality tools can enforce newer language features that prevent even more bugs.

## The Cost of Poor Quality

Low code quality has measurable costs:

- **Debugging time**: Type errors that PhpStan could catch take 10-60 minutes to debug at runtime
- **Code review friction**: Style inconsistencies generate review comments that distract from logic issues
- **Onboarding time**: New developers take longer to understand loosely typed, inconsistently formatted code
- **Production incidents**: Type mismatches cause runtime errors that affect users
- **Refactoring risk**: Without static analysis, refactoring is terrifying; with it, refactoring is safe

Investing in quality tooling pays for itself in the first few incidents it prevents.

**Should I use PhpStan or Psalm?**
Both are excellent. PhpStan has better Laravel support through extensions. Psalm has more advanced taint analysis. Try both and choose based on your needs.

**Can I run Pint on legacy codebases?**
Yes, with the `--test` flag to preview changes before applying them. Gradually introduce styling rules to avoid overwhelming diffs.

**Does strict typing affect performance?**
Minimally. The type check happens once at call time. Modern PHP with JIT compilation makes the overhead negligible compared to the bug prevention value.

**How do I handle Laravel's magic methods with PhpStan?**
Use the `@method` annotation on model classes or install the `larastan` extension for comprehensive Laravel support.

**Should I enforce final classes on all code?**
Be pragmatic. Use `final` by default but make exceptions for classes specifically designed for extension.

**What's the right type coverage target?**
Aim for 100% on new code. For existing codebases, 90%+ is achievable and provides significant quality improvements.

**Can I run quality checks in development without CI?**
Yes. Use tools like `husky` or `pre-commit` to run checks before every commit. This catches issues before they reach the repository.

## Conclusion

Code quality tools aren't gatekeepers — they're accelerators. PhpStan, Laravel Pint, and type coverage catch mistakes that would otherwise require runtime debugging. They enforce consistency that makes code review faster. They create a shared standard that makes onboarding smoother.

Invest time in configuring these tools correctly. The upfront effort of setting up strict rules pays back every single day in reduced debugging time, fewer production incidents, and more predictable development cycles.

Your future self — and your teammates — will thank you for every type hint and every consistent formatting choice.

**[PhpStan Documentation](https://phpstan.org/) | [Laravel Pint](https://laravel.com/docs/pint)**
