<?php declare(strict_types=1);

/**
 * Test: Nette\Loaders\RobotLoader bug # 17 POC.
 */

use Nette\Loaders\RobotLoader;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$loader = new RobotLoader;
$loader->setTempDirectory(getTempDir());

Assert::noError(
	function () use ($loader) {
		$loader->addDirectory(...[]);
	},
);

Assert::noError(
	function () use ($loader) {
		$loader->excludeDirectory(...[]);
	},
);
