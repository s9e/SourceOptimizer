<?php

namespace s9e\SourceOptimizer\Tests;

use PHPUnit_Framework_TestCase;
use s9e\SourceOptimizer\Optimizer;

abstract class PassTest extends PHPUnit_Framework_TestCase
{
	/**
	* @dataProvider getOptimizeTests
	*/
	public function testOptimize($src, $expected, array $options = [])
	{
		$thisName = get_class($this);
		$passName = substr($thisName, 1 + strrpos($thisName, '\\'), -4);

		$optimizer = new Optimizer;
		$optimizer->disableAll();
		$optimizer->enable($passName, $options);

		$this->assertSame($expected, $optimizer->optimize($src));
	}

	abstract public function getOptimizeTests();
}