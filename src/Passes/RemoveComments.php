<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Pass;
use s9e\SourceOptimizer\TokenStream;

class RemoveComments extends Pass
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
	* @var TokenStream Token stream of the source being processed
	*/
	protected $stream;

	/**
	* {@inheritdoc}
	*/
	public function optimize(TokenStream $stream)
	{
		$this->stream = $stream;
		if ($this->removeDocBlocks)
		{
			$this->removeDocBlocks();
		}
		if ($this->removeMultiLineComments || $this->removeSingleLineComments)
		{
			$this->removeComments();
		}
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
		$prevToken = $this->stream->lookbehind();
		$nextToken = $this->stream->lookahead();

		$isPrecededByWhitespace = (is_array($prevToken) && $prevToken[0] === T_WHITESPACE);
		$isFollowedByWhitespace = (is_array($nextToken) && $nextToken[0] === T_WHITESPACE);

		// Replace this comment with whitespace if it's not preceeded or followed by whitespace
		if (!$isPrecededByWhitespace && !$isFollowedByWhitespace)
		{
			$this->stream->replace([T_WHITESPACE, ' ']);
		}
		else
		{
			$this->stream->remove();
		}
		$this->stream->next();

		// If the comment is surrounded by whitespace, we remove the latter
		if ($isPrecededByWhitespace && $isFollowedByWhitespace)
		{
			$this->stream->remove();
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