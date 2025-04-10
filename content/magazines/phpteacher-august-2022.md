---
title: PHP Teacher magazine - August 2022
publishDate: 2022-02-01 00:00:00
description: This month's issue is designed to provide both practical knowledge and theoretical insights, ensuring that every PHP developer, from novice to expert, finds something of value. Let’s dive in!
image: /assets/services/security.svg
tags:
  - magazines
  - php
  - august
  - 2022
---

## Introduction to This Month's Issue

Welcome to another packed edition of *The PHP Architect Magazine*! This month, we delve into a diverse range of topics that will enhance your skills and broaden your understanding of the PHP landscape. We explore database paradigms, with a focus on graph databases and how to migrate from traditional SQL. We also have an in-depth look at the powerful text editor, Vim, and its modern fork, Neovim. For the security-conscious, we have a piece on broken authentication, as well as discussions about using Git to your advantage, building applications using scaffolding, and exploring boundaries in software architecture. We will also explore how to create content programmatically using Drupal, converting decimals to fractions using PHP and the importance of stepping outside your comfort zone. 

This month's issue is designed to provide both practical knowledge and theoretical insights, ensuring that every PHP developer, from novice to expert, finds something of value. Let’s dive in!


## Graphing Relational DB Models

```
      _
    /   \
   |  O  |
   \ ___/
     | |
   -----
  /     \
 /_______\
```
*Graph Databases: A New Paradigm for Relational Data*

In this article, we delve into the world of graph databases, exploring their advantages over traditional SQL databases, especially when dealing with highly relational data. SQL databases, while powerful, can become limiting when your data has complex relationships. This is where graph databases, such as Neo4j, excel.

**Why Consider Graph Databases?**
The article begins by noting that many PHP developers are accustomed to the LAMP stack (Linux, Apache, MySQL, and PHP), however the SQL part of this stack may not be the best option for all tasks. The article states that the most common reason for switching from SQL to graph databases is performance issues, such as slow join operations. Other issues include the need for excessive caching, creating esoteric solutions to solve simple problems, and the tendency to use non-normalised tables to compensate for expensive joins. 

Another reason for using graph databases is data science, with Gartner predicting that graph technologies will be used in more than 80% of data and analytics innovations. This makes graph databases an important tool for developers planning to incorporate data science into their tech stack.

**Key Differences and Modeling**

The core difference lies in how data is modeled. In SQL, relationships between tables are emulated using foreign keys. Graph databases treat relationships as first-class citizens, making it much easier to manage highly connected data.

To demonstrate, the article uses a book collaboration platform as an example. This platform has articles, tags, users, and comments with relationships between them. In SQL, this would involve multiple tables with foreign key relations. In Neo4j, these items become nodes and relationships, resulting in a more intuitive structure.

**Migrating from SQL to Neo4j**
The article uses a practical example, demonstrating how to migrate data from a SQL database to Neo4j. The following steps are outlined using code examples:
1. **Setting up the Environment:** The article recommends using PHP 8.1 or higher, and having docker installed. The article provides a git repository to allow readers to run all code examples. The article provides commands to clone the repository, install libraries, set up MariaDB and Neo4j, and migrate the SQL and Neo4j schemas.
    ```bash
    git clone git@github.com:transistive/book-example.git
    cd book-example
    composer install # install PHP all libraries
    docker-compose up -d # set up MariaDB and Neo4j
    vendor/bin/phinx migrate # migrate the SQL schema
    vendor/bin/phinx seed:run # generate random MariaDB dataset
    php migrate_to_neo4j.php # migrate the data to Neo4j
    ```
   
2.  **Connecting to Databases**: The article shows how to create drivers to connect to both Neo4j and MySQL databases using PHP.
    ```php
    // Create a driver to connect to Neo4j with user
    // Neo4j and password test
    $driver = Driver::create('neo4j://neo4j:test@localhost');

    // Verify the connectivity beforehand to make sure the
    // connection was successful.
    $driver->verifyConnectivity() ?? throw new Error(
    'Cannot connect to database');

    // You can run queries on a session
    $session = $driver->createSession();

    //PDO for MySQL
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;
    dbname=test', 'test', 'sql');
    ```
   
3.  **Inserting Rows as Nodes**: The article provides examples of converting SQL rows into Neo4j nodes using the `MERGE` command. The article also shows how to make the code more generic and reusable using a function that takes a tag and an iterable of nodes. To avoid memory overflows, the code uses generators to process data in chunks.
    ```php
    public function storeRowsAsNodes(string $tag, iterable $nodes): void {
      $session->run(<<<'CYPHER'
          UNWIND $nodes AS node
          MERGE (x:$tag {id: node.id})
          ON CREATE SET x = node
          CYPHER, compact('nodes'));
      }
      ```
     
    ```php
    foreach (Helper::chunk($pdo->yieldRows('articles'), 25000)
      as $chunk) {
    Helper::logCreatedNodes(
      TablesEnum::ARTICLES,
      $nodes->storeRowsAsNodes('articles', $chunk)
      );
      }
    ```
    
4.  **Basic Foreign Key Relations:** The article explains how to create relationships between nodes using the `MATCH` and `MERGE` commands.
    ```php
    public function connectArticles(): ResultSummary
    {
      return $this->session->run(<<<'CYPHER'
        MATCH (child:Article),
        (parent:Article {id: child['parent_id']})
        MERGE (child) - [:HAS_PARENT] -> (parent)
      CYPHER)->getSummary();
    }
    ```
     
5.  **Multiple Foreign Keys:** The article addresses the challenge of handling multiple foreign keys in a single SQL table, showing that it's better to split them into separate queries for clarity.
    ```php
    public function connectCommentToArticles(): ResultSummary
    {
      return $this->session->run(<<<'CYPHER'
        MATCH (c:Comment), (a:Article {id: c.article_id})
        MERGE (c) - [:COMMENTED_ON] -> (a)
      CYPHER)->getSummary();
    }

    public function connectParentComments(): ResultSummary
    {
      return $this->session->run(<<<'CYPHER'
        MATCH (c:Comment), (p:Comment {id: c.parent_id})
        MERGE (c) - [:COMMENTED_ON] -> (p)
      CYPHER)->getSummary();
    }
    ```
   
6. **Polymorphism**: The article touches on handling polymorphic relationships, using translation tables and mappings.
    ```php
    $translationTable = [
      'article_tags' => 'ArticleTag',
      'articles' => 'Article',
      'comments' => 'Comment',
      'polymorphic_categories' => 'Category',
      'tags' => 'Tag',
      'users' => 'User',
    ];

    $categories = $this->yieldRows('polymorphic_categories');
      $categories = Helper::map($categories,
        static fn (array $x) => [
          ...$x,
          ...['label' => TablesEnum::from(
          $x['resource_table'])->asTag()]
        ]);
      $this->storeRowsAsNodes('Category', $categories);
    ```
    

**Real-Time Queries**

The article concludes with some real-time query examples using Cypher:
*   Finding all tags of an article and its sub-articles.
    ```php
        public function listAllTags(int $articleId): array {
            return $this->session->run(<<<'CYPHER'
              MATCH p = (:Article {id: $articleId}) < [:HAS_PARENT*0..] - (:Article)
              UNWIND nodes(p) AS article
              WITH DISTINCT article
              MATCH (article) <- [:TAGS] - (tag:Tag)
              RETURN tag.name AS tag
            CYPHER, compact('articleId'))
            ->pluck('tag')
            ->toArray();
          }
    ```
   
*   Identifying the node with the most categories attached to it.
    ```php
    public function topCategoryNode(int $articleId): void
        {
        $node = $this->session->run(<<<'CYPHER'
              MATCH (c:Category) - [:CATEGORIZES] -> (node)
              WITH node, collect(c) AS categoryDegree
              RETURN node
              ORDER BY categoryDegree DESC
              LIMIT 1
            CYPHER, compact('articleId'))
              ->getAsCypherMap(0)
              ->getAsNode('node');

        echo 'LABEL: ' . $node->getLabels()->first() .
        PHP_EOL;
        echo 'ID: ' . $node->getProperty('id') . PHP_EOL;
        }
    ```
    
*   Finding users who have commented on at least two different articles.
    ```php
      public function doubleCommenters(int $articleId): array {
            return $this->session->run(<<<'CYPHER'
              MATCH (b:Article) <- [:COMMENTED_ON*1..] 
              (:Comment) <- [:Commented] - (u:User),
              (u) - [:COMMENTED] -> (:Comment) 
              [:COMMENTED_ON*1..] -> (a:Article)
              WHERE a <> b
              RETURN DISTINCT u AS user
              CYPHER)
              ->pluck('user')
              ->toArray();
          }
    ```



## Universal Vim - No Plugins Required

```
      /\_/\
     ( o.o )
     > ^ <
```
*Mastering Vim and Neovim Without External Plugins*

This article explores the powerful text editors Vim and Neovim, showing how to create a universal configuration that works for both without requiring any plugins. The focus is on building a strong foundation using only Vim's built-in features, allowing users to become proficient with the core functionality before exploring plugins.

**Vim and Neovim: A Universal Approach**

The article acknowledges that Vim is a well-known editor and that it has a modern fork named Neovim. Neovim is fully compatible with Vim's features at the moment, however, this may change in the future. Because of the compatibility, it is possible to create a single configuration file that can be used with either editor. The article recommends that readers consult the help pages within Vim for each setting, as this will reveal useful details. Vim and Neovim have different configuration files: Neovim uses `$HOME/.config/nvim/init.vim` and Vim uses `$HOME/.vimrc`.

**What is Universal in This Context?**

In this context, "universal" refers to a configuration that works for both Vim and Neovim, allowing the user to choose either editor. The second meaning is that the configuration is useful for writing code, research papers or blog posts. The goal of the article is to provide a solid foundation that does not require any plugins, so the article only discusses settings provided by Vim.

**Config File Structure**

The article recommends structuring the vimrc file into six sections:
*   Settings
*   Plugins
*   Variables
*   Leaders
*   Remaps
*   Auto Commands
   

The first setting is `set nocompatible` which disables compatibility with Vi, Vim's predecessor.

**User Interface Settings**

The article provides several settings to enhance the user interface:
*   **Line Numbers:**
    *   `set number`: Displays real line numbers.
    *   `set norelativenumber`: Turns off relative line numbers.
    *   `set numberwidth=6`: Controls the width of the line number column.
*   **Status Bar:**
    *   `set laststatus=2`: Always displays the status bar.
    *   Custom status line settings to display buffer number, filename, file type, current line number, and column number.
        ```vim
        if has("statusline")
          set statusline=
          set statusline+=\<%n\>
          set statusline+=\ %t
          set statusline+=\ %m%r%h%w
          set statusline+=%=
          set statusline+=(%{&ff})
          set statusline+=\ line:%l\/%L
          set statusline+=\ (%p%%)
          set statusline+=\ col:%c
        endif
        ```
       
*   **Display Guide:**
    *   `set colorcolumn=65`: Shows a column at the specified position using the "hi" setting to set background color. The article suggests setting the value one greater than the desired line length.

**Content Settings**

The article discusses several indentation-related settings, referencing the PHP coding standard PSR-12 as a default.
*   **Indentation:**
    *   `set tabstop=4`: Sets the number of spaces a tab character represents.
    *   `set softtabstop=4`: Sets the number of spaces to use when pressing the tab key.
    *   `set shiftwidth=4`: Sets the number of spaces to indent with << or >>.
    *   `set smartindent`: Automatically indents new lines.
    *   `set expandtab`: Converts tabs to spaces.
*   **Miscellaneous:**
    *   `set nojoinspaces`: Disables adding two spaces after a period when joining lines.
    *   `set textwidth=0`: Disables automatic line wrapping based on a maximum line length.
    *   `set wrapmargin=0`: Disables line wrapping at the edge of the screen.
    *   `set nowrap`: Turns off line wrapping.
    *   `set linebreak`: Enables line wrapping at word boundaries.
    *   `set scrolloff=8`: Sets the minimum number of lines to keep above and below the cursor.
    *   `set sidescroll=12`: Sets the minimum number of columns to keep visible when scrolling horizontally.
    *   `set sidescrolloff=12`: Sets the number of screen columns to use when scrolling horizontally.
    *   `set listchars=""`: Controls the display of special characters.
        ```vim
          set listchars+=tab:>¬
          set listchars+=eol:¶
          set listchars+=trail:∙
          set listchars+=extends:»
          set listchars+=precedes:«
          set listchars+=nbsp:¤
        ```
       

**Search Settings**
*   `set incsearch`: Searches incrementally as you type.
*   `set ignorecase`: Ignores case when searching.
*   `set smartcase`: Overrides `ignorecase` if the search term contains an uppercase letter.
*   `set hlsearch`: Highlights search matches.

**File Explorer and Terminal**
The article recommends adding a file explorer and a terminal to the Vim environment.
*   The file explorer can be toggled with a shortcut and its size can be configured using `g:netrw_winsize`.
*   A terminal can be opened in a horizontal or vertical split.



## Broken Authentication

```
    _.--""--._
  .'          `.
 /   O      O   \
|    \  ^^  /    |
 \     `--'     /
  `. _______ .'
    //_____\\
   ((______)
```

*The Importance of Clear Communication in Security*

This article emphasizes the critical role of clear communication in security, particularly regarding authentication and authorization. Misunderstanding these concepts can lead to critical software vulnerabilities.

**Authentication vs. Authorization**

The article highlights the common mistake of confusing authentication with authorization. Authentication is the process of verifying a user's identity. Authorization, on the other hand, is the process of verifying what a user is allowed to do within the application.

The article uses the example of logging in to an application. While a username, email address, or identification number can prove who a user is, it does not determine the user's access rights. The user needs to be authorised to perform specific actions within the application.

The article mentions a previous article that clarified that authentication is not authorization, specifically when covering multi-factor authentication. Biometrics such as a face scan, can authenticate a user but does not prove their intent to perform an action within the system.

**OpenID Connect (OIDC)**

Many modern applications use tools such as OpenID Connect (OIDC) for both authentication and authorization. OIDC is a federated authentication system, where a user authenticates with an external provider instead of directly with your system. The identity provider, such as Google, issues a token that contains information about the user's identity as well as the user's permissions (scopes). The user will request a token from the provider which includes the scope of the action they want to perform.

**Scopes**

Scopes are used to define specific actions against specific resources. For instance, a user might have the scope `movie:edit` or `user:delete`. Before a user can perform an action, the application needs to validate that the user has the required scope.

The article uses the following code example, which attempts to validate the signature and scopes in an authentication token:
```php
global $idp;

try {
  $idp->validateToken($token, $scopes);
} catch (Exception $error) {
  error_log('Auth failed: ' . $error->getMessage());
}
```

If the signature is invalid, or the required scopes are missing, the identity provider will throw an exception.

**Security Best Practices**

The article highlights that authentication and authorization are complex and require a clear understanding. The author recommends using a robust authentication system, and keeping the concepts of authentication and authorization separate.

**Conclusion**

The author notes that security is a continuous process and should not be added as an afterthought. The article makes the point that clear communication and understanding of core security concepts is critical for developers to prevent vulnerabilities exploited by attackers.

This article underscores the importance of clear communication and proper implementation of authentication and authorization processes for securing applications.

---

## Blueprinting our Application

```
     /
    |
    |    /---\
    |   |  _  |
    |   | / \ |
    |   \---/
   / \
  /___\
```

*Scaffolding: Automating Code Generation in Laravel*

This article explores the concept of scaffolding, which involves using an application to generate code based on configuration. The article focuses on the Blueprint package for Laravel, which adds extensive scaffolding options.

**What is Scaffolding?**

The article introduces scaffolding using the example of the bake command in CakePHP. The bake command, along with similar tools in other frameworks, can generate controllers, models, and more. The article mentions that while scaffolding is now a foundational part of many large frameworks, it might not be as feature rich as you would like. The article explains that The Blueprint package can generate Models, Controllers, Routes, Form Requests, and Tests for Laravel applications.

**Using Blueprint**
To use the Blueprint package, the article suggests the following commands to create a new laravel project and add the Blueprint package:
```bash
composer create-project laravel/laravel fresh
cd fresh
composer require --dev laravel-shift/blueprint
touch draft.yaml
```


The configuration for Blueprint is specified in a `draft.yaml` file.

**Model Generation**

The article demonstrates adding model definitions, including their fields and relationships, to the `draft.yaml` file. It also shows how to instruct blueprint to create model relationships automatically. Here is the example model structure:
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

The command `php artisan blueprint:build` is used to generate the model files based on this configuration. The article shows the results of this command, showing the created files:
```bash
php artisan blueprint:build

Created:
- database/factories/GameFactory.php
- database/factories/GameTagFactory.php
- database/migrations/1973_01_22_153133_create_games_table.php
- database/migrations/1973_01_22_153134_create_game_tags_table.php
- app/Models/Game.php
- app/Models/GameTag.php
```

The generated `app/Models/Game.php` includes the fields in a `$fillable` array and the id and published fields are cast to an integer and boolean. It also creates the relationship between the Game and GameTag models.
```php
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
        return $this->hasMany(
            GameTag::class
        );
    }
  }
```

The generated `app/Models/GameTag.php` includes the `game()` method using the `belongsTo` relationship.
```php
  namespace App\Models;

  use Illuminate\Database\Eloquent\Factories\HasFactory;
  use Illuminate\Database\Eloquent\Model;

  class GameTag extends Model
  {
    use HasFactory;

    protected $fillable = [
      'name',
      'game_id',
    ];

    protected $casts = [
      'id' => 'integer',
      'game_id' => 'integer',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
  }
```


**Database Migrations**

The article notes that the code in the generated migration file looks similar to the official Laravel documentation. The article demonstrates that the migration will create the necessary tables and indexes.

**Model Factories**
The article highlights that while Blueprint generates model factories it is up to the developer to review the faker content.
The article shows how the faker library can be used to generate more realistic data.

**Code Generation Logic**

The magic behind blueprint is the use of stubs, which act as templates for the contents of the destination files. The stubs allow Blueprint to build fully functional code based on the configuration in the `draft.yaml` file.

**Controller and Request Generation**

The article shows how Blueprint can generate controllers and form requests by expanding the `draft.yaml` configuration to include a `controllers` section. Here is an example:
```yaml
controllers:
  Game:
    index:
        query: all
        render: game.index with:games
    create:
        render: game.create
    store:
        validate: name, slug, site_url, publisher_url,
                  publisher, description,
                  thumbnail_url, screen_url, published
        save: game
        flash: game.name
        redirect: game.index
    destroy:
      delete: game
      redirect: game.index
```

The `php artisan blueprint:build` command is used to generate the controller, request, routes, and view files.
The generated code also includes a `GameStoreRequest` class to validate form data.
```php
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
      $request->session()->flash('game.name', $game->name);

      $game = Game::create($request->validated());

      return redirect()->route('game.index');
    }
    public function destroy(Request $request, Game $game)
    {
      $game->delete();
      return redirect()->route('game.index');
    }
  }
```

The article notes that the generated controller includes basic functionality for listing, creating, storing, and destroying games.

**Test Generation**

The article demonstrates how Blueprint can also generate tests. The article reviews the generated test class, `tests/Feature/Http/Controllers/GameControllerTest.php` which includes basic exercises to test the generated code.
The `index_displays_view()` method is used to test the index page.



##  Exploring Boundaries

```
    +-------+
   /       /|
  +-------+ |
  |       | +
  |       |/
  +-------+
```
*Bounded Contexts: Structuring Software by Use Case*

This article delves into the concept of boundaries within software architecture, specifically in relation to the Bounded Context pattern, which was introduced in the previous month’s article. The article explores the creation and management of boundaries, and the software layers that result from this pattern.

**Essential Questions**
The article begins with a set of questions that the reader should be able to answer after reading the article:
*   What does the term "boundary" mean in the context of software architecture?
*   What is meant by "crossing the boundary?"
*   What is a software layer?
*   What is the Dependency Inversion Principle?
*   What is a Separated Interface?

**Creating Boundaries and Software Layers**

The article explains that a boundary is a separation between different parts of your software, such as a folder in your code, and that the layers of your architecture are created by this separation. The article uses a file system example, with a top-level folder named `BoundedContexts` that contains folders for different bounded contexts. Each bounded context has folders for different layers such as `Application`, `Domain` and `Infrastructure`.

```
BoundedContexts/
    AppEvent/
      Application/
      Domain/
      Infrastructure/
    DomainEvent/
      Application/
      Domain/
      Infrastructure/
```


The article emphasises that each folder represents a software layer, and that this separation creates a boundary between what's inside the folder and what is outside.

**Boundary Crossing**
The article states that when a component in one layer needs to interact with another layer, it is described as "crossing the boundary". The article lists the following ways to interact with objects on the other side of a boundary:
*   Use a factory to obtain the needed object
*   Use public methods, without assumptions about the internal workings
*   Use an interface to define available public methods

The article emphasizes that the boundary crossing doesn't need to be overly formal or complex, but simply needs to be a conscious act of respecting the boundaries.

**Refactoring and Boundaries**

The article recommends keeping refactoring within a boundary to minimise the impact on other parts of the code.
The article uses the example of changing a column name in a database, which requires several steps, such as:
*   Altering the table to create the new column name with a default null value.
*   Updating each table row to set the new column’s value to the same as the old column’s value.
*   Adjusting the PHP code to use the new column name.

The article suggests that these steps should be performed in different layers of the architecture, to reduce coupling and increase maintainability.

**The Dependency Inversion Principle**

The article introduces the Dependency Inversion Principle using a previous article as a reference.
The principle suggests that requests should only flow in one direction, where higher-level modules should not depend on lower level modules, but rather on abstractions. For example, the use case handler in the application service layer makes requests of the repository, but the repository does not make requests of the application service.

The article notes that framework table models do not invoke the repository directly, however, the models can publish events that the repository subscribes to using the Observer pattern, which is an example of the Dependency Inversion Principle.

**Class Files and Dependencies**

The article references a diagram which shows where the different class files should be placed in the directory structure. It includes class files such as: `CAppEventOriginatingContexts.php`, `IAppEvent.php` and `IRAppEvent.php` within the `Domain Model`.
The article also provides a diagram to show the direction of the dependencies between the layers:
*   Application services can use the repository but not the other way around
*   Application services can use the domain model
*   The infrastructure layer is used by other layers, however, it does not invoke other layers.


**Separated Interface Pattern**

The article refers to a quote from the book *Working Effectively with Legacy Code* by Michael Feathers which states: "*As you develop a system, you can improve the quality of its design by reducing the coupling between the system’s parts. A good way to do this is to group the classes into packages and control the dependencies between them.*"

The article argues that unit tests are often closely coupled with the system under test. Because of this, refactoring can be difficult. The article suggests creating a separate interface for each class, so that the unit tests are coupled to the interface instead of the class. This interface will lock down the required boundary crossings, which allows the underlying implementation to be changed.

**Conclusion**

The article acknowledges that determining where to set boundaries is a difficult question. The article suggests that boundaries can be shifted as a deeper understanding is gained, and that it is better to just get started, than to wait until the perfect solution is found.
The article states that communication between layers should pass in a single direction, and that the Dependency Inversion Principle and Separated Interface pattern relate to the needed direction of dependencies.

This article provides an in-depth look at the concept of boundaries and software layers as they relate to the bounded context pattern.

