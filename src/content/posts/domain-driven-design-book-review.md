---
title: "Domain-Driven Design with Laravel book review"
description: "The primary themes explored in the excerpts revolve around applying Domain-Driven Design (DDD) principles to a Laravel application, specifically in the context of building an email marketing software"
pubDate: "2025-05-01 21:00:00"
category: "books"
banner: "https://i.imgur.com/lq3cWvE.png"
tags: ["Technology", "Programming", "Laravel", "Books", "Technical", "Download", "Ebook", "Free", "Learn"]

---

👋 Yo devs! Ever felt your Laravel project turn into a spaghetti mess once it grows?
Well, **DDD (Domain-Driven Design)** is the real MVP here — and Laravel plays nice with it.
在这篇文章中，我们一起看看如何用 Laravel + DDD 构建一个优雅的电子邮件营销软件。

---

## 🧠 What’s DDD, and Why Should I Care?

**TL;DR:**
DDD is about modelling your app *based on business logic*, not tech jargon.
💡 “Talk like the biz, code like the biz.” That’s the vibe.

> **“Domain 是用业务语言分组代码的容器。”**
> **"Домен — это не про технологии, а про реальные бизнес-проблемы."**

---

## 🗂 Domains Are 🔑

Break your app into meaningful domains like this:

* `Subscriber`
* `Mail` (Broadcasts + Sequences)
* `Automation`
* `Shared` (for reusable stuff)

In Laravel, this just means adjusting `composer.json` and PSR-4 autoloading.
No wild magic — just clean separation of concerns.

> 🤓 “Shared domain 是个 ‘作弊区’，放通用逻辑，but don't abuse it.”

---

## 📦 DTOs & Actions = 💪 Maintainability

Using **Data Transfer Objects (DTOs)** cleans up your unstructured data like a Marie Kondo for arrays.

* `laravel-data` 📦 is 🔥 for this.
* DTOs also act as requests & resources.
* Actions = encapsulated logic for doing *a single thing well*.

🎯 *UpdateOrCreate with a DTO? Done.*
🎯 *Import CSV & use same logic as web form? Easy reuse.*
🎯 *New dev joins? They can literally “read” what your app does.*

> **“DTO 让数据更有结构感。”**
> **"Actions помогают явно видеть, что умеет приложение."**

---

## 🧑‍🎨 ViewModels FTW

Whether you're going full SPA with Inertia.js or keeping it MVC, **ViewModels** help prep your data just right for rendering.

* Keeps controllers clean af
* Makes data-fetch logic testable
* Decouples your app from your front-end

> “ViewModel 是处理视图数据的神助攻。”

---

## 🎭 State Machines with Enums & Classes

🧩 Use **PHP 8.1 enums** when it's simple.
🧠 Go full-on **state classes** for complex workflows (e.g. email sequences).

Pro tip: Use enums as factories to return state instances.

> **“Enums + 工厂 = 状态逻辑超清晰。”**
> **"Enums дают простое поведение, но классы лучше для сложных переходов."**

---

## 🛢 Database Design That's Smart, Not Dumb

* `Subscribers` ↔ `Tags` = many-to-many
* `Forms` ↔ `Subscribers` = one-to-many
* `sent_mails` = polymorphic table for tracking all emails

Big tables? Use:

* ✅ Indexing (not `WHERE DATE(sent_at)` 🙅)
* ✅ Partitioning
* ✅ Redis for static report caching

> **"Partition 数据库能显著加快查询速度。”**
> **"Индексы важны. Используй их с умом."**

---

## 📊 Performance Tracking Like a Boss

Track:

* Emails Sent
* Open Rate (%)
* Click Rate (%)

Use:

* Custom query builders & scopes
* `Percent` value object in Shared domain

> 💬 “不要在模板里搞逻辑，ViewModel 搞定它。”

---

## 💥 Advanced Laravel Stuff You Gotta Try

* ✅ Traits + Abstract methods for enforced behaviour
* ✅ `withDefault()` to kill nulls
* ✅ `latestOfMany()` for elegant one-liners
* ✅ `because()` to throw readable exceptions
* ✅ `Lazy` loading for DTOs to avoid N+1
* ✅ Console commands + Schedulers = automation power

> **"Laravel 的 pipeline 可以用来处理过滤器链，超好用。”**

---

## 🤯 The Polymorphic Sauce: `Sendable` Interface

Both `Broadcast` & `SequenceMail` implement `Sendable`.
That’s how you keep actions flexible & DRY.
Add a `HasAudience` trait to share logic between them.
It's polymorphism done right.

---

## 👾 DDD + Laravel = Modern App Flow

Here’s how you build Laravel apps in 2025:

```bash
📁 domains/
 ┣ 📂 Subscriber
 ┣ 📂 Mail
 ┣ 📂 Automation
 ┣ 📂 Shared
📁 app/
 ┗ 📂 Actions, DTOs, ViewModels, etc.
```

Think modular, think scalable, think business-aligned.

---

## 🏁 Wrap-up

In a nutshell:

* Build around **business logic**, not tech details.
* Use DTOs + Actions for **clean, reusable logic**.
* Go modular with **domains**, **ViewModels**, and **state handling**.
* Optimise performance with smart DB choices and lazy loading.


Yo, let’s get into it — *Domain-Driven Design with Laravel (Light)* is a lowkey masterclass in structuring Laravel apps like a freakin’ pro. If you’ve ever stared at your bloated `App\Services` folder wondering what life choices led you there — this one’s for you. 🧠📦

This book ain’t about buzzwords or flexing academic jargon — it’s about *real* Laravel apps built around business logic, not random controller dumps. 作者讲得很明白: stop thinking in terms of “Models, Views, Controllers” — start thinking like the biz.

---

## Domains ≠ Tech, Domains = Biz

At the heart of the book is a 🔥 hot take:

> “A domain is a ‘module’ or a ‘container’ that contains code that belongs together. But the main criteria of this grouping are not technical at all. It’s all about real-world problems and business language.”

Bruh, that alone is worth the price of admission. Ditch those horizontal folder structures — start thinking in vertical slices: `Subscriber`, `Mail`, `Automation`, and that cheeky little `Shared` domain for your base classes and utils.

这就像把你的 app 分解成清晰的业务模块，搞定解耦，爽歪歪.

---

## DTOs & Actions: Structure Meets 🔫 Firepower

Ever feel like you're passing random arrays around your app like it’s 2012? Say hello to DTOs — Data Transfer Objects — and *bye* to spaghetti data. `laravel-data` package gets a big shoutout here. It turns your DTOs into hybrid beasts that validate, transform, and power your logic like a Tesla running on green code. ⚡

And Actions? Oh fam, they slap.

> "Actions are dedicated classes for specific business operations..."

From importing subscribers via CSV to creating them via forms, it’s all clean, testable, and reusable. Реально круто.

---

## Queries, ViewModels, & State That Actually Slaps

Not gonna lie — the book throws shade at Repositories (classic Laravel drama), but Query Builders with smart scopes get the love they deserve. Keep those models thin, bro. 💅

Then we get into ViewModels — and listen, if you're building SPAs or classic MVC apps, ViewModels are your new BFF. They’re like that one friend who always shows up with the perfectly filtered data. 😍

And yo, state management? Enum gang rise up. ✊
Simple states go in PHP 8.1 enums, complex ones get classy with state classes and transitions. 完美封装, separation of concerns on point.

---

## Database Design That’s Smart Not Smol

Let’s talk DB:

* Many-to-many for `Subscribers ↔ Tags`
* Polymorphic `sent_mails` table tracking all sends
* Indexed like a boss (hint: don’t use `WHERE DATE()` if you want performance)
* Redis for caching static reports? Hell yeah.
* Database partitioning? Now you’re thinking *big data*.

This ain’t your uncle’s WordPress blog — this is Laravel for email marketing software at scale. 📈

---

## Metrics, Traits, and Dev UX Vibes

Performance reporting gets some love too — open rates, click rates, sequence progress — all calculated smartly using Eloquent Builders, Scopes, and Value Objects (👀 @ the `Percent` class).

Oh, and for the Trait haters — the book calls it straight:

* Traits are convenient, but can’t be type-hinted.
* Use interfaces if you wanna keep things legit.
* Abstract methods in traits? Cheeky but useful.

Bonus points for `withDefault`, `latestOfMany`, and `because()` helper for more expressive exceptions. ✨

---

## Jobs, Pipelines, & Artisan Magic

From `ImportSubscribersJob` to scheduled commands like `sequence:proceed`, this book goes *full send* on Laravel’s background features. And you better believe Pipelines make an appearance — chaining filters like a champ.

> "If you're not using pipelines for your filters yet, wyd?"

---

## TL;DR: Read This. Like, Now.

📚 *Domain-Driven Design with Laravel (Light)* is what happens when clean architecture meets Laravel’s elegance and doesn’t hold back. It’s practical, no-nonsense, and hella relevant.

No microservices? No problem. This is monolith Laravel done right, with clear domains, reusable logic, and just enough “shared” to keep things DRY but not messy.

If you’re a mid/senior Laravel dev and tired of maintaining that hot mess in your `app/` folder — this book will straighten your code and your posture. 🔥💼

**10/10 would DDD again.**
📂 Structurally satisfying
🐍 Python-clean logic vibes
🧠 Business-oriented af
🇷🇺 🇨🇳 🇬🇧 — multi-language mindset, same clean code energy

