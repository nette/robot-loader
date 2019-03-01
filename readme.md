RobotLoader: comfortable autoloading
====================================

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/robot-loader.svg)](https://packagist.org/packages/nette/robot-loader)
[![Build Status](https://travis-ci.org/nette/robot-loader.svg?branch=master)](https://travis-ci.org/nette/robot-loader)
[![Coverage Status](https://coveralls.io/repos/github/nette/robot-loader/badge.svg?branch=master)](https://coveralls.io/github/nette/robot-loader?branch=master)
[![Latest Stable Version](https://poser.pugx.org/nette/robot-loader/v/stable)](https://github.com/nette/robot-loader/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/robot-loader/blob/master/license.md)


Introduction
------------

RobotLoader is a tool that gives you comfort of automated class loading for your entire application including third-party libraries.

- get rid of all `require`
- requires no strict file naming conventions
- allows more classes in single file
- extremely fast
- no manual cache updates, everything runs automatically
- highly mature, stable and widely used library

RobotLoader is incredibly comfortable and addictive!

If you like Nette, **[please make a donation now](https://nette.org/donate)**. Thank you!

So we can forget about those famous code blocks:

```php
require_once 'Utils/Page.php';
require_once 'Utils/Style.php';
require_once 'Utils/Paginator.php';
...
```

Like the Google robot crawls and indexes websites, RobotLoader crawls all PHP scripts and records what classes and interfaces were found in them.
These records are then saved in cache and used during all subsequent requests.

Documentation can be found on the [website](https://doc.nette.org/robotloader).


Installation
------------

The recommended way to install is via Composer:

```
composer require nette/robot-loader
```

It requires PHP version 7.1.


Usage
-----

You just need to specifiy what directories to index and where to save the cache:

```php
$loader = new Nette\Loaders\RobotLoader;

// Add directories for RobotLoader to index
$loader->addDirectory(__DIR__ . '/app');
$loader->addDirectory(__DIR__ . '/libs');

// And set caching to the 'temp' directory
$loader->setTempDirectory(__DIR__ . '/temp');
$loader->register(); // Run the RobotLoader
```

And that's all. From now on, you don't need to use `require`. Great, isn't it?

When RobotLoader encounters duplicate class name during indexing, it throws an exception and informs you about it.

The `$loader->setAutoRefresh(true or false)` determines whether RobotLoader should reindex files if asked for nonexistent class.
This feature should be disabled on production server.

If you want RobotLoader to skip some directory, use `$loader->excludeDirectory('temp')`.

By default, RobotLoader reports errors in PHP files by throwing exception `ParseError`. It can be disabled via `$loader->reportParseErrors(false)`.


PHP files analyzer
------------------

RobotLoader can also be used to find classes, interfaces, and trait in PHP files without using the autoloading feature:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');

// Scans directories for classes / intefaces / traits
$loader->rebuild();

// Returns array of class => filename pairs
$res = $loader->getIndexedClasses();
```

When scanning files again, we can use the cache and unmodified files will not be analyzed repeatedly:

```php
$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__ . '/app');
$loader->setTempDirectory(__DIR__ . '/temp');

// Scans directories using a cache
$loader->refresh();

// Returns array of class => filename pairs
$res = $loader->getIndexedClasses();
```

Enjoy RobotLoader!
