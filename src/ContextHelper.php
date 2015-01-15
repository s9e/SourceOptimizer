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
	* Execute given callback on each namespace block in given stream
	*
	* @param  TokenStream $stream   Token stream
	* @param  callable    $callback Callback
	* @return void
	*/
	public static function forEachNamespace(TokenStream $stream, callable $callback)
	{
		foreach (self::getNamespaces($stream) as $block)
		{
			call_user_func_array($callback, $block);
		}
	}

	/**
	* Get the list of namespaces in given stream
	*
	* @param  TokenStream $stream Token stream
	* @return array[]             List of arrays with namespace, start offset, end offset
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

		$blocks = [];
		foreach ($namespaces as $offset => $namespace)
		{
			if ($blocks)
			{
				$blocks[count($blocks) - 1][2] = $offset - 1;
			}
			$blocks[] = [$namespace, $offset];
		}
		$blocks[count($blocks) - 1][2] = $stream->key() - 1;

		return $blocks;
	}
}