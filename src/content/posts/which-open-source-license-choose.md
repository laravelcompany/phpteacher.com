---
title: "Which Open Source License Should You Choose for Your PHP Project?"
description: "Guide to choosing the right open source license for your PHP project. Compare GPL, MIT, Apache, BSD, AGPL, and LGPL with pros, cons, and use cases for each."
pubDate: "2022-04-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Open Source", "Licensing", "GPL", "MIT", "Apache License", "PHP", "Copyright", "Legal"]
selected: false
---

Last month, we covered the history of software licensing — how Richard Stallman's GNU GPL fought proprietary lock-in, how the Open Source Initiative rebranded free software for the corporate world, and how PHP's permissive PHP License shaped the ecosystem you work in today.

Now comes the practical question. You have a PHP project. Maybe it is a small Composer package you want to share on Packagist. Maybe it is a Laravel application you are considering open-sourcing. Maybe your company wants to release an internal library to the community. What license do you choose?

This decision matters more than most developers realize. Your license tells the world what they can and cannot do with your code. It determines whether a Fortune 500 company can use your package in their proprietary SaaS product. It decides whether the Free Software Foundation considers your project a friend or a legal risk. It even affects whether other open source projects can incorporate your work.

The PHP ecosystem has strong norms, but understanding *why* those norms exist — and when to break them — requires a deeper look at the licenses themselves.

## What You'll Learn

- What each major open source license actually says and requires
- How PHP and Composer handle license declarations
- What happens when you do not specify a license (spoiler: it is not public domain)
- Which licenses work together and which ones clash
- A practical decision framework for choosing your license
- Exactly how to add a license to your project

## Why Licensing Still Matters for PHP Developers

You might think licensing is irrelevant if you are not a lawyer. You would be wrong. Licensing governs every aspect of how your code moves through the world.

When you run `composer require laravel/framework`, you enter into a legal agreement governed by the MIT License. When you fork a package on GitHub and submit a pull request, you grant the project maintainer a license to use your contribution. When you publish a package on Packagist, you are inviting strangers to copy, modify, and distribute your work under whatever terms you specify.

The PHP ecosystem runs on trust, but that trust is backed by licenses. Without them, every `git clone` would be copyright infringement. Every `composer install` would be a potential lawsuit.

If you skipped last month's article, here is the short version of why licensing history matters: software licensing exists on a spectrum. On one end, permissive licenses (MIT, BSD, Apache) let anyone do almost anything with your code. On the other end, copyleft licenses (GPL, AGPL) require derivative works to be released under the same license. Where you fall on that spectrum determines who can use your code and under what conditions.

## The Major Open Source Licenses Explained

### MIT License

The MIT License is the king of PHP packages. Look at your `vendor/` directory right now. Open any random package's `LICENSE` file. There is a good chance it is MIT. Laravel is MIT. Symfony is MIT. Composer is MIT. PHPUnit is MIT. The list goes on.

The MIT License is short — about 150 words. It says you can do anything you want with this software: use it, modify it, distribute it, sell it, sublicense it. The only requirement is that you include the original copyright notice and the license text in all copies or substantial portions of the software.

That is it. No restrictions on derivative works. No requirement to share your changes. No patent clauses. No network disclosure requirements.

Why is MIT so dominant in PHP? Three reasons. First, it maximizes adoption. Companies will not touch a library with strings attached if a permissive alternative exists. Second, it is simple. Anyone can read and understand the MIT License in thirty seconds. Third, it is compatible with nearly everything. MIT code can be incorporated into GPL projects, proprietary projects, and other MIT projects without conflict.

The downside of MIT is that it offers no protection against proprietary exploitation. A company can take your MIT-licensed PHP package, wrap it in a proprietary service, and never contribute a single improvement back. If that bothers you, MIT is not your license.

### Apache License 2.0

Apache 2.0 is the MIT License's more formal cousin. It permits the same broad usage — use, modify, distribute, sell — but adds explicit legal provisions that corporate lawyers love.

The most important addition is the patent grant. Apache 2.0 includes a clause that terminates the contributor's patent rights if they sue someone for patent infringement related to the software. This protects users from the scenario where a company contributes code to your project and then sues you for using it. It also requires contributors to grant a royalty-free patent license for their contributions.

Apache 2.0 also handles contributions more explicitly. Unlike MIT, which is silent on the topic, Apache 2.0 states that any contributions are automatically licensed under the same terms. This avoids ambiguity about whether a contributor needs to sign a separate contributor license agreement.

In the PHP ecosystem, Apache 2.0 is less common than MIT but still popular. Amazon Web Services uses Apache 2.0 for its PHP SDK. Google's PHP client libraries often use Apache 2.0. If your package is targeted at enterprise customers with legal compliance departments, Apache 2.0 signals that you have thought about patent issues.

The trade-off is complexity. Apache 2.0 is thousands of words long. Your users have to read more legalese. And while Apache 2.0 is compatible with GPLv3, it is *not* compatible with GPLv2 — a detail that matters if your code ever needs to be combined with Linux-kernel-level GPLv2 projects.

### BSD Licenses

The BSD licenses come from the University of California, Berkeley, where the BSD Unix operating system was developed. There are two versions you should care about.

The **two-clause BSD license** (also called BSD Simplified or FreeBSD License) is functionally identical to MIT. It says: do whatever you want with this code, just keep the copyright notice. If you are choosing between MIT and BSD 2-clause, pick whichever one your ecosystem prefers. PHP developers tend to pick MIT.

The **three-clause BSD license** (also called BSD New or Modified BSD) adds a clause that prohibits using the institution's name to endorse derived products. This matters for universities and research institutions. If your PHP package comes from an academic project, the three-clause BSD prevents companies from saying "University of Example endorses this commercial product."

BSD 3-clause appears occasionally in the PHP ecosystem. The original PHP License is based on BSD principles. Some database drivers and system-level PHP extensions use BSD. But for general-purpose PHP packages, MIT has largely replaced BSD in popularity.

### GNU General Public License (GPLv2 and GPLv3)

The GPL is the original copyleft license. It guarantees end users the four essential freedoms: run the program for any purpose, study and modify the source code, redistribute copies, and distribute modified copies. The catch is that any derivative work must be distributed under the same license.

GPLv2, released in 1991, is the license of the Linux kernel. It is short, battle-tested, and well understood. If you incorporate GPLv2 code into your project, your entire project must be GPLv2. There is no exception for linking — if your PHP application calls a GPLv2 C extension, the PHP application itself might need to be GPLv2 depending on how you interpret derivative work.

GPLv3, released in 2007, addressed several weaknesses in GPLv2. It added patent retaliation — if someone sues you for patent infringement, they lose their right to use your GPLv3 code. It added anti-tivoization provisions that prevent hardware manufacturers from locking down GPLv3 software on their devices. And it made the license compatible with Apache 2.0.

In the PHP ecosystem, GPL is rare. Very few Composer packages use it. The practical reason is that GPL is a barrier to adoption. Companies avoid GPL dependencies. PHP developers building libraries want the widest possible audience, so they choose MIT. If you publish a PHP package under GPL, you are deliberately limiting your user base to people who either embrace copyleft or have no alternative.

That said, GPL makes sense for certain projects. If you are building a tool that you want to remain free forever — something where proprietary forks would undermine the project's mission — GPL enforces that. WordPress uses GPLv2. The WordPress ecosystem is proof that GPL can sustain a massive community of developers and companies. But WordPress is the exception, not the rule, in PHP.

### Lesser General Public License (LGPL)

The LGPL is the GPL with a carve-out for libraries. It allows proprietary software to link to LGPL-licensed libraries without triggering the copyleft requirement on the calling application. The copyleft only applies to changes to the library itself.

Why would you use LGPL instead of MIT? Two reasons. First, you want to ensure that improvements to your library remain open source. If someone fixes a bug in your LGPL library, they must contribute that fix back. Second, you want to prevent proprietary forks of your library while still allowing proprietary applications to *use* it.

In PHP, LGPL is uncommon. PHP libraries are typically included as source code via Composer rather than linked as binaries, which makes the "linking" distinction that LGPL relies on less meaningful. Most PHP developers who want copyleft protections just use GPL directly.

### Affero General Public License (AGPL)

The AGPL closes the "SaaS loophole" in the GPL. The GPL's copyleft requirement only triggers when you *distribute* the software. If you run modified GPL software on your own server and never ship it to anyone, you never have to release your modifications. Companies exploited this for years — they took GPL software, improved it, and offered it as a web service without sharing their changes.

The AGPL says: if you run modified AGPL software as a network service, you must make the source code of your modifications available to all users of that service. This is the strongest copyleft available.

AGPL in the PHP ecosystem is rare but not nonexistent. Some database drivers and security-focused libraries use AGPL. The MongoDB PHP driver has historically used AGPL, which caused significant controversy. Companies building proprietary SaaS products on MongoDB had to either purchase a commercial license or switch databases.

If you are building a PHP tool specifically for competing with proprietary SaaS products, AGPL ensures that anyone offering your tool as a service must also open their modifications. But be prepared for limited adoption. Most PHP developers and companies avoid AGPL like the plague.

## License Compatibility

Not all open source licenses work together. When you combine code under different licenses, the most restrictive license in the chain can dictate the terms for the entire combined work.

Here is how the major licenses interact:

**MIT + anything**: MIT code can be incorporated into projects under almost any license. MIT code in a GPL project makes the MIT portion subject to GPL terms. MIT code in a proprietary project stays MIT. MIT is the ultimate team player.

**Apache 2.0 + GPLv3**: Compatible. The Free Software Foundation considers Apache 2.0 compatible with GPLv3. You can combine them in a single project.

**Apache 2.0 + GPLv2**: Incompatible. GPLv2 has a requirement that the entire work must be licensable under GPLv2, and Apache 2.0 has restrictions (like the patent termination clause) that GPLv2 considers unacceptable. The Linux kernel, which is GPLv2, cannot legally include Apache 2.0 code.

**GPL + proprietary**: Incompatible unless you own the copyright and dual-license. You cannot mix GPL code into a proprietary application. This is why companies audit their Composer dependencies.

**AGPL + anything**: The AGPL infects everything it touches. Any combined work must be AGPL. This is by design — the AGPL is meant to be a viral license that ensures all modifications are shared.

**BSD/MIT + Apache 2.0**: Fully compatible. These permissive licenses coexist without conflict.

For PHP developers, this compatibility matrix matters less than you might think. Because most PHP packages are MIT, the risk of license conflicts is low. But if you depend on a single GPL or AGPL library, that license can dominate your entire application. Always check the `license` field in `composer.json` of your dependencies before building on top of them.

## What Happens When You Do Not Specify a License

This is the most common licensing mistake PHP developers make. You publish a package on GitHub. You do not add a `LICENSE` file. You do not specify a license in `composer.json`. You assume that since the code is public, people can use it freely.

They cannot.

Under the Berne Convention, which virtually every country has signed, copyright is automatic the moment you write code. You do not need to register it. You do not need to put a copyright symbol on it. The moment you save a `.php` file, you own the copyright to that code.

Without an explicit license, nobody has permission to do anything with your code. They cannot run it. They cannot modify it. They cannot distribute it. They cannot fork it. They cannot use it in their own projects. Your code is technically proprietary — even though it is sitting on a public GitHub repository.

This creates a real problem for the PHP ecosystem. Imagine a popular package on Packagist that has no license declared. Every project that depends on it is built on legally uncertain ground. If the copyright holder decides to enforce their rights, they could demand that every downstream user remove the code.

The fix is trivial: add a license. Even if you are not sure which one to pick, picking any OSI-approved open source license is better than picking none. Put a `LICENSE` file in your repository root. Set the `license` field in your `composer.json`. The community defaults to MIT for a reason — it is safe, well-understood, and widely accepted.

## How Composer Handles License Declarations

Composer requires you to declare a license for your package in `composer.json`. The `license` field accepts SPDX identifiers — standardized short strings that represent specific licenses.

Here is what it looks like:

```json
{
    "name": "vendor/package",
    "description": "A PHP package",
    "license": "MIT"
}
```

You can specify multiple licenses by using an array:

```json
{
    "license": ["MIT", "Apache-2.0"]
}
```

This means "users can choose either license" — dual licensing, not combined licensing. The user decides which terms to follow.

Composer does not enforce license compatibility. It does not check whether your declared license conflicts with your dependencies' licenses. It simply records the information for downstream consumers. The responsibility is on you to ensure your license choice is accurate and compatible.

You can see the licenses of all your installed packages by running:

```bash
composer licenses
```

This command lists every package and its declared license. Add it to your CI pipeline. Generate a report before every release. If a dependency introduces a GPL license unexpectedly, you want to catch it before your legal team does.

For SPDX identifiers, the PHP ecosystem commonly uses:

- `MIT` — the standard
- `Apache-2.0` — enterprise favorite
- `BSD-2-Clause` or `BSD-3-Clause` — BSD variants
- `GPL-2.0-only` or `GPL-2.0-or-later` — GPLv2
- `GPL-3.0-only` or `GPL-3.0-or-later` — GPLv3
- `LGPL-2.1-only` or `LGPL-2.1-or-later` — LGPL
- `AGPL-3.0-only` or `AGPL-3.0-or-later` — AGPL
- `PHP-3.01` — the PHP License itself

The `-only` and `-or-later` suffixes matter. `GPL-2.0-only` means "exactly GPLv2, not later versions." `GPL-2.0-or-later` means "GPLv2 or any later version published by the FSF." Most projects use the `-or-later` variant to allow upgrading license versions.

## PHP Ecosystem Norms: Why Most Packages Are MIT

The PHP ecosystem has a strong norm toward permissive licensing, and MIT in particular. You can verify this yourself. Look at the top 100 most-downloaded packages on Packagist. The vast majority will be MIT. A handful will be BSD or Apache 2.0. Almost none will be GPL or AGPL.

This norm exists for structural reasons. PHP is primarily used for web applications — a delivery model that inherently reduces copyleft concerns. Since you distribute PHP code to your server, not to end users, even GPL code rarely triggers its copyleft requirements in practice. But companies still prefer MIT because it eliminates the legal analysis entirely. No lawyer needs to review whether a dependency triggers distribution obligations. MIT means "use it however you want."

The norm is also self-reinforcing. When Laravel chooses MIT, every package in the Laravel ecosystem follows suit. When Symfony chooses MIT, Symfony bundles and plugins use MIT. Developers learn by example. New PHP developers see that every package they use is MIT, so they license their own packages as MIT.

If you break this norm by choosing GPL for a PHP library, you are making a statement. You are saying "I value software freedom over adoption." That is a legitimate choice. But understand the trade-off. Your package will have fewer users. Companies will skip it. Other open source projects may not be able to depend on it. You are choosing principle over scale.

## Practical Decision Framework

Here is a straightforward way to decide which license fits your PHP project.

Start by asking: **Is this a library or an application?**

Libraries — packages intended for use in other projects — should generally use permissive licenses. MIT is the default. If your library is targeted at enterprise users who care about patent protection, use Apache 2.0. If your library comes from an academic institution, use BSD 3-clause.

Applications — complete projects that users run directly — have more flexibility. If you want maximum adoption and contribution, use MIT. If you want to ensure the application and all its modifications remain free forever, use GPLv3. If you are building a web service where you want to prevent proprietary SaaS competitors from using your code without sharing, use AGPL.

Second question: **Do you care about patent protection?**

If you work for a company with patents, or if your project involves novel algorithms that could be patented, Apache 2.0 provides explicit protections that MIT does not. MIT has no patent language. If a contributor holds a patent on part of your MIT-licensed code, they could theoretically sue users of your package. Apache 2.0's patent grant prevents this.

Third question: **Do you want contributions back?**

If you want to ensure that improvements to your code are shared with the community, you need copyleft. GPL forces contributors to share their modifications of the library itself. But remember: contributors must also share their entire application if it incorporates GPL code. This is a heavy requirement that drives away corporate contributors.

LGPL offers a middle ground for libraries: share changes to the library, but not to the applications that use it. This works well for system-level libraries but is rare in PHP.

Fourth question: **Is adoption your primary goal?**

If you want as many people as possible to use your package, MIT is the answer. No license removes more barriers to adoption. No license requires less effort to understand. No license is more trusted by companies and individual developers alike.

Here is a quick reference table:

| Goal | Recommended License |
|------|-------------------|
| Maximum adoption | MIT |
| Enterprise comfort + patent protection | Apache 2.0 |
| Academic or institutional credit | BSD 3-Clause |
| Prevent proprietary forks of a library | LGPL |
| Prevent proprietary forks of an application | GPLv3 |
| Prevent proprietary SaaS competition | AGPL |
| I don't know what to choose | MIT |

## How to Add a License to Your Project

Adding a license to your PHP project takes five minutes and saves years of legal headaches.

### Step 1: Choose your license

Use the framework above. When in doubt, pick MIT. You can always change later for future versions, though changing the license of an existing project with multiple contributors requires consent from all copyright holders.

### Step 2: Create the LICENSE file

Copy the full license text into a file named `LICENSE` or `LICENSE.txt` in the root of your repository. Do not abbreviate it. Do not summarize it. Use the verbatim text from the official source.

For MIT:

```bash
curl -O https://raw.githubusercontent.com/licenses/license-templates/master/templates/MIT.txt
```

Edit the file to replace `[year]` and `[fullname]` with your information:

```
Copyright (c) 2022 Your Name
```

### Step 3: Add the SPDX identifier to composer.json

```json
{
    "name": "yourname/your-package",
    "license": "MIT"
}
```

### Step 4: Add a copyright header to your source files

This step is optional but recommended. Add a small header to the top of each PHP file:

```php
/**
 * This file is part of yourname/your-package.
 *
 * (c) 2022 Your Name <your.email@example.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */
```

This header reminds users of the license terms and makes the copyright ownership clear.

### Step 5: Document the license in your README

Add a section to your `README.md`:

```markdown
## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
```

That is it. You are now properly licensed. The next time someone asks "can I use this in my project?", the answer is written in plain text in your repository.

## Common Pitfalls to Avoid

**Dual licensing without owning all rights.** You cannot dual-license a project that has contributions from other people unless every contributor has agreed to the dual-licensing terms. This is why companies that want to offer commercial licenses alongside open source require contributors to sign a Contributor License Agreement.

**Copy-pasting license text from random websites.** Always get license text from the official source. Use choosealicense.com, the Free Software Foundation's license list, or the OSI's approved licenses page.

**Assuming "public" means "public domain."** Public domain is not the same as open source. In many countries, you cannot dedicate code to the public domain. Open source licenses grant specific permissions that public domain does not. Do not put "public domain" in your license field.

**Using Creative Commons licenses for code.** Creative Commons licenses (CC-BY, CC-BY-SA, etc.) are designed for creative works, not software. They do not address patents, linking, or distribution in ways that make sense for code. Use a software license for software.

**Ignoring contributor license agreements.** If your project grows beyond a single developer, consider whether you need a Contributor License Agreement (CLA). CLA tools like CLA Assistant automate the process. Without a CLA, you must rely on the implicit license grant in most open source licenses, which may not give you the rights you need to change the license later or defend against patent claims.

## Final Thoughts

Choosing an open source license is not a permanent decision, but it is a consequential one. The license you pick shapes your project's community, adoption, and legal standing for years to come.

The PHP ecosystem gives you a clear signal: most projects choose MIT, and that choice has helped PHP become the most widely used web development language in the world. Permissive licensing reduces friction. It lets code flow freely between projects, companies, and developers. It is the reason you can install Laravel, Symfony, PHPUnit, and a thousand other tools with a single `composer require`.

But the right license for you depends on your goals. If you are building a tool to compete with proprietary SaaS products, AGPL ensures your work cannot be absorbed by closed-source competitors. If you are building a foundational library, LGPL guarantees that improvements to the library stay open while allowing maximum adoption. If you are building a community project and you just want people to use it, MIT removes every barrier.

The wrong answer is not "GPL" or "MIT" or "Apache." The wrong answer is "no license." Put a `LICENSE` file in your repository. Set the `license` field in `composer.json`. Make your intentions clear. The open source community — and your future self — will thank you.

Next time you run `composer init` and the prompt asks for a license, you will know exactly what to type.
