<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

abstract class Helper
{
	/**
	* Merge consecutive whitespace tokens
	*
	* @param  TokenStream $stream Token stream
	* @return void
	*/
	public static function mergeWhitespace(TokenStream $stream)
	{
		foreach ($stream as $k => $token)
		{
			if ($token[0] !== T_WHITESPACE)
			{
				unset($wsToken);
				continue;
			}

			if (isset($wsToken))
			{
				$stream[$k] = [T_WHITESPACE, $stream[$wsToken][1] . $token[1]];
				unset($stream[$wsToken]);
			}
			$wsToken = $k;
		}
	}
}