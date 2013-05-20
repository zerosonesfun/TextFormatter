<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Template;

class TemplateCollection extends NormalizedCollection
{
	/**
	* Normalize a template for storage
	*
	* @param  mixed    $template Either a string, a callback or an instance of Template
	* @return Template           An instance of Template
	*/
	public function normalizeValue($template)
	{
		// Create an instance of Template if it's not one
		if (!($template instanceof Template))
		{
			$template = new Template($template);
		}

		return $template;
	}
}