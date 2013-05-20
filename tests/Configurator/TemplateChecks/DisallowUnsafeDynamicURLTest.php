<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicURL;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractDynamicContentCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeDynamicURL
*/
class DisallowUnsafeDynamicURLTest extends Test
{
	protected function loadTemplate($template)
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom->documentElement;
	}

	/**
	* @testdox Allowed: <a href="http://example.org">...</a>
	*/
	public function testAllowedStaticAttribute()
	{
		$node = $this->loadTemplate('<a href="http://example.org">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed if #url: <a href="{@foo}">...</a>
	*/
	public function testAllowedDynamic()
	{
		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Url);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Allowed even if unknown: <a href="http://{@foo}">...</a>
	*/
	public function testAllowedAnchored()
	{
		$node = $this->loadTemplate('<a href="http://{@foo}">...</a>');

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed if unknown: <a href="{@foo}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknown()
	{
		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="{@foo}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfiltered()
	{
		$node = $this->loadTemplate('<a href="{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="javascript:{@foo}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredJavascript()
	{
		$node = $this->loadTemplate('<a href="javascript:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="JAVASCRIPT:{@foo}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredJavascriptCase()
	{
		$node = $this->loadTemplate('<a href="JAVASCRIPT:{@foo}">...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a href="<TAB>javascript:{@foo}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredJavascriptTab()
	{
		$node = $this->loadTemplate("<a href=\"\tjavascript:{@foo}\">...</a>");

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <a href="{.}">...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedDot()
	{
		$node = $this->loadTemplate('<a href="{.}">...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->getAttributeNode('href'));

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #url: <a><xsl:copy-of select="@href"/>...</a>
	*/
	public function testAllowedCopyOf()
	{
		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;
		$tag->attributes->add('href')->filterChain->append(new Url);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:copy-of select="@href"/>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'href'
	*/
	public function testDisallowedUnknownCopyOf()
	{
		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a><xsl:copy-of select="@href"/>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'href' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredCopyOf()
	{
		$node = $this->loadTemplate('<a><xsl:copy-of select="@href"/>...</a>');

		$tag = new Tag;
		$tag->attributes->add('href');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed if #url: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	*/
	public function testAllowedValueOf()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Url);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknownValueOf()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed even if unknown: <a><xsl:attribute name="href">http://<xsl:value-of select="@foo"/></xsl:attribute>...</a>
	*/
	public function testAllowedValueOfAnchored()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href">http://<xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo')->filterChain->append(new Url);

		$check = new DisallowUnsafeDynamicURL;
		$check->check($node, $tag);
	}

	/**
	* @testdox Disallowed if unknown: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of unknown attribute 'foo'
	*/
	public function testDisallowedUnknownValueOfJavascript()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href">javascript:<xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->nextSibling->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Attribute 'foo' is not properly sanitized to be used in this context
	*/
	public function testDisallowedUnfilteredValueOf()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="@foo"/></xsl:attribute>...</a>');

		$tag = new Tag;
		$tag->attributes->add('foo');

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed if unfiltered: <a><xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of expression '.'
	*/
	public function testDisallowedValueOfDot()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:value-of select="."/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <a><xsl:attribute name="href"><xsl:apply-templates/></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot allow unfiltered data in this context
	*/
	public function testDisallowedApplyTemplates()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:apply-templates/></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <a><xsl:attribute name="href"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</a>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess context due to 'xsl:for-each'
	*/
	public function testUnsafeContext()
	{
		$node = $this->loadTemplate('<a><xsl:attribute name="href"><xsl:for-each select="//*"><xsl:value-of select="@foo"/></xsl:for-each></xsl:attribute>...</a>');

		$tag = new Tag;

		try
		{
			$check = new DisallowUnsafeDynamicURL;
			$check->check($node, $tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame(
				$e->getNode(),
				$node->firstChild->firstChild->firstChild->firstChild->getAttributeNode('select')
			);

			throw $e;
		}
	}
}