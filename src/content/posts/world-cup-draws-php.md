---
title: "Simulating World Cup Draws in PHP"
description: "Simulate World Cup draws in PHP with realistic algorithms. Model tournament brackets and group stage randomization for sports simulations."
category: "PHP"
banner: "/logo.svg"
pubDate: "2022-11-22 21:00:00"
tags: ["PHP", "World Cup", "Simulation", "Algorithms", "Sports"]
---

Tournament draws sound simple — pick teams out of a hat. But real draws come with business rules that make naive randomization fail. Teams from the same pot can't meet in the group stage. UEFA teams have special limits. Regional confederations create additional constraints.

Let's build a simulation in PHP that handles all of it.

## The Rules

For a 32-team World Cup with 8 groups of 4:

- Teams from the same pot cannot share a group
- Except for UEFA teams, no two teams from the same confederation can be in the same group
- No group can have more than two UEFA teams

## Representing Teams

PHP 8.1's constructor property promotion makes the Team class concise:

```php
class Team
{
    public function __construct(
        public readonly string $region,
        public readonly string $name,
    ) {}
}
```

Read a CSV of qualified teams with SPL's file objects:

```php
$all = [];
$file = __DIR__ . '/teams.csv';
$file = new SplFileObject($file);

while (! $file->eof()) {
    $row = $file->fgetcsv();
    $all[] = new Team($row[0], $row[1]);
}
```

## Group Management

Define a Group class with validation logic:

```php
class Group
{
    private array $teams = [];

    public function __construct(
        public string $label,
        public int $max,
    ) {}

    public function isFull(): bool
    {
        return count($this->teams) === $this->max;
    }

    public function addTeam(Team $team): bool
    {
        if ($this->isFull()) {
            return false;
        }

        // Limit: max 2 UEFA teams per group
        if ($team->region === 'UEFA') {
            if ($this->getCountForRegion('UEFA') === 2) {
                return false;
            }
            $this->teams[] = $team;
            return true;
        }

        // Edge case: need to save a slot for UEFA
        if (count($this->teams) === 3
            && $this->getCountForRegion('UEFA') === 0
        ) {
            return false;
        }

        // CONCACAF and CONMEBOL cannot share a group
        if ($team->region === 'CONCACAF'
            && $this->getCountForRegion('CONMEBOL') > 0
        ) {
            return false;
        }

        if ($team->region === 'CONMEBOL'
            && $this->getCountForRegion('CONCACAF') > 0
        ) {
            return false;
        }

        // No same-region teams (except UEFA, handled above)
        if ($this->getCountForRegion($team->region) > 0) {
            return false;
        }

        $this->teams[] = $team;
        return true;
    }

    private function getCountForRegion(string $region): int
    {
        return count(array_filter(
            $this->teams,
            fn(Team $t) => $t->region === $region,
        ));
    }

    public function forceAddTeam(Team $team): void
    {
        $this->teams[] = $team;
    }

    public function getTeams(): array
    {
        return $this->teams;
    }
}
```

The `addTeam()` method returns `false` when the team can't be placed, letting the calling code decide what to do. The calling code either tries the next group or puts the team back in the pool.

## The Assignment Algorithm

Initialize groups, shuffle teams, and iterate through the pool until all teams are placed:

```php
$groups = $done = [];

foreach (range('A', 'H') as $label) {
    $groups[] = new Group($label, 4);
}

shuffle($all);

$target = array_shift($groups);
$x = 0;

while ($all && $x < 60) {
    $candidate = array_pop($all);

    if ($target->addTeam($candidate)) {
        if ($target->isFull()) {
            $done[] = $target;
            $target = array_shift($groups);
        }
    } else {
        // Try this team again later
        array_unshift($all, $candidate);
    }

    $x++;

    // Safety valve: force remaining teams into last group
    if ($x === 60) {
        foreach ($all as $team) {
            $target->forceAddTeam($team);
        }
        $done[] = $target;
    }
}
```

### Why This Works

The `addTeam()` method handles all constraint logic. The main loop only cares about whether the operation succeeded. This separation of concerns keeps the algorithm readable.

The loop counter prevents infinite loops. In practice, constraint satisfaction problems can deadlock — the safety valve forces remaining teams into the last group.

## Rendering Results

HTML output using flexbox cards:

```php
<html>
<style>
    .row { display: flex; flex-wrap: wrap; }
    .card {
        border: 1px solid #888;
        padding: 1rem;
        min-width: 220px;
        margin: 0.5rem;
        border-radius: 0.5rem;
    }
    span { color: #999; }
    ul { padding: 0 0 0 1rem; }
</style>
<body>
<h1>Draws</h1>
<div class="row">
    <?php foreach ($done as $group): ?>
    <div class="card">
        <h3><?= $group->label ?></h3>
        <ul>
            <?php foreach ($group->getTeams() as $team): ?>
            <li>
                <?= $team->name ?>
                <span>(<?= $team->region ?>)</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
```

## Edge Cases Discovered

Building this simulation uncovered constraints not obvious from the original requirements:

1. **Each group needs at least one UEFA team** — Otherwise, residual UEFA teams can't fit the final groups.
2. **CONCACAF and CONMEBOL can't mix** — Confederation rules prevent these from sharing groups.
3. **Residual placement** — Even with rules, some edge cases need a forced fallback.

In real life, these rules evolved from decades of tournament experience. When we simplify a problem, we often discard the wisdom of that experience.

## Sticker Swap Bonus

World Cup sticker collecting adds another puzzle. Given a list of needed stickers and a list of duplicates, find the overlap:

```php
function findMatches(string $neededText, string $dupesText): array
{
    // Parse format like "FWC 2, 3, 7, QAT 5, QAT 6..."
    // Return stickers found in both lists
}
```

This is simpler — parse both lists, find intersection, return results. The challenge is the messy text format with inconsistent delimiters.

## Key Takeaways

- Constraint satisfaction problems require careful ordering of validation rules
- `shuffle()` combined with a try-again loop works well for moderately constrained problems
- Always include a safety valve to prevent infinite loops
- Separate constraint logic from assignment logic for maintainability
- Real-world problems have edge cases that only emerge during implementation

The full code with team data and CSV parser gives you a complete World Cup draw simulator. Try extending it with seeding rules for the real tournament experience.
