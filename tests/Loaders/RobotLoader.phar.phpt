<?php

/**
 * Test: Nette\Loaders\RobotLoader loading from PHAR.
 *
 * @phpIni phar.readonly=0
 */

declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$pharFile = getTempDir() . '/test.phar';

$phar = new Phar($pharFile);
$phar['class.A.php'] = '<?php class A {}';
$phar['class.B.php'] = '<?php class B {}';
$phar['class.C.php'] = '<?php class C {}';
$phar['sub/class.D.php'] = '<?php class D {}';
$phar->setStub('<?php __HALT_COMPILER();');
unset($phar);

Assert::true(is_file($pharFile));
Phar::loadPhar($pharFile, 'test.phar');


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory("phar://$pharFile/sub");
$loader->addDirectory("PHAR://$pharFile/class.B.php");
$loader->addDirectory('phar://test.phar/class.C.php');
$loader->register();

Assert::false(class_exists('A'));
Assert::true(class_exists('B'));
Assert::true(class_exists('C'));
Assert::true(class_exists('D'));


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory("phar://$pharFile/non-dir");
Assert::exception(
	fn() => $loader->rebuild(),
	Nette\IOException::class,
	"File or directory 'phar://$pharFile/non-dir' not found.",
);


$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());
$loader->addDirectory("phar://$pharFile/non-file.php");
Assert::exception(
	fn() => $loader->rebuild(),
	Nette\IOException::class,
	"File or directory 'phar://$pharFile/non-file.php' not found.",
);
