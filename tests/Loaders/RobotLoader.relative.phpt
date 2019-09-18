<?php

/**
 * Test: Nette\Loaders\RobotLoader and relative parent dirs.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory(__DIR__ . '/../Loaders/files');
$loader->excludeDirectory(__DIR__ . '/../Loaders/files/exclude');
$loader->excludeDirectory(__DIR__ . '/../Loaders/files/exclude2/excluded.php');

$loader->register();

Assert::false(class_exists('ExcludedClass')); // files/exclude2/excluded.php
Assert::false(class_exists('Excluded2Class')); // files/exclude2/excluded.php
