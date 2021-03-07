<?php

/**
 * Test: Nette\Loaders\RobotLoader basic usage.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory(__DIR__ . '/files');
$loader->addDirectory(__DIR__ . '/files/'); // purposely doubled
$loader->addDirectory(__DIR__ . '/file/interface.php'); // as file
$loader->addDirectory(__DIR__ . '/file/class.const.php');
$loader->addDirectory(__DIR__ . '/file/trait.php');
$loader->excludeDirectory(__DIR__ . '/files/exclude');
$loader->excludeDirectory(__DIR__ . '/files/exclude2/excluded.php');
$loader->register();

Assert::false(class_exists('ConditionalClass'));   // files/conditional.class.php
Assert::true(interface_exists('TestInterface'));   // file/interface.php
Assert::true(trait_exists('TestTrait')); // file/trait.php

Assert::true(class_exists('TestClass'));           // files/namespaces1.php
Assert::true(class_exists('MySpace1\TestClass1')); // files/namespaces1.php
Assert::true(class_exists('MySpace2\TestClass2')); // files/namespaces2.php
Assert::true(class_exists('MySpace3\TestClass3')); // files/namespaces2.php

Assert::false(class_exists('ExcludedClass')); // files/exclude/excluded.php
Assert::false(class_exists('Excluded2Class')); // files/exclude2/excluded.php
