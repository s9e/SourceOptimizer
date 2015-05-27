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

class MinifyVars extends Pass
{
	/**
	* @var integer Number of variables processed in current function block
	*/
	protected $cnt;

	/**
	* @var string Regexp that matches variable names that need to be preserved
	*/
	public $preserveRegexp = '(^\\$(?:\\$|__|(?:this|GLOBALS|_[A-Z]+|php_errormsg|HTTP_RAW_POST_DATA|http_response_header|arg[cv]))$)S';

	/**
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* @var array Map of [original name => minified name]
	*/
	protected $varNames;

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$this->stream = $stream;
		ContextHelper::forEachFunction(
			$this->stream,
			function ($startOffset, $endOffset)
			{
				$this->optimizeBlock($startOffset, $endOffset);
			}
		);
	}

	/**
	* Test whether current token is a variable that can be minified
	*
	* @return bool
	*/
	protected function canBeMinified()
	{
		if (!$this->stream->is(T_VARIABLE))
		{
			return false;
		}

		return !preg_match($this->preserveRegexp, $this->stream->currentText());
	}

	/**
	* Generate a minified name for given variable
	*
	* @param  string $varName Original variable
	* @return string          Minified variable
	*/
	protected function getName($varName)
	{
		if (!isset($this->varNames[$varName]))
		{
			$this->varNames[$varName] = $this->generateName();
		}

		return $this->varNames[$varName];
	}

	/**
	* Generate a minified variable name
	*
	* @return string
	*/
	protected function generateName()
	{
		$n = $this->cnt;
		$chars = '_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		// Increment the counter and skip over the digits range if the name would start with one
		$this->cnt += ($this->cnt % 63 < 52) ? 1 : 11;

		$varName = '$';
		do
		{
			$varName .= $chars[$n % 63];
			$n = floor($n / 63);
		}
		while ($n > 0);

		return $varName;
	}

	/**
	* Minify variables in given function block
	*
	* @param  integer $startOffset
	* @param  integer $endOffset
	* @return void
	*/
	protected function optimizeBlock($startOffset, $endOffset)
	{
		$this->resetNames();
		$this->stream->seek($startOffset);
		while ($this->stream->valid() && $this->stream->key() <= $endOffset)
		{
			if ($this->stream->is(T_DOUBLE_COLON))
			{
				// Prepare to skip the next significant token after a double colon, e.g. foo::$bar
				$this->stream->next();
				$this->stream->skipNoise();
			}
			elseif ($this->canBeMinified())
			{
				$varName = $this->stream->currentText();
				$this->stream->replace([T_VARIABLE, $this->getName($varName)]);
			}
			$this->stream->next();
		}
	}

	/**
	* Reset the map of variable names
	*
	* @return void
	*/
	protected function resetNames()
	{
		$this->cnt      = 0;
		$this->varNames = [];
	}
}