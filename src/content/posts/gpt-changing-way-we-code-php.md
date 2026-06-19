---
title: "GPT: Changing The Way We Code PHP in 2023"
description: "Learn how GPT AI is transforming PHP development — from code generation and unit testing to automated legacy upgrades. Practical guide for modern PHP developers."
pubDate: "2023-04-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["GPT", "PHP", "AI Code Generation", "OpenAI", "Rector", "PHPStan", "Automated Refactoring", "PHP 8.x"]
selected: false
---

The PHP ecosystem is experiencing a shift unlike anything since the introduction of Composer. Large Language Models — specifically OpenAI's GPT family — are moving from experimental toys to production-ready development tools. In the span of a few months, the conversation has gone from "can AI write code?" to "how do I integrate AI-generated code into my daily workflow without sacrificing quality?"

This isn't about replacing developers. It's about removing toil. Repetitive tasks like writing unit tests for legacy code, upgrading framework versions, and fixing PHPStan errors are exactly the kind of pattern-recognition work that modern AI excels at. The question is no longer whether GPT can help you code — it's how quickly you can adapt.

Tomas Votruba, creator of the Rector project, explores the practical intersection of GPT and PHP development. Drawing from his experience building Testgen AI and working with OpenAI APIs, he lays out a vision where AI handles the grunt work while developers focus on architecture, business logic, and creative problem-solving.

## What You'll Learn

- How to integrate the OpenAI GPT API into PHP applications in under 10 lines of code
- Real startup ideas that combine GPT with PHP tooling like Rector and PHPStan
- How GPT can automate PHP version upgrades from 7.2 to 8.2
- Techniques for generating PHPUnit tests with AI
- Strategies for building AI-augmented development workflows
- How to avoid common pitfalls when using GPT for code generation

## The Speed of Change in AI-Assisted PHP Development

The pace of change in the AI coding space is unprecedented. When Tomas originally pitched this article, the plan was to write a tutorial on using OpenAI's Codex model to generate unit tests. Two weeks later, that approach was already obsolete. GPT-4 had been released, and the Codex model was being deprecated.

In the PHP world, developers are used to predictable release cycles. A new PHP version arrives once a year. A major Laravel or Symfony release happens every six to twelve months. You plan your upgrades, schedule the work, and execute over weeks or months.

AI doesn't work on that timeline. Models improve weekly. APIs change with days of notice. The March 2023 deprecation of the Codex model in favor of GPT-4's chat completions API is a concrete example — developers who built integrations around Codex had a matter of days to adapt.

This speed creates both opportunity and risk. The opportunity is that PHP tooling built on AI can improve continuously. The risk is that you may build on an API that shifts beneath you. The solution is to abstract your AI integration behind a clean interface, so you can swap models and providers as the landscape evolves.

## GPT Integration in PHP: The Five-Line Core

The complexity of GPT integration is often overstated. When you strip away the marketing hype, the core interaction between PHP and the OpenAI API is remarkably simple. Here is the complete implementation used in the Dave GPT demo project:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

function generate_answer_with_gpt(string $question): string
{
    $client = OpenAI::client('sk-your-api-key-here');
    $response = $client->completions()->create([
        'model' => 'text-davinci-003',
        'prompt' => $question,
    ]);
    return $response->choices[0]->text;
}
```

That is the entire integration. Five lines of meaningful code. You obtain an API key from OpenAI, construct a client using the community-maintained `openai-php/client` package, send a prompt, and read the response.

The surrounding application — the form handling, the UI, the error management — is where the real work lives. But the core GPT call is trivial. This means the barrier to entry for PHP developers is nearly zero. If you can install a Composer package and make an HTTP request, you can build AI-powered features.

### The Frontend Component

The accompanying HTML form keeps things minimal:

```php
<form method="post" action="/">
    <label for="textarea">Type your question:</label><br>
    <textarea id="textarea" name="question" rows="4" cols="50"></textarea><br>
    <input type="submit" value="Submit">
</form>
<?php
if (isset($_POST['question'])) {
    $question = $_POST['question'];
    $answer = generate_answer_with_gpt($question);
    echo "<h1>Your question was: {$question}</h1>";
    echo "<h2>The answer is: {$answer}</h2>";
}
?>
```

The user submits a question, the PHP script forwards it to GPT, and the response is displayed. This is the simplest possible interaction, but it demonstrates the fundamental pattern that powers everything from code generation to chatbot applications.

## AI-Powered PHP Startups You Can Build Today

The simplicity of the GPT API opens the door to a range of practical PHP tools. These are not speculative future products — they are buildable today with existing technology.

### Stanfix AI: Automated PHPStan Error Resolution

PHPStan is one of the most powerful static analysis tools in the PHP ecosystem. It catches type errors, undefined variables, and countless subtle bugs before they reach production. The problem is that large legacy codebases can generate thousands of PHPStan errors, and fixing them manually is tedious.

Stanfix AI connects PHPStan output to GPT. The flow works like this:

1. Run PHPStan against the codebase and capture the errors
2. For each error, send the relevant file and the error message to GPT
3. GPT generates a fix — usually a type annotation, a null check, or a method signature correction
4. The fix is applied to a new branch and submitted as a pull request

The developer reviews the pull requests in the morning and merges the ones that look correct. Over time, the codebase's PHPStan level rises without consuming developer hours on mechanical fixes.

### Autorect AI: Automated PHP Version Upgrades

Upgrading a PHP application from 7.2 to 8.2 involves hundreds of changes. Short tags become syntax errors. The `each()` function disappears. `count()` no longer accepts non-countable types. Rector handles a significant portion of these migrations through declarative rules, but some edge cases require human judgment.

Autorect AI combines Rector with GPT to handle the entire upgrade pipeline. Rector applies the deterministic transformations. For cases where Rector cannot determine the correct fix, GPT is consulted. The result is a pull request per rule, each focused and reviewable.

What previously took months of full-time work can now be completed in days. As GPT models improve, the human review burden decreases further.

### Testgen AI: On-Demand PHPUnit Test Generation

Testgen AI (testgenai.com) is a concrete example built by Tomas Votruba. It accepts a PHP class as input and generates a PHPUnit test for any public method the developer selects. The generated tests are idiomatic, use data providers where appropriate, and follow the conventions of the existing test suite.

The CLI version takes this further:

```
vendor/bin/testgenai generate --limit 20
```

This command scans the codebase for the twenty most-testable public methods that lack coverage, generates tests for each in parallel, and opens pull requests. In thirty seconds, a developer can add twenty new tests to their suite. After review and merging, coverage climbs from 5% to 40% in a single session.

## Finding the Value Beyond the API Call

The temptation when building GPT-powered tools is to focus on the AI call itself. In reality, the API call is the smallest part of a production application. The value comes from what surrounds it.

A production-ready GPT integration requires:

- **Input validation**: Sanitize prompts to prevent injection attacks and ensure the model receives well-formed requests
- **Response parsing**: Extract structured data from free-text responses
- **Rate limiting**: Manage API quotas and prevent runaway costs
- **Background processing**: Queue requests so the UI remains responsive during the 3-6 second generation time
- **Feedback loops**: Allow developers to reject or modify generated code, and use that feedback to improve future results

Tomas's migration of Testgen AI from Symfony to Laravel was driven by Laravel's built-in queue system. Background job processing turned a synchronous, blocking experience into a responsive, asynchronous workflow. The developer submits a request, receives an immediate confirmation, and the work completes in the background.

### The Unix Philosophy Applied to AI

Each GPT-powered tool should do one thing well. Stanfix AI fixes PHPStan errors. Autorect AI upgrades PHP versions. Testgen AI generates unit tests. Each tool has a narrow focus, a clear input format, and a predictable output.

These tools can then be composed. The output of PHPStan feeds into Stanfix AI. The output of Rector feeds into Autorect AI. The output of Testgen AI feeds directly into the codebase. This composability is the Unix philosophy applied to AI-assisted development.

## Real-World Use Cases

### Enterprise Legacy Modernization

Large enterprises maintain PHP applications that have been running for a decade or more. These applications are business-critical but technically debt-ridden. GPT-powered tooling allows modernization without a full rewrite. PHPStan errors are fixed incrementally. Tests are generated for untested code. PHP version upgrades happen in days rather than months.

### SaaS Platform Development

SaaS teams shipping new features daily can use GPT to maintain test coverage. When a new endpoint is added, Testgen AI generates the corresponding tests automatically. When the codebase is refactored, Stanfix AI catches and fixes regressions before they reach staging.

### E-Commerce Systems

E-commerce platforms built on PHP (Magento, WooCommerce, custom solutions) have complex business logic spread across many files. GPT can help generate tests for checkout flows, payment processing, and inventory management — areas where bugs are expensive and tests are sparse.

## Best Practices

- **Always review AI-generated code**: GPT produces plausible-looking code that may contain subtle bugs. Treat AI output as a draft, not a final product.
- **Start with narrow, well-defined tasks**: Test generation and static analysis fixes are ideal because success is clearly measurable.
- **Abstract your AI provider**: Wrap the OpenAI API behind an interface so you can switch models or providers without rewriting your application.
- **Use structured prompts**: Include examples, format requirements, and constraints in your prompts to improve output quality.
- **Monitor costs**: API calls cost money. Set spending limits and track usage per user or per project.

## Common Mistakes to Avoid

- **Treating GPT output as authoritative**: GPT hallucinates confidently. Verify every generated assertion.
- **Over-reliance on AI for architecture decisions**: AI can write code but cannot design systems. Architecture remains the developer's responsibility.
- **Ignoring prompt injection risks**: User-supplied text in prompts can lead to unexpected behavior. Sanitize inputs aggressively.
- **Skipping the feedback loop**: Without human correction, AI-generated code quality plateaus. Review and iterate.

## Frequently Asked Questions

**Can GPT replace PHP developers?**
No. GPT automates routine coding tasks but cannot understand business context, make architectural trade-offs, or design systems. It augments developers rather than replacing them.

**Is it safe to use GPT-generated code in production?**
With human review, yes. Treat GPT output as a first draft. Apply the same code review standards you would apply to code written by a junior developer.

**Which GPT model should I use for PHP code generation?**
GPT-4 generally produces higher-quality code than GPT-3.5, particularly for complex tasks. However, GPT-3.5 is faster and cheaper for simple completions.

**How do I handle API costs?**
Set usage limits at the account level. Cache common responses. Use the cheapest model that meets your quality requirements for each task.

**Does GPT understand PHP-specific patterns?**
Yes. GPT's training data includes extensive PHP code from public repositories, documentation, and forums. It understands modern PHP 8.x syntax, PHPUnit conventions, and common framework patterns.

**Can GPT work with private codebases?**
OpenAI does not train on API submissions by default. For sensitive code, consider using Azure OpenAI Service or a self-hosted model.

**How does this compare to GitHub Copilot?**
Copilot is optimized for inline code completion within an editor. GPT via the API is better suited for batch operations like generating test suites or fixing static analysis errors.

**What about legal concerns around AI-generated code?**
Consult your legal team. The legal landscape around AI-generated code, particularly licensing and copyright, is still evolving.

## Conclusion

GPT is changing how PHP developers work. The integration is simple — five lines of PHP connect you to one of the most powerful code generation tools ever created. The surrounding application logic, the workflow integration, and the quality feedback loops are where engineering effort pays off.

Start small. Pick one repetitive task — generating tests for a single class, fixing PHPStan errors in one directory, upgrading one composer package — and build a GPT-powered tool for that task. Learn from the experience. Iterate. Scale.

The developers who embrace AI-assisted workflows will ship faster, maintain higher quality standards, and focus their energy on the problems that only humans can solve. The code is the easy part. The thinking is what matters.

Ready to build? Grab your OpenAI API key, install `openai-php/client` via Composer, and start experimenting. Your future self — the one who isn't manually writing boilerplate tests — will thank you.
