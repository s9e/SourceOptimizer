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
		while ($stream->valid())
		{
			$token = $stream->current();
			$offset = $stream->key();
			$stream->next();
			if ($token[0] !== T_NAMESPACE)
			{
				continue;
			}

			$namespace = '';
			while ($stream->valid())
			{
				$token = $stream->current();
				if (in_array($stream->current(), [';', '{'], true))
				{
					break;
				}
				if (!$stream->isNoise())
				{
					$namespace .= $token[1];
				}
				$stream->next();
			}
			$namespaces[$offset] = $namespace;
		}

		return $namespaces;
	}
}