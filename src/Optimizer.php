<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

class Optimizer
{
	/**
	* @var array<\s9e\SourceOptimizer\Pass>
	*/
	protected $passes = [];

	/**
	* 
	*
	* @return void
	*/
	public function __construct()
	{
		$this->enable('RemoveWhitespace');
	}

	/**
	* Disable an optimization pass
	*
	* @param  string $pass Name of the optimization pass
	* @return void
	*/
	public function disable($pass, array $options = [])
	{
		unset($this->passes[$pass]);
	}

	/**
	* Enable an optimization pass
	*
	* @param  string $pass    Name of the optimization pass
	* @param  array  $options Options to be set on the pass instance
	* @return void
	*/
	public function enable($pass, array $options = [])
	{
		$className = __NAMESPACE__ . '\\Passes\\' . $pass;

		if (!class_exists($className))
		{
			trigger_error("Pass '" . $pass . "' does not exist");

			return;
		}

		$this->passes[$pass] = new $className;
		foreach ($options as $optionName => $optionValue)
		{
			$this->passes[$pass]->$optionName = $optionValue;
		}
	}

	/**
	* 
	*
	* @param  string $old Original source code
	* @return string      Optimize source code
	*/
	public function optimize($old)
	{
		$new            = $old;
		$remainingLoops = 2;
		$reparse        = false;
		$tokens         = $this->parse($old);
		do
		{
			$old = $new;
			foreach ($this->passes as $pass)
			{
				if ($reparse)
				{
					$tokens = $this->parse($this->serialize($tokens));
				}

				$reparse = false;
				foreach ($pass->getBlocks($tokens) as list($start, $end))
				{
					$reparse |= $pass->optimize($tokens, $start, $end);
				}
			}

			$new = $this->serialize($tokens);
		}
		while (--$remainingLoops > 0 && $new !== $old);

		return $new;
	}

	/**
	* 
	*
	* @param  string              $src
	* @return array<array|string>
	*/
	protected function parse($src)
	{
		$tokens = token_get_all($src);
		foreach ($tokens as &$token)
		{
			if (is_array($token))
			{
				unset($token[3]);
			}
		}

		return $tokens;
	}

	/**
	* 
	*
	* @return string
	*/
	protected function serialize(&$tokens)
	{
		$src = '';
		foreach ($tokens as $token)
		{
			$src .= (is_array($token)) ? $token[1] : $token;
		}

		return $src;
	}
}