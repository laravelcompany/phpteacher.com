---
title: "Tech is Taking Sides - The Developer's Responsibility in an Uncertain World"
description: "Technology companies can no longer stay neutral. Explore the developer's ethical responsibility, how tech shapes society, and what it means to build software with conscience."
pubDate: "2022-04-28 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Tech Ethics", "Developer Responsibility", "Technology", "PHP Community", "Career", "Social Impact"]
selected: false
---

## The Illusion of Neutrality

For years, the technology industry clung to a comfortable fiction: that code is math, and math is neutral. We told ourselves that we were builders, not deciders — that our job was simply to implement specifications, not to question them. A sorting algorithm doesn't pick sides. A database query doesn't cast a vote. A framework doesn't hold opinions about geopolitics.

This was always a lie.

Every line of code we write embeds assumptions about the world. Every system we build privileges certain outcomes over others. Every product we ship reshapes the landscape of human interaction in ways both small and seismic. The question was never *whether* technology takes sides, but *when* the rest of the world would stop pretending otherwise.

That moment has arrived.

The Russian invasion of Ukraine in early 2022 shattered whatever remained of tech's pose of neutrality. Cloud providers terminated services. Payment processors cut off transactions. Social platforms labeled state media. Content delivery networks refused to route traffic. Open-source maintainers blocked contributions from contributors based in Russia. Technology companies that had spent years insisting they were mere platforms — neutral conduits for user-generated content — suddenly found themselves making explicitly political decisions with global consequences.

And the developers who built those systems? We watched. We participated. We chose.

## Technology is a Force, Not a Tool

There's a distinction worth making here. A hammer is a tool. It has no agency, no intent, no inherent direction. You can use it to build a house or to break a window, but the hammer itself doesn't care. Technology doesn't work that way.

A recommendation algorithm is not a hammer. It actively shapes behavior, curates reality, and amplifies some voices while silencing others. A social media platform's moderation system doesn't just reflect community standards — it defines them. A cloud provider's terms of service don't just govern business relationships — they determine who gets to participate in the digital economy.

Technology is not neutral because technology is not passive. It reaches into the world and changes it. Every time we build a system that processes data about people, we make choices about privacy, dignity, and power. Every time we write an algorithm that allocates resources or attention, we make choices about fairness and equity. Every time we deploy a system at scale, we make choices about whose interests it serves.

This is the burden of building at scale. When your code runs on millions of devices, when your API handles billions of requests, when your framework powers a significant fraction of the web — you are not just writing software. You are shaping the infrastructure of modern life.

## The Ukraine Test

The tech industry's response to Russia's invasion of Ukraine was unprecedented in its speed and coordination. Within days of the invasion, major technology companies announced actions that would have been unthinkable just weeks earlier:

Cloud providers halted new business in Russia. CDN providers stopped routing traffic. Payment platforms cut off Russian banks. Social media platforms labeled and demoted state-affiliated media. Domain registrars suspended services. E-commerce platforms stopped shipping. Even ostensibly neutral infrastructure providers made choices that had tangible geopolitical effects.

These were not decisions made by faceless corporations. They were decisions made by people — engineers, product managers, executives, legal teams — who looked at what their technology was enabling and chose to act. The infrastructure they built gave them leverage, and they used it.

But here's the uncomfortable question: where was that leverage before?

Where were the content moderation systems when Myanmar's military used Facebook to incite genocide against the Rohingya? Where were the algorithmic interventions when misinformation about vaccines spread faster than the virus itself? Where were the platform suspensions when authoritarian governments used social media to coordinate crackdowns on protesters?

The technology was the same. The capability was the same. The difference was political will — and political will is something that developers, as much as executives and policymakers, help to shape.

## The Developer's Ethical Framework

If technology is not neutral, then the people who build it cannot pretend to be neutral either. But what does ethical responsibility look like for an individual developer?

It starts, I think, with acknowledging that you are not just writing code. You are building systems that will be used by real people, in real contexts, with real consequences. The abstractions we use — users, traffic, engagement metrics — obscure the human reality of our work. Every "user" is a person. Every "traffic spike" is millions of individual decisions. Every "engagement metric" represents hours of human attention, redirected from something else.

This doesn't mean every developer needs to become an activist or refuse to work on any project with ambiguous ethical dimensions. That kind of absolutism is neither practical nor particularly useful. What it means is that developers need to develop ethical muscles — the ability to recognize when a decision has moral weight, the vocabulary to articulate concerns, and the courage to raise them.

Some practical questions every developer should be asking:

**What does this system enable?** Not just what it does, but what it makes possible. A feature that lets users share content also enables the spread of misinformation. A recommendation engine that optimizes for engagement also optimizes for outrage. A data collection system that improves personalization also creates surveillance infrastructure. These are not bugs; they are features. The question is whether you've thought about both sides of the equation.

**Who is not in the room?** The decisions we make about software are shaped by the perspectives we bring to them. Homogeneous teams build systems that work well for people like themselves and fail badly for everyone else. If your team doesn't include people from the communities your software will affect, you are building blind.

**What happens when this is used as intended?** Most technology ethics conversations focus on misuse — the bad actor who subverts a system for nefarious purposes. But the more important question is often about normal operation. When your system works exactly as designed, what does it do to the world? Does it concentrate power or distribute it? Does it increase transparency or opacity? Does it give people more agency or less?

**What happens when this is used at scale?** A feature that works fine for a hundred users can become destructive at a million. The dynamics of networked systems mean that small design decisions can have outsized consequences. A default setting, an algorithmic weight, a UI pattern — these things compound.

**Can I say no?** The hardest question, and the most important. Every developer needs to know where their boundaries are and have the courage to enforce them. This doesn't mean quitting your job every time you disagree with a product decision. But it does mean being willing to escalate concerns, to document objections, and — when the stakes are high enough — to walk away.

## Open Source and the Public Interest

The open-source community has long operated on the belief that code itself is liberating. Give people the source, the logic goes, and they will build better systems. Transparency leads to accountability. Collaboration leads to quality. Freedom leads to innovation.

And for the most part, this has proven true. Open-source software powers the vast majority of the internet's infrastructure. The web servers, programming languages, databases, encryption libraries, and frameworks that make modern technology possible are, overwhelmingly, open source. The PHP ecosystem alone — from the language itself to Composer to Laravel to Symfony to WordPress — represents an extraordinary collective achievement of distributed collaboration.

But open source is not immune to the questions of ethics and responsibility. If anything, open source makes those questions more acute, because the people building critical infrastructure are often volunteers with no institutional support, no legal protection, and no clear lines of accountability.

The recent debates about open-source maintainers blocking Russian contributors illustrated this tension perfectly. Some maintainers argued that open source should remain apolitical — that code transcends borders and politics. Others argued that maintaining infrastructure for a country engaged in war crimes was itself a political choice. Both sides had valid points. Neither side was neutral.

The truth is that open source has always been political. The decision to make something free is political. The choice of which features to prioritize is political. The governance structures of open-source projects — who gets commit access, who gets to make decisions, whose contributions are welcome — are deeply political.

What open source offers is not escape from politics, but a different model of engagement. Open source gives developers agency. It allows us to build alternatives to systems we find objectionable. It creates space for values-driven development that doesn't depend on corporate approval.

The PHP community has a particular role to play here. PHP powers a staggering percentage of the web. From small personal blogs to massive enterprise applications, from WordPress to Laravel to Drupal to Magento — PHP is the invisible infrastructure of enormous swaths of the internet. That reach carries responsibility.

When we build PHP tools and frameworks, we are not just serving a technical community. We are shaping the capabilities and constraints of millions of developers around the world. The decisions we make about security, about accessibility, about internationalization, about backward compatibility — these decisions ripple outward through the entire ecosystem.

## Finding Purpose Beyond the Paycheck

There's a phrase that circulates in developer circles: "I just write code." It's a deflection, a way of sidestepping responsibility. I've said it myself. It's comfortable, in a way, to imagine yourself as a hired gun, implementing whatever requirements land on your desk, collecting a paycheck, and going home.

But I don't think most developers actually believe this. I think we know, deep down, that what we do matters. The reason software development is so satisfying is precisely that our work has impact. We build things that people use. We solve problems that people have. We create value that didn't exist before.

The question is whether we're willing to grapple with the full implications of that impact.

Developers have more power than we often realize. We make decisions that affect millions of people. We design systems that shape behavior. We build infrastructure that determines who can participate in modern life. That power comes with responsibility — not the kind of responsibility that can be delegated to legal teams or ethics boards or product managers, but the kind that lives in the choices we make every day.

This is not a comfortable position to be in. It's much easier to focus on technical challenges — performance optimization, architecture decisions, test coverage — than to grapple with the messy, ambiguous questions of ethics and impact. But the technical questions and the ethical questions are not separate. They are the same questions, viewed from different angles.

A performance optimization that makes your site faster for users with the latest devices also widens the gap for users on older hardware. A data model that normalizes user information also centralizes risk. An API that makes integration easier also makes surveillance harder to resist. These tradeoffs are not bugs to be fixed; they are the substance of the work itself.

## Building Communities That Can Handle Hard Questions

If individual developers have responsibility, they also need support. The hard conversations about ethics and impact are not ones anyone should have to have alone. We need communities — open-source projects, user groups, conference organizers, online forums — that create space for these conversations.

The PHP community has historically been good at this. We have a culture that values pragmatism over ideology, that welcomes newcomers, that celebrates diversity of perspective. But we can do better.

We can build more inclusive communities that don't just tolerate difference but actively seek it out, because we know that better decisions come from more diverse perspectives. We can create spaces where it's safe to raise ethical concerns without fear of being dismissed or punished. We can develop shared language and frameworks for talking about the impact of our work.

We can also be honest about where we fall short. The PHP community, like every technical community, has work to do on diversity, equity, and inclusion. We have blind spots. We have patterns of exclusion that we haven't fully addressed. We have contributors and community members who don't feel fully welcome.

This matters not just because inclusion is inherently valuable (though it is), but because the quality of our work depends on it. The systems we build will be used by everyone. If we build them with only a narrow slice of perspectives at the table, we will build systems that work well for that narrow slice and fail for everyone else. That is not just an ethical failure; it is a technical failure.

## The Path Forward

I don't have a neat conclusion to offer. The questions raised by technology's role in the world are not the kind that admit of clean resolutions. They are ongoing, messy, and deeply human. The best we can do is to keep asking them.

What I believe is this: technology is not neutral. Code is not just code. The systems we build have consequences that we cannot fully anticipate but that we are nonetheless responsible for. The world is changing rapidly, and technology is both driving and responding to that change. Developers — including PHP developers — are at the center of this transformation.

We can choose to ignore that reality, to keep our heads down and focus on the technical challenges in front of us. Many developers will make that choice, and I don't judge them for it. The pressure to stay silent, to stay comfortable, to stay employed is real and powerful.

But we can also choose to engage. We can ask hard questions about the systems we build. We can advocate for users who aren't in the room. We can build tools that distribute power rather than concentrating it. We can create communities that support difficult conversations. We can use our skills not just to build products, but to build a better world.

The technology industry has taken sides. The question is whether we, as individual developers, will take ours — and whether we will do so thoughtfully, courageously, and with clear eyes.

The code we write tomorrow will outlast today's headlines. The systems we build will shape the infrastructure of the next decade. The communities we nurture will determine who gets to participate in building that future. The choices we make — about what to build, how to build it, and who to build it with — will echo far beyond our careers.

That is not a burden to be regretted. It is a responsibility to be embraced.

Build well. Build wisely. Build with conscience.
