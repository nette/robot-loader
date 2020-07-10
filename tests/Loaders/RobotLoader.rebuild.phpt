<?php

/**
 * Test: Nette\Loaders\RobotLoader rebuild only once.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$dir = getTempDir() . '/fixtures';
mkdir($dir);
file_put_contents($dir . '/file1.php', '<?php class A {}');
file_put_contents($dir . '/file2.php', '<?php class B {}');

$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory($dir);
$loader->register();
class_exists('x'); // rebuilds cache

rename($dir . '/file1.php', $dir . '/file3.php');

Assert::false(class_exists('A'));


$loader2 = new RobotLoader;
$loader2->setTempDirectory(getTempDir());
$loader2->addDirectory($dir);
$loader2->register();

Assert::true(class_exists('A'));

rename($dir . '/file2.php', $dir . '/file4.php');

Assert::false(class_exists('B'));
