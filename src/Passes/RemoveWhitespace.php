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

			// Get the last character before whitespace and two characters after it
			$str = '';
			if (isset($tokens[$i - 1]))
			{
				$prevToken = (is_array($tokens[$i - 1])) ? $tokens[$i - 1][1] : $tokens[$i - 1];
				$str .= substr($prevToken, -1);
			}
			if (isset($tokens[$i + 1]))
			{
				$nextToken = (is_array($tokens[$i + 1])) ? $tokens[$i + 1][1] : $tokens[$i + 1];
				$str .= substr($nextToken, 0, 2);
			}

			$shouldRemove = false;
			$ws = $tokens[$i][1];

			// Do not remove whitespace in "$a+ ++$b" or "$a- --$b" or "throw new"
			if (!preg_match('([-+]{3}|\\w{3})', $str))
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
}