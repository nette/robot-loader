<?php

/**
 * Test: Nette\Loaders\RobotLoader loading from PHAR.
 *
 * @phpIni phar.readonly=0
 */

use Nette\Loaders\RobotLoader,
	Nette\Caching\Storages\DevNullStorage,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$pharFile = TEMP_DIR . '/test.phar';

$phar = new Phar($pharFile);
$phar['class.A.php'] = '<?php class A {}';
$phar['class.B.php'] = '<?php class B {}';
$phar['class.C.php'] = '<?php class C {}';
$phar['sub/class.D.php'] = '<?php class D {}';
$phar->setStub('<?php __HALT_COMPILER();');
unset($phar);

Assert::true( is_file($pharFile) );


$loader = new RobotLoader;
$loader->setCacheStorage(new DevNullStorage);
$loader->addDirectory("phar://$pharFile/sub");
$loader->addDirectory("PHAR://$pharFile/class.B.php");
Phar::loadPhar($pharFile, 'test.phar');
$loader->addDirectory("phar://test.phar/class.C.php");
$loader->register();

Assert::false( class_exists('A') );
Assert::true( class_exists('B') );
Assert::true( class_exists('C') );
Assert::true( class_exists('D') );


Assert::exception(function() use ($loader, $pharFile) {
	$loader->addDirectory("phar://$pharFile/non-dir");
}, 'Nette\DirectoryNotFoundException', "Directory 'phar://$pharFile/non-dir' not found.");

Assert::exception(function() use ($loader, $pharFile) {
	$loader->addDirectory("phar://$pharFile/non-file.php");
}, 'Nette\DirectoryNotFoundException', "Directory 'phar://$pharFile/non-file.php' not found.");
