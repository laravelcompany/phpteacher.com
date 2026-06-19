---
title: "A Guide to Practical PHP FFI Usage - Call C Libraries From PHP"
description: "Learn PHP FFI with practical examples using DuckDB. Master foreign function interface to call C libraries, manage memory, and extend PHP beyond its limits."
pubDate: "2023-03-20 21:00:00"
category: "php"
banner: "/logo.svg"
tags: ["PHP FFI", "Foreign Function Interface", "DuckDB", "C Libraries", "PHP 8.1", "PHP Extensions", "Memory Management", "PHP Performance"]
selected: false
---

PHP 7.4 introduced typed properties, preloading, and the Foreign Function Interface (FFI). Most developers embraced typed properties immediately. FFI remained mysterious — an advanced feature for niche use cases. But FFI is one of the most powerful tools PHP has ever received. It lets you call C libraries directly from PHP without writing a single C extension.

Imagine connecting to a database that has no PHP driver, accelerating math operations by calling optimized C code, or controlling hardware directly from PHP. All of this is possible with FFI.

The Foreign Function Interface is a mechanism that allows code written in one programming language to call routines written in another. For PHP, this means you can load shared libraries (`.so` files on Linux, `.dll` on Windows) and invoke their functions as if they were native PHP methods. No compilation toolchains, no PECL extensions, no complicated deployment procedures.

This guide teaches you practical PHP FFI usage through real examples. You will learn the core concepts, build a complete integration with DuckDB (an embedded analytical database), understand C memory management from PHP's safe world, and walk away with patterns you can apply to any C library.

## What You'll Learn

- How FFI differs from PHP extensions and when to use each
- Creating proxy objects with `FFI::cdef()` and `FFI::load()`
- Working with C data types, pointers, and structures in PHP
- Building a complete DuckDB integration with zero PHP extensions
- Managing C memory from PHP to avoid leaks and crashes
- Best practices for secure and maintainable FFI code

## Understanding PHP FFI Basics

FFI in PHP works by parsing C function declarations and creating a bridge between the PHP engine and shared libraries. The core API is deceptively simple — two static methods and a handful of instance methods handle most use cases.

### Creating an FFI Proxy Object

The entry point is `FFI::cdef()`. This method accepts a C function declaration and a library name, returning an FFI proxy object. You call C functions as methods on this proxy.

```php
$ffi = FFI::cdef(
    'int abs(int j);',
    'libc.so.6'
);

var_dump($ffi->abs(-42)); // int(42)
```

The first argument is a raw C declaration. PHP FFI parses it to understand the function signature — parameter types, return type, and calling convention. The second argument specifies which shared library to load. On Linux, the standard C math library is `libc.so.6`. On Windows with WSL, the same path works.

**Explanation:** This example calls the C `abs()` function from the standard library. PHP FFI handles type conversion automatically for simple types like `int`, `float`, and `bool`. The return value is a native PHP integer.

### Loading Header Files

Putting all declarations inline with `cdef()` becomes impractical for real libraries. FFI provides `FFI::load()` which reads declarations from a file — similar to C header files.

```php
$ffi = FFI::load('mylib-ffi.h');
```

Header files are a C concept unfamiliar to most PHP developers. They contain function declarations, structure definitions, and macro constants shared across source files. C requires forward declarations for everything — functions, structures, and enums must be declared before use.

When writing FFI header files for PHP, keep these rules in mind:

- Variable types must always be explicitly declared
- Return types appear before the function name (C style)
- Not all C data types have PHP equivalents
- C preprocessor directives (`#include`, `#define`) are not supported by FFI — you must resolve them manually

## C Data Types in PHP FFI

Calling C functions means working with C data types. These fall into three categories, each with different handling requirements.

### Basic Data Types

Integers, floats, booleans, and `void` convert automatically between PHP and C. Pass them to FFI functions like regular PHP values. Return values come back as native PHP types.

```php
$result = $ffi->someCFunction(42, 3.14, true);
```

### Extended Data Types

Arrays, strings, and pointers require explicit creation via `FFI::new()`. Strings deserve special attention: PHP automatically converts PHP strings to C strings when passed as function parameters, but returned strings are **not** automatically converted back.

```php
$cArray = $ffi->new('int[10]');
$cArray[0] = 42;
```

### User-Defined Types

Structures and enums must be declared in the FFI header file or `cdef()` call. Create instances with `FFI::new()`. Access properties using object syntax.

```php
$database = $ffi->new('duckdb_database');
$connection = $ffi->new('duckdb_connection');
```

Enum values are accessible as dynamic properties on the FFI instance:

```php
if ($result === $ffi->DuckDBError) {
    // handle error
}
```

## Working With Pointers

Pointers are the most challenging concept for PHP developers learning FFI. C uses pointers extensively — arrays are pointers, strings are pointers, and functions often accept pointers to output parameters.

PHP has no pointer type. The closest equivalent is a reference, but references and pointers behave differently. FFI provides `FFI::addr()` to obtain a pointer from a C data type created with `FFI::new()`.

```php
$result = $ffi->duckdb_open(null, FFI::addr($database));
```

This pattern appears constantly in FFI code. The C function expects a pointer to a `duckdb_database` structure where it will write the database handle. You create the structure, get its address, and pass the pointer.

**Explanation:** `FFI::addr()` returns a pointer that is only valid for C data types — arrays, strings, and structures created through FFI. You cannot take the address of a regular PHP variable. The pointer is "non-owned" in FFI terminology, meaning PHP's garbage collector does not manage its lifetime.

## Building a DuckDB Integration With PHP FFI

DuckDB is an in-process, columnar, SQL OLAP database written in C++. Think of it as "SQLite for analytics." Its main strength is analytical query processing against large datasets. DuckDB has no official PHP extension, making it a perfect candidate for FFI.

### Setting Up the Environment

Download the prebuilt DuckDB library for Linux from the official website. The archive includes `libduckdb.so` and `duckdb.h` — the shared library and its header file.

The header file needs preparation before FFI can consume it. DuckDB's header uses C preprocessor macros that PHP FFI does not support. Remove or comment out the `#include` directives for system headers:

```
#include <stdbool.h>
#include <stdint.h>
#include <stdlib.h>
```

These includes would pull in complete function implementations, which FFI cannot handle. After cleaning the includes, use the C preprocessor to resolve remaining macros:

```bash
echo '#define FFI_LIB "./libduckdb.so"' >> duckdb-ffi.h
cpp -P -C -D"attribute(ARGS)=" duckdb.h >> duckdb-ffi.h
```

The `FFI_LIB` directive tells PHP which library to load. The `cpp` command runs the C preprocessor, expanding macros while preserving comments and line numbers.

### Opening a Database Connection

With the prepared header, loading the library and opening an in-memory database takes only a few lines:

```php
$dbFFI = FFI::load('duckdb-ffi.h');

$database = $dbFFI->new('duckdb_database');
$connection = $dbFFI->new('duckdb_connection');

$result = $dbFFI->duckdb_open(
    null,
    FFI::addr($database)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot open database');
}
```

**Explanation:** `duckdb_open()` expects a file path (null for in-memory database) and a pointer to a `duckdb_database` structure. The function returns a `duckdb_state` enum — either `DuckDBSuccess` or `DuckDBError`. Error handling requires cleaning up allocated resources before throwing.

### Connecting and Creating Tables

After opening the database, establish a connection and execute DDL statements:

```php
$result = $dbFFI->duckdb_connect(
    $database,
    FFI::addr($connection)
);

if ($result === $dbFFI->DuckDBError) {
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception('Cannot connect to database');
}

$result = $dbFFI->duckdb_query(
    $connection,
    'CREATE TABLE integers(i INTEGER, j INTEGER);',
    null
);

$result = $dbFFI->duckdb_query(
    $connection,
    'INSERT INTO integers VALUES (3,4), (5,6), (7, NULL)',
    null
);
```

**Explanation:** Queries that do not return results (DDL and DML) pass `null` as the third parameter. DuckDB's C API follows a consistent pattern: the first parameter is the connection, the second is the SQL string, and the third receives the result structure for SELECT queries.

### Querying Data

Retrieving results requires a `duckdb_result` structure and careful memory management:

```php
$queryResult = $dbFFI->new('duckdb_result');

$result = $dbFFI->duckdb_query(
    $connection,
    'SELECT * FROM integers;',
    FFI::addr($queryResult)
);

if ($result === $dbFFI->DuckDBError) {
    $resultAddr = FFI::addr($queryResult);
    $error = 'Error in query: ' .
        $dbFFI->duckdb_result_error($resultAddr);
    $dbFFI->duckdb_destroy_result($resultAddr);
    $dbFFI->duckdb_disconnect(FFI::addr($connection));
    $dbFFI->duckdb_close(FFI::addr($database));
    throw new Exception($error);
}
```

**Explanation:** The third parameter of `duckdb_query()` is a pointer to a `duckdb_result` structure. After execution, this structure contains metadata about the result set and data access functions.

### Iterating Over Results

DuckDB provides `duckdb_row_count()`, `duckdb_column_count()`, and `duckdb_value_varchar()` to traverse results:

```php
$resultAddr = FFI::addr($queryResult);
$rowCount = $dbFFI->duckdb_row_count($resultAddr);
$colCount = $dbFFI->duckdb_column_count($resultAddr);

for ($row = 0; $row < $rowCount; $row++) {
    for ($column = 0; $column < $colCount; $column++) {
        $value = $dbFFI->duckdb_value_varchar(
            $resultAddr,
            $column,
            $row
        );
        echo ($value !== null ?
            FFI::string($value) :
            '') . ' ';
        $dbFFI->duckdb_free($value);
    }
    echo "\n";
}

$dbFFI->duckdb_destroy_result(FFI::addr($queryResult));
$dbFFI->duckdb_disconnect(FFI::addr($connection));
$dbFFI->duckdb_close(FFI::addr($database));
```

**Explanation:** `duckdb_value_varchar()` returns a C string that DuckDB allocates internally. PHP FFI does not manage this memory — you must call `duckdb_free()` after converting it with `FFI::string()`. The final cleanup calls `duckdb_destroy_result()`, `duckdb_disconnect()`, and `duckdb_close()` in order.

## Memory Management in PHP FFI

PHP is garbage-collected. You rarely think about memory allocation. C is the opposite — every allocation must be matched with a corresponding free. FFI sits between these two worlds, and understanding the boundary is critical for writing correct code.

### Owned vs Non-Owned Memory

Data created with `FFI::new()` is "owned" by PHP. The garbage collector tracks it and frees it when the variable goes out of scope. You do not need to manually release owned memory.

```php
// Owned — PHP manages this
$database = $dbFFI->new('duckdb_database');
```

Pointers obtained with `FFI::addr()` are "non-owned." PHP does not track them. If the original owned object is destroyed, the pointer becomes a dangling reference.

```php
// Non-owned — must be used while the source is alive
$ptr = FFI::addr($database);
```

Data returned by C functions that allocate memory internally is also non-owned. The DuckDB function `duckdb_value_varchar()` allocates a string internally. PHP has no way to know the allocation size or when to free it. The library documentation tells you to call `duckdb_free()` — failure to do so creates a memory leak.

### Cleaning Up Resources

Every C library has its own cleanup protocol. DuckDB requires these steps in order:

1. Destroy result structures with `duckdb_destroy_result()`
2. Disconnect from the database with `duckdb_disconnect()`
3. Close the database with `duckdb_close()`

Missing any step leaks memory. Calling them in the wrong order can crash the process. Always consult the library's documentation for the correct lifecycle.

## Real-World Use Cases for PHP FFI

### Database Drivers for Unsupported Systems

DuckDB is the canonical example, but many databases lack PHP extensions. FFI lets you connect to proprietary analytical databases, time-series databases, and legacy systems without waiting for PECL maintainers.

### Performance-Critical Computations

Image processing, cryptographic operations, scientific simulations, and machine learning inference often have optimized C libraries. FFI lets you call them directly without the overhead of subprocess or HTTP calls.

```php
// Example: calling a hypothetical optimized matrix library
$ffi = FFI::load('fastmath-ffi.h');
$result = $ffi->matrix_multiply($matrixA, $matrixB, $rows, $cols);
```

### Hardware Communication

PHP can control hardware through C libraries that communicate with GPIO pins, serial ports, or USB devices. This opens IoT and embedded applications to PHP developers.

### Legacy Code Integration

Organizations with decades of C business logic can integrate it into modern PHP applications without rewriting. The existing C code runs unchanged, exposed through FFI.

## Best Practices for PHP FFI

### Validate Library Return Values

C functions communicate errors through return codes, not exceptions. Always check return values and handle failures immediately. A single unchecked return value can corrupt memory or produce silent data corruption.

### Use try/finally for Resource Cleanup

Wrap FFI operations in try/finally blocks to ensure cleanup even when exceptions occur:

```php
function queryDuckDB(string $sql): void
{
    $dbFFI = FFI::load('duckdb-ffi.h');
    $database = $dbFFI->new('duckdb_database');
    $connection = $dbFFI->new('duckdb_connection');

    try {
        // ... FFI operations ...
    } finally {
        $dbFFI->duckdb_disconnect(FFI::addr($connection));
        $dbFFI->duckdb_close(FFI::addr($database));
    }
}
```

### Keep FFI Calls Isolated

Wrap FFI interactions behind interfaces. This lets you test business logic without loading C libraries and makes it possible to swap implementations:

```php
interface DatabaseInterface
{
    public function query(string $sql): array;
}

class DuckDBFFIAdapter implements DatabaseInterface
{
    public function query(string $sql): array
    {
        // FFI implementation
    }
}
```

### Containerize Your Environment

FFI depends on specific shared library versions and architectures. Use Docker to ensure consistency across development, staging, and production environments. The same `libduckdb.so` must exist in all environments.

## Common Mistakes to Avoid

### Forgetting to Free C-Allocated Memory

The most common FFI bug. Every call to a C function that returns allocated memory must be matched with the corresponding free function. Use a mental checklist: allocate, use, free.

### Ignoring Return Values

C functions rarely throw exceptions. Ignoring a `duckdb_state` return value means you will miss errors. Always check and handle every return code.

### Using Stale Pointers

A pointer from `FFI::addr()` becomes invalid when the original FFI-created object is garbage collected. Keep the source object alive as long as the pointer is in use.

### Loading Unmodified Header Files

Most C headers include system headers and use macros. You must preprocess the header file before FFI can load it. The `cpp` command is your friend.

### Assuming Platform Compatibility

FFI behavior differs across operating systems. Library paths, naming conventions, and calling conventions vary. Test on your target deployment platform.

## Frequently Asked Questions

### What PHP version do I need for FFI?

PHP 7.4 introduced FFI, but PHP 8.1 or later is recommended. PHP 8.1 added fiber support and various FFI improvements that make integration smoother.

### Is FFI faster than PHP extensions?

Not necessarily. FFI adds a thin layer of marshalling between PHP and C. For simple function calls, the overhead is negligible. For data-intensive operations, a native PHP extension may be faster because it avoids the marshalling step.

### Can I use FFI on Windows or macOS?

FFI works on any platform with shared library support. On Windows, use `.dll` files. On macOS, use `.dylib`. The Linux examples in this article run on Windows through WSL.

### Is FFI secure?

FFI bypasses PHP's memory safety guarantees. A bug in the C library or incorrect FFI usage can crash the process, corrupt memory, or introduce security vulnerabilities. Treat FFI code with the same care as C code.

### Can I use FFI with object-oriented C++ libraries?

FFI works with C linkage. C++ libraries expose C functions if they use `extern "C"`. Pure C++ classes with virtual methods are not directly accessible through FFI.

### Do I need to install anything special to use FFI?

FFI is built into PHP since 7.4. You need the `ffi` extension enabled (`extension=ffi` in `php.ini`). On some systems, you may need `FFI::cdef()` disabled for security — check `ffi.enable` in your configuration.

### How do I debug FFI code?

Use `FFI::typeof()` to inspect C data types at runtime. Enable PHP's `ffi` warning logs. For segmentation faults, use GDB or Valgrind to trace the crash.

### Can FFI call functions from multiple libraries?

Yes. Create separate FFI instances for each library, or declare functions from multiple libraries in a single header file. Each `FFI::load()` or `FFI::cdef()` call creates an independent proxy.

### Will FFI code work in production?

Yes, with caveats. Containerize your deployment to ensure library availability. Monitor for memory leaks. Prefer native PHP extensions when they exist — FFI is best for libraries without PHP support.

### What happens if the shared library is missing?

PHP throws a fatal error when calling `FFI::load()` or `FFI::cdef()` if the library file is not found. Always validate library availability during your deployment process.

## Conclusion

PHP FFI opens a new world of possibilities. You can integrate with any C library, connect to databases without PHP drivers, accelerate performance-critical code, and extend PHP into domains that were previously inaccessible.

The DuckDB integration we built demonstrates the complete pattern: prepare the header file, load the library, create C data structures, call functions, manage memory, and clean up. This same pattern applies to any C library with a shared object file.

With great power comes great responsibility. FFI bypasses PHP's safety nets. Memory leaks, segmentation faults, and security vulnerabilities become possible if you are careless. But with careful engineering — thorough error checking, proper cleanup, and tested isolation layers — FFI is a valuable tool in any PHP developer's arsenal.

Start with a well-documented C library, containerize your environment, and build an isolated adapter layer. You will be surprised at how much C code you can productively call from PHP.

Try integrating DuckDB into your next analytics project, or explore other C libraries that solve problems in your domain. The FFI documentation and the library's C API reference are your guides.
