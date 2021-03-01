<?php

/**
 * Test: Nette\Loaders\RobotLoader bug # 17 POC.
 */

declare(strict_types=1);

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
