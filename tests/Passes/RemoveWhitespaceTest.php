<?php

namespace s9e\SourceOptimizer\Tests\Passes;

use PHPUnit_Framework_TestCase;
use s9e\SourceOptimizer\Tests\PassTest;

/**
* @covers s9e\SourceOptimizer\Passes\RemoveWhitespace
*/
class RemoveWhitespaceTest extends PassTest
{
	public function getOptimizeTests()
	{
		return [
			[
				'<?php

				$foo = 42;',
				'<?php

				$foo=42;'
			]
		];
	}
}