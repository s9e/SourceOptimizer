<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

class RemoveWhitespace extends Pass
{
	/**
	* @var bool Whether to remove all possible whitespace from the source
	*/
	public $removeAllWhitespace = false;

	/**
	* @var bool Whether to remove blank lines from the source
	*/
	public $removeBlankLines = true;

	/**
	* @var bool Whether to remove the indentation at the start of a line
	*/
	public $removeIndentation = false;

	/**
	* @var bool Whether to remove superfluous whitespace inside of a line
	*/
	public $removeSameLineWhitespace = true;

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$regexp = $this->getRegexp();
		while ($stream->skipTo(T_WHITESPACE))
		{
			$ws = $stream->currentText();
			if ($stream->canRemoveCurrentToken())
			{
				if ($this->removeAllWhitespace || ($this->removeSameLineWhitespace && strpos($ws, "\n") === false))
				{
					$stream->remove();
					continue;
				}
			}

			$stream->replace([T_WHITESPACE, preg_replace($regexp, "\n", $ws)]);
		}
	}

	/**
	* Generate the regexp that corresponds to the removal options
	*
	* @return string
	*/
	protected function getRegexp()
	{
		$remove = [];
		if ($this->removeBlankLines)
		{
			$remove[] = '\\s*\\n';
		}
		if ($this->removeIndentation)
		{
			$remove[] = '[ \\t]+';
		}

		return '(\\n(?:' . implode('|', $remove) . '))';
	}
}