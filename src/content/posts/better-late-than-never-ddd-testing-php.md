---
title: "Better Late Than Never - Testing Strategies for Domain-Driven Design in PHP"
description: "Learn testing strategies for Domain-Driven Design in PHP. From unit tests to behavioral testing with Behat, discover how to test your domain logic effectively."
pubDate: "2022-03-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["DDD", "Testing", "PHP", "Behat", "BDD", "PHPUnit", "Domain-Driven Design", "Test Strategy"]
selected: false
---

If you've worked on a PHP project long enough, you've inherited a codebase where tests are a rumor someone once heard. The domain logic — the very heart of your application — is untested, undocumented, and terrifying to touch. Adding tests feels overwhelming. The code wasn't designed for it. The deadline is next week.

Here's the truth: it's never too late to start testing your domain.

This article walks through practical testing strategies for Domain-Driven Design in PHP. We'll cover the test pyramid through a DDD lens, write Behat features that double as living documentation, unit-test value objects and aggregates, integration-test repositories, and mock our way through application services. By the end, you'll have a blueprint for bringing even the crustiest legacy codebase under test.

Let's start with a confession. I once worked on a six-year-old PHP monolith with zero tests. Zero. The domain model had `stdClass` props set via magic `__get` and `__set`. Four different "status" fields lived in the same table, each managed by a different team. Adding a feature meant regression-testing in production. The codebase was, by every definition, a dragon.

Testing DDD in that context wasn't just a technical challenge. It was a survival strategy. We couldn't rewrite everything, but we could wrap new code in tests, slowly extract domain logic, and build a safety net one assertion at a time. That's the "better late than never" philosophy: start where you are, test what you can, and relentlessly improve.

## The Test Pyramid, DDD Edition

The classic test pyramid has unit tests at the base, integration tests in the middle, and end-to-end tests at the top. DDD shifts the emphasis.

```
      ╱╲
     ╱ E2E ╲
    ╱────────╲
   ╱ Integration ╲
  ╱────────────────╲
 ╱ Behavioral / Domain Tests ╲
╱──────────────────────────────╲
```

In DDD, the most valuable tests sit at the domain level. Your domain model, value objects, aggregates, and domain services encode the business rules. When those rules are wrong, nothing else matters. These tests are fast, isolated, and expressive. They pay dividends every time you refactor.

Application service tests occupy the middle layer. They orchestrate domain objects, talk to repositories, and handle transactions. Mock the infrastructure, test the orchestration.

Integration tests verify your infrastructure — repository implementations, doctrine mappings, event buses. Write enough to be confident your adapters work, but don't test the framework.

End-to-end tests cover critical user journeys. A handful of well-written Behat scenarios beats a thousand brittle Selenium scripts.

The key insight: **test your domain logic through the domain language**, not through HTTP contracts. Your domain tests shouldn't know about controllers, requests, or JSON responses.

## Behat and Living Documentation

Behat maps Gherkin features to PHP step definitions. Each feature file describes a business capability in plain language. When those features are the first thing a new developer reads, they become living documentation — always up to date because they have to pass.

Here's a feature file for scoring in a clay-target league:

```gherkin
# features/scoring.feature
Feature: League Scoring
  In order to determine team rankings
  As a league administrator
  I need to calculate scores based on participant hits

  Scenario: Calculate team score from individual results
    Given a league named "USA Clay Target League"
    And a team "Riverside Sharpshooters" with 5 participants
    When I record scores of 22, 24, 23, 25, and 21 hits
    Then the team score should be 94
    And the team average should be 23.0
```

The language is business-native. A domain expert could write this. A stakeholder could verify it. And when the test passes, you know the code matches the business intent.

The Behat context class brings the feature to life:

```php
// src/Tests/Behavior/ScoringContext.php
namespace App\Tests\Behavior;

use App\Domain\League\League;
use App\Domain\League\Team;
use App\Domain\Scoring\ScoreCard;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

final class ScoringContext implements Context
{
    private League $league;
    private Team $team;
    private ScoreCard $scoreCard;

    /** @Given a league named :name */
    public function aLeagueNamed(string $name): void
    {
        $this->league = new League($name);
    }

    /** @Given a team :name with :count participants */
    public function aTeamWithParticipants(string $name, int $count): void
    {
        $this->team = new Team($name);
        $this->team = $this->league->registerTeam($this->team);
    }

    /** @When I record scores of :scores */
    public function iRecordScoresOf(string $scores): void
    {
        $hits = array_map('intval', explode(', ', $scores));
        $this->scoreCard = new ScoreCard($this->team, ...$hits);
    }

    /** @Then the team score should be :expected */
    public function theTeamScoreShouldBe(int $expected): void
    {
        Assert::assertSame($expected, $this->scoreCard->totalScore());
    }

    /** @Then the team average should be :expected */
    public function theTeamAverageShouldBe(float $expected): void
    {
        Assert::assertEqualsWithDelta($expected, $this->scoreCard->average(), 0.01);
    }
}
```

Every step maps directly to a domain concept. A `ScoreCard` value object wraps the raw hits. `totalScore()` and `average()` are pure domain logic. If the scoring rules change — say, the top and bottom scores get dropped — you update the value object and the feature file together. The documentation stays in sync.

This is the dragon-wrangling payoff. Business rules hide in conditionals, edge cases, and email threads. Gherkin features drag them into the open where everyone can see them.

## Testing Value Objects

Value objects are the easiest win in DDD testing. They're immutable, side-effect-free, and encode primitive obsession busting. Test them exhaustively.

Here's a `Score` value object:

```php
// src/Domain/Scoring/Score.php
namespace App\Domain\Scoring;

final class Score
{
    private const MIN = 0;
    private const MAX = 25;

    private int $value;

    public function __construct(int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \DomainException(
                \sprintf('Score must be between %d and %d, got %d', self::MIN, self::MAX, $value)
            );
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
```

Test it:

```php
// tests/Unit/Domain/Scoring/ScoreTest.php
namespace App\Tests\Unit\Domain\Scoring;

use App\Domain\Scoring\Score;
use PHPUnit\Framework\TestCase;

final class ScoreTest extends TestCase
{
    /** @test */
    public function it_accepts_a_valid_score(): void
    {
        $score = new Score(22);

        $this->assertSame(22, $score->value());
    }

    /** @test */
    public function it_rejects_a_negative_score(): void
    {
        $this->expectException(\DomainException::class);

        new Score(-1);
    }

    /** @test */
    public function it_rejects_a_score_above_maximum(): void
    {
        $this->expectException(\DomainException::class);

        new Score(26);
    }

    /** @test */
    public function two_scores_with_the_same_value_are_equal(): void
    {
        $a = new Score(20);
        $b = new Score(20);

        $this->assertTrue($a->equals($b));
    }

    /** @test */
    public function two_scores_with_different_values_are_not_equal(): void
    {
        $a = new Score(20);
        $b = new Score(21);

        $this->assertFalse($a->equals($b));
    }
}
```

Every boundary, every invariant, every equality check. These tests run in milliseconds. They document the contract. And when someone adds a new invariant — "scores for junior division cap at 20" — they add a test first.

## Testing Aggregates

Aggregates enforce consistency boundaries. The `ScoreCard` aggregate ensures team scores are calculated correctly and that individual scores can't be retroactively changed after a deadline.

```php
// src/Domain/Scoring/ScoreCard.php
namespace App\Domain\Scoring;

use App\Domain\League\Team;

final class ScoreCard
{
    /** @var Score[] */
    private array $scores;
    private Team $team;
    private \DateTimeImmutable $submittedAt;

    public function __construct(Team $team, Score ...$scores)
    {
        if (\count($scores) < 1) {
            throw new \DomainException('A score card must have at least one score');
        }

        $this->team = $team;
        $this->scores = $scores;
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function totalScore(): int
    {
        return \array_reduce(
            $this->scores,
            fn (int $carry, Score $score): int => $carry + $score->value(),
            0,
        );
    }

    public function average(): float
    {
        return $this->totalScore() / \count($this->scores);
    }

    public function team(): Team
    {
        return $this->team;
    }

    public function submittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }
}
```

Aggregate tests combine value object confidence with business rule enforcement:

```php
// tests/Unit/Domain/Scoring/ScoreCardTest.php
namespace App\Tests\Unit\Domain\Scoring;

use App\Domain\League\Team;
use App\Domain\Scoring\Score;
use App\Domain\Scoring\ScoreCard;
use PHPUnit\Framework\TestCase;

final class ScoreCardTest extends TestCase
{
    /** @test */
    public function it_calculates_total_score_from_multiple_scores(): void
    {
        $team = new Team('Riverside Sharpshooters');
        $scoreCard = new ScoreCard(
            $team,
            new Score(22),
            new Score(24),
            new Score(23),
            new Score(25),
            new Score(21),
        );

        $this->assertSame(115, $scoreCard->totalScore());
    }

    /** @test */
    public function it_calculates_average_score(): void
    {
        $team = new Team('Riverside Sharpshooters');
        $scoreCard = new ScoreCard(
            $team,
            new Score(20),
            new Score(22),
            new Score(24),
        );

        $this->assertEqualsWithDelta(22.0, $scoreCard->average(), 0.01);
    }

    /** @test */
    public function it_requires_at_least_one_score(): void
    {
        $this->expectException(\DomainException::class);

        new ScoreCard(new Team('Empty Team'));
    }

    /** @test */
    public function it_records_the_submission_timestamp(): void
    {
        $team = new Team('Timely Team');
        $scoreCard = new ScoreCard($team, new Score(15));

        $this->assertInstanceOf(\DateTimeImmutable::class, $scoreCard->submittedAt());
    }
}
```

Aggregate tests catch consistency violations at the smallest possible scope. When a new business rule appears — "a team must have exactly 5 scores" — you add it to the aggregate invariant and write the test first.

## Testing Application Services

Application services orchestrate domain objects. They're thin on purpose. Test them with mocked repositories and domain services.

```php
// src/Application/Scoring/SubmitScoresHandler.php
namespace App\Application\Scoring;

use App\Domain\League\Repository\TeamRepository;
use App\Domain\Scoring\Score;
use App\Domain\Scoring\ScoreCard;
use App\Domain\Scoring\Repository\ScoreCardRepository;

final class SubmitScoresHandler
{
    public function __construct(
        private TeamRepository $teams,
        private ScoreCardRepository $scoreCards,
    ) {
    }

    public function __invoke(SubmitScoresCommand $command): void
    {
        $team = $this->teams->byId($command->teamId);

        $scoreCard = new ScoreCard(
            $team,
            ...\array_map(
                fn (int $value): Score => new Score($value),
                $command->scores,
            ),
        );

        $this->scoreCards->save($scoreCard);
    }
}
```

The test mocks the repositories and verifies the interaction:

```php
// tests/Unit/Application/Scoring/SubmitScoresHandlerTest.php
namespace App\Tests\Unit\Application\Scoring;

use App\Application\Scoring\SubmitScoresCommand;
use App\Application\Scoring\SubmitScoresHandler;
use App\Domain\League\Team;
use App\Domain\Scoring\ScoreCard;
use App\Domain\Scoring\Repository\ScoreCardRepository;
use App\Domain\League\Repository\TeamRepository;
use PHPUnit\Framework\TestCase;

final class SubmitScoresHandlerTest extends TestCase
{
    /** @test */
    public function it_creates_a_score_card_from_command(): void
    {
        $team = new Team('Test Team');

        $teams = $this->createMock(TeamRepository::class);
        $teams->expects($this->once())
            ->method('byId')
            ->with('team-1')
            ->willReturn($team);

        $scoreCards = $this->createMock(ScoreCardRepository::class);
        $scoreCards->expects($this->once())
            ->method('save')
            ->with($this->callback(
                fn (ScoreCard $card): bool => $card->team() === $team
                    && $card->totalScore() === 44
            ));

        $handler = new SubmitScoresHandler($teams, $scoreCards);

        $handler(new SubmitScoresCommand(
            teamId: 'team-1',
            scores: [22, 22],
        ));
    }
}
```

The test asserts the handler fetches the right team, constructs a valid `ScoreCard`, and persists it. It doesn't test the repository implementation — that's for integration tests. It doesn't test HTTP — that's for end-to-end. It tests the orchestration.

**Don't mock what you don't own.** Mock your repository interfaces, not Doctrine's `EntityManager`. Your test should break when your orchestration logic changes, not when the ORM upgrades.

## Integration Testing Repositories

Integration tests verify your repository implementations against a real database. Use a test database, transaction-per-test, or an in-memory SQLite.

```php
// tests/Integration/Repository/DoctrineScoreCardRepositoryTest.php
namespace App\Tests\Integration\Repository;

use App\Domain\League\Team;
use App\Domain\Scoring\Score;
use App\Domain\Scoring\ScoreCard;
use App\Infrastructure\Persistence\Doctrine\DoctrineScoreCardRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineScoreCardRepositoryTest extends KernelTestCase
{
    private DoctrineScoreCardRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->beginTransaction();

        $this->repository = new DoctrineScoreCardRepository($em);
    }

    /** @test */
    public function it_persists_and_retrieves_a_score_card(): void
    {
        $team = new Team('Integration Team');
        $scoreCard = new ScoreCard(
            $team,
            new Score(18),
            new Score(20),
            new Score(22),
        );

        $this->repository->save($scoreCard);

        $retrieved = $this->repository->byTeamId($team->id());

        $this->assertNotNull($retrieved);
        $this->assertSame(60, $retrieved->totalScore());
    }

    protected function tearDown(): void
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->rollback();

        parent::tearDown();
    }
}
```

One or two tests per repository method is enough. You're verifying mapping, not logic. If your ORM config changes, these tests catch it. If your SQL breaks, these tests catch it. The rest of your test suite trusts the interface.

## Testing Domain Services

Domain services handle operations that don't naturally fit on a value object or aggregate. Test them with real domain objects, not mocks.

```php
// src/Domain/Scoring/TeamRankingService.php
namespace App\Domain\Scoring;

final class TeamRankingService
{
    /** @param ScoreCard[] $scoreCards */
    public function rank(array $scoreCards): array
    {
        \usort($scoreCards, fn (ScoreCard $a, ScoreCard $b): int => $b->totalScore() <=> $a->totalScore());

        return $scoreCards;
    }
}
```

```php
// tests/Unit/Domain/Scoring/TeamRankingServiceTest.php
namespace App\Tests\Unit\Domain\Scoring;

use App\Domain\League\Team;
use App\Domain\Scoring\Score;
use App\Domain\Scoring\ScoreCard;
use App\Domain\Scoring\TeamRankingService;
use PHPUnit\Framework\TestCase;

final class TeamRankingServiceTest extends TestCase
{
    /** @test */
    public function it_ranks_teams_by_total_score_descending(): void
    {
        $alpha = new ScoreCard(new Team('Alpha'), new Score(20));
        $bravo = new ScoreCard(new Team('Bravo'), new Score(25));
        $charlie = new ScoreCard(new Team('Charlie'), new Score(22));

        $service = new TeamRankingService();
        $ranked = $service->rank([$alpha, $bravo, $charlie]);

        $this->assertSame('Bravo', $ranked[0]->team()->name());
        $this->assertSame('Charlie', $ranked[1]->team()->name());
        $this->assertSame('Alpha', $ranked[2]->team()->name());
    }
}
```

Domain services are pure logic. Test them like value objects. No mocks, no infrastructure, no framework.

## The Better Late Than Never Strategy for Legacy Code

You've inherited a mess. The domain logic lives in controller methods. The "entity" is an Eloquent model with 4,000 lines. There are no tests.

Here's how you start.

### 1. Identify a seam

Find a piece of business logic you can extract. It might be a price calculation hiding in a controller. It might be a status transition buried in a service. Pick something small and self-contained.

### 2. Write a characterization test

Before you touch the code, write a test that captures the current behavior. Run it. Watch it pass. Now you have a safety net.

```php
// In a legacy codebase, this test captures current behavior
// even if the behavior is wrong. Refactor first, then fix.
/** @test */
public function it_calculates_legacy_team_score(): void
{
    $result = $someLegacyService->calculateTeamScore($teamId);

    // This is what it currently does, not what it should do
    $this->assertSame(42, $result);
}
```

### 3. Extract, don't rewrite

Pull the logic into a value object or domain service. Leave the old method in place, delegating to the new domain object. Test the new domain object. Delegate from the old code. Run the characterization test. It still passes.

### 4. Repeat

Every sprint, extract one piece of domain logic. Write tests for it. Move the seam a little wider. Over months, the domain model emerges from the primordial soup of controllers and queries.

### 5. Add Behat features for the new capability

When you add a new feature, write the Gherkin scenarios first. They document the business intent. They drive the domain model design. And they give you a regression suite that pays for itself on day one.

## Measuring What Matters

Test coverage numbers lie. 100% code coverage on a controller that delegates to a fat service object tells you nothing about whether your business rules work. Instead, measure:

- **Domain test ratio:** How many tests exercise domain objects versus infrastructure? A healthy DDD project has most tests in the domain layer.
- **Behavioral coverage:** How many critical business scenarios are captured as Gherkin features? Every rule in your domain expert's head should map to a scenario.
- **Change failure rate:** After a deployment, how often does a business rule break? This drops when your domain tests cover the rules.

## Putting It All Together

A DDD test strategy isn't about a specific tool or framework. It's about aligning your tests with your architecture.

| Layer | Test Type | Tool | What to Test |
|-------|-----------|------|--------------|
| Domain Model | Unit | PHPUnit | Invariants, value objects, aggregates |
| Domain Service | Unit | PHPUnit | Pure business logic operations |
| Application Service | Unit with mocks | PHPUnit | Orchestration, command/query handling |
| Infrastructure | Integration | PHPUnit + DB | Repository implementation, mappings |
| Behavioral | Acceptance | Behat | Business scenarios, living documentation |
| UI / API | End-to-end | Behat + BrowserKit | Critical user journeys |

Start with the domain layer. Test the rules. Test the edges. Test the things that would cost your business money if they broke.

Then layer on application service tests to cover orchestration. Add integration tests for infrastructure confidence. Finish with a handful of Behat features that tell the story of your application.

And if you're looking at a legacy codebase with zero tests, take a deep breath. Extract one value object this week. Write one test for it. Next week, do it again.

Better late than never.

---

*The code examples in this article use PHP 8.1 features including readonly properties, named arguments, and constructor promotion. All examples are available in the accompanying repository.*
