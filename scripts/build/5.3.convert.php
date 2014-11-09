<?php

namespace s9e\TextFormatter\Build\PHP53;

$version = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : PHP_VERSION;

if (version_compare($version, '5.4', '>='))
{
	echo 'No need to run ', __FILE__, ' on PHP ', $version, "\n";
	return;
}

function fqn($file)
{
	$table = array();
	if (!preg_match('#namespace ([^;]+)#', $file, $m))
	{
		die("Could not capture namespace from $filepath\n");
	}
	$namespace = $m[1];

	preg_match_all('#^use ([^;]+)#m', $file, $m);
	foreach ($m[1] as $fqn)
	{
		$table[preg_replace('#.*\\\\#', '', $fqn)] = $fqn;
	}

	return array($namespace, $table);
}

function x($str)
{
	return var_export($str, true);
}

function convertCustom($filepath, &$file)
{
	// Some specific tweaks for PHP 5.3 that would be considered bad code in 5.4
	$replacements = array(
		'AVTHelper.php' => array(
			array(
				'public static function replace(DOMAttr $attribute, callable $callback)',
				'public static function replace(DOMAttr $attribute, $callback)'
			)
		),
		'BBCodeMonkey.php' => array(
			array(
				'protected function isFilter($tokenId)',
				'public function isFilter($tokenId)'
			)
		),
		'BundleGenerator.php' => array(
			array(
				'protected function exportCallback($namespace, callable $callback, $argument)',
				'protected function exportCallback($namespace, $callback, $argument)'
			)
		),
		'Censor/Helper.php' => array(
			array(
				'protected function buildTag($word)',
				'public function buildTag($word)'
			)
		),
		'Configurator.php' => array(
			array(
				'$options[\'finalizeParser\']($parser);',
				'call_user_func($options[\'finalizeParser\'], $parser);'
			),
			array(
				'$options[\'finalizeRenderer\']($renderer);',
				'call_user_func($options[\'finalizeRenderer\'], $renderer);'
			)
		),
		'Custom.php' => array(
			array(
				'public function __construct(callable $callback)',
				'public function __construct($callback)'
			),
			array(
				'$this->callback = $callback;',
				str_replace(
					"\n\t\t\t",
					"\n",
					'if (!is_callable($callback))
					{
						trigger_error("Argument 1 passed to " . __METHOD__ . "() must be callable, " . gettype($callback) . " given", E_USER_ERROR);
					}

					$this->callback = $callback;'
				)
			)
		),
		'FilterChain.php' => array(
			array(
				'$callback = (new ProgrammableCallback($callback))->getCallback();',
				'$pc=new ProgrammableCallback($callback);$callback = $pc->getCallback();'
			)
		),
		'InlineXPathLiterals.php' => array(
			array(
				'protected function getTextContent($expr)',
				'public function getTextContent($expr)',
			)
		),
		'JavaScript.php' => array(
			array(
				'$xsl = (new XSLT)->getXSL($this->configurator->rendering);',
				"\$rendererGenerator = new XSLT;\n\t\t\$xsl = \$rendererGenerator->getXSL(\$this->configurator->rendering);"
			)
		),
		'Logger.php' => array(
			array(
				'$callback($msg, $context);',
				'call_user_func_array($callback, array(&$msg, &$context));'
			)
		),
		'MediaEmbed/ParserTest.php' => array(
			array(
				'protected static function populateCache($entries)',
				'public static function populateCache($entries)'
			),
			array(
				"\$configurator->registeredVars['cacheDir'] = self::populateCache([",
				"\$configurator->registeredVars['cacheDir'] = ParserTest::populateCache(["
			)
		),
		'PHP.php' => array(
			array(
				"return '[' . implode(',', \$pairs) . ']';",
				"return 'array(' . implode(',', \$pairs) . ')';"
			),
			array(
				"\$php[] = '		\$toks = [];';",
				"\$php[] = '		\$toks = array();';"
			),
		),
		'PHPTest.php' => array(
			array(
				"'protected static \$bt13027555=[1=>0,2=>1,3=>2,4=>3,5=>4,6=>5,7=>6,8=>7];',",
				"'protected static \$bt13027555=array(1=>0,2=>1,3=>2,4=>3,5=>4,6=>5,7=>6,8=>7);',"
			),
		),
		'PHP/XPathConvertor.php' => array(
			array(
				// mb_substr() doesn't like null as third parameter on PHP 5.3
				"\$php .= 'null';",
				'$php .= 0x7fffffe;'
			),
			array(
				"\$php .= '[';",
				"\$php .= 'array(';"
			),
			array(
				"\$php .= ']';",
				"\$php .= ')';"
			)
		),
		'PHP/XPathConvertorTest.php' => array(
			array(
				'"mb_substr(\\$node->textContent,1,null,\'utf-8\')",',
				'"mb_substr(\\$node->textContent,1,134217726,\'utf-8\')",'
			),
			array(
				"\"strtr(\\\$node->getAttribute('bar'),['é'=>'É','è'=>'È'])\"",
				"\"strtr(\\\$node->getAttribute('bar'),array('é'=>'É','è'=>'È'))\""
			),
			array(
				"\"strtr(\\\$node->getAttribute('bar'),['a'=>'A','b'=>'B','c'=>'','d'=>''])\"",
				"\"strtr(\\\$node->getAttribute('bar'),array('a'=>'A','b'=>'B','c'=>'','d'=>''))\""
			)
		),
		'Quick.php' => array(
			array(
				'$php[] = \'		self::$attributes = [];\';',
				'$php[] = \'		self::$attributes = array();\';'
			),
			array(
				'$php[] = "			[\\$this, \'quick\'],";',
				'$php[] = "			array(\\$this, \'quick\'),";'
			),
			array(
				'$php[] = \'			$attributes = [];\';',
				'$php[] = \'			$attributes = array();\';'
			),
			array(
				'$head = "\\$attributes+=[\'" . implode("\'=>null,\'", $attrNames) . "\'=>null];" . $head;',
				'$head = "\\$attributes+=array(\'" . implode("\'=>null,\'", $attrNames) . "\'=>null);" . $head;'
			),
			array(
				"return '[' . implode(',', \$entries) . ']';",
				"return 'array(' . implode(',', \$entries) . ')';"
			)
		),
		'QuickTest.php' => array(
			array(
				x('$attributes+=[\'foo\'=>null];$html=str_replace(\'&quot;\',\'"\',$attributes[\'foo\']);'),
				x('$attributes+=array(\'foo\'=>null);$html=str_replace(\'&quot;\',\'"\',$attributes[\'foo\']);')
			),
			array(
				x('$attributes+=[\'foo\'=>null];$html=\'START\';if($attributes[\'foo\']==1){$html.=\'[1]\';if($attributes[\'foo\']==2){$html.=\'[2]\';}else{$html.=\'[3]\';}}else{$html.=\'[o]\';if($attributes[\'foo\']==4){$html.=\'[4]\';}else{$html.=\'[5]\';}}self::$attributes[]=$attributes;'),
				x('$attributes+=array(\'foo\'=>null);$html=\'START\';if($attributes[\'foo\']==1){$html.=\'[1]\';if($attributes[\'foo\']==2){$html.=\'[2]\';}else{$html.=\'[3]\';}}else{$html.=\'[o]\';if($attributes[\'foo\']==4){$html.=\'[4]\';}else{$html.=\'[5]\';}}self::$attributes[]=$attributes;')
			),
			array(
				'[[\'php\', "\\$attributes+=[\'content\'=>null];\\$html=\'<!--\'.str_replace(\'&quot;\',\'\\"\',\\$attributes[\'content\']).\'-->\';"]]',
				'[[\'php\', "\\$attributes+=array(\'content\'=>null);\\$html=\'<!--\'.str_replace(\'&quot;\',\'\\"\',\\$attributes[\'content\']).\'-->\';"]]',
			),
			array(
				'"\\$static=[\'foo:bar\'=>\'foobar\']"',
				'"\\$static=array(\'foo:bar\'=>\'foobar\')"'
			),
		),
		'RegexpBuilder.php' => array(
			array(
				'if (preg_match_all(\'#.#us\', $word, $matches) === false)',
				'if (preg_match_all(\'#.#us\', $word, $matches) === false || !preg_match(\'/^(?:[[:ascii:]]|[\\xC0-\\xDF][\\x80-\\xBF]|[\\xE0-\\xEF][\\x80-\\xBF]{2}|[\\xF0-\\xF7][\\x80-\\xBF]{3})*$/D\', $word))'
			)
		),
		'Rendering.php' => array(
			array(
				// https://bugs.php.net/52854
				'$engine = $reflection->newInstanceArgs(array_slice(func_get_args(), 1));',
				'$engine = (func_num_args() > 1) ? $reflection->newInstanceArgs(array_slice(func_get_args(), 1)) : $reflection->newInstance();'
			)
		),
		'Variant.php' => array(
			array(
				'return ($isDynamic) ? $value() : $value;',
				'return ($isDynamic) ? call_user_func($value) : $value;'
			)
		),
		'XSLTTest.php' => array(
			array(
				"/**\n\t* @testdox setParameter() accepts values that contain both types of quotes\n\t*/\n\tpublic function testSetParameterBothQuotes()",
				"public function _ignore()"
			)
		)
	);

	foreach ($replacements as $path => $pairs)
	{
		$path = '/' . $path;
		if (substr($filepath, -strlen($path)) === $path)
		{
			foreach ($pairs as $pair)
			{
				if (!is_array($pair))
				{
					$file = $pair($file);
					continue;
				}

				list($search, $replace) = $pair;
				$file = str_replace($search, $replace, $file);
			}
		}
	}

	// Some class-specific modifications
	switch (basename($filepath))
	{
		case 'AttributeFilter.php':
			$block =
<<<'END'
	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}
END;
			$file = str_replace($block, '', $file);
			break;

		case 'Attribute.php':
			$block =
<<<'END'
	/**
	* Return whether this object is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test whether this attribute was marked as safe in given context
		return !empty($this->markedSafe[$context]);
	}
END;
			$file = str_replace($block, '', $file);
			break;
	}
}

function convertFile($filepath)
{
	$file    = file_get_contents($filepath);
	$oldFile = $file;

	convertUse($filepath, $file);
	convertClosureBinding($filepath, $file);
	convertCustom($filepath, $file);
	convertArraySyntax($file);

	if ($file !== $oldFile)
	{
		echo "\r\x1B[KReplacing $filepath ";
		file_put_contents($filepath, $file);
	}
}

function convertUse($filepath, &$file)
{
	if (!strpos($file, "\tuse "))
	{
		return;
	}

	list($namespace, $table) = fqn($file);

	// Hardcode a couple of names
	if (strpos($filepath, 'Parser.php'))
	{
		$table['BuiltInFilters'] = 's9e\\TextFormatter\\Parser\\BuiltInFilters';
		$table['Tag'] = 's9e\\TextFormatter\\Parser\\Tag';
	}

	$file = preg_replace_callback(
		'#^\\tuse ([^;]+);\\n*#m',
		function ($m) use ($namespace, &$table)
		{
			$fqn  = (isset($table[$m[1]])) ? $table[$m[1]] : $namespace . '\\' . $m[1];
			$path = __DIR__ . '/../../src' . str_replace('\\', DIRECTORY_SEPARATOR, substr($fqn, 17)) . '.php';

			$path = str_replace(
				'/../../src/Tests/',
				'/../../tests/',
				$path
			);

			if (!file_exists($path))
			{
				die("Cannot find $fqn in $path\n");
			}

			$file = file_get_contents($path);

			list(, $traitTable) = fqn($file);
			$table += $traitTable;

			preg_match('#\\n{\\n(.*)\\n}$#s', $file, $m);

			return $m[1] . "\n\n";
		},
		$file
	);

	if ($table)
	{
		$table = array_unique($table);
		sort($table);

		$file = preg_replace('#^use.*?;\\n\\n#ms', 'use ' . implode(";\nuse ", $table) . ";\n\n", $file);
	}
}

function convertDir($dir)
{
	foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub)
	{
		convertDir($sub);
	}

	foreach (glob($dir . '/*.php') as $filepath)
	{
		convertFile($filepath);
	}
}

function convertArraySyntax(&$file)
{
	$tokens = token_get_all($file);

	$i       = 0;
	$cnt     = count($tokens);
	$level   = 0;
	$replace = array();

	while (++$i < $cnt)
	{
		$token = $tokens[$i];

		if ($token === '[')
		{
			$j = $i;
			while ($tokens[--$j] === T_WHITESPACE);

			if ($tokens[$j] === ']')
			{
				++$level;
			}
			elseif (is_array($tokens[$j])
			    && ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_VARIABLE))
			{
				++$level;
			}
			else
			{
				$tokens[$i] = 'array(';
				$replace[]  = $level;
			}
		}
		elseif ($token === ']')
		{
			if ($level === end($replace))
			{
				$tokens[$i] = ')';
				array_pop($replace);
			}
			else
			{
				--$level;
			}
		}
	}

	$file = '';
	foreach ($tokens as $token)
	{
		$file .= (is_string($token)) ? $token : $token[1];
	}
}

function convertClosureBinding($filepath, &$file)
{
	if (!strpos($file, 'function ('))
	{
		return;
	}

	$tokens  = token_get_all($file);
	$rebuild = false;

	$i   = 0;
	$cnt = count($tokens);

	while (++$i < $cnt)
	{
		if ($tokens[$i    ][0] !== T_FUNCTION
		 || $tokens[$i + 1][0] !== T_WHITESPACE
		 || $tokens[$i + 2]    !== '(')
		{
			continue;
		}

		$rebind     = false;
		$savedIndex = $i;
		$hasUse     = false;

		$braces = 0;
		while (++$i < $cnt)
		{
			if ($tokens[$i] === '{')
			{
				++$braces;

				if ($braces === 1)
				{
					$braceIndex = $i;
				}
			}
			elseif ($tokens[$i] === '}')
			{
				--$braces;

				if (!$braces)
				{
					break;
				}
			}
			elseif ($tokens[$i][0] === T_USE)
			{
				$hasUse = true;
			}
			elseif ($tokens[$i][0] === T_VARIABLE && $tokens[$i][1] === '$this')
			{
				$tokens[$i][1] = '$_this';
				$rebind = true;
			}
		}

		if ($rebind)
		{
			$rebuild = true;

			$j = $savedIndex;
			while (--$j)
			{
				if ($tokens[$j][0] === '{'
				 && $tokens[$j - 1][0] === T_WHITESPACE
				 && $tokens[$j - 1][1] === "\n\t")
				{
					break;
				}
			}

			if ($tokens[$j] === '{')
			{
				$tokens[$j] = "{\n\t\t\$_this = \$this;\n";
			}

			if ($hasUse)
			{
				if ($tokens[$braceIndex - 2] !== ')')
				{
					echo "Could not find use statement in $filepath around line " . $tokens[$braceIndex - 2][2] . "\n";

					return;
				}

				$tokens[$braceIndex - 2] = ', $_this)';
			}
			else
			{
				if ($tokens[$braceIndex - 2] !== ')')
				{
					echo "Could not find right parenthesis in $filepath around line " . $tokens[$braceIndex - 2][2] . "\n";

					return;
				}

				$tokens[$braceIndex - 2] = ') use ($_this)';
			}
		}
	}

	if ($rebuild)
	{
		$file = '';
		foreach ($tokens as $token)
		{
			$file .= (is_array($token)) ? $token[1] : $token;
		}
	}
}

convertDir(realpath(__DIR__ . '/../../src'));
convertDir(realpath(__DIR__ . '/../../tests'));
echo "\n";

// Remove traits files
array_map('unlink', glob(__DIR__ . '/../../src/Configurator/Traits/*'));
rmdir(__DIR__ . '/../../src/Configurator/Traits');
