<?php

namespace s9e\SourceOptimizer\Tests;

use PHPUnit_Framework_TestCase;
use s9e\SourceOptimizer\Optimizer;

/**
* @covers s9e\SourceOptimizer\Optimizer
*/
class OptimizerTest extends PHPUnit_Framework_TestCase
{
	/**
	* @dataProvider getOptimizeTests
	*/
	public function testOptimize($src, $expected)
	{
		$optimizer = new Optimizer;
		$this->assertSame($expected, $optimizer->optimize($src));
	}

	public function getOptimizeTests()
	{
		return [
			[
				'<?php
				$foo = 42;',
				'<?php
				$foo=42;'
			]
		];
	}
}