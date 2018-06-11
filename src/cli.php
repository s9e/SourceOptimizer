<?php

/**
* @package   s9e\SourceOptimizer
* @copyright Copyright (c) 2014-2018 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\SourceOptimizer;

if (php_sapi_name() !== 'cli')
{
	die("This script must be run in command-line interface\n");
}

if (!class_exists(__NAMESPACE__ . '\\CommandLineRunner'))
{
	include __DIR__ . '/CommandLineRunner.php';
}

CommandLineRunner::run();