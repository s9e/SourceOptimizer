<?php

$str = 'String';

function foo($_)
{
	echo $_, $GLOBALS['str'];
}

foo('echo: ');