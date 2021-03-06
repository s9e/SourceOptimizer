<?php

namespace s9e\SourceOptimizer\Tests\Passes;

use PHPUnit\Framework\TestCase;
use s9e\SourceOptimizer\Optimizer;

abstract class AbstractPassTest extends TestCase
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

	public function getOptimizeTests()
	{
		$thisName = get_class($this);
		$passName = substr($thisName, 1 + strrpos($thisName, '\\'), -4);

		$tests = [];
		foreach (glob(__DIR__ . '/' . $passName . '/*.original.php') as $original)
		{
			$optimized = preg_replace('(riginal.php$)', 'ptimized.php', $original);
			$options   = preg_replace('(riginal.php$)', 'ptions.json', $original);

			$tests[] = [
				file_get_contents($original),
				file_get_contents($optimized),
				(file_exists($options)) ? json_decode(file_get_contents($options), true) : []
			];
		}

		return $tests;
	}
}