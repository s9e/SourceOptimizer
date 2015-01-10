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
	* @var string[] List of regexps used to exclude comments from minification
	*/
	public $excludeRegexp = [
		// Do not remove comments with a license annotation
		'(@license)'
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
	public function optimize(TokenStream $stream)
	{
		if ($this->removeDocBlocks)
		{
			$this->removeDocBlocksFrom($stream);
		}

		if ($this->removeMultiLineComments || $this->removeSingleLineComments)
		{
			$this->removeCommentsFrom($stream);
		}
	}

	/**
	* Remove all docblocks tokens from given stream
	*
	* @param  TokenStream $stream
	* @return void
	*/
	protected function removeDocBlocksFrom(TokenStream $stream)
	{
		$stream->reset();
		while ($stream->skipTo(T_DOC_COMMENT))
		{
			if ($this->isExcluded($stream->currentText()))
			{
				continue;
			}

			$this->removeComment($stream);
		}
	}

	/**
	* Remove current comment token from given stream
	*
	* @param  TokenStream $stream
	* @return void
	*/
	protected function removeComment(TokenStream $stream)
	{
		$prevToken = $stream->lookbehind();
		$nextToken = $stream->lookahead();

		$isPrecededByWhitespace = (is_array($prevToken) && $prevToken[0] === T_WHITESPACE);
		$isFollowedByWhitespace = (is_array($nextToken) && $nextToken[0] === T_WHITESPACE);

		// Replace this comment with whitespace if it's not preceeded or followed by whitespace
		if (!$isPrecededByWhitespace && !$isFollowedByWhitespace)
		{
			$stream->replace([T_WHITESPACE, ' ']);
		}
		else
		{
			$stream->remove();
		}
		$stream->next();

		// If the comment is surrounded by whitespace, we remove the latter
		if ($isPrecededByWhitespace && $isFollowedByWhitespace)
		{
			$stream->remove();
		}
	}

	/**
	* Remove single-line and/or multi-line comments from given stream
	*
	* @param  TokenStream $stream
	* @return void
	*/
	protected function removeCommentsFrom(TokenStream $stream)
	{
		$stream->reset();
		while ($stream->skipTo(T_COMMENT))
		{
			$comment = $stream->currentText();
			if ($comment[1] === '/' && !$this->removeSingleLineComments)
			{
				continue;
			}
			if ($comment[1] === '*' && !$this->removeMultiLineComments)
			{
				continue;
			}
			if ($this->isExcluded($stream->currentText()))
			{
				continue;
			}

			$this->removeComment($stream);
		}
	}

	/**
	* Test whether given comment should be excluded from minification
	*
	* @param  string $comment Original comment
	* @return bool
	*/
	protected function isExcluded($comment)
	{
		foreach ($this->excludeRegexp as $regexp)
		{
			if (preg_match($regexp, $comment))
			{
				return true;
			}
		}

		return false;
	}
}