<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

class EnforceFQN extends Pass
{
	/**
	* @var array 
	*/
	protected $functions;

	/**
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* Construct
	*
	* @return void
	*/
	public function __construct()
	{
		$this->functions = array_flip(get_defined_functions()['internal']);
	}

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$this->stream = $stream;
		foreach ($stream as $token)
		{
			if ($token === '(')
			{
				$this->processFunctionCall();
			}
		}
	}

	/**
	* Process the function call whose opening parenthesis start at current offset
	*
	* @return void
	*/
	protected function processFunctionCall()
	{
		$funcOffset = $this->stream->key() - 1;
		if ($this->stream[$funcOffset][0] !== T_STRING)
		{
			return;
		}

		$funcName = $this->stream[$funcOffset][1];
		if (!isset($this->functions[$funcName]))
		{
			return;
		}

		$savedOffset = $this->stream->key();
		$offset = $funcOffset;
		while (--$offset > 0)
		{
			// Ignore if preceded by "function", "new", "\", "->" or "::"
			$tokenValue = $this->stream[$offset][0];
			if (in_array($tokenValue, [T_FUNCTION, T_NEW, T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_PAAMAYIM_NEKUDOTAYIM], true))
			{
				return;
			}

			// Stop looking once we found a token that's not whitespace or comment
			if (!in_array($tokenValue, [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true))
			{
				break;
			}
		}

		$this->stream->seek($funcOffset);
		$this->stream->replace([T_STRING, '\\' . $funcName]);
		$this->stream->seek($savedOffset);
	}
}