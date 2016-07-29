<?php

namespace Pug\Keyword;

use Jade\Jade;
use Jade\Nodes\Tag;
use NodejsPhpFallback\CoffeeScript;
use NodejsPhpFallback\Less;
use NodejsPhpFallback\React;
use NodejsPhpFallback\Stylus;
use NodejsPhpFallback\Uglify;
use Pug\Pug;

class Minify
{
    /**
     * @var bool
     */
    protected $dev;

    /**
     * @var string
     */
    protected $assetDirectory;

    /**
     * @var string
     */
    protected $outputDirectory;

    /**
     * @var array
     */
    protected $js;

    /**
     * @var array
     */
    protected $css;

    /**
     * @var Pug|Jade
     */
    protected $pug;

    public function __construct(Jade $pug)
    {
        $this->pug = $pug;
    }

    protected function path()
    {
        return implode(DIRECTORY_SEPARATOR, array_filter(func_get_args()));
    }

    protected function prepareDirectory($path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $path;
    }

    protected function isRootPath($path)
    {
        return false === strpos(trim($path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }

    protected function parsePugInJs($code, $indent = '', $classAttribute = null)
    {
        $code = str_replace("\r", '', $code);

        if (preg_match('/^[ \t]*\n/', $code)) {
            $code = explode("\n", $code, 2);
            $code = $code[1];
            if (preg_match('/^[ \t]+/', $code, $match)) {
                $indent = $match[0];
            }
        }

        $code = preg_replace('/(^' . preg_quote($indent) . '|(?<=\n)' . preg_quote($indent) . ')/', '', $code);

        $pug = new Pug(array(
            'classAttribute' => $classAttribute,
            'singleQuote'    => false,
            'prettyprint'    => $this->dev,
        ));

        return $pug->render($code);
    }

    protected function parsePugInJsx($parameters)
    {
        return $parameters[1] . $this->parsePugInJs(str_replace('``', '`', $parameters[2]), $parameters[1], 'className');
    }

    protected function parsePugInCoffee($parameters)
    {
        return $parameters[1] . '"""' . $this->parsePugInJs($parameters[2], $parameters[1]) . '"""';
    }

    protected function writeWith($parser, $path)
    {
        $path = $this->prepareDirectory($this->path($this->outputDirectory, $path));
        file_put_contents($path, '');
        $parser->write($path);
    }

    protected function getExtensionPathAndSource($path, $newExtension)
    {
        $source = $this->path($this->assetDirectory, $path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $path = substr($path, 0, -strlen($extension)) . $newExtension;

        return array($extension, $path, $source);
    }

    protected function parseScript($path)
    {
        list($extension, $path, $source) = $this->getExtensionPathAndSource($path, 'js');
        switch ($extension) {
            case 'jsxp':
                $contents = preg_replace_callback('/(?<!\s)(\s*)::`(([^`]+|(?<!`)`(?!`))*?)`(?!`)/', array($this, 'parsePugInJsx'), file_get_contents($source));
                $this->writeWith(new React($contents), $path);
                break;
            case 'jsx':
                $this->writeWith(new React($source), $path);
                break;
            case 'cofp':
                $contents = preg_replace_callback('/(?<!\s)(\s*)::"""([\s\S]*?)"""/', array($this, 'parsePugInCoffee'), file_get_contents($source));
                $this->writeWith(new Coffeescript($contents), $path);
                break;
            case 'coffee':
                $this->writeWith(new Coffeescript($source), $path);
                break;
            default:
                copy($source, $this->prepareDirectory($this->path($this->outputDirectory, $path)));
        }
        if ($this->dev) {
            return $path . '?' . time();
        }
        $this->js[] = $path;
    }

    protected function parseStyle($path)
    {
        list($extension, $path, $source) = $this->getExtensionPathAndSource($path, 'css');
        switch ($extension) {
            case 'styl':
                $this->writeWith(new Stylus($source), $path);
                break;
            case 'less':
                $this->writeWith(new Less($source), $path);
                break;
            default:
                copy($source, $this->prepareDirectory($this->path($this->outputDirectory, $path)));
        }
        if ($this->dev) {
            return $path . '?' . time();
        }
        $this->css[] = $path;
    }

    protected function getPaths($language)
    {
        $paths = array();
        foreach ($this->$language as $path) {
            $paths[] = $this->path($this->assetDirectory, $path);
        }

        return $paths;
    }

    protected function uglify($language, $path)
    {
        $outputFile = $language . '/' . $path . '.min.' . $language;
        $uglify = new Uglify($this->getPaths($language));
        $path = $this->path($this->outputDirectory, $outputFile);
        $uglify->write($this->prepareDirectory($path));

        return $outputFile;
    }

    protected function concat($language, $path)
    {
        $outputFile = $language . '/' . $path . '.' . $language;
        $output = '';
        foreach ($this->getPaths($language) as $path) {
            $output .= file_get_contents($path) . "\n";
        }
        $path = $this->path($this->outputDirectory, $outputFile);
        file_put_contents($this->prepareDirectory($path), $output);

        return $outputFile;
    }

    protected function getOption($option, $defaultValue = null)
    {
        try {
            return $this->pug->getOption($option);
        } catch (\InvalidArgumentException $e) {
            return $defaultValue;
        }
    }

    protected function getNodeValue($node, $key)
    {
        $attribute = $node->getAttribute($key);

        return is_array($attribute)
            ? stripslashes(substr($attribute['value'], 1, -1))
            : null;
    }

    protected function setNodeValue(Tag $node, $key, $value)
    {
        foreach ($node->attributes as &$attribute) {
            if ($attribute['name'] === $key) {
                $attribute['value'] = var_export($value, true);
            }
        }
    }

    protected function scrollBlock($block) {
        if (isset($block->nodes) && is_array($block->nodes)) {
            foreach ($block->nodes as $key => $node) {
                if (isset($node->block) && is_object($node->block)) {
                    $this->scrollBlock($node->block);
                }
                if (!isset($node->name) || !($node instanceof Tag)) {
                    continue;
                }
                $path = true;
                if ($node->name === 'link' && ($path = $this->getNodeValue($node, 'href')) && $this->getNodeValue($node, 'rel') === 'stylesheet') {
                    $path = $this->parseStyle($path);
                    if ($path) {
                        $this->setNodeValue($node, 'href', $path);
                    }
                }
                if ($node->name === 'script' && ($path = $this->getNodeValue($node, 'src'))) {
                    $path = $this->parseScript($path);
                    if ($path) {
                        $this->setNodeValue($node, 'src', $path);
                        $this->setNodeValue($node, 'type', 'text/javascript');
                    }
                }
                if (!$path) {
                    unset($block->nodes[$key]);
                }
            }
        }
    }

    public function __invoke($arguments, $block, $keyword)
    {
        if (is_null($this->dev)) {
            $this->dev = substr($this->getOption('environment'), 0, 3) === 'dev';
        }
        $this->assetDirectory = $this->assetDirectory ?: $this->getOption('assetDirectory', '');
        $this->outputDirectory = $this->outputDirectory ?: $this->getOption('outputDirectory', $this->assetDirectory);

        $this->js = array();
        $this->css = array();

        $this->scrollBlock($block);

        $html = '';

        if (count($this->js) || count($this->css)) {
            $compilation = in_array(strtolower(str_replace('-', '', $keyword)) , array('concat', 'concatto'))
                ? 'concat'
                : 'uglify';

            if (count($this->js)) {
                $html .= '<script src="' . $this->$compilation('js', $arguments) . '"></script>';
            }

            if (count($this->css)) {
                $html .= '<link rel="stylesheet" href="' . $this->$compilation('css', $arguments) . '">';
            }
        }

        return $html;
    }
}
