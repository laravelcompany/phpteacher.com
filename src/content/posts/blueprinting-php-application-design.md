---
title: "Blueprinting Your PHP Application - Design Before You Code"
description: "Learn how to blueprint your PHP application before writing code. Covers wireframes, database schema design, route planning, API contracts, and tools for application scaffolding."
pubDate: "2022-08-14 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "Application Design", "Planning", "Architecture", "Development Process"]
selected: false
---

You sit down to build a new application. The feature list is clear, the client is waiting, and your fingers are itching to open the editor. The temptation to jump straight into code is powerful. But every experienced developer knows the truth: the time you spend designing before coding is never wasted.

Blueprinting your application—sketching out models, controllers, routes, and views before writing a single line—saves weeks of rework. This article shows you how to plan your PHP application using tools like Laravel Blueprint, starting with a draft.yaml and building out from there.

## Why Blueprint First?

When construction workers build a skyscraper, they don't start with the 50th floor. They build scaffolding first—temporary structures that guide the real work. Software scaffolding works the same way. It generates the skeleton of your application from a high-level description, letting you validate your design before committing to implementation details.

The benefits are concrete:

- **Consistency.** Generated code follows the same patterns every time.
- **Speed.** Scaffolding tools produce models, migrations, controllers, and tests in seconds.
- **Discoverability.** Describing your application structure makes missing pieces obvious.
- **Documentation.** The blueprint itself becomes living documentation of your architecture.

## Starting With Blueprint in Laravel

The Laravel Blueprint package generates models, controllers, routes, form requests, and tests from a YAML configuration file. Install it into a fresh Laravel application:

```bash
composer create-project laravel/laravel fresh
cd fresh
composer require --dev laravel-shift/blueprint
touch draft.yaml
```

The `draft.yaml` file is your blueprint. Everything Blueprint generates flows from this single file.

## Modeling Your Data

Start by defining your models and their fields. Here's an example for a game tagging application:

```yaml
models:
  Game:
    name: string index
    slug: string index
    site_url: string
    publisher_url: string
    publisher: string
    description: longtext
    thumbnail_url: string
    screen_url: string
    published: boolean
    relationships:
      hasMany: GameTag
  GameTag:
    name: string
    game_id: id foreign:games
```

This YAML describes two models with their columns, types, indexes, and relationships. Run the build command to generate everything:

```bash
php artisan blueprint:build
```

Blueprint outputs a list of every file it created:

```
Created:
- database/factories/GameFactory.php
- database/factories/GameTagFactory.php
- database/migrations/...create_games_table.php
- database/migrations/...create_game_tags_table.php
- app/Models/Game.php
- app/Models/GameTag.php
```

## Examining the Generated Code

Blueprint generates production-quality code. Here's the `Game` model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'site_url',
        'publisher_url',
        'publisher',
        'description',
        'thumbnail_url',
        'screen_url',
        'published',
    ];

    protected $casts = [
        'id' => 'integer',
        'published' => 'boolean',
    ];

    public function gameTags()
    {
        return $this->hasMany(GameTag::class);
    }
}
```

Each field is in the `$fillable` array for mass assignment. Type casting ensures `id` is an integer and `published` is a boolean. The `gameTags()` method defines the hasMany relationship automatically.

The `GameTag` model gets the inverse relationship:

```php
public function game()
{
    return $this->belongsTo(Game::class);
}
```

## Migrations and Factories

Blueprint also generates migration files:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('slug')->index();
            $table->string('site_url');
            $table->string('publisher_url');
            $table->string('publisher');
            $table->longText('description');
            $table->string('thumbnail_url');
            $table->string('screen_url');
            $table->boolean('published');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('games');
    }
}
```

The migration looks nearly identical to what you would write manually. Indexes are applied where specified.

Model factories are generated with Faker data. These need review since no library can assume your domain's intent:

```php
public function definition(): array
{
    return [
        'name' => $this->faker->name,
        'slug' => $this->faker->slug,
        'site_url' => $this->faker->url,
        'publisher_url' => $this->faker->url,
        'publisher' => $this->faker->company,
        'description' => $this->faker->text,
        'thumbnail_url' => $this->faker->imageUrl(240, 240),
        'screen_url' => $this->faker->imageUrl(1280, 720),
        'published' => $this->faker->boolean,
    ];
}
```

Update the Faker methods to match your real data shapes before running tests.

## Controllers and Routes

Blueprint also generates controllers and form requests. Expand the draft.yaml:

```yaml
controllers:
  Game:
    index:
      query: all
      render: game.index with:games
    create:
      render: game.create
    store:
      validate: name, slug, site_url, publisher_url, publisher, description, thumbnail_url, screen_url, published
      save: game
      flash: game.name
      redirect: game.index
    destroy:
      delete: game
      redirect: game.index
```

Running `php artisan blueprint:build` again adds the controller, updates routes, and creates a form request class:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\GameStoreRequest;
use App\Models\Game;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $games = Game::all();

        return view('game.index', compact('games'));
    }

    public function create(Request $request)
    {
        return view('game.create');
    }

    public function store(GameStoreRequest $request)
    {
        $game = Game::create($request->validated());

        $request->session()->flash('game.name', $game->name);

        return redirect()->route('game.index');
    }

    public function destroy(Request $request, Game $game)
    {
        $game->delete();

        return redirect()->route('game.index');
    }
}
```

The controller is clean. Each method has a single responsibility. Validation logic lives in the `GameStoreRequest` class, not cluttering the controller. Route model binding handles the `destroy` method's dependency injection.

## Test Generation

Blueprint generates PHPUnit tests for every controller method. Add the test assertions package:

```bash
composer require --dev jasonmccreary/laravel-test-assertions
```

The generated test class:

```php
<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /** @test */
    public function index_displays_view(): void
    {
        $games = Game::factory()->count(3)->create();

        $response = $this->get(route('game.index'));

        $response->assertOk();
        $response->assertViewIs('game.index');
        $response->assertViewHas('games');
    }
}
```

Run the tests with PHPUnit:

```bash
$ php vendor/bin/phpunit

....... 7 / 7 (100%)

Time: 00:00.498, Memory: 32.00 MB

OK (7 tests, 17 assertions)
```

## How the Scaffolding Works

Blueprint uses stub files as templates. Look in `vendor/laravel-shift/blueprint/stubs/model.class.stub`:

```php
<?php

namespace {{ namespace }};

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {{ class }} extends Model
{
    use HasFactory;
}
```

Placeholders like `{{ namespace }}` and `{{ class }}` get replaced with values from your YAML configuration. The system is simple but powerful—the same approach used by Artisan's `make` commands under the hood.

## Beyond CRUD: Designing Before You Code

Blueprinting is about more than generated files. The exercise of writing a `draft.yaml` forces you to think through your data model before you start coding. You discover missing fields, awkward relationships, and naming inconsistencies at the design stage rather than during a refactor.

Here's a checklist for your next blueprints:

1. **Define models first.** What entities does your domain have? What are their properties?
2. **Map relationships.** Which entities belong to others? One-to-many? Many-to-many?
3. **Design routes.** What endpoints does your application expose? What HTTP methods?
4. **Plan validation.** What rules govern each field? What happens when validation fails?
5. **Consider edge cases.** What does "empty" look like? What about soft deletes?

## Conclusion

Blueprinting your PHP application before writing code saves time, reduces bugs, and produces more consistent code. Laravel Blueprint's `draft.yaml` approach lets you describe your application structure at a high level and generate production-quality models, controllers, migrations, and tests from that description.

The generated code isn't perfect out of the box—factories need domain-specific Faker methods, and views need real HTML. But the skeleton it provides lets you focus on the logic that makes your application unique, not the boilerplate that every CRUD app needs.

Start your next project with a draft.yaml. Your future self will thank you.
