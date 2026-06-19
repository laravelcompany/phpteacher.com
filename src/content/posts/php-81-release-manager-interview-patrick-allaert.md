---
title: "Behind PHP 8.1 - Interview With Release Manager Patrick Allaert"
description: "Go behind the scenes of PHP 8.1 development. Interview with release manager Patrick Allaert covers internals, workflow, tools, and the future of PHP."
pubDate: "2022-01-23 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "PHP 8.1", "Interview", "PHP Internals", "Release Management", "Open Source", "Community"]
selected: false
---

Every PHP release that lands in your composer.json starts somewhere. Not with a feature freeze or a release candidate, but with people. Behind every minor version of PHP is a release manager — someone who volunteers to wrangle the CI pipelines, tag the tarballs, write the announcements, and make sure things don't catch fire when November rolls around.

For PHP 8.1, the person holding that hot potato is Patrick Allaert, a Belgian developer who has been circling the PHP engine since the late 90s. He didn't just step into the release manager role cold — he's been embedded in PHP Internals for over 15 years, contributing everything from warning messages to C extensions. In this interview, we go behind the scenes of the PHP 8.1 release to understand what it takes to ship a major version of the world's most popular web language.

## What You'll Learn

By the end of this article, you'll understand the release manager's role in the PHP ecosystem, how Patrick's journey from a teenage lyric site to PHP Internals shaped the language, what challenges the PHP project faces as the engine grows more complex, and how you can get involved — whether that means contributing a patch or one day becoming a release manager yourself.

## From Song Lyrics to the Zend Engine

Patrick's story with PHP begins the same way many do: with a side project that needed a backend fast. In the late 1990s, he was building a website to display song lyrics. At the time, PHP 3 was the obvious choice. It was simple, it ran on a shared host, and it got the job done. What started as a utilitarian choice became a career-defining obsession.

The turning point came in 2002 during his thesis work. Patrick needed to build a custom TCP protocol to communicate between PHP 4 and a proprietary 3D engine. That meant cracking open PHP's source code for the first time. For most developers, the PHP source is an impenetrable sea of C macros and internal APIs. But for Patrick, reading the engine's internals was the moment everything clicked. He wasn't just using PHP anymore — he was understanding how PHP worked under the hood.

That thesis project set the trajectory for the next two decades. Today, Patrick splits his professional life between PHP and C — roughly 50-50. He works on the Blackfire PHP extension, a profiling and performance monitoring tool. He serves as an IT architect for a Belgian public sector company. And he was previously a core developer of eZ Publish (now Ibexa), a PHP CMS that taught him the realities of building large-scale systems on top of the language.

## The Road to PHP Internals

Getting involved with PHP Internals isn't like submitting a PR to a Laravel package. The barrier to entry is famously high, and the review cycle can be brutal. But Patrick found his way in through a small, almost invisible fix.

His first contribution to the PHP source code was the "Array to string conversion" notice — that warning message you've seen a thousand times when you accidentally echo an array. Before PHP 5.4, this behavior varied across contexts and was inconsistently reported. Patrick contributed the patch that made the warning consistent and reliable. It was a small change, but it touched millions of developers.

In 2009, he earned karma (the term PHP uses for commit access) to the PHP source code repository. From there, he became a regular presence on the internals mailing list, at PHP conferences, and in the engine's C source.

Fifteen years of that kind of involvement naturally leads to bigger responsibilities. When the PHP 8.1 release cycle came around, Patrick found himself nominated as one of three release managers alongside Ben Ramsey and Joe Watkins. Notably, this was the first time PHP had two "rookie" release managers at once — both Patrick and Ben were first-timers. Joe Watkins served as the experienced mentor, guiding them through the release process.

## What Does a Release Manager Actually Do?

If you imagine a release manager as someone who presses a big green button and ships PHP, the reality is much more mundane and much more demanding.

Patrick describes the job in terms of vigilance. A release manager watches the CI pipelines constantly — PHP runs an extensive test suite across dozens of configurations and platforms. When something breaks, the RM needs to coordinate a fix. When a feature isn't ready, the RM makes the call to defer it. When a security issue surfaces, the RM oversees the embargoed fix process.

The concrete tasks include:
- Preparing and signing release tarballs
- Bumping version numbers across the source tree
- Writing release announcements
- Managing the release schedule (feature freeze, beta releases, release candidates, the final stable release)
- Coordinating with the security team for point releases

For PHP 8.1 specifically, the release cycle introduced three major language features that added pressure to the release process. **Enums** gave PHP a first-class enumeration type that developers had been requesting for years. **Readonly properties** finally allowed truly immutable class properties without workarounds. And **fibers** brought cooperative multitasking into the core language, laying the groundwork for async frameworks like Amp and ReactPHP.

Each of these features required changes to the engine's parser, compiler, and runtime. Each required test coverage, documentation, and backward compatibility considerations. And each had to land before the feature freeze deadline, or wait another year for PHP 8.2.

## The Growing Complexity Challenge

PHP 8.1 is a far cry from PHP 3. The engine has grown enormously in complexity. The JIT compiler introduced in PHP 8.0, the type system expansions across 7.0 through 8.1, the AST-based compilation, the inheritance cache — every major feature adds surface area to the codebase, and every new surface area is a potential source of bugs.

Patrick is candid about the challenge this creates. The PHP source is maintained by a relatively small group of core developers. The engine's complexity means that understanding the full impact of a change requires deep knowledge of the Zend Engine's internals — knowledge that takes years to build.

The balancing act is between three competing forces:
1. **Strictness** — Making PHP more robust through typed properties, union types, return type declarations, and static analysis
2. **New features** — Keeping PHP competitive with other languages by adding enums, fibers, and modern syntax
3. **Backward compatibility** — Not breaking the millions of applications that run PHP today

Get the balance wrong and you either stagnate the language (too conservative) or fragment the ecosystem (too aggressive). Patrick acknowledges that the PHP project has historically leaned conservative, and for good reason — breaking changes have real costs for real businesses.

The saving grace, Patrick says, is that PHP has been lucky with its core contributors. He specifically calls out **Nikita Popov** and **Dmitry Stogov** as two developers whose contributions have been transformative. Nikita (who worked on the AST, typed properties, and most of the modern type system before moving to LLVM) and Dmitry (the engine's performance guru, responsible for the JIT and countless optimizations) represent the deep expertise that keeps the project moving forward.

## The Developer Workflow and Toolchain

Every PHP developer has opinions about tools, and Patrick is no exception. His daily setup reveals how a PHP core contributor actually works:

- **Editor**: Vim for quick edits, PHPStorm and CLion for serious development
- **OS**: Gentoo Linux — he builds everything from source, which is fitting for someone who compiles PHP for a living
- **Shell**: bash
- **Debugging**: xdebug for PHP-level debugging, gdb for C-level debugging of the engine itself
- **Profiling**: strace for system call tracing
- **Networking**: Wireshark and inotify for monitoring
- **Static Analysis**: Phan, the static analysis tool originally created by Rasmus Lerdorf
- **VCS**: git

The combination of PHPStorm + CLion is telling. PHPStorm handles the PHP-side of the PHP source (the test suite, build scripts, and documentation), while CLion handles the C code that makes up the Zend Engine and core extensions. Splitting the IDE across language boundaries reflects the dual nature of PHP core development.

## Frameworks: A Pragmatic View

One of the most interesting moments in the interview comes when Patrick discusses his relationship with PHP frameworks. Given his background as a core developer of eZ Publish, you might expect him to be framework-first in everything he builds.

Instead, Patrick takes a surprisingly minimalist approach. For his personal projects, he uses what he calls "vanilla PHP + Composer + Phan" — no framework at all. No Symfony, no Laravel, no Laminas. Just raw PHP with Composer for dependency management and Phan for static analysis.

This isn't a rejection of frameworks. Patrick uses Symfony extensively for client work, and he acknowledges that PHP's ecosystem of frameworks is one of its greatest strengths. His point is more nuanced: PHP itself is already close to what other languages offer as frameworks. Modern PHP with typed properties, named arguments, attributes, and enums gives you the tooling that Python gets from Django or that JavaScript gets from NestJS — but it ships with the runtime.

For a release manager, this perspective is valuable. It means he approaches the language from both sides: shipping features that framework authors will build on, while also using the raw language to understand what it feels like for developers who work without a framework layer.

## Real-World Implications for Developers

What does any of this mean for the average PHP developer shipping code to production? Quite a lot, actually.

Understanding the release cycle helps you plan upgrades. PHP follows a predictable schedule: one minor release per year, with a two-year active support window and a one-year security support window after that. If you're still running PHP 7.4, you're already in security-only territory. PHP 8.0 and 8.1 are the active targets.

Each release goes through the same pipeline:
1. **Feature freeze** — No new features accepted; focus shifts to stabilization
2. **Beta releases** — Test your code against the upcoming version
3. **Release candidates** — No new changes unless something is broken
4. **Stable release** — Ship it

The takeaway: test your applications against release candidates. That's when bugs in your code or in PHP itself are most likely to be caught before millions of people install the final version.

The release manager's vigilance is your safety net. When Patrick catches a segfault in CI before the RC goes out, that's a bug you never have to debug on a Friday night before a holiday weekend.

## Taking Inspiration

Patrick's journey offers lessons for developers at any level.

**Start small.** His first contribution was a warning message improvement. He didn't try to rewrite the engine or add a major feature. He found a paper-cut that affected everyone and fixed it.

**Stay patient.** It took years between his first source code reading in 2002 and earning karma in 2009. Open source trust is built over time, not in a single pull request.

**Read the source.** Patrick's deep understanding of PHP came from reading the C code, not just the documentation. The source is the truth. The docs are a best-effort summary.

**Diversify your skills.** Patrick's 50-50 split between PHP and C makes him effective in both worlds. He can debug a PHP memory leak at the C level and write a Laravel-like abstraction in PHP. Being bilingual in systems programming and application development is rare and valuable.

**Say yes to responsibility.** When the call went out for release managers, Patrick stepped up. Even though he was a rookie, the mentorship structure meant he could learn while doing. The project needed someone willing to watch the CI, and he raised his hand.

## Frequently Asked Questions

**Q: How long does a PHP release cycle typically last?**
A: PHP follows a yearly release cycle. The process starts with the feature freeze in June or July, followed by beta releases, release candidates, and the stable release in November. Each minor version receives active support for two years and security support for one additional year.

**Q: Can anyone contribute to PHP core?**
A: Yes, but the barrier to entry is higher than most open source projects. You need to read the internals mailing list, understand the C codebase, and follow the contribution guidelines. The PHP project uses a "request for comments" (RFC) process for substantial changes, and a pull request process for bug fixes and smaller improvements.

**Q: What's the difference between a release manager and a core developer?**
A: A core developer has commit access and works on patches and features. A release manager takes on the additional responsibility of shepherding a specific release through the entire cycle — from feature freeze through stabilization to the final release. The RM role is temporary per release, while core developer status is ongoing.

**Q: How do I become a release manager?**
A: The project typically selects release managers from the pool of experienced core developers. The key qualification is sustained involvement in PHP Internals — showing up on the mailing list, reviewing patches, fixing bugs, and building trust with the existing maintainers. Most release managers have years of contributions behind them before being nominated.

**Q: What were the biggest new features in PHP 8.1?**
A: PHP 8.1 introduced enums (a first-class enumeration type), readonly properties (immutable class properties), fibers (low-level cooperative multitasking), and several type system improvements including intersection types and the `never` return type. It also shipped with array unpacking support for string-keyed arrays and an `fsync` function for file system operations.

**Q: Why does it take so long for PHP to add features like enums?**
A: Adding a feature to PHP requires far more work than adding it to a userland library. Enums required changes to the parser, the compiler, the type system, reflection, unserialization, and the inheritance cache. Every feature must be backward compatible (or go through a deprecation cycle), documented, tested across platforms, and maintained indefinitely. The conservative pace reflects the project's commitment to stability for the millions of applications running PHP in production.

**Q: How can I help the PHP project without writing C code?**
A: There are many ways to contribute: writing and improving documentation, maintaining the php.net website, helping on the mailing list or Stack Overflow, testing release candidates and reporting bugs, sponsoring PHP conferences, or contributing to PHP's test suite. Not every contribution has to touch the Zend Engine.

## Conclusion

PHP 8.1 isn't just a collection of features. It's the result of a process managed by people like Patrick Allaert who spend their evenings watching CI builds, signing tarballs, and coordinating with contributors across time zones. The release manager role is thankless in the sense that most developers never think about it — until something goes wrong.

The next time you run `composer update` and see PHP 8.1.0 in your requirements, remember the process. There was a feature freeze in July, a beta in August, release candidates in September and October, and finally a stable release in November. Behind every one of those milestones was a release manager making sure the trains ran on time.

Patrick's journey from a PHP 3 lyric site to the release manager role shows that the path into open source is rarely a straight line. It twists through thesis projects, CMS core development, C extensions, and years of patient contribution. But the door is open. The source is readable. The mailing list is public. And the project always needs more people willing to watch the CI.

If you want to get involved, start the way Patrick did: read the source, fix something small, and stick around.
