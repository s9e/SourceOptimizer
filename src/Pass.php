<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

abstract class Pass
{
	/**
	* Return the list of ranges to be optimized
	*
	* @param  array<array|string> &$tokens Source's tokens
	* @return array<array> Each array contains the first and last indexes of the range
	*/
	public function getBlocks(array &$tokens)
	{
		return [[0, count($tokens) - 1]];
	}

	/**
	* 
	*
	* @param  array<array|string> &$tokens Source's tokens
	* @param  integer              $start  Index of the first token of the range
	* @param  integer              $end    Index of the last token of the range
	* @return bool                         Whether the source needs to be reparsed
	*/
	abstract public function optimize(array &$tokens, $start, $end);
}