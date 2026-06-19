---
title: "Contributing to PHP Core - One Test at a Time"
description: "Learn how to contribute tests to the PHP core codebase. Step-by-step guide to cloning php-src, writing PHPT tests, and submitting your first pull request to PHP."
pubDate: "2022-04-10 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP", "PHP Core", "Testing", "PHPT", "Open Source", "Contributing", "PHP Internals", "C"]
selected: false
---

## From User to Contributor

I first touched PHP in the year 2000. Back then I figured it was a neat little scripting language, but surely nobody would build serious software with it. I was wrong. Dead wrong. Two decades later PHP powers something like 78% of the web. More importantly, my entire career stands on its shoulders. Every paycheck, every project, every late-night debugging session that ended in triumph — PHP was there.

That debt adds up. At some point you start looking for ways to give back. You could write blog posts, create packages, or answer questions on Stack Overflow. All valid. But I wanted to go deeper. I wanted to contribute to the engine itself — the C code that makes PHP run.

That sounds intimidating. It is, a little. But here is the thing: you do not need to be a C wizard to contribute to PHP core. You do not need to understand zend internals or memory management or the intricacies of the opcache. You can start with something simpler and arguably just as valuable: tests.

Testing the PHP core is one of the most accessible entry points for new contributors. The format is straightforward, the tooling is built-in, and the core devs absolutely love getting test coverage for edge cases they never thought of. This article walks you through everything you need to write and submit your first PHPT test.

## Why Bother?

Before we get into the mechanics, let us talk about motivation. Why should you spend your free time writing tests for a twenty-five-year-old C codebase?

**Giving back.** PHP has given you a career. Contributing tests is a low-friction way to repay that. You do not need to understand the VM or the garbage collector. You just need to understand PHP itself — which you already do.

**Learning.** Writing tests for the core forces you to think about edge cases you have never considered. What happens when you pass a negative integer to `array_slice()`? What about a float? What about a string that looks like a number? PHP has decades of quirky behavior baked in, and exploring those boundaries will make you a better PHP developer.

**Resume value.** "Submitted 47 test cases to php-src" is a brag that actually means something. It shows you understand the language at a level most developers never reach. It demonstrates open source contribution, attention to detail, and the ability to follow strict contribution guidelines.

**Low barrier to entry.** The PHP test format is intentionally simple. You can learn it in fifteen minutes. From there, contributing is just a matter of finding gaps in coverage and filling them.

## Setting Up Your Environment

You need a working build of PHP from source. The official source lives on GitHub.

```bash
git clone git@github.com:php/php-src.git
cd php-src
```

This clones the entire PHP source tree. It is a big repository — PHP, the extensions, the build system, and thousands of tests. Give it a minute.

### Building PHP from Source

Once you have the source, you need to compile it. Do not overthink this. You do not need every extension enabled. You just need a working PHP binary so you can run tests.

```bash
./buildconf
./configure --with-zlib
make -j $(nproc)
```

`buildconf` generates the configure script. `configure` checks your system for dependencies and generates the Makefile. I pass `--with-zlib` because it is commonly needed, but you can add or remove flags depending on what you want to test. Finally `make` compiles everything. The `-j $(nproc)` flag tells make to use all your CPU cores, which speeds things up considerably.

On Ubuntu or Debian, you might need to install some development packages first:

```bash
sudo apt update
sudo apt install build-essential autoconf libtool bison re2c libxml2-dev libsqlite3-dev
```

Other distributions have equivalent packages. If you run into missing dependency errors, `apt search` or your package manager's equivalent will sort you out.

### The Docker Shortcut

If you do not want to pollute your host system with build dependencies, use Docker. The php-src repository includes a Dockerfile for development.

```bash
docker build -t php-src-dev .
docker run -it --rm -v $(pwd):/usr/src/php php-src-dev
```

This gives you a clean container with everything needed to build and test. Mount your local checkout so changes persist. This is my preferred workflow — clean, repeatable, and it matches what CI runs anyway.

### Verify Your Build

After the build finishes, check that your freshly compiled PHP works:

```bash
sapi/cli/php -v
```

You should see a version string that matches the branch you built from, something like "PHP 8.2.0-dev". The "-dev" suffix confirms you are running an unreleased development version. That is exactly what you want.

## Understanding PHPT Tests

PHP tests use a format called PHPT — PHP Test. The format was originally created for the PHP QA team and has been the standard for core testing ever since.

A PHPT test is a `.phpt` file with several optional sections. The file extension tells the test runner what it is. The sections are delimited by `--SECTION_NAME--` markers.

### The Core Sections

Here is what a minimal test looks like:

```php
--TEST--
Basic string concatenation
--FILE--
<?php
$a = "Hello, ";
$b = "World!";
echo $a . $b;
?>
--EXPECT--
Hello, World!
```

Four sections doing simple work:

**`--TEST--`** — A one-line human-readable description of what the test covers. This shows up in test output and failure messages. Make it descriptive but concise.

**`--FILE--`** — The PHP code to execute. This is the test itself. Write whatever PHP code you need to exercise the feature or function you are testing.

**`--EXPECT--`** — The exact expected output, byte for byte. The test runner executes the code in `--FILE--`, captures stdout, and compares it against `--EXPECT--`. If they match, the test passes. If they do not, it fails.

**`--EXPECTF--`** — A more flexible version of `--EXPECT--` that supports placeholders. Use this when the output might vary in predictable ways — file paths, memory addresses, timestamps. The format uses `%s` for strings, `%d` for integers, `%f` for floats, and `%r...%r` for arbitrary regex.

```php
--TEST--
File path in error message
--FILE--
<?php
$f = fopen("/nonexistent/file.txt", "r");
?>
--EXPECTF--
Warning: fopen(/nonexistent/file.txt): Failed to open stream: No such file or directory in %s on line %d
```

The `%s` and `%d` placeholders make this test pass regardless of where the file lives on disk or what line number the code is on. Without them, you would need to hardcode paths that only work on your machine.

### Additional Sections

You will encounter other sections when reading existing tests:

**`--SKIPIF--`** — PHP code that determines whether the test should run. If this section returns `true` (or any truthy value), the test is skipped. Use this for platform-specific features, extensions that might not be loaded, or PHP version requirements.

**`--CREDITS--`** — Credits for the test author. Include your name and email here. It is optional but encouraged.

**`--INI--`** — INI settings to apply for the duration of the test. This lets you test behavior under specific configurations without modifying your php.ini.

**`--CLEAN--`** — Cleanup code that runs after the test, regardless of whether it passed or failed. Use this to delete temp files or release resources.

### A More Complete Example

Here is a test that uses several sections together:

```php
--TEST--
array_slice() with negative offset
--FILE--
<?php
$input = array("a", "b", "c", "d", "e");
$result = array_slice($input, -2, 2);
var_dump($result);
?>
--EXPECT--
array(2) {
  [0]=>
  string(1) "d"
  [1]=>
  string(1) "e"
}
--CREDITS--
Jane Doe <jane@example.com>
```

This tests that `array_slice()` with a negative offset behaves correctly. The `-2` offset means "start two from the end." The output shows the last two elements, and the keys reset to zero-indexed. If somebody breaks this behavior in a future commit, this test catches it.

## Finding Uncovered Territory

You have a build environment. You understand the format. Now comes the real question: what should you test?

The php-src repository ships with thousands of tests. They live in `ext/` (one subdirectory per extension) and `tests/` (language-level tests). Each extension has its own `tests/` directory. For example, `ext/standard/tests/` holds tests for the standard extension — array functions, string functions, file operations, and so on.

Run the existing test suite to see what coverage looks like:

```bash
make test
```

This runs the entire test suite. It takes a while. On my machine it is about fifteen minutes. When it finishes you get a summary:

```
=====================================================================
Number of tests : 15000              100%
Tests skipped   : 1200 (  8.0%) --------
Tests warned    :    0 (  0.0%) (0.0%)
Tests failed    :    5 (  0.0%)
Expected fail   :   10 (  0.1%)
Tests passed    : 13785 ( 91.9%)
====================================================================---
```

A small number of failures is normal — some tests are environment-specific and fail on certain configurations. Do not worry about those. The key number is `Tests passed`.

### Finding Gaps

Now look for gaps. Run a single extension's tests to see which parts are well-covered and which are thin:

```bash
make test TESTS=ext/standard/tests/
```

Browse through the test directories and look for functions or features with few or no tests. Good candidates:

- **New functions** added in recent PHP versions. Old functions have decades of tests. New ones might only have basic coverage.
- **Edge cases around type juggling.** PHP's type system is full of surprising behavior. Mixed-type comparisons, implicit conversions, and strict type interactions are prime territory.
- **Error conditions.** Most tests cover the happy path. Fewer tests cover what happens when you pass `null` to a function that expects a string, or an object to a function that expects an int.
- **Boundary values.** Zero, negative numbers, very large numbers, empty strings, strings with null bytes, multidimensional arrays, deeply nested arrays — these are all worth testing.
- **Interactions between extensions.** What happens when you call `json_encode()` on a SimpleXML object? These cross-extension interactions are historically under-tested.

The PHP manual itself is a great source of test ideas. Every function page documents parameters, return values, and edge cases. Pick one that looks interesting and write tests for the scenarios the manual describes.

## Writing Your First Test

Let us walk through writing a real test from scratch. I will use `array_chunk()` as an example because it is simple but has interesting edge cases.

Start by understanding the function. `array_chunk()` splits an array into chunks. It takes an array, a size, and an optional `$preserve_keys` boolean. The manual tells us:

- If size is less than 1, it throws a ValueError
- If preserve_keys is true, the original keys are preserved
- If the array length is not evenly divisible, the last chunk is shorter

Let us test the preserve_keys behavior, which is easy to get wrong:

```php
--TEST--
array_chunk() with preserve_keys
--FILE--
<?php
$input = array("a" => 1, "b" => 2, "c" => 3, "d" => 4);
$chunks = array_chunk($input, 3, true);
var_dump($chunks);
?>
--EXPECT--
array(2) {
  [0]=>
  array(3) {
    ["a"]=>
    int(1)
    ["b"]=>
    int(2)
    ["c"]=>
    int(3)
  }
  [1]=>
  array(1) {
    ["d"]=>
    int(4)
  }
}
```

Save this as `ext/standard/tests/array/array_chunk_preserve_keys.phpt`.

Now run it:

```bash
make test TESTS=ext/standard/tests/array/array_chunk_preserve_keys.phpt
```

If the output matches, you see:

```
PASS array_chunk() with preserve_keys
```

If it does not match, the test runner shows you a diff between expected and actual output. Fix either the test or your understanding of the function, and rerun.

### Testing the Error Path

Now test the error case — size less than 1. PHP 8.0 introduced ValueError for this:

```php
--TEST--
array_chunk() with invalid size throws ValueError
--FILE--
<?php
try {
    array_chunk([1, 2, 3], 0);
} catch (ValueError $e) {
    echo $e->getMessage(), "\n";
}
?>
--EXPECT--
array_chunk(): Argument #2 ($length) must be greater than 0
```

Note the error message format. PHP error messages follow specific conventions. If you are unsure what the exact message is, run the code first without an expected output, see what PHP produces, and then write that into `--EXPECT--`. That is not cheating — it is how most people write tests.

### Running a Single Test

Running one test at a time is essential during development:

```bash
make test TESTS=path/to/your/test.phpt
```

The `TESTS` environment variable accepts a file or directory. Point it at your test file and iterate until it passes.

The test runner also supports useful flags. Add `-v` for verbose output and `-q` for quiet mode. `make test` passes these through the `TEST_PHP_ARGS` variable:

```bash
make test TESTS="path/to/test.phpt -v"
```

Verbose output shows you exactly what PHP produced versus what the test expected, which is invaluable when debugging a failing test.

## Running the Full Suite

Before submitting, run the full suite to make sure your test does not break anything else:

```bash
make test
```

This takes time, but it is mandatory. A test that passes in isolation might cause a different test to fail — for example, if it consumes too much memory or creates files that other tests depend on. The full suite catches these interactions.

If you are testing in a specific extension, you can run just that extension's tests as a middle ground:

```bash
make test TESTS=ext/standard/tests/
```

This gives you reasonable confidence without the full fifteen-minute wait.

## Creating a Pull Request

You have a passing test. Now share it with the world.

Start by forking php-src on GitHub. Click the fork button in the top-right corner of the repository page. Then add your fork as a remote:

```bash
git remote add mine git@github.com:your-username/php-src.git
```

Create a branch for your change:

```bash
git checkout -b test-array-chunk-preserve-keys
git add ext/standard/tests/array/array_chunk_preserve_keys.phpt
git commit -m "Add test for array_chunk() with preserve_keys"
git push mine test-array-chunk-preserve-keys
```

Now open a pull request on GitHub from your branch to the main php-src repository. There is no strict template for PR descriptions, but include:

1. What the test covers
2. Why coverage was missing (new function, discovered gap, etc.)
3. The PHP versions this applies to

Keep the scope narrow. One test per PR is fine. Multiple tests for the same function or feature in one PR is also fine. Do not mix unrelated tests in the same PR — it makes review harder.

## What Happens After Submission

Submitting is not the end. It is the beginning of a review process.

**Automated CI.** GitHub Actions kicks off immediately. PHP builds on multiple platforms (Linux, macOS, Windows) with multiple configurations (debug, non-debug, ZTS, NTS). Your test must pass on all of them. CI usually finishes within 30-60 minutes.

**Human review.** A PHP internals developer looks at your test. They check that it tests something meaningful, that it matches the expected behavior, and that it does not duplicate existing tests. They might ask for changes — a better description, additional edge cases, or a different approach.

**Iteration.** This is normal. Do not take feedback personally. Internals developers are protective of the test suite because flaky tests waste everyone's time. If they ask for changes, make them and push to the same branch. The PR updates automatically.

**Merging.** Once a reviewer is satisfied, they merge your test into the main branch. A few hours later it is part of the PHP source code, distributed to everyone who clones php-src. Your test will run on every future commit, forever protecting against regressions.

### Potential Issues

Some things cause tests to be rejected or require rework:

- **Flaky tests.** If your test depends on timing, random values, or external resources, it will fail intermittently. PHP internals hates flaky tests. Avoid `sleep()`, random data, and network calls.
- **Duplicated coverage.** Search for existing tests before writing new ones. The test suite is large. Your edge case might already be covered.
- **Wrong branch.** If you submit a test for a feature that only exists in PHP 8.2, target the `master` branch. If it applies to an older maintained version, target the appropriate release branch. Check which branches are active before opening your PR.
- **Missing SKIPIF.** If your test uses an extension that might not be compiled, add a `--SKIPIF--` section to gracefully skip when the extension is unavailable.

## Tips from Experience

I have submitted dozens of tests to php-src over the last year. Here is what I have learned.

**Start small.** Your first contribution should be one test file testing one function. Do not try to write a comprehensive test suite for an entire extension. Build confidence first.

**Read existing tests.** Before writing your own, read ten or twenty existing tests in the same directory. You will pick up conventions around formatting, naming, and section usage. The existing tests are your style guide.

**Use `--EXPECTF--` when in doubt.** Exact string matching breaks when environments differ. A trailing whitespace difference or a slightly different file path will fail your test on someone else's machine. `--EXPECTF--` is more forgiving and often more appropriate.

**Test both sides of every boundary.** If a function accepts integers from 1 to 100, test 0, 1, 100, and 101. The boundaries are where bugs live.

**Test type coercion explicitly.** PHP converts types automatically in many contexts. Write tests that pass a string where an int is expected, or a float where an array is expected. If the behavior is not documented, your test clarifies it for everyone.

**Do not test the same thing twice.** If there are already five tests for the basic behavior of `str_replace()`, your sixth test for the same thing adds no value. Find uncovered corners instead.

**Engage with the community.** The PHP internals mailing list and the `#php.pecl` channel on EFNet are where core developers hang out. Ask questions before writing. Someone might tell you "that test already exists in a different directory" or "that behavior is intentional, do not test it." A five-minute question saves an hour of wasted effort.

**Be patient.** PHP cores developers are volunteers. Reviews take time. A PR might sit for weeks. That does not mean it was ignored — it means the reviewer has not gotten to it yet. Politely bump the thread after a month if there is no activity.

## Beyond Tests

Once you are comfortable with PHPT tests, consider expanding your contributions.

**Documentation fixes.** If a test reveals behavior that the manual gets wrong, fix the manual. PHP documentation is on GitHub too (php/doc-en).

**C fixes.** After writing enough tests, you will start noticing patterns in how PHP's C code works. A simple bug fix — like a null pointer check or an off-by-one error — is a natural next step. Your tests gave you the safety net to make changes confidently.

**Reviewing other tests.** Once you have contributed, you have earned the right to review. Look at open PRs that add tests. Review them the way you want yours reviewed. This helps the community scale.

## The Impact

Every test you add makes PHP more stable. It sounds dramatic, but it is true. PHP runs on millions of servers. Every regression caught by a test is a production outage that never happens, a bug report that never gets filed, a late-night debugging session that never needs to happen.

The test suite is a collectively owned safety net. It protects users, developers, and the language itself from accidental breakage. Adding a single test file takes fifteen minutes. That fifteen minutes of your time is then amplified across every future PHP execution for the rest of the language's lifespan.

That is leverage. That is worth doing.

PHP gave me a career. Writing tests is my way of paying it forward, one edge case at a time. The barrier is lower than you think. The community is welcoming. And the feeling of seeing your name in the credits of a PHP release — that is pretty cool too.

Clone php-src, build it, and write your first test today. The language you love will be better for it.
