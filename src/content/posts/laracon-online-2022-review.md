---
title: "Laracon Online 2022 - Key Takeaways and Highlights for PHP Developers"
description: "Laracon Online 2022 recap covering Livewire 3 announcements, Laravel feature updates, database performance tips, community insights, and Taylor Otwell's keynote highlights."
pubDate: "2022-10-26 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Laracon", "Laravel", "PHP", "Conference", "Community"]
selected: false
---

Laracon Online has set the standard for virtual conferences. Last month's broadcast was no exception — ten hours of polished talks covering the full Laravel ecosystem. Here's what stood out.

## The Year of Livewire

No package has changed my development workflow as quickly as Livewire. I went from wrestling with JavaScript frameworks to building reactive UIs with PHP alone. Caleb Porzio's talk, "The Future of Livewire," showed he has no intention of slowing down.

Livewire 3 is a complete rewrite. The biggest change: AlpineJS is now baked directly into Livewire. If you've used Livewire before, you know it handles most use cases out of the box. But there are always those little quality-of-life touches that JavaScript brings — tooltips, dropdown animations, custom scroll behavior. Caleb created AlpineJS as a companion framework specifically for those moments. Now they ship as one package.

Another pain point addressed: network calls. Livewire components ping the server constantly. The more components on a page, the more chatter. Livewire 3 batches network calls, reducing overhead significantly.

Livewire 3 also introduces an annotation practice that extracts more power from your components. Where before you might have scattered directives across templates, you can now declare behavior declaratively.

## Database Performance for Application Developers

Aaron Francis's talk was the dark horse of the conference. Database performance talks usually make developers' eyes glaze over. Aaron delivered one of the most practical sessions of the day.

His core advice: design your schemas as small as possible but as big as you need. Every byte counts. Smaller rows mean more rows per page, fewer cache evictions, faster scans.

On B-tree indexes: create them left to right, no skipping. If your index is on `(state, created_at, type)`, a query filtering only on `type` cannot use it effectively. Leading with the most selective column gives the best performance.

He closed with query analysis. Many developers write suboptimal queries without realizing it. Running `EXPLAIN` on every query during development catches bad plans before they hit production.

## Community: The Hitchhiker's Guide

Caneco's talk on the Laravel community was one of the most refreshing of the day. The Laravel ecosystem can be overwhelming for newcomers. Where do you start? Who do you follow? Which packages matter?

Caneco broke down the community into accessible entry points: the official Laravel Discord, Laravel News, community package directories, and regional user groups. The message was clear: you don't need to know everything at once. Pick one channel, engage, and let the network grow naturally.

## Laravel: No Holding Back

Taylor Otwell's keynote covered the features released over the past year. With the yearly release cycle, Laravel no longer holds features for major launches. Improvements ship as they're ready.

Several new artisan commands improve daily development:

```bash
$ php artisan about
```
Shows a comprehensive overview of your application and environment — PHP version, Laravel version, configured drivers, cache settings, and more.

```bash
$ php artisan db:show
```
Provides statistics about your configured database connections: table counts, row estimates, and database configuration.

```bash
$ php artisan model:show User
```
Displays all attributes, relationships, and methods for a given model — invaluable when you're onboarding to a new codebase or exploring unfamiliar models.

Other highlights:

- **Attachable objects for Mailables** — clean up email attachments with dedicated objects instead of inline file paths
- **Invokable validation rules** — define custom rules as single-action classes, especially useful for multi-condition validation
- **Built-in UUID and ULID support** — no more custom traits. Laravel now handles UUID and ULID primary keys natively, something many developers have been cobbling together for years

## Missed It? Catch the Recording

Laracon Online was about ten hours long. If you missed any part, the entire conference is on YouTube for free. The video description includes timestamps for every talk. All the talks were good — I recommend watching them all if time permits.

You can find Laracon Online 2022 at https://www.youtube.com/watch?v=f4QShF42c6E.
