<?php

namespace s9e\SourceOptimizer\Tests;

use PHPUnit_Framework_TestCase;
use s9e\SourceOptimizer\TokenStream;

class TokenStreamTest extends PHPUnit_Framework_TestCase
{
	/**
	* @testdox skipTo() returns TRUE upon finding a token
	*/
	public function testSkipToTrue()
	{
		$stream = new TokenStream('<?php // Comment');
		$this->assertTrue($stream->skipTo(T_COMMENT));
	}

	/**
	* @testdox skipTo() returns FALSE upon failing to find a token
	*/
	public function testSkipToFalse()
	{
		$stream = new TokenStream('<?php // Comment');
		$this->assertFalse($stream->skipTo(T_ELSE));
	}

	/**
	* @testdox skipTo() ignores current token
	*/
	public function testSkipToCurrent()
	{
		$stream = new TokenStream('<?php // Comment');
		$this->assertTrue($stream->skipTo(T_COMMENT));
		$this->assertFalse($stream->skipTo(T_COMMENT));
	}
}