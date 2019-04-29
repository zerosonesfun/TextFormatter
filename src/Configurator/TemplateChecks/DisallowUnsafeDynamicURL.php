<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* This primary use of this check is to ensure that dynamic content cannot be used to create
* javascript: links
*/
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	/**
	* @var string Regexp used to exclude nodes that start with a hardcoded scheme part, a hardcoded
	*             local part, or a fragment
	*/
	protected $safeUrlRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';

	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		if (!$this->isSafeUrl($attribute->value))
		{
			parent::checkAttributeNode($attribute, $tag);
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		if (!$this->elementHasSafeUrl($element))
		{
			parent::checkElementNode($element, $tag);
		}
	}

	/**
	* Test whether given element contains a known-safe URL
	*
	* @param  DOMElement $element
	* @return bool
	*/
	protected function elementHasSafeUrl(DOMElement $element)
	{
		return $element->firstChild instanceof DOMText && $this->isSafeUrl($element->firstChild->textContent);
	}

	/**
	* Test whether given URL is known to be safe
	*
	* @param  string $url
	* @return bool
	*/
	protected function isSafeUrl($url)
	{
		return (bool) preg_match($this->safeUrlRegexp, $url);
	}
}