<?php declare(strict_types=1);

/**
 * PHPStan type tests.
 */

use Nette\Loaders\RobotLoader;
use function PHPStan\Testing\assertType;


function testGetIndexedClasses(RobotLoader $loader): void
{
	$result = $loader->getIndexedClasses();
	assertType('array<class-string, string>', $result);
}
