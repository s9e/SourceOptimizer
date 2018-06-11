<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

class ConcatenateConstantStrings extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	protected function optimizeStream()
	{
		while ($this->stream->skipTo(T_CONSTANT_ENCAPSED_STRING))
		{
			$offset = $this->stream->key();
			$left   = $this->stream->currentText();
			$this->stream->next();
			$this->stream->skipNoise();
			if (!$this->stream->is('.'))
			{
				continue;
			}
			$this->stream->next();
			$this->stream->skipNoise();
			$right = $this->stream->currentText();
			if (!$this->stream->is(T_CONSTANT_ENCAPSED_STRING) || $left[0] !== $right[0])
			{
				continue;
			}
			$this->stream->replace([T_CONSTANT_ENCAPSED_STRING, substr($left, 0, -1) . substr($right, 1)]);
			$i = $this->stream->key();
			$this->stream->seek($i - 1);
			while (--$i >= $offset)
			{
				unset($this->stream[$i]);
			}
		}
	}
}