---
title: Журнал PHP Teacher - сентябрь 2022 г.
publishDate: 2022-09-01 00:00:00
description: AST - это технология, которая изменила мою жизнь и то, как я вижу код. Я хочу поделиться этим способом видения кода, чтобы вы могли решать свои «невозможные проблемы» причудливым и ленивым способом.
image: /assets/services/security.svg
tags:
- magazines
- php
- september
- 2022
---
**Введение**

Добро пожаловать в очередной познавательный выпуск "Журнала учителя PHP"! В этом месяце мы углубимся в широкий спектр тем, призванных повысить ваше мастерство кодирования и расширить ваше понимание разработки программного обеспечения. Мы рассмотрим все: от тонкостей абстрактных синтаксических деревьев (AST) до практического применения шаблонов проектирования и от оптимизации рабочего процесса с помощью инструментов командной строки до повышения производительности приложений с помощью стратегий кэширования. Независимо от того, являетесь ли вы опытным разработчиком или только начинаете свой путь, этот выпуск полон идей и практических знаний, которые помогут вам расти. У нас также есть наши регулярные статьи о сообществе и релизах, которые всегда помогают вам оставаться в курсе того, что происходит в мире PHP.

**Указатель технологических слов**
Абстрактное синтаксическое дерево (AST), Внедрение зависимостей, Обходчик узлов, Посетитель узлов, Модель-Представление-Контроллер (MVC), Нечеткий поиск, FZF, RipGrep, Vim Plug, Шаблон фабрики, Шаблон строителя, Наименьшее общее кратное (LCM), Кэш, Время жизни (TTL), CacheItemInterface, CacheItemPoolInterface, Событие домена, Шаблон ограниченного контекста, Varnish, Drush, Composer, PHPUnit, Действия Github, Непрерывная интеграция/Непрерывная разработка (CI/CD), Envoy, PSR-6.

## Реальный мир AST**

*Автор: Томас Вотруба*

В этом месяце мы начнем с углубленного обзора абстрактных синтаксических деревьев (AST), преобразующей технологии, которая, по словам Томаса Вотрубы, может изменить то, как вы смотрите на код. AST не ограничиваются сообществом или языком PHP, а являются шаблоном, применимым к любому языку. Понимание этого шаблона поможет вам решать сложные проблемы с более обоснованным и элегантным подходом.

**Что такое AST?**

AST — это древовидная структура, которая представляет структуру языка программирования. Это похоже на понимание внедрения зависимостей, шаблона, который, будучи усвоенным, упрощает сложный код. Вместо того, чтобы описывать AST с помощью изображения дерева, полезнее начать с более простого примера, например, со структуры английского предложения. Рассмотрим «Подлежащее Глагол Объект» или «SVO», например «Томас пишет пост». Это можно расширить до «Субъект Глагол Объект Место Время» или «SVOPT», например, «Томас написал пост в кофейне сегодня».

**Видение кода как AST**

Когда вы смотрите на код, ваш разум уже использует концепции AST. Например, идентификация переменных включает распознавание шаблонов. Переменная обычно представляет собой строку, которая начинается со знака доллара `$`, за которым следуют буквенно-цифровые символы, а затем присваивание `=`.
```php
$article = new Article('Как кто-либо может быстро выучить AST?');
$author = new Author('Tomas');
$author->writeContentForArticle($article);
$magazine = new Magazine('PHP Arch');
$author->deliverFinishedArticleToMagazine($article, $magazine);
```
Это распознавание образов является основой того, как языки программирования анализируют синтаксис, аналогично правилам грамматики в английском языке.

**Обход узлов**

Обход дерева означает чтение каждого элемента, затем ввод всех более мелких элементов и рекурсивное продолжение этого процесса, пока не будет прочитана каждая часть. Например, текст разбивается на предложения, предложения на слова и т. д.
```
* текст
|
* предложение
* предложение
* предложение
```
В коде это означает чтение от элементов более высокого уровня к элементам более низкого уровня. Код — это не просто длинная строка символов, это упорядоченный набор элементов. AST использует эти элементы, а затем их дочерние элементы, которые меньше и строятся на предыдущем уровне. Чтобы сделать эту концепцию более конкретной, мы можем представить код абстрактным образом с помощью объектов:

```php
final class Text
{
/**
* @param Sentence[] $sentences
*/
public function __construct(
public array $sentences
) {
}
}
```

```php
final class Sentence
{
/**
* @param Word[] $words
*/
public function __construct(
public array $words
) {
}
}
```

```php
final class Word
{
public function __construct(
public string $content
) {
}
}

```

Эти классы можно составлять для создания предложений, например, предложения «Томас пьет кофе»:

```php
$words = [
new Word('Томас'),
new Word('drinks'),
new Word('coffee'),
];
$sentence = new Sentence($words);
$text = new Text([$sentence]);
```
Обходчик узлов используется для посещения каждого узла, начиная с самого верха, переходя к дочерним узлам, а затем к дочерним узлам этих узлов и т. д. Этот процесс продолжается до тех пор, пока не будет достигнут каждый узел.
```php
final class NodeTraverser
{
public function traverse(Text $text): void
{
$this->visit($text);
foreach ($text->sentences as $sentence) {
$this->visit($sentence);
foreach ($sentence->words as $word) {
$this->visit($word);
}
}
}

private function visit($node)
{
// ...
}
}
```

**Базовые элементы AST**

AST состоит из трех основных элементов:

* **Узлы:** Базовые строительные блоки дерева, каждый из которых представляет собой фрагмент кода, обычно класс, определенный в парсере. Узлы имеют дочерние элементы и содержат данные о позициях строк, именах файлов и возможности добавления общих метаданных.
* **Обходчик узлов:** Он отвечает за обход дерева, посещение каждого узла и его дочерних элементов в направлении сверху вниз. Обходчик узлов обычно является общим и входит в пакет парсера.

* **Посетители узлов:** Это пользовательские скрипты, которые извлекают информацию или изменяют код по мере его обхода. Они взаимодействуют с отдельными узлами и выполняют операции в зависимости от необходимости. Например, посетитель узла может использоваться для подсчета того, сколько раз встречается слово «Томас». Вот простой пример:

```php
class TomasCountNodeVisitor implements NodeVisitorInterface
{
private int $tomasCounter = 0;

public function getTomasCounter(): int
{
return $this->tomasCounter;
}

public function visit(AbstractNode $node)
{
if (! $node instanceof Word) {
return;
}

if ($node->content !== 'Tomas') {
return;
}
$this->tomasCounter += 1;
}
}
```

Содержимое посетителя узла в методе `visit()` можно изменить в зависимости от того, что вам нужно сделать. Посетители узла — это то, где можно реализовать силу AST. Посетитель узла может быть добавлен в NodeTraverser:

```php
$tomasCountNodeVisitor = new TomasCountNodeVisitor();

$nodeTraverser = new NodeTravser();
$nodeTraverser->addNodeVisitor($tomasCountNodeVisitor);
```

**NTV — аббревиатура AST**

Чтобы запомнить ключевые компоненты, подумайте о **NTV**:
* **Nodes**
* **Node Traverser**
* **Node Visitors**

**Заключение**
AST — это мощный инструмент для понимания и управления кодом. Понимая AST, вы можете создавать скрипты, которые изменяют код, или выполнять другие сложные операции, которые облегчают вашу работу. Статья этого месяца дает представление о том, какую работу выполняют для вас парсеры, и знакомит с элементами, которые вам нужно будет освоить, чтобы стать профессионалом в AST.

```
 /\_/\
 (оо)
 > ^ <
```

## Universal Vim Part Two: Fuzzy Search Fun**

In this article, Andrew Woods guides us through enhancing our Vim experience with FZF, a performant fuzzy finder.  Building on the foundations of a universal vimrc, this article focuses on search, turning Vim into an IDE-like environment.

**FZF on the Command Line**

FZF, created by Junegunn Choi, filters lists by fuzzy matching lines against a search term. The simplicity of FZF is what makes it great. Once installed, FZF narrows your search results, focusing on what's essential. You can view the documentation on the project's GitHub repo. Installation is simple:
```
$ git clone --depth 1 ~/.fzf
$ ~/.fzf/install
```
FZF also integrates with other tools like RipGrep, fd, and bat, further enhancing its capabilities.

**Command-Line Usage**

FZF provides three key bindings for command line usage, adding efficiency:
*   `CTRL+R`: Fuzzy search your command history. This is more efficient than the standard `history | less` method of searching your command history.

*   `CTRL+T`: Select files and directories with fuzzy search. This is useful for finding files within a project. Type `vi` then `CTRL+T` to fuzzy search your project files.

*   `ALT+C`: Select a directory and create the `cd` command. Instead of navigating the directories one level at a time like tab completion allows, FZF lets you view all directories ahead of time as a list.

**RipGrep Integration**

FZF becomes even more powerful with RipGrep. RipGrep is used to find files first, if you only remember the content of the file you need to find, not the name. Once a match is found, the matching line, filename, and line number are passed to FZF.  FZF then uses `bat` to display the matching content.

**FZF Command Examples**

You can use `fzf` without parameters to see all the files in the current directory. Pipping content from a command to `fzf` is also useful, such as `ps -ef | fzf` to see a list of processes. To select multiple items, you can use the tab key.

The preview option of `fzf` can also be customised, for example:
```
$ fzf --preview 'cat -n {}'
```
to show line numbers, or to use `bat`:
```
$ fzf --preview 'bat --style=numbers \
--color=always --tabs=4 {}'
```
It is not recommended to add the `--preview` option to the `FZF_DEFAULT_OPTS` variable. Instead, make an alias like `fzfp`.
```
# Put this in your ~/.bashrc
alias fzfp="fzf --preview 'bat --style=numbers \
--color=always --tabs=4 {}'"
```
**The FZF Vim Plugin**

The vim plugin makes use of command line `fzf` and its environment variables.

**Vim Plug Installation**

Junegunn Choi also created Vim Plug, a plugin manager for Vim. You can install it from GitHub by downloading the `plug.vim` file and putting it in the “autoload” directory.
```
$ curl -fLo ~/.vim/autoload/plug.vim --create-dirs \
https://phpa.me/junegunn-vim
```
**Fzf.vim Plugin Installation**

The fzf.vim plugin is available on GitHub. To install the `fzf` and `fzf.vim` plugins, add the following to your `vimrc` plugin section:
```
" ==== Plugins ==============================================
"
call plug#begin('~/.vim/plugged')
Plug 'junegunn/fzf', {'dir':'~/.fzf', 'do':'./install --all'}
Plug 'junegunn/fzf.vim'
call plug#end()
```

After restarting Vim and running `:Files`, you will see FZF display a list of files.

**Customising FZF**

FZF provides many options, but there are four main variables to consider:

*   `g:fzf_action`: Customise keybindings for different ways of opening files.
*   `FZF_DEFAULT_OPTS`: Change the appearance and layout of FZF.
```
FZF_DEFAULT_OPTS+=' --border=sharp'
FZF_DEFAULT_OPTS+=' --layout=default'
export FZF_DEFAULT_OPTS
```
*   `g:fzf_preview_window`: Set the default preview window size and position.
*   `g:fzf_layout`: Set the layout for FZF.

**Mapping FZF Functions**

Map FZF functions to your leader key to easily access them. Some good default mappings are:
*   `ob`: Open buffer list `:Buffers`
*   `of`: Open project files `:Files`
*   `og`: Open git managed files `:GFiles`
*   `sc`: Search within project `:Rg`
*   `sf`: Search in fullscreen `:Rg!`
*   `sa`: Search lines in all buffers `:Lines`
*   `sb`: Search lines in current buffer `:BLines`
*   `dg`: Display git status `:GFiles?`
*   `db`: Display marks `:Marks`
*   `dm`: Display mappings `:Maps`
*   `dt`: Choose from color schemes `:Colors`

Here are the mappings in the `.vimrc` format:
```
" Open the buffers list
nnoremap ob :Buffers

" Open any of our project files
nnoremap of :Files

" Open any of our Git managed files
nnoremap og :GFiles

" Search
nnoremap sc :Rg

" Search in full screen
nnoremap sf :Rg!

" Search lines in All Buffers
nnoremap sa :Lines

" Search lines in Current Buffer
nnoremap sb :BLines

" Git status
nnoremap dg :GFiles?

" Display Marks
nnoremap db :Marks

" Display Mappings
nnoremap dm :Maps

" Choose from the themes (colorschemes) list
nnoremap dt :Colors
```
**Conclusion**

FZF is a powerful and fun tool to use, both in and out of Vim.  By integrating FZF into your workflow, you can greatly enhance your productivity. The author also notes that using FZF means a file explorer becomes less necessary in Vim.

```
   (\_/)
  (='.'=)
(")_(")
```

##  Building Code**

*By Chris Tankersley*

This month, Chris Tankersley explores the Factory and Builder patterns, two popular methods for creating new objects. Patterns are essential for putting objects together, as they help us avoid repeating code when we need similar logic in different parts of a project.

**The Factory Pattern**

The Factory pattern moves object creation logic to a central location, allowing you to determine what kind of object is created, based on parameters.

**Starting with a Problem**

Imagine you have a controller that returns JSON data:
```php
class OurController {
    public function someAction() {
        // Magical business logic that creates a $returnData array
        header('Content-Type: application/json');
        http_response_code(200);
        echo json_encode($returnData);
    }
}
```
If multiple controllers need to return JSON data, it makes sense to create a `JsonResponse` object:
```php
class JsonResponse {
    public function __construct(
        protected array $data = [],
        protected int $returnCode = 200,
    ) { }

    public function output() {
        header('Content-Type: application/json');
        http_response_code($this->returnCode);
        echo json_encode($this->data);
    }
}
```
However, what happens when a client needs the same API to return XML? You can create an `XMLResponse` class:
```php
class XMLResponse {
    public function __construct(
        protected array $data = [],
        protected int $returnCode = 200,
    ) { }

    public function output() {
        header('Content-Type: application/xml');
        http_response_code($this->returnCode);
        $xml = new SimpleXMLElement('');
        array_walk_recursive(
            array_flip($this->data),
            array ($xml, 'addChild')
        );
        print $xml->asXML();
    }
}
```
Then, you might end up using a `switch` statement in the controller:
```php
class OurController {
    public function someAction() {
        // Magical business logic that
        // creates a $returnData array
        switch(getallheaders()['Accept']) {
            case 'application/xml':
                $resp = new XMLResponse($returnData);
            break;
            default:
            case 'application/json':
                $resp = new JsonResponse($returnData);
            break;
        }
        $resp->output();
    }
}
```
This can become error-prone and difficult to maintain as the number of response types increases. The Factory Pattern can resolve this.

**The Factory**

The Factory moves the decision logic into a central place. An interface can be created, to enforce that the return object will have certain properties:
```php
interface ResponseInterface {
    public function __construct(mixed $data);
    public function output(): void;
    public function setReturnCode(int $code): static;
}
```
The response classes can also extend an Abstract class to share common logic:
```php
abstract class AbstractResponse
implements ResponseInterface {
    protected int $returnCode = 200;

    public function __construct(
        protected mixed $data,
    ) { }

    public function setReturnCode(int $code): static {
        $this->returnCode = $code;
        return $this;
    }

    abstract function output(): void;
}
```
Now `JsonResponse` and `XMLResponse` can extend the `AbstractResponse` class and implement the `ResponseInterface`:

```php
class JsonResponse extends AbstractResponse {
    public function output(): void {
        header('Content-Type: application/json');
        http_response_code($this->returnCode);
        echo json_encode($this->data);
    }
}

class XMLResponse extends AbstractResponse {
    public function output(): void {
        header('Content-Type: application/xml');
        http_response_code($this->returnCode);
        $xml = new SimpleXMLElement('');
        array_walk_recursive(
            array_flip($this->data),
            array ($xml, 'addChild')
        );
        print $xml->asXML();
    }
}
```
The factory will contain the logic to determine what type of response is needed:
```php
class ResponseFactory {
    static public function create(
        mixed $data,
        int $returnCode = 200
    ): ResponseInterface {
        switch(getallheaders()['Accept']) {
            case 'application/xml':
                $resp = new XMLResponse($data);
            break;
            default:
            case 'application/json':
                $resp = new JsonResponse($data);
            break;
        }
        $resp->setReturnCode($returnCode);
        return $resp;
    }
}
```
The controller now becomes simplified:
```php
class OurController {
    public function someAction() {
        // Magical business logic that
        // creates a $returnData array
        $resp = ResponseFactory::create($returnData);
        $resp->output();
    }
}
```
You can also pass dependencies into the factory using the constructor:

```php
class RepositoryFactory {
    public function __construct(protected \PDO $pdo)
    { }

    public function create(
        string $entityName,
        string $idColumn = 'id',
        ?string $tableName = null
    ) {
        if (is_null($tableName)) {
            $tableName = $entityName;
        }

        if (class_exists($entityName . 'Repository')) {
            $className = $entityName . 'Repository';
            return new $className($this->pdo);
        }
        $repo = new GenericRepository($this->pdo);
        $repo->setId($idColumn);
        $repo->setTableName($tableName);
        return $repo;
    }
}
```
Coupling this with a service locator reduces the number of objects being passed around.

**The Builder Pattern**

The Builder pattern allows customisation of parts of an object by using setter methods, as well as preserving some state between build calls. For example, you might create a `Book` object, with properties like `title`, `author`, and `cover`:

```php
enum BookCover: string {
    case HARDCOVER = 'hardcover';
    case PAPERBACK = 'paperback';
}

enum BookSize: string {
    case TRADE_5x8 = 't58';
    case TRADE_6x9 = 't69';
    case TRADE_8x10 = 't810';
    CASE MAGAZINE = 'mag';
}

class BookBuilder {
    protected string $author = 'Anonymous';
    protected string $contents;
    protected BookCover $cover = BookCover::PAPERBACK;
    protected string $coverImage = 'generic.png';
    protected BookSize $size = BookSize::TRADE_5x8;
    protected string $title;

  public function setAuthor(string $author): static {
        $this->author = $author;
        return $this;
    }

  public function setContents(string $contents): static {
        $this->contents = $contents;
        return $this;
    }

   public function setCover(BookCover $cover): static {
        $this->cover = $cover;
        return $this;
    }

    public function setCoverImage(string $image): static {
        $this->coverImage = $image;
        return $this;
    }

     public function setSize(BookSize $size): static {
        $this->size = $size;
        return $this;
    }

     public function setTitle(string $title): static {
        $this->title = $title;
        return $this;
    }

    public function build(): Book {
       return new Book(
            title: $this->title,
            contents: $this->contents,
            author: $this->author,
            cover: $this->cover,
            coverImage: $this->coverImage,
            size: $this->size,
        );
    }
}
```
Now, you can use this to create similar objects:
```php
$magazineBuilder = (new BookBuilder())
    ->setCover(BookCover::PAPERBACK)
    ->setSize(BookSize::MAGAZINE);

$jan2022 = $magazineBuilder
    ->setTitle('php[architect] January 2022')
    ->setCoverImage('jan2022.png')
    ->setContents($archJan2022)
    ->build();
$feb2022 = $magazineBuilder
    ->setTitle('php[architect] February 2022')
    ->setCoverImage('feb2022.png')
    ->setContents($archFeb2022)
    ->build();
```
You can reuse the builder to create different objects, such as a book:
```php
$bookFeb = $magazineBuilder
    ->setSize(BookSize::TRADE_5x8)
    ->build();
```
The important thing to remember with a builder is that the state is preserved between build calls. This also means if you don’t update a property, it will keep the old value, and this can cause issues.

**Conclusion**
Factories help manage object creation in a central location, while the Builder pattern helps create and customise objects with multiple optional properties. Both patterns make your code easier to maintain and more efficient. The article also briefly mentions Abstract Factory and Prototype patterns.

```
    .--.
   |  o  |
   | === |
   '----'
```
## Fractional Math**

*By Oscar Merida*

Oscar Merida explores fractional math in PHP, starting with a `Fraction` class and how to add, subtract, multiply and divide fractions. This exploration is a good reminder of the fundamental math concepts that are the building blocks of computer science.

**Adding Fractions**

To add fractions, you must find the lowest common multiple (LCM) of the denominators. For example, to add 1/4 and 5/6, the LCM of 4 and 6 is 12.

```
1   5    ?
--- + --- = ?
4   6
```
To rewrite the fractions with a common denominator, we get 3/12 and 10/12. To add these you add the numerators and keep the denominator:
```
3   10    13
--- + --- = ---
12  12    12
```
Here is the `add()` function:
```php
class FractionCalculator
{
    public function add(
        Fraction $a,
        Fraction $b
    ): Fraction {
        $lcm = $this->getLCM($a->denominator, $b->denominator);
        // convert the numerators
        $newA = $a->numerator * ($lcm / $a->denominator);
        $newB = $b->numerator * ($lcm / $b->denominator);
        $sum = new Fraction($newA + $newB, $lcm);
        return $sum->simplify();
    }
  private function getLCM(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        if ($a === 0 || $b === 0) {
            throw new \InvalidArgumentException("Do not use zero");
        }
        if ($a === $b) {
           return $a;
        }
        $max = max($a, $b);
        if ($a === 1 || $b === 1) {
            return $max;
        }
        $lcm = null;
        for ($i = 1; $i <= $max; $i++) {
            $multsA[] = $i * $a;
            $multsB[] = $i * $b;

            $common = array_intersect($multsA, $multsB);
            if ($common) {
                $lcm = array_shift($common);
                break;
            }
        }
        return $lcm;
    }
}
```
The `getLCM()` function will find the lowest common multiple.

**Subtracting Fractions**

Subtracting fractions is similar to addition, you need to find the LCM and then subtract the numerators:

```php
public function subtract(Fraction $a, Fraction $b) : Fraction
{
    $lcm = $this->getLCM($a->denominator, $b->denominator);
    // convert the numerators
    $newA = $a->numerator * ($lcm / $a->denominator);
    $newB = $b->numerator * ($lcm / $b->denominator);
    $sum = new Fraction($newA - $newB, $lcm);
    return $sum->simplify();
}
```
**Multiplying Fractions**

To multiply fractions, you do not need to find the LCM. Simply multiply the numerators together and multiply the denominators together:
```php
 public function multiply(Fraction $a, Fraction $b):Fraction
 {
     $numProduct = $a->numerator * $b->numerator;
     $numDenominator = $a->denominator * $b->denominator;
     $prod = new Fraction($numProduct, $numDenominator);
     return $prod->simplify();
 }
```
**Dividing Fractions**

To divide fractions, invert the second fraction and multiply:
```php
 public function divide(Fraction $a, Fraction $b) : Fraction
 {
     $numProduct = $a->numerator * $b->denominator;
     $numDenominator = $a->denominator * $b->numerator;
     $quotient = new Fraction($numProduct, $numDenominator);
     return $quotient->simplify();
 }
```
**Pretty Printing**

The output of fractions can be improved, so that it outputs whole numbers and fractions.
```php
public function __toString(): string
{
    if ($this->numerator === 0) {
        return '0';
    }
    $out = '';
    if ($this->numerator < 0) {
        $out = '-';
    }
    $whole = intdiv($this->numerator, $this->denominator);
    if ($whole > 0) {
        $out .= $whole;
    }
    $remainder = $this->numerator % $this->denominator;
    if ($remainder > 0) {
        if ($whole > 0) {
           $out .= ' and ';
        }
        $out .= $remainder . '/' . $this->denominator;
    }
    return $out;
}
```
This will output `1 and 11/48` for example.

**Converting Float Strings**

The article also looks at how to convert a currency string into pennies. The string may be formatted as `$100`, `$100.00`, `100`, `100.00` and so on.

**Conclusion**

This article demonstrates the core concepts of fractional math in PHP. It's important to go back to the basics from time to time to ensure that your understanding is complete and robust.

```
   _
  / \
 /___\
|     |
\     /
 ----
```
## Surviving Cybersecurity**

*By Eric Mann*

Eric Mann discusses the challenges and realities of working in cybersecurity, noting that engineers don’t always last long in this field. The article gives you some tips on how to thrive in the field.

**The Realities of Cybersecurity**

Working in cybersecurity involves reacting to incidents and threats, as well as understanding what went wrong and how to fix it. The job can be exciting, as it involves problem-solving, however, it is also very stressful.

**The Need for Resilience**

To succeed in cybersecurity, you need to be resilient. This means being able to handle the stress and pressure of the job and having a passion for problem-solving. A solid foundation of the fundamentals is very useful.

**Conclusion**
This article highlights how demanding cybersecurity can be. Being aware of those demands can help you make an informed choice about this career path, and, if you chose it, how to thrive in the area.

```
  /\\_/\\
 ( o.o )
 >  <
```

## Making Things Happen**

*By Joe Ferguson*

Joe Ferguson explains how to use Makefiles to simplify your life as a developer. `Make` is a tool common to Linux and macOS that helps build just about anything you need.

**What are Makefiles?**

A Makefile allows you to create commands to execute long and complex operations.  Makefiles should be stored in the project's root and checked into version control.  You need to ensure that users have the correct permissions for private repositories, and that SSH is configured for deployments.  You should use `.PHONY` on all your tasks because you are using Make to run commands and not create files from source.

**Basic Makefile**

Add a Makefile to your existing project and add a target. Makefiles use tabs not spaces for indentations. A basic Makefile is:
```makefile
.DEFAULT_GOAL := setup

.PHONY: setup
setup:
  cp .env.example .env
  composer install # must be tab indent
  npm install
  npm run development
  mysql -u root -e "CREATE DATABASE IF NOT EXISTS app;"
  php artisan key:generate
  php artisan migrate

.PHONY: clean
clean:
  rm -rf vendor/
  rm -rf node_modules/
```
In this example, you have a default target of `setup`. By running `make`, the `setup` target will run. You have also defined a `clean` target, that can be used to clean the `vendor` and `node_modules` folder.

**Everyday Tasks**

Make can help you with day-to-day tasks, such as resetting your local database:

```makefile
.PHONY: clean_db
clean_db:
  php artisan migrate:fresh
  php artisan db:seed
```

**Testing**

Make can also run your test suite:
```makefile
.PHONY: test
test:
  php vendor/bin/phpunit
```
**GitHub Actions**

You can use make targets in GitHub Actions, to make the action scripts easier to understand and maintain:
```
steps:
  - name: Checkout Code
    uses: actions/checkout@v1

  - name: Setup PHP
    uses: shivammathur/setup-php@2.9.0
    with:
        php-version: ${{ matrix.php }}
        extensions: dom, curl, libxml, mbstring, zip, pcntl, ...
        coverage: none

  - name: Install dependencies & Setup App
    run: make setup
    env:
        DB_PORT: ${{ job.services.mysql.ports }}

  - name: Execute tests
    run: make test
    env:
       DB_PORT: ${{ job.services.mysql.ports }}
```

