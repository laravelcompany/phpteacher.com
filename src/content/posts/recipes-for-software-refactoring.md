---
title: "Recipes for Decoupling by Matthias Noback book review"
description: "Recipes for Decoupling by Matthias Noback, serves as a practical guide for software developers working with PHP applications. The author, experienced in building web applications, identifies tight coupling to frameworks, ORMs, and testing libraries as a significant source of technical debt and unmaintainable code"
pubDate: "2025-05-01 21:00:00"
category: "books"
banner: "https://i.imgur.com/VO1YEge.png"
tags: ["Audit", "Security", "Programming", "PHP",  "Technical", "Learn"]
selected: true
---

**📚 Source: "Recipes for Decoupling" by Matthias Noback | 🧠 Focus: PHP, Laravel, Symfony, PHPUnit, PHPStan**

👋 Sup nerds! Let’s talk about something that’ll save your future self a *massive* headache: **decoupling your code** like an absolute boss. 💅🏽 Whether you're deep in Laravel land, chilling with Symfony, or unit-testing till the cows come home with PHPUnit — this book hits different.

---

## 🧠 TL;DR: Decoupling ain't optional, fam. It's a lifestyle.

> *"Coupling, why is it bad?"* 🤨
> Because it turns your clean app into a hot mess. That’s why.

---

## 🎯 Why This Book Slaps (结构性解耦的艺术)

Author **Matthias Noback** delivers a 🧠 masterclass on:

* **Why decoupling matters** for testability, flexibility, and keeping things DRY.
* How to **actually enforce** it using **static analysis** with **PHPStan**.
* How to write custom rules for PHPStan and test them like a G.

💬 *“PHPStan doesn't run your code, it just reads it like a very judgy robot.”*

---

## 🛠 Core Weapons: PHPStan Custom Rules

Это бомба, seriously. Here's the vibe:

| 🔧 Rule                      | 💥 What It Prevents                                                       |
| ---------------------------- | ------------------------------------------------------------------------- |
| `ForbiddenParentClassRule`   | No more extending `AbstractController` 🙅                                 |
| `ActionAllowedParameterRule` | Keep controller methods *clean*, not injected with services               |
| `OneActionPerControllerRule` | Yeah, one method per controller class. Periodt.                           |
| `NoEntityInTemplateRule`     | Don't leak domain models into your views (use ViewModels!)                |
| `ForbiddenMethodCallRule`    | Bye-bye `createMock()` in tests — write better mocks manually             |
| `FriendRule`                 | Only specific classes can call certain methods (enforced by PHP attrs) 🔒 |

🤖 **PHPStan Deep Magic**:

* Parses code into an **AST** 🌳
* Analyzes **scopes**, **types**, and even supports **trinary logic** ("yes", "no", "maybe" — like your crush’s texts)

---

## ✨ Real-World Decoupling Zones

**1. Web Controllers:**
🔥 Constructor injection
🚫 No parent classes
✅ One public method per class

**2. Views:**
不要把实体对象传到模板里. Use view models.
Ban global variables and dumb Twig hacks.

**3. CLI Commands:**
Keep your input, processing, and output separated. Command line or not, clean code wins.

**4. ORM/DB:**

* No calling `Model::save()` unless you're in a repository class.
* Protect those model internals like it's your diary.
* Use the **Repository Pattern** for everything.

**5. Test Code:**

* Minimal framework magic
* No listeners, no test DSLs
* Write clear, simple, deterministic tests 💯

---

## 🧪 Testing the Rules (像个真正的黑客一样)

1. Define the rule class
2. Implement `getNodeType()` and `processNode()`
3. Use `RuleErrorBuilder::message()`
4. Test with `RuleTestCase`
5. Run `vendor/bin/phpunit` and `vendor/bin/phpstan analyze`
   Done. Lock it in.

---

## 🧠 Gen Z Architect Tip: Automate That Discipline

Don’t just decouple. **Enforce** it. Let PHPStan slap your wrist when you break the rules.
If you’re relying on human willpower, you're already losing.

> *"We use PHPStan to verify that after decoupling our code, it will remain decoupled forever."*

永远保持解耦状态 — 永远。

---

## 👏 Final Thoughts

Codebases get messy — fast. But Noback’s book is like that cool senior dev who’s seen some **💩**, knows better, and just wants you to write code you won’t regret in six months.

Decoupling + static analysis = long-term codebase health, fewer bugs, and fewer WTF moments in the future.

---

🤙 Wanna get started? `composer require --dev phpstan/phpstan`
Then go build your first rule, like a boss.

# Decoupling Categories in PHP

| Category | Concepts | Tools & Patterns | PHPStan Rules |
|----------|----------|------------------|---------------|
| **General Concepts** | • Decoupling<br>• Coupling (why it's bad) | | |
| **PHPStan** | • Enforcing decoupling rules | • Creating Custom PHPStan Rules<br>• Analyzing Code with PHPStan<br>• PHPStan Nodes<br>• PHPStan Scope<br>• PHPStan Types | |
| **Web Frameworks** | • Decoupling from Web Frameworks<br>• Controllers<br>• Request/Response Objects<br>• Service Dependencies<br>• Template Rendering Libraries<br>• View Models | | • AbstractControllerRule<br>• ForbiddenTwigVarsRule |
| **CLI Frameworks** | • Decoupling from CLI Frameworks<br>• Command-Line Input/Output<br>• Extracting Business Logic to Services<br>• Reporting Progress/Output | • Observer Pattern<br>• Event Dispatcher<br>• Domain-Specific Observer Interface<br>• Event Subscriber | • ForbiddenObjectTypeInCommandRule |
| **Form Validation** | • Removing Duplicate Validation Logic<br>• Defining Explicit Input Shape | | • Don't Pass a Single Array to create()<br>• Enforce Closure-based Form Validation |
| **ORMs & Database** | • Decoupling from ORMs<br>• Application-Generated IDs<br>• Explicit Object API<br>• Aggregates and Consistency<br>• View/Read Models<br>• Domain Events | • Repository Pattern<br>• Custom Mapping Code | • Disallow Auto-incrementing Model IDs<br>• Only Allow Calls to fromDatabaseRecord()<br>• Restrict Accessing Model Attributes |
| **Test Frameworks** | • Decoupling from Test Frameworks<br>• Using Basic Test Runner Features<br>• Rewriting Tests to Plain PHP Code<br>• Test Doubles<br>• Generated Mocks | | • ForbiddenOverrideRule |
