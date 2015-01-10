<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

use ArrayAccess;
use Iterator;

class TokenStream implements ArrayAccess, Iterator
{
	/**
	* @var integer Number of tokens
	*/
	protected $cnt;

	/**
	* @var string[] List of exact triplet of tokens to exclude from minification
	*/
	protected $excludeExact = [
		// 1 - - 1 and 1 + + 1 should not become 1--1 or 1++1
		'- -',
		'+ +',
		// $a - --$b should not become $a---$b
		'- --',
		'+ ++'
	];

	/**
	* @var bool Whether the source code needs to be reparsed before this stream is handed off to a
	*           new pass
	*/
	public $needsReparsing;

	/**
	* @var integer Current token index
	*/
	protected $offset;

	/**
	* @var bool Whether some tokens have been removed
	*/
	protected $sparse;

	/**
	* @var array<array|string> List of tokens
	*/
	protected $tokens;

	/**
	* Constructor
	*
	* @param  string $src Original source code
	* @return 
	*/
	public function __construct($src)
	{
		$this->parse($src);
	}

	/**
	* Test whether current token can be removed safely
	*
	* @return bool
	*/
	public function canRemoveCurrentToken()
	{
		$prevToken = $this->lookbehind();
		$nextToken = $this->lookahead();

		if ($prevToken === false || $nextToken === false)
		{
			return true;
		}

		if (is_array($prevToken))
		{
			if ($prevToken[0] === T_COMMENT && substr($prevToken[1], 0, 2) === '//')
			{
				return false;
			}

			$prevToken = $prevToken[1];
		}
		if (is_array($nextToken))
		{
			$nextToken = $nextToken[1];
		}

		$str = $prevToken . ' ' . $nextToken;
		if (in_array($str, $this->excludeExact, true))
		{
			return false;
		}

		$delimiters = "\t\n\r !\"#$%&'()*+,-./:;<=>?@[\\]^`{|}~";
		$prevChar = substr($prevToken, -1);
		$nextChar = $nextToken[0];

		return (strpos($delimiters, $prevChar) !== false || strpos($delimiters, $nextChar) !== false);
	}

	/**
	* Test whether there's a token at given offset
	*
	* @param  integer $offset
	* @return bool
	*/
	public function offsetExists($offset)
	{
		return isset($this->tokens[$offset]);
	}

	/**
	* Return the token stored at given offset
	*
	* @param  integer $offset
	* @return array|string
	*/
	public function offsetGet($offset)
	{
		return $this->tokens[$offset];
	}

	/**
	* Replace the token stored at given offset
	*
	* @param  integer      $offset
	* @param  array|string $token
	* @return void
	*/
	public function offsetSet($offset, $token)
	{
		$this->tokens[$offset] = $token;
	}

	/**
	* Remove the token stored at given offset
	*
	* @return void
	*/
	public function offsetUnset($offset)
	{
		unset($this->tokens[$offset]);
		$this->sparse = true;
	}

	/**
	* Return the current token
	*
	* @return array|string
	*/
	public function current()
	{
		return $this->tokens[$this->offset];
	}

	/**
	* Get current token's text
	*
	* @return string
	*/
	public function currentText()
	{
		return (is_array($this->tokens[$this->offset])) ? $this->tokens[$this->offset][1] : $this->tokens[$this->offset];
	}

	/**
	* Get current token's value
	*
	* @return integer|string Token's value if applicable, or the token's text otherwise
	*/
	public function currentValue()
	{
		return (is_array($this->tokens[$this->offset])) ? $this->tokens[$this->offset][0] : $this->tokens[$this->offset];
	}

	/**
	* Return the offset of current token
	*
	* @return integer
	*/
	public function key()
	{
		return $this->offset;
	}

	/**
	* Peek at the next token
	*
	* @return array|string|false
	*/
	public function lookahead()
	{
		$i = $this->offset;
		while (++$i < $this->cnt)
		{
			if (isset($this->tokens[$i]))
			{
				return $this->tokens[$i];
			}
		}

		return false;
	}

	/**
	* Peek at the previous token
	*
	* @return array|string|false
	*/
	public function lookbehind()
	{
		$i = $this->offset;
		while (--$i >= 0)
		{
			if (isset($this->tokens[$i]))
			{
				return $this->tokens[$i];
			}
		}

		return false;
	}

	/**
	* Move to the next token in the stream
	*
	* @return void
	*/
	public function next()
	{
		while (++$this->offset < $this->cnt && !isset($this->tokens[$this->offset]));
	}

	/**
	* Parse/tokenize given PHP source
	*
	* @param  string $src
	* @return void
	*/
	protected function parse($src)
	{
		$this->tokens = token_get_all($src);
		foreach ($this->tokens as &$token)
		{
			if (is_array($token))
			{
				unset($token[2]);
			}
		}

		$this->cnt = count($this->tokens);
		$this->offset = 0;
		$this->needsReparsing = false;
		$this->sparse = false;
	}

	/**
	* Move to the previous token in the stream
	*
	* @return void
	*/
	public function previous()
	{
		while ($this->offset > 0)
		{
			--$this->offset;
			if (isset($this->tokens[$this->offset]))
			{
				break;
			}
		}
	}

	/**
	* Remove current token
	*
	* @return void
	*/
	public function remove()
	{
		$this->offsetUnset($this->offset);
	}

	/**
	* Replace current token
	*
	* @param  array|string $token Token replacement
	* @return void
	*/
	public function replace($token)
	{
		$this->offsetSet($this->offset, $token);
	}

	/**
	* Reset this stream
	*
	* @return void
	*/
	public function reset()
	{
		$this->offset = 0;
		if ($this->needsReparsing)
		{
			$this->parse($this->serialize());
		}
		elseif ($this->sparse)
		{
			$this->tokens = array_values($this->tokens);
		}
	}

	/**
	* Rewind/reset this stream
	*
	* @return void
	*/
	public function rewind()
	{
		$this->reset();
	}

	/**
	* Move the stream to given offset
	*
	* @param  integer $offset
	* @return void
	*/
	public function seek($offset)
	{
		$this->offset = $offset;
	}

	/**
	* Serialize these tokens back to source code
	*
	* @return string
	*/
	public function serialize()
	{
		$src = '';
		foreach ($this->tokens as $token)
		{
			$src .= (is_array($token)) ? $token[1] : $token;
		}

		return $src;
	}

	/**
	* Skip all whitespace, comments and docblocks starting at current offset
	*
	* @return void
	*/
	public function skipNoise()
	{
		// Tokens that we wish to skip
		$tokens = [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE];
		while ($this->offset < $this->cnt)
		{
			if (!in_array($this->tokens[$this->offset][0], $tokens, true))
			{
				break;
			}
			++$this->offset;
		}
	}

	/**
	* Skip all whitespace starting at current offset
	*
	* @return void
	*/
	public function skipWhitespace()
	{
		while ($this->offset < $this->cnt)
		{
			if ($this->tokens[$this->offset][0] !== T_WHITESPACE)
			{
				break;
			}
			++$this->offset;
		}
	}

	/**
	* Iterate through tokens until the stream reaches a token of given value or the end of stream
	*
	* @param  integer $tokenValue The target value, e.g. T_ELSE
	* @return bool                Whether a matching token was found
	*/
	public function skipTo($tokenValue)
	{
		while (++$this->offset < $this->cnt)
		{
			if (isset($this->tokens[$this->offset][0]) && $this->tokens[$this->offset][0] === $tokenValue)
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Iterate through tokens until the stream reaches given token or the end of stream
	*
	* @param  array|string $token The target token, either a string or a [tokenValue, string] pair
	* @return bool                Whether a matching token was found
	*/
	public function skipToToken($token)
	{
		while (++$this->offset < $this->cnt)
		{
			if (isset($this->tokens[$this->offset]) && $this->tokens[$this->offset] === $token)
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Test whether the stream is at a valid offset
	*
	* @return bool
	*/
	public function valid()
	{
		return ($this->offset < $this->cnt);
	}
}