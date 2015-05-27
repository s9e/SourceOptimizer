<?php

function foo($foo, $bar)
{
	return $foo . $bar;
}

function bar($bar, $foo)
{
	return $foo . $bar;
}

echo foo('x', 'y');
echo bar('X', 'Y');