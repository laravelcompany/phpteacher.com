---
title: "Get a Blog! - Why Every PHP Developer Should Start Blogging"
description: "Why every PHP developer should start blogging. Build your personal brand, deepen your knowledge, and contribute to the PHP community."
category: "Career"
banner: "/logo.svg"
pubDate: "2022-12-14 21:00:00"
tags: ["Blogging", "PHP", "Career", "Writing", "Community"]
---

Before Discord and Slack, before Twitter threads and Reddit posts, developers shared knowledge through ancient tomes known as blogs. They are still one of the most powerful tools in a developer's career arsenal.

Most developers have a blog, even if they never published it. Building a blog is a rite of passage, a portfolio piece, and a way to give back to the community that helped you learn. My favorite part about blogging is the ability to go back and update content when you solve a problem. I have found my own blog posts when searching for solutions more times than I care to admit.

## Why Blog?

Writing about technical topics forces clarity. To explain something well, you must understand it thoroughly. The act of writing exposes gaps in your knowledge and solidifies what you think you know.

Blogging builds your reputation. Every post is a piece of your portfolio that potential employers and clients can read. It demonstrates communication skills, technical depth, and a willingness to share knowledge.

Blogging creates opportunities. Speaking at conferences often starts with writing. Books start with blog posts. Job offers come from visible expertise. The PHP community thrives on shared knowledge, and blogs are the foundation of that sharing.

## Perfection Is the Enemy of Progress

Your blog does not have to be perfect. You will make mistakes. You will fix them. The important thing is to focus on sharing knowledge.

The temptation not to publish out of fear of failure is strong. Publish anyway. You can always fix it later. Share your ideas and your work with the world. Learn to embrace and quickly fix typos and grammatical errors. Every post you write is more valuable than the perfect post you never publish.

## Own Your Content

Before choosing a platform, understand the most important principle: own your content. Owning your content means you control where it lives and how it is displayed.

My blog lives in a Git repository that I control explicitly. I can put that content into Laravel, Symfony, WordPress, or any other system. The source of truth is the Git repository. I publish to my own domain name on my own hosting. If I do not like my host, I move the repository. If I do not like my registrar, I transfer the domain. My content is completely decoupled from any service or company.

## Starting from Scratch

### Get a Domain Name

Domain registrars are companies that hold domain name registrations. Look for a good deal but pay attention to renewal rates, not just the first-year discount. Ensure the registration includes DNS management. Expect to pay around $20–30 per year depending on your top-level domain.

### Choose Your Hosting

The type of blog you build dictates your hosting needs. WordPress requires a host specialized in the WordPress ecosystem. But here is the secret: make your blog boring.

Boring means easy to update and maintain. Do not use the latest snazzy widget framework unless that tool is boring to you. I abandoned several blogs out of frustration because I could never get `packages.json` to work after the initial build.

Removing blockers to creating content is the key to sharing that content. My personal blog is the most boring static site generator setup imaginable. The static site host connects directly to my Git repository and automatically publishes changes when I push to the main branch. I create a new file, fill in the content, commit, and automation takes over.

### Connect Domain to Hosting

Change the DNS servers your domain uses at the registrar. Most registrars include DNS management, so you can apply the host's settings while retaining control. Set A records pointing to your host's IP address. Propagation takes anywhere from minutes to days.

## Static Site Generators for PHP Developers

Static site generators produce HTML from source files. No database. No server-side processing. Just fast, deployable HTML.

### Sculpin

Sculpin is a PHP static site generator written in Symfony components. Creating a new blog is one Composer command away:

```bash
composer create-project sculpin/blog-skeleton phparch-sculpin
```

Run the development server:

```bash
vendor/bin/sculpin generate --watch --server
```

Sculpin uses Twig templates, familiar to any PHP developer. Content goes in `source/_posts/` as Markdown files with YAML front matter:

```markdown
---
title: My Own Blog
categories:
    - news
---

That's it, this is the blog post!
```

To deploy:

```bash
vendor/bin/sculpin generate --env=prod
```

Upload the contents of `output_prod` to your web host.

### Jigsaw

Jigsaw is a static site generator from Tighten that uses Laravel's Blade template engine.

```bash
composer require tightenco/jigsaw
vendor/bin/jigsaw init blog
```

Build with:

```bash
vendor/bin/jigsaw build
```

The file structure mirrors Sculpin: `source/_posts/` for content, `source/_layouts/` for templates. Jigsaw ships with Tailwind CSS by default.

Blade layouts keep your templates clean:

```blade
@extends('_layouts.default')

@section('body')
    My Awesome content is here!
    Everything else gets inherited from default!
@endsection
```

Front matter supports categories, dates, and custom variables:

```markdown
---
id: 975
title: "Building Homestead in 2022"
date: 2022-01-09T09:09:09-05:00
categories:
    - vagrant
    - homestead
    - opensource
    - applesilicon
---
```

## Automation Is Key

If your host supports Git repository integration, connect it and configure auto-publish. When you push to the main branch, the host rebuilds and deploys your site automatically.

For hosts without direct integration, use GitHub Actions to build the site and deploy the assets:

```yaml
name: Build and Deploy

on:
  push:
    branches: [main]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Build site
        run: |
          composer install
          vendor/bin/jigsaw build production

      - name: Deploy
        run: |
          rsync -avz build_production/ user@host:/var/www/html/
```

The goal is to remove every blocker between having an idea and publishing it. No FTP clients. No database migrations. No server configuration. Just write, commit, and ship.

## What to Write About

Start with problems you have solved. If you spent three hours debugging a weird PHP issue, write about it. The next person who hits that same bug will find your post.

Write about things you are learning. Tutorials are the most common type of technical blog post. They reinforce your own understanding while helping others.

Write about opinions backed by experience. Framework comparisons, tool recommendations, and architectural decisions all make compelling content.

Most importantly, do not worry about being original. Every technical topic has been covered before. Your perspective is unique because your experience is unique. The way you explain things will resonate with someone in a way that no other explanation did.

## Get a Blog and Post Your Ideas

Get a blog and post your ideas somewhere they will stick around longer than the next billionaire buys and wrecks on your favorite social media site. I have had my blog longer than I have had any other social media. It is not perfect, but it is mine.

Show me your blog. I would love to see it.
