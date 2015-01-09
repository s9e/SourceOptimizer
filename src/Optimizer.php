<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
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
		$this->enable('RemoveComments');
		$this->enable('RemoveWhitespace');
	}

	/**
	* Disable an optimization pass
	*
	* @param  string $passName Name of the optimization pass
	* @return void
	*/
	public function disable($passName)
	{
		unset($this->passes[$passName]);
	}

	/**
	* Disable all optimization passes
	*
	* @return void
	*/
	public function disableAll()
	{
		$this->passes = [];
	}

	/**
	* Enable an optimization pass
	*
	* @param  string $passName Name of the optimization pass
	* @param  array  $options  Options to be set on the pass instance
	* @return void
	*/
	public function enable($passName, array $options = [])
	{
		$className = __NAMESPACE__ . '\\Passes\\' . $passName;

		if (!class_exists($className))
		{
			trigger_error("Pass '" . $passName . "' does not exist");

			return;
		}

		$this->passes[$passName] = new $className;
		foreach ($options as $optionName => $optionValue)
		{
			$this->passes[$passName]->$optionName = $optionValue;
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
		$remainingLoops = 1;
		$new = $old;

		$stream = new TokenStream($old);
		do
		{
			$old = $new;
			foreach ($this->passes as $pass)
			{
				$stream->reset();
				$pass->optimize($stream);
			}

			$new = $stream->serialize();
		}
		while (--$remainingLoops > 0 && $new !== $old);

		return $new;
	}

	/**
	* Optimize a PHP file
	*
	* @param  string $filepath Path to the file
	* @return void
	*/
	public function optimizeDir($path)
	{
		array_map([$this, 'optimizeFile'], glob($path . '/*.php'));
		array_map([$this, 'optimizeDir'], glob($path . '/*', GLOB_ONLYDIR));
	}

	/**
	* Optimize all .php files in given directory and its subdirectories
	*
	* @param  string $filepath Path to the file
	* @return void
	*/
	public function optimizeFile($filepath)
	{
		$old = file_get_contents($filepath);
		$new = $this->optimize($old);

		if ($new !== $old)
		{
			file_put_contents($filepath, $new);
		}
	}
}