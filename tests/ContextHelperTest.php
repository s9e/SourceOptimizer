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
		$this->assertEquals($expected, ContextHelper::getNamespaces($tokenStream));
	}

	public function getGetNamespacesTests()
	{
		return [
			[
				'<?php $a=1;',
				[['', 0, 4]]
			],
			[
				'<?php namespace foo; $a=2; namespace bar\\baz; $b=3;',
				[
					['', 0, 0],
					['foo', 1, 10],
					['bar\\baz', 11, 21]
				]
			],
			[
				'<?php namespace foo {} namespace bar\\baz {}',
				[
					['', 0, 0],
					['foo', 1, 7],
					['bar\\baz', 8, 15]
				]
			],
			[
				'<?php namespace foo \\ /***/ bar { var_dump(__NAMESPACE__); }',
				[
					['', 0, 0],
					['foo\\bar', 1, 19]
				]
			],
		];
	}
}