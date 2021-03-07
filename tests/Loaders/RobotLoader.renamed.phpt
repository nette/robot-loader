<?php

/**
 * Test: Nette\Loaders\RobotLoader and renamed classes.
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$dir = realpath(getTempDir()) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
mkdir($dir);

$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory($dir);

file_put_contents($dir . 'file1.php', '<?php class A {}');
file_put_contents($dir . 'file2.php', '<?php class B {}');

$loader->register();

Assert::equal([
	'A' => $dir . 'file1.php',
	'B' => $dir . 'file2.php',
], $loader->getIndexedClasses());


rename($dir . 'file1.php', $dir . 'file3.php');

$loader->rebuild();

Assert::equal([
	'A' => $dir . 'file3.php',
	'B' => $dir . 'file2.php',
], $loader->getIndexedClasses());


sleep(2); // filemtime resolution is in seconds
file_put_contents($dir . 'file1.php', '<?php class B {}');
file_put_contents($dir . 'file2.php', '<?php ');

$loader->rebuild();

Assert::equal([
	'A' => $dir . 'file3.php',
	'B' => $dir . 'file1.php',
], $loader->getIndexedClasses());
