<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\ContextHelper;
use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

class EnforceFQN extends Pass
{
	/**
	* @var array List of internal functions (function names used as keys)
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

		// Collect the namespaces and add an entry that serves as an upper bound
		$namespaces = ContextHelper::getNamespaces($this->stream);
		$namespaces[PHP_INT_MAX] = '_';
		foreach ($namespaces as $offset => $namespace)
		{
			if (isset($startOffset))
			{
				$this->optimizeFunctionCalls($startOffset, $offset - 1);
				unset($startOffset);
			}
			if ($namespace !== '')
			{
				$startOffset = $offset;
			}
		}
	}

	/**
	* Test whether the token at given offset is preceded by any token of given values
	*
	* @param  integer   $offset
	* @param  integer[] $tokenValues
	* @return bool
	*/
	protected function isPrecededBy($offset, array $tokenValues)
	{
		while (--$offset > 0)
		{
			$tokenValue = $this->stream[$offset][0];
			if (in_array($tokenValue, $tokenValues, true))
			{
				return true;
			}

			// Stop looking once we found a token that's not whitespace or comment
			if (!in_array($tokenValue, [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE], true))
			{
				break;
			}
		}

		return false;
	}

	/**
	* Optimize all function calls in given range
	*
	* @param  integer $startOffset
	* @param  integer $endOffset
	* @return void
	*/
	protected function optimizeFunctionCalls($startOffset, $endOffset)
	{
		$this->stream->seek($startOffset);
		while ($this->stream->skipToToken('(') && $this->stream->key() <= $endOffset)
		{
			$this->processFunctionCall();
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

		// Ignore if preceded by "function", "new", "\", "->" or "::"
		if ($this->isPrecededBy($funcOffset, [T_FUNCTION, T_NEW, T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_PAAMAYIM_NEKUDOTAYIM]))
		{
			return;
		}

		$this->stream[$funcOffset] = [T_STRING, '\\' . $funcName];
	}
}