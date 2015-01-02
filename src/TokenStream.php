<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

class TokenStream
{
	/**
	* @var integer Number of tokens
	*/
	protected $cnt;

	/**
	* @var integer Current token index
	*/
	protected $i;

	/**
	* @var bool Whether some tokens have been modified
	*/
	protected $modified;

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
	* 
	*
	* @return void
	*/
	protected function getNamespaceRanges()
	{
		$savedIndex = $this->i;

		$currentNamespace = '';
		$i = -1;
		while (++$i < $this->cnt)
		{
			if ($this->tokens[$i][0] === T_NAMESPACE)
			{
				$start = $i;
				$namespace = '';
				++$i;
				while ($tokens[$i] !== '{' && $tokens[$i] !== ';')
				{
					if ($tokens[$i][0] !== T_WHITESPACE)
					{
						$namespace .= $tokens[$i];
					}
					++$i;
				}
			}
		}
	}

	/**
	* Get current token (or a token relative to current token) as text
	*
	* @param  integer $i Token index, relative to current token (usually either +1 or -1)
	* @return string
	*/
	public function getText($i = 0)
	{
		$i += $this->i;
		if (!isset($this->tokens[$i]))
		{
			return '';
		}

		return (is_array($this->tokens[$i])) ? $this->tokens[$i][1] : $this->tokens[$i];
	}

	/**
	* Test whether current token is followed by a whitespace token
	*
	* @return bool
	*/
	public function isFollowedByWhitespace()
	{
		$i = $this->i;
		while (++$i < $this->cnt)
		{
			if (isset($this->tokens[$i]))
			{
				return ($this->tokens[$i][0] === T_WHITESPACE);
			}
		}

		return false;
	}

	/**
	* Test whether current token is preceded by a whitespace token
	*
	* @return bool
	*/
	public function isPrecededByWhitespace()
	{
		$i = $this->i;
		while (--$i >= 0)
		{
			if (isset($this->tokens[$i]))
			{
				return ($this->tokens[$i][0] === T_WHITESPACE);
			}
		}

		return false;
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
				unset($token[3]);
			}
		}

		$this->cnt = count($this->tokens);
		$this->i = 0;
		$this->modified = false;
		$this->sparse = false;
	}

	/**
	* Remove current token (or a token relative to current token)
	*
	* @param  integer $i Token index, relative to current token (usually either +1 or -1)
	* @return void
	*/
	public function remove($i = 0)
	{
		$i += $this->i;
		unset($this->tokens[$i]);
		$this->sparse = true;
	}

	/**
	* Replace current token (or a token relative to current token)
	*
	* @param  array|string $token Token replacement
	* @param  integer      $i     Token index, relative to current token (usually either +1 or -1)
	* @return void
	*/
	public function replace($token, $i = 0)
	{
		$i += $this->i;
		if ($this->tokens[$i] !== $token)
		{
			$this->modified = true;
			$this->tokens[$i] = $token;
		}
	}

	/**
	* Reset this stream
	*
	* @return void
	*/
	public function reset()
	{
		$this->i = 0;
		if ($this->modified)
		{
			$this->parse($this->serialize());
		}
		elseif ($this->sparse)
		{
			$this->tokens = array_values($this->tokens);
		}
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
	* Iterate through tokens until the stream reaches a token of given value or the end of stream
	*
	* @param  integer $tokenValue The target value, e.g. T_ELSE
	* @return bool                Whether a matching token was found
	*/
	public function skipTo($tokenValue)
	{
		while (++$this->i < $this->cnt)
		{
			if (isset($this->tokens[$this->i][0]) && $this->tokens[$this->i][0] === $tokenValue)
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
		while (++$this->i < $this->cnt)
		{
			if (isset($this->tokens[$this->i]) && $this->tokens[$this->i] === $token)
			{
				return true;
			}
		}

		return false;
	}
}