<?php

$str = 'String';

function foo($str)
{
	echo $str, $GLOBALS['str'];
}

foo('echo: ');