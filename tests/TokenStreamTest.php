<?php

namespace s9e\SourceOptimizer\Tests;

use PHPUnit\Framework\TestCase;
use s9e\SourceOptimizer\TokenStream;

class TokenStreamTest extends TestCase
{
	/**
	* @testdox Can be read using array notation
	*/
	public function testOffsetGet()
	{
		$stream = new TokenStream('<?php {}');
		$this->assertEquals('{', $stream[1]);
		$this->assertEquals('}', $stream[2]);
	}

	/**
	* @testdox Tokens can be replaced using array notation
	*/
	public function testOffsetSet()
	{
		$stream = new TokenStream('<?php ;');
		$stream[1] = [T_COMMENT, '// Comment'];
		$this->assertEquals([T_COMMENT, '// Comment'], $stream[1]);
	}

	/**
	* @testdox Existence of tokens can be tested using array notation
	*/
	public function testOffsetIsset()
	{
		$stream = new TokenStream('<?php ;');
		$this->assertTrue(isset($stream[1]));
		$this->assertFalse(isset($stream[2]));
	}

	/**
	* @testdox Tokens can be removed using array notation
	*/
	public function testOffsetUnset()
	{
		$stream = new TokenStream('<?php ;');
		$this->assertTrue(isset($stream[1]));
		unset($stream[1]);
		$this->assertFalse(isset($stream[1]));
	}

	/**
	* @testdox TokenStream can be used in iteration
	*/
	public function testIterator()
	{
		$stream = new TokenStream('<?php $a=1;');
		$this->assertSame(
			[
				[T_OPEN_TAG, '<?php '],
				[T_VARIABLE, '$a'],
				'=',
				[T_LNUMBER, '1'],
				';'
			],
			iterator_to_array($stream)
		);
	}

	/**
	* @testdox seek() moves the internal pointer
	*/
	public function testSeek()
	{
		$stream = new TokenStream('<?php $a=1;');
		$stream->seek(2);
		$this->assertSame('=', $stream->current());
	}

	/**
	* @testdox skipNoise() skips whitespace tokens, comments and docblocks
	*/
	public function testSkipNoise()
	{
		$stream = new TokenStream("<?php // Comment\n\n// Another comment\n\n/** doc */\n\$a=1;");
		$stream->seek(2);
		$stream->skipNoise();
		$this->assertEquals(7, $stream->key());
	}

	/**
	* @testdox skipNoise() does nothing if current token isn't whitespace, a comment or a docblock
	*/
	public function testSkipNoiseMiss()
	{
		$stream = new TokenStream("<?php // Comment\n\n\$a=1;");
		$stream->skipNoise();
		$this->assertEquals(0, $stream->key());
	}

	/**
	* @testdox skipWhitespace() skips whitespace tokens
	*/
	public function testSkipWhitespace()
	{
		$stream = new TokenStream("<?php // Comment\n\n\$a=1;");
		$stream->seek(2);
		$stream->skipWhitespace();
		$this->assertEquals(3, $stream->key());
	}

	/**
	* @testdox skipWhitespace() does nothing if current token isn't whitespace
	*/
	public function testSkipWhitespaceMiss()
	{
		$stream = new TokenStream("<?php // Comment\n\n\$a=1;");
		$stream->skipWhitespace();
		$this->assertEquals(0, $stream->key());
	}

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

	/**
	* @testdox canRemoveCurrentToken() returns FALSE if previous token is a single-line comment
	*/
	public function testCannotRemoveAfterSingleComment()
	{
		$stream = new TokenStream("<?php // Comment\n\n\$a=1;");
		$stream->seek(2);
		$this->assertFalse($stream->canRemoveCurrentToken(2));
	}

	/**
	* @testdox canRemoveCurrentToken() returns TRUE if previous token is a multi-line comment
	*/
	public function testCanRemoveAfterMultiComment()
	{
		$stream = new TokenStream("<?php /* Comment */\n\n\$a=1;");
		$stream->seek(2);
		$this->assertTrue($stream->canRemoveCurrentToken(2));
	}

	/**
	* @testdox isNoise() returns TRUE for whitespace
	*/
	public function testIsNoiseWhitespace()
	{
		$stream = new TokenStream('<?php /** Comment */ // Comment');
		$stream->seek(2);
		$this->assertTrue($stream->isNoise());
	}

	/**
	* @testdox isNoise() returns TRUE for comments
	*/
	public function testIsNoiseComment()
	{
		$stream = new TokenStream('<?php /** Comment */ // Comment');
		$stream->seek(3);
		$this->assertTrue($stream->isNoise());
	}

	/**
	* @testdox isNoise() returns TRUE for docblocks
	*/
	public function testIsNoiseDocblock()
	{
		$stream = new TokenStream('<?php /** Comment */ // Comment');
		$stream->seek(1);
		$this->assertTrue($stream->isNoise());
	}

	/**
	* @testdox isNoise() returns FALSE for other tokens
	*/
	public function testIsNoiseFalse()
	{
		$stream = new TokenStream('<?php /** Comment */ // Comment');
		$this->assertFalse($stream->isNoise());
	}
}