<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

class RemoveWhitespace extends AbstractPass
{
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
	protected function optimizeStream()
	{
		$regexp = $this->getRegexp();
		while ($this->stream->skipTo(T_WHITESPACE))
		{
			$ws = $this->stream->currentText();
			if ($this->removeSameLineWhitespace && strpos($ws, "\n") === false && $this->stream->canRemoveCurrentToken())
			{
				$this->stream->remove();
				continue;
			}

			$this->stream->replace([T_WHITESPACE, preg_replace($regexp, "\n", $ws)]);
		}
	}

	/**
	* Generate the regexp that corresponds to the removal options
	*
	* @return string
	*/
	protected function getRegexp()
	{
		if ($this->removeBlankLines)
		{
			return ($this->removeIndentation) ? '(\\n\\s+)' : '(\\n\\s*\\n)';
		}

		if ($this->removeIndentation)
		{
			return '(\\n[ \\t]+)';
		}

		return '((?!))';
	}
}