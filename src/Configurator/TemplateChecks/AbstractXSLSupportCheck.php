<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class AbstractXSLSupportCheck extends TemplateCheck
{
	/**
	* @var string[] 
	*/
	protected $supportedElements = [];

	/**
	* Check for elements not supported by the PHP renderer
	*
	* @param DOMElement $template <xsl:template/> node
	* @param Tag        $tag      Tag this template belongs to
	*/
	public function check(DOMElement $template, Tag $tag): void
	{
		$this->checkXslElements();
	}

	/**
	* Check XSL elements in given template
	*/
	protected function checkXslElements(DOMElement $template): void
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$nodes = $xpath->query('/xsl:template//xsl:*');
		foreach ($nodes as $node)
		{
			if (!in_array($node->localName, $this->supportedElements, true))
			{
				throw new RuntimeException('xsl:' . $node->localName . ' elements are not supported');
			}

			$methodName = 'checkXsl' . str_replace(' ', '', ucwords(str_replace('-', ' ', $node->localName))) . 'Element';
			if (method_exists($this, $methodName))
			{
				$this->$methodName($node);
			}
		}
	}
}