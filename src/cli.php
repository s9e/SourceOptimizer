<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

if (php_sapi_name() !== 'cli')
{
	die("This script must be run in command-line interface\n");
}

if (!class_exists('s9e\\SourceOptimizer\\CommandLineRunner'))
{
	include __DIR__ . '/CommandLineRunner.php';
}

\s9e\SourceOptimizer\CommandLineRunner::run();