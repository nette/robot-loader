# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Nette RobotLoader is a high-performance PHP autoloader library that automatically discovers and loads classes, interfaces, traits, and enums without requiring strict PSR-4 naming conventions. It's part of the Nette Framework ecosystem but works as a standalone component.

**Key characteristics:**
- Single-class implementation (~500 LOC in `src/RobotLoader/RobotLoader.php`)
- Intelligent caching with platform-specific optimizations (Linux vs Windows)
- Cache stampede prevention for production environments
- Token-based PHP file parsing using `\PhpToken::tokenize()`
- Automatic cache invalidation on file changes in development mode

## Essential Commands

### Testing
```bash
# Run all tests
composer run tester

# Run specific test file
vendor/bin/tester tests/Loaders/RobotLoader.phpt -s

# Run tests in a specific directory
vendor/bin/tester tests/Loaders/ -s

# Run tests with verbose output (-s shows skipped tests)
vendor/bin/tester tests -s -p php
```

### Code Quality
```bash
# Static analysis (PHPStan level 5)
composer run phpstan
```

### Development
```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Generate API documentation (if available)
# See https://api.nette.org/robot-loader/
```

## Architecture

### Core Component: RobotLoader Class

The entire library is a single class (`Nette\Loaders\RobotLoader`) with these primary responsibilities:

1. **Directory Scanning** - Recursively indexes PHP files using `Nette\Utils\Finder`
2. **Class Extraction** - Token-based parsing to find classes/interfaces/traits/enums
3. **Caching System** - Three-tier cache: `$classes`, `$missingClasses`, `$emptyFiles`
4. **Autoloading** - Registers with PHP's `spl_autoload_register()`
5. **Change Detection** - mtime-based incremental updates

### State Management

```php
// Three-tier caching state
private array $classes = [];           // class => [file, mtime]
private array $missingClasses = [];    // class => retry_counter (max 3)
private array $emptyFiles = [];        // file => mtime (optimization)
```

### Platform-Specific Optimizations

**Linux:** Direct cache include without locks + atomic rename
**Windows:** Mandatory file locking (files can't be renamed while open)

The code checks `PHP_WINDOWS_VERSION_BUILD` constant to determine the platform and adjusts locking strategy accordingly.

### Cache Stampede Prevention

When multiple concurrent requests hit production before cache exists:
1. First request acquires exclusive lock (LOCK_EX)
2. Subsequent requests wait with shared lock (LOCK_SH)
3. After first request builds cache, others reuse it
4. Double-check pattern: re-read cache after acquiring exclusive lock

### Key Methods

- `addDirectory()` / `excludeDirectory()` - Configure scan paths
- `register()` - Activate autoloader (calls `spl_autoload_register()`)
- `refresh()` - Smart cache refresh (scans only changed files)
- `rebuild()` - Full rebuild from scratch
- `scanPhp()` - Token-based class extraction from PHP files
- `loadCache()` / `saveCache()` - Atomic cache operations with locking

## Usage Patterns

### Standalone Usage

Basic setup for any PHP application:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');
$loader->addDirectory(__DIR__ . '/libs');
$loader->setCacheDirectory(__DIR__ . '/temp');
$loader->register(); // Activate RobotLoader
```

### Nette Application Integration

When used within a Nette Application (recommended approach), RobotLoader setup is simplified through the `$configurator` object in `Bootstrap.php`:

```php
$configurator = new Nette\Bootstrap\Configurator;
// ...
$configurator->setCacheDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../libs')
	->register();
```

**Benefits:**
- Automatic temp directory configuration
- Fluent interface for directory setup
- Auto-refresh automatically disabled in production mode
- Integrated with Nette Application lifecycle

### As PHP Files Analyzer (Without Autoloading)

RobotLoader can be used purely for indexing classes without autoloading:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');
$loader->setCacheDirectory(__DIR__ . '/temp');
$loader->refresh(); // Scans directories using cache
$classes = $loader->getIndexedClasses(); // Returns class => file array
```

Use `rebuild()` instead of `refresh()` to force full rebuild from scratch.

### RobotLoader vs PSR-4

**Use RobotLoader when:**
- Directory structure doesn't match namespace structure
- Working with legacy code that doesn't follow PSR-4
- You want automatic discovery without strict conventions
- Need to load from multiple disparate directories

**Use PSR-4 (Composer) when:**
- Building new applications with consistent structure
- Following modern PHP standards strictly
- Directory structure matches namespace structure (e.g., `App\Core\RouterFactory` → `/path/to/App/Core/RouterFactory.php`)

**Both can be used together** - PSR-4 for your structured code, RobotLoader for legacy dependencies or non-standard libraries.

### Production vs Development Configuration

**Development:**
```php
$loader->setAutoRefresh(true);  // Default - automatically updates cache
```

**Production:**
```php
$loader->setAutoRefresh(false); // Disable auto-refresh for performance
// Clear cache when deploying: rm -rf temp/cache
```

In Nette Application, this is handled automatically based on debug mode.

## Testing Framework

Uses **Nette Tester** with `.phpt` file format:

```php
<?php
/**
 * Test: Description of what is being tested
 */
declare(strict_types=1);

use Tester\Assert;
require __DIR__ . '/../bootstrap.php';

// Test code using Assert methods
Assert::same('expected', $actual);
Assert::exception(fn() => $code(), ExceptionClass::class, 'Message pattern %a%');
```

### Test Utilities

**`getTempDir()` function** (in `tests/bootstrap.php`):
- Creates per-process temp directories (`tests/tmp/<pid>`)
- Garbage collection with file locking for parallel safety
- Automatically used by tests for cache directories

### Test Coverage Areas

1. **RobotLoader.phpt** - Basic functionality, directory/file scanning, exclusions
2. **RobotLoader.rebuild.phpt** - Cache rebuild behavior
3. **RobotLoader.renamed.phpt** - File rename detection
4. **RobotLoader.caseSensitivity.phpt** - Case-sensitive class matching
5. **RobotLoader.relative.phpt** - Relative path handling
6. **RobotLoader.phar.phpt** - PHAR archive support
7. **RobotLoader.stress.phpt** - Concurrency testing (50 parallel runs via `@multiple`)
8. **RobotLoader.emptyArrayVariadicArgument.phpt** - Edge case handling

## Coding Conventions

Follows **Nette Coding Standard** (based on PSR-12) with these specifics:

- `declare(strict_types=1)` in all PHP files
- Tabs for indentation
- Return type and opening brace on separate lines for multi-parameter methods
- Two spaces after `@param` and `@return` in phpDoc
- Document shut-up operator usage: `@mkdir($dir); // @ - directory may already exist`

### Exception Handling

- `ParseError` - PHP syntax errors in scanned files (configurable)
- `Nette\InvalidStateException` - Duplicate class definitions
- `Nette\IOException` - Directory not found
- `Nette\InvalidArgumentException` - Invalid temp directory
- `RuntimeException` - Cache file write failures, lock acquisition failures

## CI/CD Pipeline

Three GitHub Actions workflows:

1. **Coding Style** - Code checker + coding standard enforcement
2. **Static Analysis** - PHPStan (runs on master branch only)
3. **Tests** - Matrix testing across PHP versions (8.1-8.5)

## Dependencies

- **PHP**: 8.1 - 8.5
- **ext-tokenizer**: Required for PHP parsing
- **nette/utils**: ^4.0 (FileSystem, Finder)
- **Dev**: nette/tester, tracy/tracy, phpstan/phpstan-nette

## Common Development Patterns

### Adding New Functionality

When modifying RobotLoader:
1. Consider backward compatibility (library is mature and widely used)
2. Add comprehensive tests covering edge cases
3. Update phpDoc annotations for IDE support
4. Test on both Linux and Windows if touching file operations
5. Consider performance implications (this is a hot path in applications)

### Debugging Tests

```bash
# Run single test with Tracy debugger
vendor/bin/tester tests/Loaders/RobotLoader.phpt -s

# Check test temp files (not auto-cleaned during failures)
ls tests/tmp/

# Manual cleanup
rm -rf tests/tmp/*
```

### Performance Considerations

- Cache operations are atomic to prevent corruption
- OPcache invalidation after cache updates (`opcache_invalidate()`)
- Lazy initialization - cache loaded only on first autoload attempt
- Empty files tracked separately to avoid re-scanning
- Missing class retry limit (3 attempts) prevents infinite loops
