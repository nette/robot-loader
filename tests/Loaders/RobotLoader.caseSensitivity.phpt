<?php

/**
 * Test: Nette\Loaders\RobotLoader case sensitivity.
 */

use Nette\Loaders\RobotLoader;
use Nette\Caching\Storages\DevNullStorage;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setCacheStorage(new DevNullStorage);
$loader->addDirectory(__DIR__ . '/files');
$loader->register();

Assert::error(function () {
	Assert::true(class_exists('testClass'));
}, E_USER_WARNING, "Case mismatch on class name 'testClass', correct name is 'TestClass'.");
