<?php
namespace foo;
if (0)
{
	function mt_rand()
	{
		$with = $without = 0;
		foreach (get_defined_functions()['internal'] as $funcName)
		{
			if (is_bool(strpos($funcName, '_'))
			{
				++$without;
			}
			else
			{
				++$with;
			}
		}
	}
}