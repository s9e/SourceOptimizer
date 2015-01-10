<?php

namespace s9e\SourceOptimizer\Tests;

use PHPUnit_Framework_TestCase;
use s9e\SourceOptimizer\ContextHelper;
use s9e\SourceOptimizer\TokenStream;

class ContextHelperTest extends PHPUnit_Framework_TestCase
{
	/**
	* @dataProvider getGetNamespacesTests
	*/
	public function testGetNamespaces($php, $expected)
	{
		$tokenStream = new TokenStream($php);
		$this->assertSame($expected, ContextHelper::getNamespaces($tokenStream));
	}

	public function getGetNamespacesTests()
	{
		return [
			[
				'<?php $a=1;',
				['']
			],
			[
				'<?php namespace foo; $a=2; namespace bar\\baz; $b=3;',
				['', 1 => 'foo', 11 => 'bar\\baz']
			],
		];
	}
}