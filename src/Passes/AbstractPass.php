<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\TokenStream;

abstract class AbstractPass
{
	/**
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* Optimize a given list of tokens
	*
	* @param  TokenStream $stream
	* @return void
	*/
	public function optimize(TokenStream $stream)
	{
		$this->stream = $stream;
		$this->optimizeStream();
	}

	/**
	* Optimize the stored token stream
	*
	* @return void
	*/
	abstract protected function optimizeStream();
}