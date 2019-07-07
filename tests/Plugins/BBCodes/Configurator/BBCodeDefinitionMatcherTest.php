<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\FilterSyntaxMatcher;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeDefinitionMatcher;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeDefinitionMatcher
*/
class BBCodeDefinitionMatcherTest extends Test
{
	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($filterString, $expected)
	{
		if ($expected instanceof RuntimeException)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
		}

		$parser = new RecursiveParser;
		$parser->setMatchers([
			new BBCodeDefinitionMatcher($parser),
			new FilterSyntaxMatcher($parser)
		]);

		$this->assertEquals($expected, $parser->parse($filterString, 'BBCodeDefinition')['value']);
	}

	public function getParseTests()
	{
		return [
			[
				'[b]{TEXT}[/b]',
				[
					'bbcodeName' => 'B',
					'content'    => [['id' => 'TEXT']]
				]
			],
			[
				'[br]',
				[
					'bbcodeName' => 'BR',
					'content'    => []
				]
			],
			[
				'[x foo={TEXT}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT']]
						]
					]
				]
			],
			[
				'[x foo="{TEXT}"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT']]
						]
					]
				]
			],
			[
				'[x foo={TEXT1} bar={TEXT2}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT1']]
						],
						[
							'name'    => 'bar',
							'content' => [['id' => 'TEXT2']]
						]
					]
				]
			],
			[
				'[x $forceLookahead=true]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'forceLookahead', 'value' => true]]
				]
			],
			[
				'[x $forceLookahead]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'forceLookahead']]
				]
			],
			[
				'[x $foo=[1,2]]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'foo', 'value' => [1, 2]]]
				]
			],
			[
				'[x $foo="]"]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'options'    => [['name' => 'foo', 'value' => ']']]
				]
			],
			[
				'[x #autoClose=false]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose', 'value' => false]]
				]
			],
			[
				'[x #autoClose=True]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose', 'value' => true]]
				]
			],
			[
				'[x #autoClose]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'      => [['name' => 'autoClose']]
				]
			],
			[
				'[x #closeParent=foo,bar]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'rules'    => [
						['name' => 'closeParent', 'value' => 'foo'],
						['name' => 'closeParent', 'value' => 'bar']
					]
				]
			],
			[
				'[x foo={TEXT?}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								[
									'id'      => 'TEXT',
									'options' => [['name' => 'required', 'value' => false]]
								]
							]
						]
					]
				]
			],
			[
				'[x foo={REGEXP=/foo/}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								[
									'id'          => 'REGEXP',
									'filterValue' => new Regexp('/foo/', true)
								]
							]
						]
					]
				]
			],
			[
				'[x foo={TEXT1}
					bar={TEXT2}
				]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [['id' => 'TEXT1']]
						],
						[
							'name'    => 'bar',
							'content' => [['id' => 'TEXT2']]
						]
					]
				]
			],
			[
				'[x foo={TEXT1;
						foo=1;
						bar=["ab", "cd"];
				}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [
						[
							'name'    => 'foo',
							'content' => [
								[
									'id'      => 'TEXT1',
									'options' => [
										['name' => 'foo', 'value' => 1],
										['name' => 'bar', 'value' => ['ab', 'cd']]
									]
								]
							]
						]
					]
				]
			],
			[
				'[x $tagName=FOO
					$filterChain.append=MyFilter::foo($tag, 1, 2)
					$filterChain.append=MyFilter::bar()
					$filterChain.prepend=MyFilter::baz]',
				[
					'bbcodeName'  => 'X',
					'content'     => [],
					'options'     => [['name' => 'tagName', 'value' => 'FOO']],
					'filterChain' => [
						['mode' => 'append',  'filter' => 'MyFilter::foo($tag, 1, 2)'],
						['mode' => 'append',  'filter' => 'MyFilter::bar()'],
						['mode' => 'prepend', 'filter' => 'MyFilter::baz'],
					]
				]
			],
			[
				'[url={URL;useContent}]{TEXT}[/url]',
				[
					'bbcodeName' => 'URL',
					'content'    => [['id' => 'TEXT']],
					'attributes' => [[
						'name'    => 'url',
						'content' => [[
							'id' => 'URL',
							'options' => [['name' => 'useContent']]
						]]
					]]
				]
			],
			[
				'[X x={TEXT;preFilter=strtolower}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [[
								'name'  => 'filterChain.prepend',
								'value' => 'strtolower'
							]]
						]]
					]]
				]
			],
			[
				'[X x={TEXT;filterChain.prepend=str_replace($attrValue, "_", "-")}]',
				[
					'bbcodeName' => 'X',
					'content'    => [],
					'attributes' => [[
						'name'    => 'x',
						'content' => [[
							'id'      => 'TEXT',
							'options' => [[
								'name'  => 'filterChain.prepend',
								'value' => 'str_replace($attrValue, "_", "-")'
							]]
						]]
					]]
				]
			],
		];
	}
}