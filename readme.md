[![RobotLoader](https://github.com/nette/robot-loader/assets/194960/53a155e2-5959-44c5-8944-d1b9ec203923)](https://doc.nette.org/en/robot-loader)

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/robot-loader.svg)](https://packagist.org/packages/nette/robot-loader)
[![Tests](https://github.com/nette/robot-loader/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/robot-loader/actions)
[![Coverage Status](https://coveralls.io/repos/github/nette/robot-loader/badge.svg?branch=master)](https://coveralls.io/github/nette/robot-loader?branch=master)
[![Latest Stable Version](https://poser.pugx.org/nette/robot-loader/v/stable)](https://github.com/nette/robot-loader/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/robot-loader/blob/master/license.md)


Introduction
------------

RobotLoader is a tool that gives you comfort of automated class loading for your entire application including third-party libraries.

✅ get rid of all `require`<br>
✅ doesn't require strict naming conventions for directories or files<br>
✅ extremely fast<br>
✅ no manual cache updates, everything runs automatically<br>
✅ mature, stable and widely used library<br>

Thus, we can forget about these familiar code blocks:

```php
require_once 'Utils/Page.php';
require_once 'Utils/Style.php';
require_once 'Utils/Paginator.php';
...
```

 <!---->

[Support Me](https://github.com/sponsors/dg)
--------------------------------------------

Do you like RobotLoader? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!

 <!---->

Installation
------------

You can download RobotLoader as a [single standalone file `RobotLoader.php`](https://github.com/nette/robot-loader/raw/standalone/src/RobotLoader/RobotLoader.php), which you include using `require` in your script, and instantly enjoy comfortable autoloading for the entire application.

```php
require '/path/to/RobotLoader.php';

$loader = new Nette\Loaders\RobotLoader;
// ...
```

If you're building an application using [Composer](https://doc.nette.org/en/best-practices/composer), you can install it via:

```shell
composer require nette/robot-loader
```

It requires PHP version 8.1 and supports PHP up to 8.5.

 <!---->

Usage
-----

Similar to how the Google robot crawls and indexes web pages, the [RobotLoader](https://api.nette.org/robot-loader/master/Nette/Loaders/RobotLoader.html) goes through all PHP scripts and notes which classes, interfaces, traits and enums it found. It then stores the results in cache for use in subsequent requests. You just need to specify which directories it should go through and where to store the cache:

```php
$loader = new Nette\Loaders\RobotLoader;

// Directories for RobotLoader to index (including subdirectories)
$loader->addDirectory(__DIR__ . '/app');
$loader->addDirectory(__DIR__ . '/libs');

// Set caching to the 'temp' directory
$loader->setTempDirectory(__DIR__ . '/temp');
$loader->register(); // Activate RobotLoader
```

And that's it, from this point on, we don't need to use `require`. Awesome!

If RobotLoader encounters a duplicate class name during indexing, it will throw an exception and notify you. RobotLoader also automatically updates the cache when it needs to load an unknown class. We recommend turning this off on production servers, see [#Caching].

If you want RobotLoader to skip certain directories, use `$loader->excludeDirectory('temp')` (can be called multiple times or pass multiple directories).

By default, RobotLoader reports errors in PHP files by throwing a `ParseError` exception. This can be suppressed using `$loader->reportParseErrors(false)`.

 <!---->

PHP Files Analyzer
------------------

RobotLoader can also be used purely for finding classes, interfaces, traits and enums in PHP files **without** using the autoloading function:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');

// Scans directories for classes/interfaces/traits/enums
$loader->rebuild();

// Returns an array of class => filename pairs
$res = $loader->getIndexedClasses();
```

Even with such usage, you can utilize caching. This ensures that unchanged files won't be rescanned:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');

// Set caching to the 'temp' directory
$loader->setTempDirectory(__DIR__ . '/temp');

// Scans directories using cache
$loader->refresh();

// Returns an array of class => filename pairs
$res = $loader->getIndexedClasses();
```

 <!---->

Caching
-------

RobotLoader is very fast because it cleverly uses caching.

During development, you hardly notice it running in the background. It continuously updates its cache, considering that classes and files can be created, deleted, renamed, etc. And it doesn't rescan unchanged files.

On a production server, on the other hand, we recommend turning off cache updates using `$loader->setAutoRefresh(false)` (in a Nette Application, this happens automatically), because files don't change. At the same time, it's necessary to **clear the cache** when uploading a new version to hosting.

The initial file scanning, when the cache doesn't exist yet, can naturally take a moment for larger applications. RobotLoader has built-in prevention against [cache stampede](https://en.wikipedia.org/wiki/Cache_stampede).
This is a situation where a large number of concurrent requests on a production server would trigger RobotLoader, and since the cache doesn't exist yet, they would all start scanning files, which would overload the server.
Fortunately, RobotLoader works in such a way that only the first thread indexes the files, creates the cache, and the rest wait and then use the cache.

 <!---->

PSR-4
-----

Nowadays, you can use [Composer for autoloading](https://doc.nette.org/en/best-practices/composer#toc-autoloading) while adhering to PSR-4. Simply put, it's a system where namespaces and class names correspond to the directory structure and file names, e.g., `App\Router\RouterFactory` will be in the file `/path/to/App/Router/RouterFactory.php`.

RobotLoader isn't tied to any fixed structure, so it's useful in situations where you don't want to have the directory structure designed exactly like the PHP namespaces, or when developing an application that historically doesn't use such conventions. It's also possible to use both loaders together.


If you like RobotLoader, **[please make a donation now](https://nette.org/donate)**. Thank you!
