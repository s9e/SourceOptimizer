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
		if (!isset($this->stream[$funcOffset]) || $this->stream[$funcOffset][0] !== T_STRING)
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
			if (!isset($this->stream[$offset]))
			{
				return;
			}

			// Ignore if preceded by "function", "new", "\", "->" or "::"
			$tokenValue = $this->stream[$offset][0];
			if ($tokenValue === T_FUNCTION
			 || $tokenValue === T_NEW
			 || $tokenValue === T_NS_SEPARATOR
			 || $tokenValue === T_OBJECT_OPERATOR
			 || $tokenValue === T_PAAMAYIM_NEKUDOTAYIM)
			{
				return;
			}

			// Stop looking once we found a token that's not whitespace or comment
			if ($tokenValue !== T_WHITESPACE
			 && $tokenValue !== T_COMMENT
			 && $tokenValue !== T_DOC_COMMENT)
			{
				break;
			}
		}

		$this->stream->seek($funcOffset);
		$this->stream->replace([T_STRING, '\\' . $funcName]);
		$this->stream->seek($savedOffset);
	}
}