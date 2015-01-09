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
		while ($stream->skipTo(T_WHITESPACE))
		{
			$shouldRemove = false;
			$ws = $stream->currentText();

			if ($stream->canRemoveCurrentToken())
			{
				if ($this->removeAllWhitespace)
				{
					$shouldRemove = true;
				}
				if ($this->removeSameLineWhitespace && strpos($ws, "\n") === false)
				{
					$shouldRemove = true;
				}
			}
			if ($shouldRemove)
			{
				$stream->remove();
				continue;
			}

			if ($this->removeBlankLines)
			{
				$ws = preg_replace('(\\n\\s*\\n)', "\n", $ws);
			}
			if ($this->removeIndentation)
			{
				$ws = preg_replace('(\\n[ \\t]+)', "\n", $ws);
			}

			$stream->replace([T_WHITESPACE, $ws]);
		}
	}
}