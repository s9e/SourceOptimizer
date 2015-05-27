<?php

class foo
{
	public static $bar = 'bar';
}

function bar()
{
	echo foo::$bar;
}

bar();