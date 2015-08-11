<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\ConfigOptimizer
*/
class ConfigOptimizerTest extends Test
{
	/**
	* @testdox reset() clears the stored objects
	*/
	public function testReset()
	{
		$optimizer = new ConfigOptimizer;
		$optimizer->optimizeObject(['xxxxxxxx']);
		$this->assertNotEmpty($optimizer->getObjects());
		$optimizer->reset();
		$this->assertEmpty($optimizer->getObjects());
	}

	/**
	* @testdox OptimizeObject tests
	* @dataProvider getOptimizeObjectTests
	*/
	public function testOptimizeObject($original, $expected, $objects)
	{
		$optimizer = new ConfigOptimizer;
		$this->assertEquals($expected, $optimizer->optimizeObject($original));
		$this->assertEquals(implode("\n", $objects), rtrim($optimizer->getObjects()));
	}

	public function getOptimizeObjectTests()
	{
		return [
			[
				[
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				],
				'o66699550',
				[
					'/** @const */ var o3D7424E0=[12345,54321];',
					'/** @const */ var o66699550={foo:o3D7424E0,bar:o3D7424E0};'
				]
			],
			[
				new Dictionary([
					'foo' => [12345, 54321],
					'bar' => [12345, 54321]
				]),
				'oEE749F80',
				[
					'/** @const */ var o3D7424E0=[12345,54321];',
					'/** @const */ var oEE749F80={"foo":o3D7424E0,"bar":o3D7424E0};'
				]
			],
			[
				// Small literals are preserved
				[
					'foo' => [0],
					'bar' => [0]
				],
				'oC5C69F9F',
				[
					'/** @const */ var oC5C69F9F={foo:[0],bar:[0]};'
				]
			],
		];
	}
}