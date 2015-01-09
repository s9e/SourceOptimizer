#!/usr/bin/php
<?php

$arg = escapeshellarg('die(serialize(get_defined_constants()));');

$constants53 = unserialize(shell_exec('/usr/lib64/php5.4/bin/php -r ' . $arg));
$constants56 = unserialize(shell_exec('/usr/lib64/php5.6/bin/php -r ' . $arg));

foreach ($constants53 as $k => $v)
{
	if (isset($constants56[$k]) && $constants56[$k] !== $v)
	{
		echo "$k $v $constants56[$k]\n";
	}
}