#!/usr/bin/php
<?php

function getSource($path)
{
	$php = '';
	foreach (glob($path . '/*.php') as $filepath)
	{
		$php .= preg_replace('(<\\?php\\s*)', '', file_get_contents($filepath)) . "\n";
	}
	foreach (glob($path . '/*', GLOB_ONLYDIR) as $dir)
	{
		$php .= getSource($dir);
	}

	return $php;
}

include __DIR__ . '/../src/CommandLineRunner.php';
s9e\SourceOptimizer\CommandLineRunner::autoload();

$target = __DIR__ . '/../bin/source-optimizer';
$php = "#!/usr/bin/php\n<?php\n" . getSource(__DIR__ . '/../src');

$optimizer = new s9e\SourceOptimizer\Optimizer;
$php = $optimizer->optimize($php);

file_put_contents($target, $php);

die("Done.\n");