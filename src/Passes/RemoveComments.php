<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Helper;

class RemoveComments extends AbstractPass
{
	/**
	* @var string[] List of annotations to preserve
	*/
	public $preserveAnnotations = [
		'license'
	];

	/**
	* @var bool Whether to remove DocBlock comments
	*/
	public $removeDocBlocks = true;

	/**
	* @var bool Whether to remove "C style" comments
	*/
	public $removeMultiLineComments = true;

	/**
	* @var bool Whether to remove "one-line" comments
	*/
	public $removeSingleLineComments = true;

	/**
	* {@inheritdoc}
	*/
	protected function optimizeStream()
	{
		if ($this->removeDocBlocks)
		{
			$this->removeDocBlocks();
		}
		if ($this->removeMultiLineComments || $this->removeSingleLineComments)
		{
			$this->removeComments();
		}
		Helper::mergeWhitespace($this->stream);
	}

	/**
	* Generate a regexp that matches preserved annotations
	*
	* @return string
	*/
	protected function getRegexp()
	{
		if (empty($this->preserveAnnotations))
		{
			return '((?!))';
		}

		return '(@(?:' . implode('|', $this->preserveAnnotations) . '))';
	}

	/**
	* Remove all docblocks tokens from given stream
	*
	* @return void
	*/
	protected function removeDocBlocks()
	{
		$regexp = $this->getRegexp();
		$this->stream->reset();
		while ($this->stream->skipTo(T_DOC_COMMENT))
		{
			$docblock = $this->stream->currentText();
			if (strpos($docblock, '@') !== false && preg_match($regexp, $docblock))
			{
				continue;
			}

			$this->removeComment();
		}
	}

	/**
	* Remove current comment token from given stream
	*
	* @return void
	*/
	protected function removeComment()
	{
		$offset = $this->stream->key();
		$this->stream->previous();
		if ($this->stream->is(T_WHITESPACE))
		{
			$ws = preg_replace('(\\n[ \\t]*$)', '', $this->stream->currentText());
			if ($ws === '')
			{
				$this->stream->remove();
			}
			else
			{
				$this->stream->replace([T_WHITESPACE, $ws]);
			}
		}

		$this->stream->seek($offset);
		if ($this->stream->canRemoveCurrentToken())
		{
			$this->stream->remove();
		}
		else
		{
			$this->stream->replace([T_WHITESPACE, ' ']);
		}
	}

	/**
	* Remove single-line and/or multi-line comments from given stream
	*
	* @return void
	*/
	protected function removeComments()
	{
		$this->stream->reset();
		while ($this->stream->skipTo(T_COMMENT))
		{
			$comment = $this->stream->currentText();
			if ($comment[1] === '/' && !$this->removeSingleLineComments)
			{
				continue;
			}
			if ($comment[1] === '*' && !$this->removeMultiLineComments)
			{
				continue;
			}

			$this->removeComment();
		}
	}
}