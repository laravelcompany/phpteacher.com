---
title: "Blind - Navigating Uncertainty as a PHP Developer"
seoTitle: "Blind - Navigating Uncertainty as a PHP Developer"
description: "Navigating uncertainty and imposter syndrome as a PHP developer. Practical advice for building confidence and thriving in an ever-changing tech landscape."
category: "Career"
banner: "/logo.svg"
pubDate: "2022-07-30 21:00:00"
tags: ["PHP", "Career", "Developer Experience", "Community", "Navigation"]
---

As conferences and in-person events return, many organizers are re-examining their speaker selection processes. One popular approach is "blind" talk selection: stripping speaker names, employers, and locations from proposals so that talks are evaluated solely on merit. It sounds fair in theory, but in practice, blind selection is deeply flawed.

This isn't just about conferences. The blind selection problem mirrors a broader challenge in software development: the assumption that removing information removes bias, when in reality, bias runs much deeper than names and titles.

## The Problem with Blind Selection

Blind talk selection removes identifiable information from proposals before review. No speaker name, no employer, no location—just the title and abstract. The reasoning seems sound: if you don't know who submitted, you can't be biased against them.

But bias isn't that simple.

### Identifying Information Leaks Through

Even without speaker names, abstracts contain identifying details. "As a long-time contributor to the Laravel core team..." or "In my years leading the Symfony documentation effort..." immediately reveal the speaker. References to past talks, GitHub projects, or published books all leak identity.

Some speakers use nicknames or handles that differ from their legal names. "Elizabeth" on the submission form might be "Beth" in the abstract. Conference organizers would need to manually scrub every proposal to catch these references—a process that's both labor-intensive and subjective.

### Voice and Style Are Identifiable

Experienced reviewers recognize writing styles, catchphrases, and topic patterns. A speaker who has presented "Test-Driven Laravel" at three previous conferences will have a distinct voice in their abstract. Even when names are hidden, the content itself gives away the author to anyone familiar with the community.

### Demographics Leak Through Language

Consider these three descriptions of the same talk:

1. "Pulling from more than ten years of professional experience writing high-level code for a wide variety of clients, I will teach you the best strategies to use to estimate the time it will take to complete a project and ensure that your projects do not go into the red."

2. "I've been a consultant for over ten years, and I have found a foolproof way to estimate how long it will take to finish a project so you won't be losing money while working."

3. "After 10 years of work, I've seen some messed-up projects, but I know exactly how long each job will take so I always get paid right."

These describe the same experience, but they telegraph vastly different demographic information. Tone, vocabulary, sentence structure, and confidence level all carry implicit signals. Reviewers may unconsciously favor speakers who sound like themselves or match their mental model of "what an expert sounds like."

Blind selection doesn't remove these signals—it just allows them to operate without the reviewer being aware they're present.

## What Actually Works

If blind selection doesn't solve bias, what does?

### Diverse Review Committees

A selection committee that reflects diverse backgrounds, experiences, and perspectives catches biases that a homogeneous committee wouldn't notice. When someone says "this abstract sounds like it might be from a junior developer," another committee member might point out that the language pattern in question is common in a different region or culture.

### Rubric-Based Evaluation

Use a standardized scoring rubric that evaluates specific criteria: relevance, clarity, learning objectives, practical takeaways. Each proposal gets scored on the same dimensions with the same weight. This forces reviewers to articulate why a talk is or isn't a fit, rather than relying on gut feelings.

### Open Scoring

Share scores and feedback among the committee. When reviewers must justify their ratings to peers, they naturally self-correct for obvious biases. Someone who gave a proposal a 2/5 on relevance while everyone else gave it 5/5 has to explain their reasoning, which may reveal an unconscious bias.

### Multiple Rounds

Evaluate proposals in stages. The first round removes clearly unsuitable talks. The second round deeper-dives into the remaining pool. Each round narrows focus and reduces cognitive load, letting reviewers concentrate on the most competitive proposals.

## The Developer's Parallel: Dealing with Unknown Code

The blind selection problem has a direct parallel in development: how you handle code you don't understand.

Every developer faces code that feels like a black box. Maybe it's a legacy system with no tests, a library you've never used, or a domain you don't understand. The natural reaction is to assume the worst—that the code is bad, the architecture is wrong, or you're not qualified to work on it.

This is bias in action. You're evaluating based on what you don't know rather than what you can learn.

### Strategies for Navigating Unknown Code

**Start with the tests**. Tests document how the code is supposed to behave. If there are no tests, write characterization tests that capture current behavior before making changes. This protects against regressions while you learn.

**Read the commit history**. `git log --all --follow -- file.php` shows every change to a file, with context from the commit messages. Understanding why code was written a certain way is more valuable than knowing what it does.

**Use the debugger**. Step through the code path for a known input. Seeing the execution flow in real time reveals assumptions the author made about data formats, error states, and edge cases.

**Ask the author**. Before rewriting something mysterious, reach out to the original author. Often there's a reason for what looks like bad code—a framework limitation, a deadline constraint, or a specific business requirement that's no longer documented elsewhere.

**Assume good intent**. The code worked in production, which means it solved a real problem at some point. Start from the assumption that the original developer made reasonable choices given their constraints. This changes your mindset from "fixing broken code" to "understanding a solution."

## Building Confidence Through Teaching

One of the best ways to navigate uncertainty is to teach what you're learning. You don't need to be an expert to share knowledge. Writing about your learning process, giving lightening talks at local user groups, or mentoring junior developers all reinforce your own understanding while building confidence.

The PHP community is remarkably supportive of new speakers. Local user groups like the Memphis PHP User Group, Madison Web Design & Development, and countless others actively seek first-time speakers. The barrier to entry for a 15-minute local talk is much lower than a 45-minute conference keynote, and the feedback is immediate and constructive.

### Start Small

- Write a blog post about a tricky bug you solved
- Present a 5-minute "lightning talk" at a local meetup
- Lead a lunch-and-learn at your company
- Submit to a regional conference before aiming for national/international events

Each step builds confidence. The first talk is the hardest. The second is easier. After ten, you stop worrying about whether you "belong" and focus on what you want to share.

## Mentorship: The Two-Way Street

Mentorship is often framed as a senior developer helping a junior developer. But the best mentorship relationships are reciprocal. Junior developers bring fresh perspectives, questions that challenge assumptions, and awareness of new tools and practices that senior developers might overlook.

If you're a senior developer, consider what you gain from mentoring:

- **Articulating fundamentals**: Explaining why you use dependency injection or why you structure code a certain way forces you to examine your own practices critically.

- **Staying current**: Junior developers often know newer libraries, frameworks, and patterns that you haven't explored yet.

- **Communication skills**: Teaching complex topics to someone with less context improves your ability to communicate with non-technical stakeholders and cross-functional teams.

If you're a junior developer, look for mentors who challenge rather than validate you. The best mentor isn't the one who tells you you're doing great—it's the one who points out your blind spots and helps you see what you're missing.

### Finding a Mentor in the PHP Community

The PHP community is rich with mentorship opportunities if you know where to look:

**Local user groups**: Meetup.com and the PHP Community Calendar list hundreds of user groups worldwide. Attend regularly, participate in discussions, and ask questions. Regular attendees naturally form mentorship relationships.

**Open source contributions**: Contributing to Laravel, Symfony, or any popular PHP package puts you in contact with experienced maintainers. Start with documentation improvements or small bug fixes. The review process is mentorship in action.

**Conferences and hackathons**: Events like Laracon, SymfonyCon, and PHP UK have mentorship programs or networking sessions specifically designed for career development.

**Online communities**: The PHP subreddit (r/PHP), Laravel News Discord, Symfony Slack, and the PHP Architects community are active with developers at all levels. Engage thoughtfully—ask specific questions, share what you're learning, and offer help when you can.

Mentorship doesn't require a formal arrangement. Some of the most valuable mentoring relationships start with a single question on a forum or a conversation after a meetup talk.

## Impostor Syndrome and the PHP Community

Impostor syndrome is rampant in software development. It's the feeling that you're a fraud who will be discovered at any moment. If you feel this way, you're in good company—surveys consistently show 70% of developers experience impostor syndrome at some point in their careers.

The PHP community has a particular challenge with impostor syndrome because of the language's reputation. PHP developers often feel they need to defend their choice of language, or that they're "real developers" compared to colleagues using Go, Rust, or Python. This external pressure amplifies internal doubt.

Strategies for combating impostor syndrome:

**Track your wins**: Keep a file of positive outcomes—successful deployments, bugs squashed, compliments from users, features shipped. When doubt strikes, read the file.

**Teach what you know**: You don't need to be a world-class expert to teach. If you understand a concept well enough to explain it to a colleague, you know enough to share. Teaching reinforces your knowledge and proves your competence to yourself.

**Embrace "I don't know"**: The best developers say "I don't know" regularly—and then follow up with "but I'll find out." Certainty is a red flag. Curiosity is a strength.

**Compare yourself to yesterday's you**: The only useful comparison is between where you are now and where you were last month. Impostor syndrome thrives on comparing yourself to developers with 15 more years of experience.

## The Conference Talk Journey

For those ready to step onto the stage, here's the path from first-time attendee to seasoned speaker:

**Attend local user groups first**. Present a 10-minute lightning talk on something you know well. The atmosphere is supportive, and the worst outcome is learning what to improve.

**Submit to a regional conference**. One-day events and regional conferences have lower barriers to entry than international conferences. Many actively seek new speakers.

**Start with a tutorial or workshop**. Teaching a hands-on workshop is less intimidating than a solo talk. You share the stage with learners who came specifically to be guided.

**Record and improve**. Record your talks (audio is fine) and review them. What sections felt natural? Where did you stumble? Each talk is practice for the next one.

**Diversity in tech matters at every level**. If you're part of an underrepresented group in tech, your perspective is uniquely valuable. Conferences actively seek diverse speakers not as a checkbox but because different experiences produce more relevant, insightful talks. The PHP community is stronger when every voice is heard.

## The Feedback Loop

Healthy communities and healthy development processes share a common trait: short, safe feedback loops. A pull request reviewed within hours instead of weeks. A conference talk abstract that gets constructive feedback instead of a form rejection. A mentorship check-in that happens regularly instead of annually.

Short feedback loops accelerate learning. When you try something new and get feedback quickly, you adjust, improve, and try again. Long feedback loops mean you keep making the same mistakes for months before anyone tells you.

Build short feedback loops into your development practice:

- Pair program regularly—instant feedback on code structure and approach
- Open PRs early, even as drafts—don't wait until code is perfect
- Attend code reviews actively—learn from how others solve problems
- Present work-in-progress at standups—get directional feedback before investing hours

The same principle applies to conference selection: get feedback early, iterate on proposals, and don't wait for perfection. The first draft of an abstract is never the final version.

## Conclusion

Blind selection doesn't eliminate bias—it just hides it from view. The solution isn't less information, but better processes: diverse committees, rubric-based evaluation, open scoring, and multiple review rounds.

The same principle applies to how you approach code and career growth. You can't eliminate uncertainty by hiding from it. You navigate it by building better processes: characterization tests before refactoring, commit history research before rewriting, and mentorship relationships before going it alone.

The PHP community has room for everyone. Whether you're submitting your first conference talk or your fiftieth, whether you're debugging your first Laravel app or architecting a Symfony microservice empire, the path forward is the same: engage, contribute, teach, and keep learning.
