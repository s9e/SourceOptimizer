<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\ContextHelper;
use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

class EnforceFQN extends Pass
{
	/**
	* @var array List of global constants (constant names used as keys)
	*/
	protected $constants;

	/**
	* @var array List of internal functions (function names used as keys)
	*/
	protected $functions;

	/**
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->constants = get_defined_constants();
		$this->functions = array_flip(get_defined_functions()['internal']);
	}

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$this->stream = $stream;
		ContextHelper::forEachNamespace(
			$this->stream,
			function ($namespace, $startOffset, $endOffset)
			{
				if ($namespace !== '')
				{
					$this->optimizeBlock($startOffset, $endOffset);
				}
			}
		);
	}

	/**
	* Test whether current token is followed by an object operator or a namespace separator
	*
	* @return bool
	*/
	protected function isFollowedByOperator()
	{
		$offset = $this->stream->key();
		$this->stream->next();
		$this->stream->skipNoise();
		$isFollowedByOperator = ($this->stream->valid() && $this->stream->isAny([T_NS_SEPARATOR, T_PAAMAYIM_NEKUDOTAYIM]));
		$this->stream->seek($offset);

		return $isFollowedByOperator;
	}

	/**
	* Test whether current token is followed by a parenthesis
	*
	* @return bool
	*/
	protected function isFollowedByParenthesis()
	{
		$offset = $this->stream->key();
		$this->stream->next();
		$this->stream->skipNoise();
		$isFollowedByParenthesis = ($this->stream->valid() && $this->stream->current() === '(');
		$this->stream->seek($offset);

		return $isFollowedByParenthesis;
	}

	/**
	* Test whether the token at given offset is preceded by a keyword
	*
	* @return bool
	*/
	protected function isPrecededByKeyword()
	{
		$offset = $this->stream->key();
		while (--$offset > 0)
		{
			$tokenValue = $this->stream[$offset][0];
			if (in_array($tokenValue, [T_CLASS, T_CONST, T_FUNCTION, T_INTERFACE, T_NEW, T_NS_SEPARATOR, T_OBJECT_OPERATOR, T_PAAMAYIM_NEKUDOTAYIM, T_TRAIT, T_USE], true))
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
	* Optimize all constants and function calls in given range
	*
	* @param  integer $startOffset
	* @param  integer $endOffset
	* @return void
	*/
	protected function optimizeBlock($startOffset, $endOffset)
	{
		$this->stream->seek($startOffset);
		while ($this->stream->skipTo(T_STRING) && $this->stream->key() <= $endOffset)
		{
			if ($this->isPrecededByKeyword() || $this->isFollowedByOperator())
			{
				continue;
			}
			if ($this->isFollowedByParenthesis())
			{
				$this->processFunctionCall();
			}
			else
			{
				$this->processConstant();
			}
		}
	}

	/**
	* Process the constant at current offset
	*
	* @return void
	*/
	protected function processConstant()
	{
		$constName = $this->stream->currentText();
		if (!isset($this->constants[$constName]) && !preg_match('(^(?:false|null|true)$)Di', $constName))
		{
			return;
		}

		$this->stream->replace([T_STRING, '\\' . $constName]);
	}

	/**
	* Process the function name at current offset
	*
	* @return void
	*/
	protected function processFunctionCall()
	{
		$funcName = $this->stream->currentText();
		if (!isset($this->functions[$funcName]))
		{
			return;
		}

		$this->stream->replace([T_STRING, '\\' . $funcName]);
	}
}