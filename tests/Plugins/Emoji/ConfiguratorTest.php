<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Plugins\Emoji\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "EMOJI" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Emoji');
		$this->assertTrue($this->configurator->tags->exists('EMOJI'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('EMOJI');
		$this->configurator->plugins->load('Emoji');

		$this->assertSame($tag, $this->configurator->tags->get('EMOJI'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains no entry for custom aliases if there are none
	*/
	public function testConfigNoCustomAliases()
	{
		$plugin = $this->configurator->Emoji;
		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('customQuickMatch', $config);
		$this->assertArrayNotHasKey('customRegexp',     $config);
	}

	/**
	* @testdox A variable named Emoji.aliases is registered and contains aliases
	*/
	public function testConfigAliases()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':)'] = "\xF0\x9F\x98\x80";
		$plugin->finalize();

		$this->assertArrayHasKey('Emoji.aliases', $this->configurator->registeredVars);
		$this->assertArrayHasKey(':)', $this->configurator->registeredVars['Emoji.aliases']);
		$this->assertSame(
			"\xF0\x9F\x98\x80",
			$this->configurator->registeredVars['Emoji.aliases'][':)']
		);
	}

	/**
	* @testdox The config array contains a regexp for custom aliases if applicable
	*/
	public function testConfigAliasesRegexp()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':D'] = "\xF0\x9F\x98\x80";

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('customRegexp', $config);
		$this->assertEquals('/:D/', $config['customRegexp']);
	}

	/**
	* @testdox The config array contains a quickMatch for custom aliases if applicable
	*/
	public function testConfigAliasesQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':D'] = "\xF0\x9F\x98\x80";

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('customQuickMatch', $config);
		$this->assertEquals(':D', $config['customQuickMatch']);
	}

	/**
	* @testdox The config array does not contain a quickMatch for aliases if impossible
	*/
	public function testConfigAliasesNoQuickMatch()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':D'] = "\xF0\x9F\x98\x80";
		$plugin->aliases[';)'] = "\xF0\x9F\x98\x80";

		$config = $plugin->asConfig();

		$this->assertArrayNotHasKey('customQuickMatch', $config);
	}

	/**
	* @testdox Uses Twemoji by default
	*/
	public function testDefaultTemplateTwemoji()
	{
		$this->configurator->Emoji;
		$this->assertStringContainsString('twemoji', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_REGEXP' => false] by default
	*/
	public function testGetJSHintsRegexpFalse()
	{
		$plugin = $this->configurator->Emoji;
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_REGEXP' => false],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_REGEXP' => true] if a custom regexp exists
	*/
	public function testGetJSHintsRegexpTrue()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':)'] = "\xF0\x9F\x98\x80";
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_REGEXP' => true],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_QUICKMATCH' => false] by default
	*/
	public function testGetJSHintsAliasQuickmatchFalse()
	{
		$plugin = $this->configurator->Emoji;
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_QUICKMATCH' => false],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['EMOJI_HAS_CUSTOM_QUICKMATCH' => true] if an alias quick match exists
	*/
	public function testGetJSHintsAliasQuickmatchTrue()
	{
		$plugin = $this->configurator->Emoji;
		$plugin->aliases[':)'] = "\xF0\x9F\x98\x80";
		$plugin->aliases[':D'] = "\xF0\x9F\x98\x80";
		$this->assertArrayMatches(
			['EMOJI_HAS_CUSTOM_QUICKMATCH' => true],
			$plugin->getJSHints()
		);
	}
}