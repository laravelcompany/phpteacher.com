---
title: "Teaching Through Code Review - Turn PR Reviews Into Learning Opportunities"
description: "Transform your code review process from gatekeeping to teaching. Learn practical strategies for constructive feedback, active mentoring, and building better engineering teams."
pubDate: "2022-02-12 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["Code Review", "Mentoring", "Team Building", "PHP", "Best Practices", "Communication", "Developer Experience", "Pull Request"]
selected: false
---

## The Gatekeeper Mentality Is Hurting Your Team

If your code review process exists primarily to block bad code from reaching production, you're leaving your team's potential on the table.

The gatekeeper approach treats pull requests as a security checkpoint. The senior developer reviews, rejects, or approves. The junior developer waits. Knowledge stays in one head. The team doesn't get stronger — it stays dependent.

The pandemic made this worse. Remote work severed the casual connections that made learning happen naturally — the tap on the shoulder, the whiteboard sketch, the walk to someone's desk. Code review became the primary touchpoint between developers. For many teams, it became the only place where teaching happened at all.

That's actually an opportunity. If code review is where your team already convenes, you can turn it into a classroom. Not a lecture hall — a workshop where every PR is a chance to level someone up.

In this post, you'll learn how to shift from gatekeeper to teacher, what that looks like in practice with real PHP 8.1+ code, and how to build a review culture that produces better code **and** better developers.

## What You'll Learn

- Why the gatekeeper approach limits your team's growth
- How to automate the boring stuff so reviews focus on substance
- How to ask questions instead of issuing commands in PR comments
- How to write code that's easy to review (and easy to teach from)
- How to avoid reviewer fatigue and keep feedback constructive
- How to include junior developers as reviewers, not just reviewees
- A complete FAQ covering time management, pushback, and difficult reviews

## The Gatekeeper Problem

When you position yourself as a gatekeeper, you become the bottleneck. Every PR waits on you. Every deploy depends on your availability. Your team learns to defer judgment instead of developing it.

More importantly, the gatekeeper mindset trains junior developers to minimize interaction. They learn to write code that will pass your review rather than code that's well-designed. They optimize for approval, not understanding. The result is a team that can follow instructions but struggles when left alone.

Google's engineering culture offers a useful counter-pattern. Their internal code review guidelines encourage reviewers to prefix educational comments with "Nit:" — short for "nitpick." This signals that the comment is minor and shouldn't block approval. More importantly, it frames the comment as a teaching moment. The reviewer isn't saying "fix this." They're saying "here's something to consider for next time."

That one prefix changes the entire tone of a review. It transforms a demand into a lesson.

## Setting Up for Success

Before you can teach through code review, you need to remove the noise. Nothing kills a teaching moment faster than leaving the same formatting comment on every PR.

### Automate Coding Standards

PHP-CS-Fixer handles formatting automatically. Run it as a pre-commit hook so nobody has to think about PSR-12 compliance. Add it to your CI pipeline so violations block the merge button, not the reviewer's attention.

Pair it with Husky for the pre-commit hook. The goal is zero style discussion in PR comments. Every second spent arguing about spaces vs tabs is a second you could spend teaching something that matters.

Configure your `.php-cs-fixer.dist.php`:

```php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR-12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'trailing_comma_in_multiline' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
```

### Enforce Static Analysis Before Review

PHPStan or Psalm at level 6+ catches type errors, dead code, and missing return types before a human ever sees the diff. Add it to CI. Make it required. Now your reviewers can focus on design, not defects.

### Establish Team Coding Standards

Write them down. Keep them short. Cover naming conventions, constructor promotion usage, return type strategy, and exception handling patterns. Link them in your PR template so every new contributor sees them upfront.

The rule is simple: if a machine can check it, a machine should check it. Reserve human review for things machines can't do — design, tradeoffs, architecture, and teaching.

## The Teaching Review

Once automation handles the mechanical stuff, your review comments become pure teaching material. The most effective technique is to replace commands with questions.

Consider this diff. A developer has written a DTO collection class and needs to sort customers by name:

```php
class CustomerCollectionDTO
{
    /** @var CustomerDTO[] */
    private array $customers;

    public function __construct(CustomerDTO ...$customers)
    {
        $this->customers = $customers;
    }

    public function sortByName(): array
    {
        $sorted = $this->customers;
        usort($sorted, function (CustomerDTO $a, CustomerDTO $b) {
            return strcmp($a->name, $b->name);
        });
        return $sorted;
    }
}
```

A gatekeeper comment would say: **"Don't use `strcmp`. Use the spaceship operator instead."**

A teaching comment would say: **"Could the spaceship operator (`<=>`) work here? It compares two expressions and returns -1, 0, or 1 — same as `strcmp` but with native type support."**

The first comment closes the conversation. The second opens one. The developer might not know about the spaceship operator. Now they do. And because you asked a question instead of giving an order, they're more likely to remember the lesson.

A follow-up teaching comment might continue: **"Also consider: when `$a->name` and `$b->name` might contain locale-specific characters, should we use `strcoll` instead?"** This layers on deeper knowledge without overwhelming the initial point.

Now modernize it. With PHP 8.1 you can use enums and readonly properties. A teaching review might guide the developer toward:

```php
readonly class CustomerDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public CustomerTier $tier,
    ) {}
}

enum CustomerTier: string
{
    case Standard = 'standard';
    case Premium = 'premium';
    case Enterprise = 'enterprise';
}
```

And the sort method becomes:

```php
public function sortByName(): array
{
    $sorted = $this->customers;
    usort($sorted, fn (CustomerDTO $a, CustomerDTO $b) => $a->name <=> $b->name);
    return $sorted;
}
```

The question-based approach works at every experience level. For a junior, the question teaches a new language feature. For a mid-level developer, it invites critical thinking about edge cases. For a senior, it validates that they considered alternatives and chose deliberately.

## Writing Code to Be Read

Code that's hard to read is hard to review. Code that's hard to review gets rubber-stamped or ignored. If you want meaningful feedback, make your PRs approachable.

### Use Descriptive Names

This isn't just about variable names. It's about diff clarity. A method called `processData` tells the reviewer nothing. `applyDiscountToEligibleOrders` tells them exactly what to expect.

### Leverage PHP 8.1+ Features for Clarity

Named arguments make call sites self-documenting:

```php
// Without named arguments — what does false mean?
$cache = new Cache(3600, false, true);

// With named arguments — crystal clear
$cache = new Cache(
    ttl: 3600,
    compression: false,
    serialization: true,
);
```

Readonly properties signal intent. If a property is readonly, the reviewer knows it's set once and never mutated. That's one less thing to audit.

```php
readonly class OrderResponse
{
    public function __construct(
        public string $orderId,
        public string $status,
        public float $total,
    ) {}
}
```

### Write Small Methods

A method that does one thing is easy to review. A method that does five things is easy to miss bugs in. When you keep methods small, your reviewer can trace the logic in under thirty seconds.

## Avoiding Reviewer Fatigue

Reviewer fatigue is real. When PRs pile up, attention drops. When attention drops, teaching stops.

### Keep PRs Small

A 500-line diff is intimidating. A 50-line diff is approachable. Encourage your team to submit smaller, more frequent PRs. This isn't just about reviewability — small PRs get merged faster, which means less merge conflict overhead and faster feedback loops.

A good rule of thumb: if a PR description can't summarize the change in two sentences, the PR is too big.

### Write Explicit PR Descriptions

The PR description is the first teaching moment. A good description explains what changed and why. It calls out design decisions. It flags areas where the author wants feedback.

Bad: "Fixed the order processing bug."

Good: "Changed the tax calculation to use line-item-level rates instead of order-level rates. The original code assumed all items shared a tax rate, which broke for multi-jurisdiction orders. I moved the rate lookup into `OrderItemProcessor`. Would appreciate feedback on the data flow."

The second description primes the reviewer. They know what to look for. They know what the author is unsure about. The review becomes a focused conversation instead of a slog.

### Rotate Reviewers

Having the same person review every PR creates a single point of failure — for both knowledge and fatigue. Rotate reviewers so expertise spreads across the team. When a junior reviews a PR (even one from a senior), they learn how the codebase works. When a senior reviews a PR from a different team, they gain cross-system context.

## Commenting with Kindness

Text strips tone. A comment that sounds neutral in your head can read as harsh on screen. The cost of this miscommunication is high — it erodes psychological safety and makes people defensive.

### Comment on the Code, Not the Person

Compare these two comments:

Bad: **"You forgot to handle the null case here. You need to add a check."**

Good: **"This method doesn't handle a null `$response` from the API client. Should we add a guard clause or let it throw?"**

The first comment assigns blame. The second describes the problem and opens a discussion.

### Avoid "You" and "Your"

These words put people on the defensive. Instead of "Your method is too long," try "This method handles pagination, sorting, and filtering. Could we split it into three smaller methods?"

### Prefix Nits

Borrow the "Nit:" pattern from Google. When you leave a minor suggestion, prefix it:

- "Nit: extra blank line here."
- "Nit: we usually alphabetize imports."
- "Nit: consider extracting this magic number to a constant."

This tells the author: this is optional, don't let it block the merge, learn from it for next time.

### Leave Positive Comments

Most PRs only get feedback when something is wrong. That's a missed opportunity. When you see something well-designed, say so.

**"Nice use of a backed enum here. This is much clearer than the string comparison pattern we used before."**

**"This test coverage is excellent. I appreciate the edge case for empty cart payloads."**

Positive comments reinforce good habits. They also build trust. When a developer knows you'll praise good work, they're more willing to hear criticism.

## Real-World Use Cases

### Onboarding New Developers

The first PR from a new hire sets the tone. Review it with extra care. Leave more explanations than you normally would. Point out why certain patterns exist in the codebase. Invite them to ask questions. A good first review experience can determine whether a new developer feels welcome or intimidated.

### Cross-Team Reviews

When a developer reviews code from another team, they bring fresh perspective. They question assumptions the home team stopped seeing. They also learn the other team's domain. Cross-team reviews build organizational knowledge and break down silos.

### Open Source Contributions

Open source reviews are teaching by default. The contributor doesn't know your codebase. Every comment needs context. This is pure teaching — and the skills transfer directly to internal reviews.

### Pair Programming

Code review doesn't have to be async. Sit down together and walk through a diff. Screen share. Talk through the decisions out loud. This is sometimes called the "accidental code review" — it's the remote equivalent of the old cubicle peek, where someone asks "hey, can you look at this real quick?"

## Who Can Participate

Junior developers should review code. Not just have their code reviewed — they should be reviewers too.

This seems counterintuitive. How can someone with less experience evaluate someone with more? The answer is that reviewing isn't just about finding bugs. It's about reading code, understanding intent, and asking questions. A junior reviewing a senior's PR learns the codebase structure, picks up idioms, and builds confidence.

Set expectations. Tell juniors: "You're not expected to catch every bug. Read the code, ask questions about anything you don't understand, and note anything that seems inconsistent with our standards."

The senior's job in this scenario is to respond graciously to those questions. Every question from a junior reviewer is feedback about clarity. If they didn't understand something, the code could be clearer.

## Best Practices Summary

- **Small PRs** — Keep diffs under 250 lines when possible
- **Clear descriptions** — Explain what and why, not just what
- **Automated linting** — Let machines handle formatting
- **Question-based feedback** — Teach instead of dictate
- **Positive reinforcement** — Call out well-written code
- **Prefix nits** — Signal minor suggestions explicitly
- **Rotate reviewers** — Spread knowledge and reduce fatigue
- **Self-review first** — Read your own diff before opening the PR

## Common Mistakes

### Rubber Stamping

Approving a PR without reading it. This destroys trust and lets bugs through. If you don't have time to review properly, say so. Better to delay than to approve blindly.

### Gatekeeping

The opposite extreme. Blocking PRs for trivial reasons. Demanding changes that don't affect correctness or maintainability. Remember: the goal is to ship quality code, not to achieve perfection.

### Vague Feedback

"Can we clean this up?" "This doesn't look right." "Let's revisit this approach." None of these help the author. Be specific or don't comment.

### Tone Deaf Comments

Writing criticism without context. Assuming bad intent. Leaving drive-by comments without explanation. Every comment should teach something, even if that lesson is "this won't work because of X edge case."

## FAQ

**How do I handle a developer who gets defensive about feedback?**

Start by asking questions instead of issuing commands. Frame feedback as collaborative ("What do you think about...?"). If defensiveness persists, have a one-on-one conversation about growth mindset and how the team approaches reviews. Some developers need explicit permission to separate feedback from personal criticism.

**What if a senior developer pushes back against teaching-oriented reviews?**

Seniors who resist teaching-oriented reviews often feel pressure to maintain their role as subject matter experts. Show them that teaching doesn't diminish their status — it amplifies their impact. A senior who teaches ten juniors has more leverage than a senior who gatekeeps alone.

**How do I find time for thorough reviews when I'm already overbooked?**

Account for review time in your sprint estimates. If you know your team produces 2000 lines of changed code per sprint, budget 2-3 hours for review. Block that time on your calendar. Treat reviews as work, not overhead.

**Should I approve a PR with issues I've flagged as nits?**

Yes. That's the point of "Nit:" — it signals the issue shouldn't block the merge. The author can fix it in a follow-up or leave it. Trust them to make the call.

**How do I handle a PR that needs major architectural changes?**

Don't try to fix it in comments. Approve a small, safe slice of the change if possible, or reject the PR with a clear explanation of the structural issue. Offer to pair on the rework. Architecture feedback is almost always better delivered synchronously.

**What if I'm the only person reviewing most PRs on my team?**

That's a bus factor problem. Start pairing reviews with other team members. Have them shadow your reviews. Share the review load gradually. Your goal is to make yourself replaceable.

**How do I review tests?**

Apply the same teaching approach. "Could we add a test for the null payload case?" "I like how this test uses a data provider — that's exactly the right pattern here." "This test covers the happy path but doesn't verify the error response. Want to add that case?"

**Should I leave comments on code style even if we have automation?**

No. That's what the automation is for. If you catch a style issue the linter missed, add a linting rule instead of leaving a human comment. Solve it once at the system level.

## Conclusion

Code review is the most underutilized teaching tool in software engineering. Every pull request is a chance to transfer knowledge, build judgment, and make your team stronger.

The shift from gatekeeper to teacher isn't complicated. Automate the mechanical stuff. Ask questions instead of giving commands. Leave positive feedback. Include everyone in the process. Keep reviews small and focused.

The code you ship today matters. The developers you build tomorrow matter more.

Start with your next PR. Read it like a teacher instead of an inspector. Leave one comment that teaches something instead of just pointing out a problem. See how the author responds. Then do it again on the next one.

That's how you build a team that doesn't need a gatekeeper.
