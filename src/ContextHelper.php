<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2017 The s9e Authors
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
	* Execute given callback on each function block in given stream
	*
	* @param  TokenStream $stream   Token stream
	* @param  callable    $callback Callback
	* @return void
	*/
	public static function forEachFunction(TokenStream $stream, callable $callback)
	{
		foreach (self::getFunctionBlocks($stream) as $block)
		{
			call_user_func_array($callback, $block);
		}
	}

	/**
	* Get the list of function blocks in given stream
	*
	* @param  TokenStream $stream Token stream
	* @return array[]             List of arrays of [start, end] offsets
	*/
	public static function getFunctionBlocks(TokenStream $stream)
	{
		$blocks = [];
		$stream->reset();
		while ($stream->skipTo(T_FUNCTION))
		{
			$offset = $stream->key();
			$stream->skipToToken('{');
			$cnt = 0;
			while ($stream->valid())
			{
				$token = $stream->current();
				if ($token === '{')
				{
					++$cnt;
				}
				elseif ($token === '}')
				{
					if (--$cnt === 0)
					{
						$blocks[] = [$offset, $stream->key()];
						break;
					}
				}
				$stream->next();
			}
		}

		return $blocks;
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

		$i = 0;
		$blocks = [];
		foreach ($namespaces as $offset => $namespace)
		{
			if ($i > 0)
			{
				$blocks[$i - 1][2] = $offset - 1;
			}
			$blocks[] = [$namespace, $offset];
			++$i;
		}
		$blocks[$i - 1][2] = $stream->key() - 1;

		return $blocks;
	}
}