---
title: "Making the Cut: Developer Growth and Imposter Syndrome"
description: "When do you become a 'real' developer? Explore imposter syndrome, defining career moments, mentorship, and how hiring practices can better identify great programmers."
pubDate: "2023-02-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Developer Growth", "Imposter Syndrome", "Mentorship", "Hiring Developers", "Tech Career", "Junior Developers", "Software Engineering", "Career Development"]
selected: false
---

International Data Corporation projects that the global shortage of full-time developers will increase from 1.4 million in 2021 to 4.0 million in 2025. The U.S. Bureau of Labor Statistics predicts software developer employment will grow 25 percent from 2021 to 2031 — much faster than the average for all occupations.

We have a massive shortage of developers. Yet our hiring practices remain stuck in the past, filtering out the very people we need most.

At the same time, countless developers struggle with imposter syndrome. They wonder when they will finally feel like a "real" developer. They compare themselves to senior engineers with ten years of experience and feel inadequate. They worry that their GitHub profile is not active enough, that they do not contribute to open source, that they do not know every algorithm in CLRS.

This article explores what it means to be a developer, how to hire better, and how to grow in your career without burning out.

## What You'll Learn

- The real shortage in tech: finding good developers vs. hiring for the right skills
- Why current technical interview practices miss the mark
- How to recognize when you are a "real" developer
- Strategies for overcoming imposter syndrome
- Practical mentorship approaches for senior and junior developers
- How to restructure hiring to find practical problem-solvers

## The Developer Shortage Myth

There is no shortage of people who can learn to code. There is a shortage of companies willing to train them.

Job listings demand three to five years of experience in every language the company has ever considered using. If applicants pass that gate and land an interview, they face logic puzzles, whiteboard coding exercises, and build-at-home projects that take twenty hours.

These practices filter for people good at memorizing syntax, people practiced in academic coding exercises, and people who have the time and resources to code for free in their off hours. These are not the skills that define a good developer.

A good developer is excited about solving problems and thinking creatively. A good developer knows where to find syntax specifications quickly because things change too fast to memorize everything. A good developer has a solid work-life balance and will not burn out. A good developer listens well and communicates effectively with clients and teammates.

### What We Should Be Testing

Instead of an obscure riddle, set up a mock meeting with someone from the marketing department. Ask the candidate to listen to project requirements, ask clarifying questions, and propose an approach. Watching someone navigate real requirements — pushing back on unnecessary features, asking about edge cases, considering the end user — reveals more about their development skill than any whiteboard exercise.

Instead of traversing a binary tree, ask something practical: how would you validate and secure a contact form? How would you set up a blog for rapid scaling? These questions test real-world knowledge that matters on day one.

Instead of asking candidates to build a useless project for free at home, hire them for a few hours to code for an open-source project your team uses. They are compensated for their work. Even if you do not hire them, you benefit from the contribution.

## When Do You Become a Real Developer?

There is no single moment when you become a "real" developer. No certification ceremony. No union card. No magic number of lines written.

For some, it is the first time they deployed code to production and real users interacted with it. For others, it is the first time they debugged a complex issue without asking for help. For many, it is the first time they taught something to another developer.

### The Defining Moments

Here are milestones that often mark the transition:

**First production bug fix.** You found a bug, diagnosed the root cause, implemented a fix, and deployed it. Real users benefited from your work. You are a developer.

**First code review.** Another developer looked at your code, suggested improvements, and you both learned something. You are part of a team of professionals. You are a developer.

**First time you say "I do not know."** The senior developer asks a question about your approach. Instead of making something up, you say "I do not know — let me research that." This is not weakness. This is the most important skill in software development.

**First time you help someone else.** A junior developer asks for help. You explain a concept. They understand. You just mentored someone. You are absolutely a developer.

**First time you write code you later refactor.** The code you wrote six months ago looks terrible to you now. This is not shame. This is growth. You are a developer.

## The Imposter Cycle

Imposter syndrome follows a predictable cycle. You join a new team or start a new project. Everything is unfamiliar. You compare yourself to colleagues who have been there for years. You feel like a fraud. You work extra hours to catch up. You learn the system. You become productive. You feel competent.

Then you start a new project or join a new company. The cycle repeats.

The fix is not to avoid the cycle. The fix is to recognize it and shorten the recovery time. Every time you have gone through this cycle, you have come out the other side more capable than before. The next cycle will be shorter.

```php
<?php

// A mental model for imposter syndrome

interface Developer
{
    public function currentSkillLevel(): SkillLevel;
    public function learn(Resource $resource): void;
    public function apply(Problem $problem): Solution;
}

class ImposterDeveloper implements Developer
{
    public function currentSkillLevel(): SkillLevel
    {
        // Always perceives self as below actual level
        return $this->actualLevel()->decrement(2);
    }

    public function learn(Resource $resource): void
    {
        // Feels like everyone else already knows this
        $this->knowledge->add($resource);
    }

    public function apply(Problem $problem): Solution
    {
        // Solves the problem, then dismisses it as "easy"
        $solution = $this->solve($problem);
        $this->internalNarrator->say('Anyone could have done that');
        return $solution;
    }
}
```

The solution is data. Keep a brag document. Record every bug you fixed, every feature you shipped, every colleague you helped, every positive customer interaction. When imposter syndrome hits, read the document.

## Mentorship: The Force Multiplier

The most effective way to grow as a developer is to teach others. Mentorship is not a one-way street. Senior developers teach juniors. Juniors teach seniors about new tools and fresh perspectives. Everyone benefits.

### For Senior Developers

You do not need to be an expert to mentor. If you have six months more experience than someone, you can mentor them. Share what you know. Point them to resources. Review their code. Asked to explain why a certain pattern is used, not just what the pattern is.

Schedule regular one-on-ones. These are not status updates. They are space for the junior developer to ask questions they might feel silly asking in a group. The answer to "is this a stupid question?" is always "there are no stupid questions, only unanswered ones."

### For Junior Developers

Find a mentor. This can be a senior engineer on your team, a former professor, or someone you met at a meetup. Come to mentorship sessions prepared. Have specific questions. Show your work. Do not ask "how do I do this?" — ask "I tried X and Y, and neither worked because of Z. Can you help me think about this differently?"

### For Organizations

Pair junior and senior developers intentionally. Assign mentors formally, not just hoping mentorship happens organically. Create a culture where asking questions is celebrated, not penalized. Allocate time for learning and teaching — it is not separate from work, it is part of work.

## Rethinking Technical Interviews

The current technical interview gauntlet selects for the wrong traits. Here is how to design a better process.

### The Practical Problem

Give the candidate a real problem your team has already solved. Provide the same tools they would use on the job — internet access, documentation, their preferred editor. See how they approach the problem, not whether they produce the exact solution.

### The Communication Test

Pair the candidate with a team member on a small task. Watch how they communicate. Do they ask questions? Do they explain their thinking? Do they accept feedback gracefully? These skills matter more than knowing the exact syntax for `array_reduce`.

### The Code Review

Give the candidate a piece of code with intentional issues. Ask them to review it. This tests their ability to read code — which is at least as important as writing it — and their ability to communicate constructive feedback.

### The Follow-Through

Instead of a take-home project, give a small paid contract. The candidate builds a real feature. You pay them for their time. You evaluate the work. Even if you do not hire them, you have working code.

## Hiring Junior Developers

Every senior developer was once a junior developer. Someone took a chance on them. The developer shortage will not be solved by competing for the same pool of senior engineers. It will be solved by training the next generation.

Junior developers bring enthusiasm, fresh perspectives, and a willingness to learn. They ask "why do we do it this way?" — a question that often reveals outdated practices. They are not yet set in their ways. They can be shaped by your team's culture and practices.

The risk of hiring a junior developer is that they need time to ramp up. The risk of not hiring junior developers is that your senior team never grows, burnout increases, and the industry shortage gets worse.

### Structuring Junior Developer Onboarding

- **First week**: Environment setup, codebase tour, pair programming
- **First month**: Small, well-defined tasks with close mentorship
- **First quarter**: Larger tasks with decreasing supervision
- **First year**: Ownership of a feature or module

The initial investment is high. The long-term return is enormous.

## Real-World Use Cases

### Bootcamp Graduate to Team Member

A bootcamp graduate joins a team with no professional experience. The team assigns a mentor for the first three months. The mentor does daily standup check-ins, weekly code review sessions, and monthly career discussions. After six months, the junior developer ships their first independent feature. After one year, they are mentoring the next new hire.

### Career Changer Transition

An accountant with ten years of experience decides to become a developer. They know how to communicate with stakeholders, manage deadlines, and understand business requirements. Their technical skills are beginner level. A company hires them for their soft skills and trains them on the technical side. Two years later, they are the most effective developer on the team because they combine domain knowledge with technical skills.

### Senior Developer as Mentor

A senior developer with fifteen years of experience has been doing the same type of work for five years. They are bored. They start mentoring junior developers. Teaching forces them to revisit fundamentals, consider alternative approaches, and stay current with new technologies. Their engagement increases. Their code improves. The juniors become productive faster.

## Best Practices

### Hire for Aptitude, Not Knowledge

Syntax can be learned. Problem-solving aptitude is harder to teach. Hire people who can think through problems, communicate clearly, and learn quickly.

### Create Psychological Safety

Developers need to be able to say "I do not know" without fear. They need to be able to ask questions without being mocked. They need to be able to make mistakes and learn from them. This environment does not happen by accident. It must be cultivated deliberately.

### Invest in Onboarding

The first week sets the tone for a developer's entire tenure. Make it good. Have a checklist. Assign a buddy. Set clear expectations. Remove blockers before the new developer encounters them.

### Measure Growth, Not Velocity

A junior developer cannot produce at the same rate as a senior. That is expected. Measure growth instead: are they shipping more complex features? Are they needing less review guidance? Are they helping others? These metrics matter.

## Common Mistakes to Avoid

**Hiring only seniors.** A team of all seniors is expensive, prone to stagnation, and lacks fresh perspectives. Balance matters.

**Ignoring cultural fit.** A brilliant developer who cannot work with others will destroy team morale. Skills are trainable. Attitude is not.

**Overvaluing open source contributions.** Open source is a privilege, not a measure of skill. Many excellent developers have never contributed to open source because they have family obligations, second jobs, or simply prefer to spend their free time elsewhere.

**Underinvesting in onboarding.** A developer who flounders in their first month might quit in their third. A developer who thrives in their first month often stays for years.

## Frequently Asked Questions

### How do I know if I am a "real" developer?

If you write code that solves real problems for real people, you are a real developer. There is no minimum experience requirement, no certification, no entrance exam. You are a developer when you say you are.

### How do I overcome imposter syndrome?

Keep a brag document of your accomplishments. Talk to other developers — they feel the same way. Recognize that feeling like an imposter often means you are growing. The discomfort of learning is not evidence of fraud.

### What should I look for in a mentor?

Someone who listens more than they talk. Someone who asks questions that help you find your own answers. Someone who shares their own mistakes and failures, not just their successes.

### How can I find a mentor?

Start within your organization. Ask a senior developer if they have time for a monthly thirty-minute chat. Attend meetups and conferences. Join online communities. Be specific about what you need help with.

### Should I leave my job if I feel stagnant?

Not necessarily. First, talk to your manager. Ask for new challenges, different projects, or mentorship opportunities. If the organization cannot provide growth, then consider a move.

### How do I hire good junior developers?

Look for curiosity, communication skills, and a willingness to learn. Give a practical problem, not a puzzle. Ask about their learning process, not their completed projects. Trust your gut on attitude.

## Conclusion

The developer shortage is not a shortage of potential. It is a shortage of opportunity. We are filtering out talented people with outdated hiring practices. We are burning out our existing workforce with unreasonable expectations. We are failing to train the next generation because training takes time and time is what we do not have.

The fix is straightforward: hire for aptitude, not knowledge. Invest in junior developers. Create mentorship structures. Redesign interviews to test practical skills. Create environments where developers can grow without burning out.

If you are wondering whether you are a "real" developer, the answer is yes. You are. Keep learning. Keep teaching. Keep building.

**Ready to make a difference?** If you are a senior developer, offer to mentor one junior developer this quarter. If you are a junior developer, ask a senior for a thirty-minute coffee chat. If you are a manager, review your hiring process and make one change that filters for potential instead of pedigree.
