<?php

function foo($_, $a)
{
	return $_ . $a;
}

function bar($_, $a)
{
	return $a . $_;
}

echo foo('x', 'y');
echo bar('X', 'Y');