<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

abstract class ContextHelper
{
	/**
	* Get the list of namespaces in given stream
	*
	* @param  TokenStream $stream Token stream
	* @return array               Offset as key, namespace as value
	*/
	public static function getNamespaces(TokenStream $stream)
	{
		$namespaces = [''];
		$stream->reset();
		while ($stream->skipTo(T_NAMESPACE))
		{
			$offset = $stream->key();
			$stream->next();
			$stream->skipNoise();
			$namespace = '';
			while ($stream->valid())
			{
				$token = $stream->current();
				if (in_array($token, [';', '{'], true))
				{
					break;
				}
				$namespace .= $token[1];
				$stream->next();
				$stream->skipNoise();
			}
			$namespaces[$offset] = $namespace;
		}

		return $namespaces;
	}
}