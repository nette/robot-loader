<?php

/**
 * @multiple   50
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

$loader = new Nette\Loaders\RobotLoader;
$loader->setAutoRefresh(true);
$loader->setTempDirectory(__DIR__ . '/../tmp');
$loader->addDirectory(__DIR__);
$loader->register();

assert(class_exists(Foo::class) === false);
assert(class_exists(Unknown::class) === false);
