<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

class CommandLineRunner
{
	/**
	* Ensure that all the required files are loaded
	*
	* @return void
	*/
	public static function autoload()
	{
		if (!class_exists(__NAMESPACE__ . '\\Optimizer'))
		{
			self::preload(__DIR__);
		}
	}

	/**
	* Run the command-line program
	*
	* @return void
	*/
	public static function run()
	{
		self::autoload();
		$optimizer = new Optimizer;
		foreach (self::getTargets() as $path)
		{
			if (!file_exists($path))
			{
				echo "$path not found\n";
				continue;
			}
			$methodName = (is_dir($path)) ? 'optimizeDir' : 'optimizeFile';
			$optimizer->$methodName($path);
		}
	}

	/**
	* Get the list of targets from command-line
	*
	* @return string[]
	*/
	protected static function getTargets()
	{
		return array_slice($_SERVER['argv'], 1);
	}

	/**
	* Preload all PHP files from given dir
	*
	* @param  string $dir Path to the dir
	* @return void
	*/
	protected static function preload($dir)
	{
		foreach (glob($dir . '/[A-Z]*.php') as $path)
		{
			include_once $path;
		}
		array_map(__METHOD__, glob($dir . '/*', GLOB_ONLYDIR));
	}
}