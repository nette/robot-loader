<?php

/**
 * Test: Nette\Loaders\RobotLoader rebuild only once.
 */

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


file_put_contents(TEMP_DIR . '/file1.php', '<?php class A {}');
file_put_contents(TEMP_DIR . '/file2.php', '<?php class B {}');

$loader = new RobotLoader;
$loader->setTempDirectory(TEMP_DIR);
$loader->addDirectory(TEMP_DIR);
$loader->register(); // rebuilds cache

rename(TEMP_DIR . '/file1.php', TEMP_DIR . '/file3.php');

Assert::false(class_exists('A'));


$loader2 = new RobotLoader;
$loader2->setTempDirectory(TEMP_DIR);
$loader2->addDirectory(TEMP_DIR);
$loader2->register();

Assert::true(class_exists('A'));

rename(TEMP_DIR . '/file2.php', TEMP_DIR . '/file4.php');

Assert::false(class_exists('B'));
