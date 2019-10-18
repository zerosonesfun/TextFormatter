#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$configurator = s9e\TextFormatter\Configurator\Bundles\Fatdown::getConfigurator();
$configurator->enableJavaScript();

$filepath = __DIR__ . '/../vendor/node_modules/google-closure-compiler-linux/compiler';
$minifier = $configurator->javascript->setMinifier('ClosureCompilerApplication', $filepath);
$minifier->cacheDir = __DIR__ . '/../tests/.cache';
$minifier->options .= ' --jscomp_error "*"';

$configurator->javascript->exports = ['disablePlugin', 'enablePlugin', 'preview'];

extract($configurator->finalize());

ob_start();
?><!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>s9e\TextFormatter &bull; Fatdown/JS Demo</title>
	<base href="https://s9e.github.io/TextFormatter/fatdown.html" />
	<style type="text/css">
		#preview
		{
			font-family: sans;
			padding: 5px;
			background-color: #f8f8f8;
			border: dashed 1px #ddd;
			border-radius: 5px;
		}
		pre, code
		{
			padding: 2px;
			background-color: #fff;
			border-radius: 3px;
			border: solid 1px #ddd;
		}
		pre code
		{
			border: 0;
		}
		blockquote
		{
			background-color: #fff;
			border: solid 1px #ddd;
			border-left-width: 4px;
			padding-left: 1em;
		}
		p
		{
			margin: 0;
		}
		td, th
		{
			border: solid 1px #ddd;
			background-color: #fff;
		}
	</style>

</head>
<body>
	<div style="float:left;width:80%;max-width:800px">
		<form>
			<textarea style="width:99%" rows="15">The Fatdown bundle includes the following plugins:

 - **Autoemail** --- email addresses such as example@example.org are automatically turned into links
 - **Autolink** --- URLs such as https://github.com are automatically turned into links
 - **Escaper** --- special characters can be escaped with a backslash like \*this\*
 - **FancyPants** --- some typography is enhanced, e.g. (c) (tm) and "quotes"
 - **HTMLComments** --- you can use HTML comments <!-- like this one -->
 - **HTMLElements** --- several HTML elements such as <sub>sub</sub> and <sup>sup</sup> are allowed
 - **HTMLEntities** --- HTML entities such as &amp;hearts; are decoded
 - **Litedown** --- a Markdown*-like* syntax
 - **MediaEmbed** --- URLs from media sites are automatically embedded:  
   https://youtu.be/QH2-TGUlwu4
 - **PipeTables** --- ASCII-style tables are supported. See [its syntax](https://s9etextformatter.readthedocs.io/Plugins/PipeTables/Syntax/)

***

The parser/renderer used on this page has been generated by [this script](https://github.com/s9e/TextFormatter/blob/master/scripts/generateFatdownDemo.php). It's been minified with Google Closure Compiler to <?php printf('%.1f', strlen($js) / 1024); ?> KB (<?php printf('%.1f', strlen(gzcompress($js, 9)) / 1024); ?> KB compressed)</textarea>
		</form>
	</div>

	<div style="float:left;">
		<form><?php

			$list = [];

			foreach ($configurator->plugins as $pluginName => $plugin)
			{
				$list[$pluginName] = '<input type="checkbox" id="' . $pluginName . '" checked="checked" onchange="toggle(this)"><label for="' . $pluginName . '">&nbsp;'. $pluginName . '</label>';
			}

			ksort($list);
			echo implode('<br>', $list);

		?></form>
	</div>

	<div style="clear:both"></div>

	<div id="preview"></div>

	<script><?php echo $js; ?>

		var text,
			textareaEl = document.getElementsByTagName('textarea')[0],
			previewEl = document.getElementById('preview');

		setInterval(function()
		{
			if (textareaEl.value === text)
			{
				return;
			}

			text = textareaEl.value;
			s9e.TextFormatter.preview(text, previewEl);
		}, 20);

		function toggle(el)
		{
			(el.checked) ? s9e.TextFormatter.enablePlugin(el.id)
			             : s9e.TextFormatter.disablePlugin(el.id);

			text = '';
		}
	</script>
	<script async src="https://cdnjs.cloudflare.com/ajax/libs/punycode/1.4.1/punycode.min.js"></script>
</body>
</html><?php

file_put_contents(__DIR__ . '/../../s9e.github.io/TextFormatter/fatdown.html', ob_get_clean());

echo "Done.\n";