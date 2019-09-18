<?php

/**
 * Test: Nette\Loaders\RobotLoader rebuild only once.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


file_put_contents(getTempDir() . '/file1.php', '<?php class A {}');
file_put_contents(getTempDir() . '/file2.php', '<?php class B {}');

$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory(getTempDir());
$loader->register(); // rebuilds cache

rename(getTempDir() . '/file1.php', getTempDir() . '/file3.php');

Assert::false(class_exists('A'));


$loader2 = new RobotLoader;
$loader2->setTempDirectory(getTempDir());
$loader2->addDirectory(getTempDir());
$loader2->register();

Assert::true(class_exists('A'));

rename(getTempDir() . '/file2.php', getTempDir() . '/file4.php');

Assert::false(class_exists('B'));
