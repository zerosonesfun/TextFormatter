<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;

/**
* @method mixed   add(string $key)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method void    count()
* @method void    current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method void    key()
* @method void    next()
* @method string  normalizeKey(string $key)
* @method string  normalizeValue(string $value)
* @method void    offsetExists()
* @method void    offsetGet()
* @method void    offsetSet()
* @method void    offsetUnset()
* @method string  onDuplicate(string $action)
* @method void    rewind()
* @method mixed   set(string $key)
* @method void    valid()
*/
class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var EmoticonCollection
	*/
	protected $collection;

	/**
	* @var string PCRE subpattern used in a negative lookbehind assertion before the emoticons
	*/
	public $notAfter = '';

	/**
	* @var string PCRE subpattern used in a negative lookahead assertion after the emoticons
	*/
	public $notBefore = '';

	/**
	* @var string XPath expression that, if true, forces emoticons to be rendered as text
	*/
	public $notIfCondition;

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'E';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		$this->collection = new EmoticonCollection;

		if (!$this->configurator->tags->exists($this->tagName))
		{
			$this->configurator->tags->add($this->tagName);
		}
	}

	/**
	* Create the template used for emoticons
	*
	* @return void
	*/
	public function finalize()
	{
		$tag = $this->getTag();

		if (!isset($tag->template))
		{
			$tag->template = $this->getTemplate();
		}
	}

	/**
	* @return array
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		// Grab the emoticons from the collection
		$codes = array_keys(iterator_to_array($this->collection));

		// Build the regexp used to match emoticons
		$regexp = '/';

		if ($this->notAfter !== '')
		{
			$regexp .= '(?<!' . $this->notAfter . ')';
		}

		$regexp .= RegexpBuilder::fromList($codes);

		if ($this->notBefore !== '')
		{
			$regexp .= '(?!' . $this->notBefore . ')';
		}

		$regexp .= '/S';

		// Set the Unicode mode if Unicode properties are used
		if (preg_match('/\\\\[pP](?>\\{\\^?\\w+\\}|\\w\\w?)/', $regexp))
		{
			$regexp .= 'u';
		}

		// Force the regexp to use atomic grouping for performance
		$regexp = preg_replace('/(?<!\\\\)((?>\\\\\\\\)*)\\(\\?:/', '$1(?>', $regexp);

		// Prepare the config array
		$config = [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $regexp,
			'tagName'    => $this->tagName
		];

		// If notAfter is used, we need to create a JavaScript-specific regexp that does not use a
		// lookbehind assertion, and we add the notAfter subpattern to the config as a RegExp
		if ($this->notAfter !== '')
		{
			// Skip the first assertion by skipping the first N characters, where N equals the
			// length of $this->notAfter plus 1 for the first "/" and 5 for "(?<!)"
			$lpos = 6 + strlen($this->notAfter);
			$rpos = strrpos($regexp, '/');
			$jsRegexp = RegexpConvertor::toJS('/' . substr($regexp, $lpos, $rpos - $lpos) . '/');
			$jsRegexp->flags .= 'g';

			$config['regexp'] = new Variant($regexp);
			$config['regexp']->set('JS', $jsRegexp);

			$config['notAfter'] = new Variant;
			$config['notAfter']->set('JS', RegexpConvertor::toJS('/' . $this->notAfter . '/'));
		}

		// Try to find a quickMatch if none is set
		if ($this->quickMatch === false)
		{
			$config['quickMatch'] = ConfigHelper::generateQuickMatchFromList($codes);
		}

		return $config;
	}

	/**
	* Generate the dynamic template that renders all emoticons
	*
	* @return string
	*/
	public function getTemplate()
	{
		// Group the codes by template in order to merge duplicate templates. Replace codes with
		// their representation as a string (with quotes)
		$templates = [];
		foreach ($this->collection as $code => $template)
		{
			$templates[$template][] = htmlspecialchars(TemplateHelper::asXPath($code));
		}

		// Build the <xsl:choose> node
		$xsl = '<xsl:choose>';

		// First, test whether the emoticon should be rendered as text if applicable
		if (!empty($this->notIfCondition))
		{
			$xsl .= '<xsl:when test="' . htmlspecialchars($this->notIfCondition) . '">'
			      . '<xsl:value-of select="."/>'
			      . '</xsl:when>';
		}

		// Iterate over codes, create an <xsl:when> for each group of codes
		foreach ($templates as $template => $codes)
		{
			$xsl .= '<xsl:when test=".=' . implode('or.=', $codes) . '">'
			      . $template
			      . '</xsl:when>';
		}

		// Finish it with an <xsl:otherwise> that displays the unknown codes as text
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>';

		// Now close everything and return
		$xsl .= '</xsl:choose>';

		return $xsl;
	}
}