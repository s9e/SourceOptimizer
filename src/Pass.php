<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

abstract class Pass
{
	/**
	* Optimize a given list of tokens
	*
	* @param  TokenStream $stream
	* @return void
	*/
	abstract public function optimize(TokenStream $stream);
}