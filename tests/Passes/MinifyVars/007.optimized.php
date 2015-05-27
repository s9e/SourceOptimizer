<?php

namespace s9e\SourceOptimizer\Tests\Test007;

class foo
{
	static $bar = 'baz';
	public static function bar()
	{
		echo "bar\n";
	}
	public static function baz()
	{
		echo "baz\n";
	}
}
function test()
{
	$_ = 'bar';
	foo::$_();
}
test();