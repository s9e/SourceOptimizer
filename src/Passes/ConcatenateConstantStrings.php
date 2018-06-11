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

class ConcatenateConstantStrings extends Pass
{
	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		while ($stream->skipTo(T_CONSTANT_ENCAPSED_STRING))
		{
			$offset = $stream->key();
			$left   = $stream->currentText();
			$stream->next();
			$stream->skipNoise();
			if (!$stream->is('.'))
			{
				continue;
			}
			$stream->next();
			$stream->skipNoise();
			$right = $stream->currentText();
			if (!$stream->is(T_CONSTANT_ENCAPSED_STRING) || $left[0] !== $right[0])
			{
				continue;
			}
			$stream->replace([T_CONSTANT_ENCAPSED_STRING, substr($left, 0, -1) . substr($right, 1)]);
			$i = $stream->key();
			$stream->seek($i - 1);
			while (--$i >= $offset)
			{
				unset($stream[$i]);
			}
		}
	}
}