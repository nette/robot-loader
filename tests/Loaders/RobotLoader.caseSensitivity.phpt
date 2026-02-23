<?php declare(strict_types=1);

/**
 * Test: Nette\Loaders\RobotLoader case sensitivity.
 */

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory(__DIR__ . '/files');
$loader->register();

Assert::false(class_exists('testClass'));
