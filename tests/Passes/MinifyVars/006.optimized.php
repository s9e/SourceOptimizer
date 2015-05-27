<?php

class foo
{
	public $baz = 'BAZ';
	public function bar()
	{
		echo $this->baz;
	}
}

$foo = new foo;
$foo->bar();