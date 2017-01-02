<?php

/**
 * Test: Nette\Loaders\RobotLoader case sensitivity.
 */

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setTempDirectory(TEMP_DIR);
$loader->addDirectory(__DIR__ . '/files');
$loader->register();

Assert::false(class_exists('testClass'));
