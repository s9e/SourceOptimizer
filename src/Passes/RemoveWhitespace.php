<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer\Passes;

use s9e\SourceOptimizer\Pass;

class RemoveWhitespace extends Pass
{
	/**
	* @var string[] List of exact triplet of tokens to exclude from minification
	*/
	public $excludeExact = [
		// 1 - - 1 and 1 + + 1 should not become 1--1 or 1++1
		'- -',
		'+ +',
		// $a - --$b should not become $a---$b
		'- --',
		'+ ++'
	];

	/**
	* @var string[] List of regexps used to exclude from minification
	*/
	public $excludeRegexp = [
		// throw new should not become thrownew
		'(^\\w+ \\w)'
	];

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
	* 
	*
	* @return void
	*/
	public function optimize(array &$tokens, $start, $end)
	{
		$reparse = false;
		$i = $start - 1;
		while (++$i <= $end)
		{
			if ($tokens[$i][0] !== T_WHITESPACE)
			{
				continue;
			}

			// Build a string that contain the tokens adjacent to this whitespace
			$str = '';
			if (isset($tokens[$i - 1]))
			{
				$str .= (is_array($tokens[$i - 1])) ? $tokens[$i - 1][1] : $tokens[$i - 1];
			}
			$str .= ' ';
			if (isset($tokens[$i + 1]))
			{
				$str .= (is_array($tokens[$i + 1])) ? $tokens[$i + 1][1] : $tokens[$i + 1];
			}

			$shouldRemove = false;
			$ws = $tokens[$i][1];

			if (!$this->isExcluded($str))
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
				unset($tokens[$i]);
				$reparse = true;
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

			$tokens[$i][1] = $ws;
		}

		return $reparse;
	}

	/**
	* Test whether given string should be excluded from minification
	*
	* @param  string $str Triplet of tokens, e.g. "$a +"
	* @return bool
	*/
	protected function isExcluded($str)
	{
		if (in_array($str, $this->excludeExact, true))
		{
			return true;
		}

		foreach ($this->excludeRegexp as $regexp)
		{
			if (preg_match($regexp, $str))
			{
				return true;
			}
		}

		return false;
	}
}