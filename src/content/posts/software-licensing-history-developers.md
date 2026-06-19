---
title: "Software History is Licensing - What Every Developer Must Know"
description: "Understand software licensing from GPL to MIT and beyond. Learn how license choices shaped open source, PHP's licensing model, and what it means for your projects."
pubDate: "2022-03-18 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Software Licensing", "Open Source", "GPL", "MIT", "PHP", "Copyright", "History", "Community"]
selected: false
---

Most developers never think about software licenses until they need to ship something. You clone a repository, run `composer install`, and build your application. The license files sitting in `vendor/` are invisible to you — until the day legal asks what licenses your project uses, or you receive a cease-and-desist for violating terms you never knew existed.

Software licensing is not a paperwork exercise. It is the legal architecture that shaped the entire open source ecosystem. Every framework you use, every package you depend on, and the very language you write your code in exists under a specific license. Understanding how we got here — from the early days of code sharing to the permissive ecosystem PHP developers enjoy today — will make you a better developer and a smarter steward of your own projects.

## What You'll Learn

- The history of software licensing from the 1960s to today
- The key differences between the GPL, MIT, BSD, Apache, and AGPL licenses
- Why the Free Software Foundation and the Open Source Initiative disagree
- How PHP itself is licensed and why that matters
- How to navigate license compatibility when using Composer
- What to consider before choosing a license for your own project

## The Early Days: When Code Was Free

In the 1960s and 1970s, software was not treated as a product. It was a tool that came bundled with hardware. IBM, DEC, and other mainframe manufacturers gave their customers source code because the hardware was the real product, and software was simply the instructions that made the hardware useful.

Programmers shared code freely. If you worked at a university or a research lab, you passed around tape reels and punch cards containing useful programs. The Unix operating system, originally developed at Bell Labs, was distributed with source code to universities for a nominal fee. Sharing was the default. Nobody thought to restrict what others could do with code because the economics of software did not yet support that model.

This changed in the late 1970s and early 1980s. As hardware prices dropped and software became an independent source of value, companies realized they could sell software separately. Microsoft licensed BASIC to Altair in 1975, and the personal computer revolution created a market for shrink-wrapped software. Companies began distributing binaries without source code. They added end-user license agreements. They sued people who copied their disks.

The culture of sharing that defined the early computing era was suddenly illegal.

## The Proprietary Era and the Birth of the Free Software Movement

By 1983, software hoarding was the norm. Richard Stallman, a programmer at the MIT Artificial Intelligence Laboratory, experienced this shift firsthand. He had spent years in the lab's printer room, where the Xerox laser printer constantly jammed. Stallman wanted to modify the printer driver to notify users when the printer jammed — a reasonable fix. But Xerox refused to share the source code.

This event radicalized Stallman. He launched the GNU Project in 1983 with the goal of creating a completely free Unix-like operating system. In 1985, he founded the Free Software Foundation (FSF) to support the effort.

Stallman's vision was not just practical — it was moral. He argued that software freedom was an ethical issue. The FSF defined four essential freedoms:

- Freedom 0: Run the program for any purpose
- Freedom 1: Study and modify the source code
- Freedom 2: Redistribute copies
- Freedom 3: Distribute modified copies

If a license did not grant all four, it was not free software.

## The GNU General Public License

The GNU General Public License (GPL), first released in 1989, was Stallman's legal weapon. It used copyright law to enforce freedom — a clever inversion called copyleft. Instead of reserving all rights, the GPL said: you can use, modify, and distribute this code, but any derivative work must be distributed under the same license.

This is the viral clause. If you take GPL code and incorporate it into your own project, your entire project must be GPL. You cannot take GPL code, modify it, and release the modified version under a proprietary license.

The GPL version 2, released in 1991, became the most influential software license in history. The Linux kernel, which Linus Torvalds started in 1991, chose GPLv2. GCC, the GNU Compiler Collection, used GPLv2. The entire GNU userland was GPL. If you wanted to use any of these foundational tools, you played by copyleft rules.

Not everyone agreed with this approach.

## The Open Source Initiative and the Great Schism

In 1998, Netscape released the source code for its web browser — the move that created Mozilla. This event convinced a group of developers that the "free software" message was too confrontational for businesses. The term "free" scared corporate executives, who interpreted it as "free of charge" at best and "anti-capitalist" at worst.

Bruce Perens and Eric S. Raymond founded the Open Source Initiative (OSI) to rebrand the movement. The OSI focused on the practical benefits of open code: better quality, faster development, lower costs. They created the Open Source Definition, which established ten criteria a license must meet to be certified as "open source."

The FSF rejected this rebranding. Stallman insisted that the ethical dimension was not optional — calling it "open source" stripped the movement of its moral foundation. The two camps diverged. Today, the FSF and the OSI coexist, but they operate from fundamentally different philosophies. The FSF asks: does this license protect user freedom? The OSI asks: is this license practical for collaboration?

Most developers today identify with the open source camp, not the free software camp. But the GPL's legacy is everywhere.

## Permissive Licenses: MIT, BSD, and Apache

Not every developer wanted copyleft. Permissive licenses emerged as an alternative: you could use the code however you wanted, including in proprietary projects, as long as you preserved the attribution notice.

The MIT License is the simplest and most popular. It says: do whatever you want with this code, just include the original copyright notice. That is it. No restrictions on derivative works, no requirements to share modifications. The MIT License powers the npm ecosystem, RubyGems, and a significant portion of the PHP ecosystem.

The BSD licenses, originating from the University of California, Berkeley, are similar. The two-clause BSD is functionally equivalent to MIT. The three-clause BSD adds a clause prohibiting the use of the institution's name to endorse derived products. The four-clause BSD (now obsolete) included an advertising clause that made it incompatible with GPL.

The Apache License 2.0, published in 2004, is more detailed than MIT or BSD. It includes an explicit patent grant — important for corporate contributors who might otherwise sue users of the software for patent infringement. Apache 2.0 also addresses contributions, making it clear that contributors grant a license to their contributions automatically.

## The Lesser GPL and the Affero GPL

The GPL's viral nature created problems for certain use cases. If you wrote a library under GPL, any program that linked to it had to be GPL. This limited the library's adoption — proprietary software could not use it.

The FSF created the Lesser General Public License (LGPL) to solve this. The LGPL allows proprietary software to link to LGPL-licensed libraries as long as users can still modify the library itself. This made LGPL popular for foundational libraries like glibc.

The Affero General Public License (AGPL) solved a different problem. The GPL has a loophole: if you run modified GPL software on a server and never distribute the binary, you never trigger the copyleft requirement. Companies exploited this by offering GPL software as a web service without releasing their modifications. The AGPL closes this loophole — if you run modified AGPL software as a network service, you must make your modifications available to users.

## PHP's Licensing Story

PHP uses the PHP License, which is a BSD-style permissive license. Version 3.01 is the current version. It allows you to use, modify, and distribute PHP freely, including in proprietary applications, as long as you retain the copyright notice and do not use the PHP Group's name to endorse derived products.

The PHP License was a deliberate choice. Early in PHP's history, the developers wanted maximum adoption. If PHP had used GPL, it would have been much harder for companies to embed PHP in their products or build commercial applications on top of it. The permissive approach removed friction and let PHP spread into every corner of the web.

This decision shaped the PHP ecosystem profoundly. Composer, the PHP package manager, operates in a permissive legal environment. Most PHP packages on Packagist use MIT, BSD, or Apache 2.0 licenses. You can mix and match them without worrying about copyleft contamination. You can build a proprietary Laravel application that uses dozens of MIT-licensed packages, sell it as a SaaS product, and never release a line of your own source code.

## How Licensing Affects PHP Developers Today

### Composer Dependencies

Every time you run `composer install`, you pull in packages under various licenses. Composer does not enforce license compatibility — it is your responsibility to check. Many teams add a license reporting step to their CI pipeline. Tools like `composer-license-checker` and `composer license` can list every package's license so you can audit compliance.

### Choosing a License for Your Own Package

If you publish a package on Packagist, your license choice signals your intent:

- **MIT**: Use this however you want, I do not care
- **Apache 2.0**: Use this however you want, and here is a patent grant
- **GPL**: Use this, but any derivative work must also be GPL
- **LGPL**: Use this in proprietary projects, but share any changes to the library itself
- **AGPL**: Use this on your server, but share your modifications

Most PHP package authors choose MIT. It is simple, well-understood, and imposes no restrictions on downstream users. If your goal is maximum adoption, MIT is the safest choice.

### License Compatibility

License compatibility matters when you combine code under different licenses. The GPL is famously picky: you cannot combine GPL code with MIT code and release the result under MIT. The GPL's copyleft infects the combined work.

The PHP ecosystem mostly avoids these issues because permissive licenses dominate. MIT and Apache 2.0 are compatible with each other and with proprietary licenses. You can build almost any combination of popular PHP packages without legal conflict.

But edge cases exist. Some packages use GPL or AGPL. If a library you depend on is GPL, your entire application must be GPL. This matters for closed-source SaaS products and internal enterprise tools. Always check the licenses of your dependencies before building on top of them.

### The PHP License in Practice

The PHP License applies to the PHP interpreter itself, not to code you write in PHP. When you write a PHP application, your code is your own intellectual property. You can license it however you want, regardless of the PHP License.

This distinction confuses many developers. The PHP License governs the runtime you install on your server. Your application code is independent. This is the same relationship you have with the Linux kernel (GPLv2) — you can run proprietary software on top of GPL-licensed infrastructure without triggering copyleft.

## Real-World Use Cases

### Enterprise Applications

Enterprise legal teams care deeply about licensing. If you build a proprietary enterprise application, every dependency's license matters. A single GPL library can force you to open-source your entire codebase. Enterprise adoption of PHP succeeded partly because PHP's permissive licensing removed this risk. Companies like Facebook (now Meta), Etsy, and Slack built massive proprietary systems on PHP without licensing concerns.

### SaaS Platforms

SaaS platforms run code on servers without distributing it. This means even GPL code is generally safe to use — the copyleft requirement only triggers on distribution of binaries, not on use. But AGPL code requires attention: running it as a service triggers the disclosure requirement. Most PHP SaaS teams avoid AGPL dependencies entirely.

### Open Source PHP Projects

If you are building an open source PHP library, your license choice affects adoption. Laravel uses MIT. Symfony uses MIT. Composer uses MIT. The ecosystem norm is permissive. Choosing GPL for a PHP library will limit adoption because corporate users will avoid it. Unless you have a specific reason to enforce copyleft, MIT is the standard.

## Best Practices for PHP Developers

1. **Know your dependencies' licenses.** Run `composer license` before shipping to production. Generate a license report for your legal team if you have one.

2. **Choose MIT for PHP packages.** It is the ecosystem standard. Maximum adoption, zero friction.

3. **Never ignore a GPL dependency.** If a critical library in your `composer.json` is GPL, understand what that means for your project before you build on it.

4. **Distinguish infrastructure from application.** Running on GPL-licensed PHP or Linux does not make your application GPL.

5. **Add a LICENSE file to every project.** Even if your project is private, a license clarifies intent. Unlicensed code defaults to full copyright — nobody has permission to do anything with it.

6. **Use SPDX identifiers** in your `composer.json` license field. Machine-readable license declarations help automated tooling and legal audits.

7. **Document licensing for your team.** Add a `LICENSES.md` file that explains which licenses your dependencies use and what obligations they create.

## Common Mistakes to Avoid

- **Assuming no license means public domain.** It does not. Unlicensed code is under full copyright. Nobody can legally use, modify, or distribute it.

- **Copying code from a GPL project into your proprietary codebase.** This is the most common licensing violation. Even a few lines of GPL code can force your entire project to be GPL.

- **Ignoring dependency licensing in CI/CD.** Your build pipeline is the right place to catch licensing issues. Do not wait until legal asks.

- **Using AGPL code in a SaaS product without understanding the disclosure obligation.** You may be required to release your modifications.

- **Confusing "free" as in price with "free" as in freedom.** Open source does not mean free of charge, and not all free-of-charge software is open source.

## Frequently Asked Questions

**Can I use MIT-licensed code in a proprietary commercial product?**

Yes. The MIT License explicitly permits use in proprietary software. You only need to include the original copyright notice.

**What happens if I violate a license?**

The copyright holder can sue you for copyright infringement. In practice, many violations are resolved with a cease-and-desist letter asking you to comply. But statutory damages for copyright infringement can be significant.

**Does the GPL apply to code I write in a GPL-licensed language?**

No. The GPL applies to the language runtime or compiler, not to the code you write with it. You can write proprietary PHP code on the GPL-licensed HHVM without triggering copyleft.

**Is the PHP License compatible with the GPL?**

The Free Software Foundation considers the PHP License a free software license but notes it is incompatible with the GPL. PHP 4's license had compatibility issues; PHP 5 and later use version 3.01, which the FSF considers incompatible with GPLv2 due to the PHP License's restriction on using "PHP" in derived product names.

**Do I need a lawyer to choose a license?**

No. For most PHP projects, choosing MIT is straightforward and well-documented. If your company has specific compliance requirements or if you are combining code under conflicting licenses, consulting a lawyer is wise.

**Can I change my project's license after publishing it?**

You can only change the license for versions you control. Anyone who already received your code under the old license can continue using it under those terms. Changing licenses requires permission from all copyright holders if there are multiple contributors.

**What is the difference between GPLv2 and GPLv3?**

GPLv3, released in 2007, added provisions for patent retaliation, anti-tivoization (preventing hardware restrictions that block modified software), and compatibility with the Apache License 2.0. The Linux kernel remains under GPLv2 because Torvalds disagrees with GPLv3's anti-tivoization clause.

**Why does the FSF say the PHP License is incompatible with the GPL?**

The PHP License includes a restriction on derivative product names that the FSF considers an additional restriction beyond what the GPL allows. This creates a practical incompatibility: you cannot combine PHP-licensed code with GPL-licensed code in the same project.

## Conclusion

Software licensing is not just for lawyers. It is the legal framework that determines what you can do with the code you use and what others can do with the code you write. Every `composer require` is a legal transaction. Every open source project you start begins with a licensing decision.

The PHP ecosystem owes its success to permissive licensing. PHP itself is BSD-style. Laravel, Symfony, and Composer are MIT. The culture of sharing without restrictions attracted a generation of developers and made PHP the language that powers the web.

When you publish your next package, choose a license deliberately. When you import a dependency, read its license. When your legal team asks for a compliance report, have the answer ready. The history of software licensing is your history too — and understanding it makes you not just a better programmer, but a better participant in the open source community.

The next time you run `git init` on a new project, add a `LICENSE` file. It is the first commit every repository deserves.
