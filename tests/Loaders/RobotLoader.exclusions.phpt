<?php

/**
 * Test: Nette\Loaders\RobotLoader excluding files and minimizing retries.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory(__DIR__ . '/files');
$loader->setRetryLimit(1);
$loader->addExclusion('MySpace1\TestClass1');
$loader->register();

Assert::false(class_exists('MySpace1\TestClass1')); // files/namespaces1.php
Assert::true(class_exists('MySpace2\TestClass2'));  // files/namespaces2.php
