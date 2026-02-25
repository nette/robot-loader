<?php declare(strict_types=1);

/**
 * @multiple   50
 */

require __DIR__ . '/../../vendor/autoload.php';

$loader = new Nette\Loaders\RobotLoader;
$loader->setAutoRefresh(true);
$loader->setCacheDirectory(__DIR__ . '/../tmp');
$loader->addDirectory(__DIR__);
$loader->register();

assert(class_exists(Foo::class) === false);
assert(class_exists(Unknown::class) === false);
