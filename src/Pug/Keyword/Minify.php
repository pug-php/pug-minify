<?php

namespace Pug\Keyword;

use InvalidArgumentException;
use NodejsPhpFallback\CoffeeScript;
use NodejsPhpFallback\Less;
use NodejsPhpFallback\React;
use NodejsPhpFallback\Stylus;
use NodejsPhpFallback\Uglify;
use Pug\Keyword\Minify\BlockExtractor;
use Pug\Pug;

class Minify
{
    /**
     * @var bool
     */
    protected $dev;

    /**
     * @var array
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
     * @var array
     */
    protected $events;

    /**
     * @var \Pug\Pug|\Jade\Jade
     */
    protected $pug;

    public function __construct($pug)
    {
        if (!($pug instanceof \Jade\Jade) && !($pug instanceof \Pug\Pug) && !($pug instanceof \Phug\Renderer)) {
            throw new InvalidArgumentException(
                'Allowed pug engine are Jade\\Jade, Pug\\Pug or Phug\\Renderer, ' . get_class($pug) . ' given.'
            );
        }

        $this->pug = $pug;
    }

    protected function path()
    {
        $parts = array_filter(func_get_args());
        if (isset($parts[0]) && is_array($parts[0])) {
            $bases = $parts[0];
            $copy = $parts;
            $parts[0] = $bases[0];
            foreach ($bases as $base) {
                $copy[0] = $base;
                if (file_exists(implode(DIRECTORY_SEPARATOR, $copy))) {
                    $parts[0] = $base;
                    break;
                }
            }
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    protected function prepareDirectory($path)
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $path;
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
            'singleQuote'    => $this->getOption('singleQuote'),
            'prettyprint'    => $this->getOption('prettyprint'),
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
        file_put_contents($path, '');
        $parser->write($path);
    }

    protected function getPathInfo($path, $newExtension)
    {
        $source = $this->path($this->assetDirectory, $path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $path = substr($path, 0, -strlen($extension)) . $newExtension;
        $destination = $this->prepareDirectory($this->path($this->outputDirectory, $path));

        return array($extension, $path, $source, $destination);
    }

    protected function needUpdate($source, $destination)
    {
        return !$this->dev || !file_exists($destination) || filemtime($source) >= filemtime($destination);
    }

    protected function parseScript($path)
    {
        list($extension, $path, $source, $destination) = $this->getPathInfo($path, 'js');
        $params = (object) array(
            'extension'   => $extension,
            'type'        => 'script',
            'path'        => $path,
            'source'      => $source,
            'destination' => $destination,
        );
        if ($this->needUpdate($params->source, $params->destination)) {
            switch ($params->extension) {
                case 'jsxp':
                    $params->contents = preg_replace_callback('/(?<!\s)(\s*)::`(([^`]+|(?<!`)`(?!`))*?)`(?!`)/', array($this, 'parsePugInJsx'), file_get_contents($params->source));
                    break;
                case 'cofp':
                    $params->contents = preg_replace_callback('/(?<!\s)(\s*)::"""([\s\S]*?)"""/', array($this, 'parsePugInCoffee'), file_get_contents($params->source));
                    break;
            }
            $this->trigger('pre-parse', $params);
            switch ($params->extension) {
                case 'jsxp':
                    $this->writeWith(new React($params->contents), $destination);
                    break;
                case 'jsx':
                    $this->writeWith(new React($params->source), $params->destination);
                    break;
                case 'cofp':
                    $this->writeWith(new Coffeescript($params->contents), $params->destination);
                    break;
                case 'coffee':
                    $this->writeWith(new Coffeescript($params->source), $params->destination);
                    break;
                default:
                    copy($params->source, $params->destination);
            }
            $this->trigger('post-parse', $params);
        }
        if ($this->dev) {
            return $params->path . '?' . time();
        }
        $this->js[] = $destination;
    }

    protected function parseStyle($path)
    {
        list($extension, $path, $source, $destination) = $this->getPathInfo($path, 'css');
        $params = (object) array(
            'extension'   => $extension,
            'type'        => 'style',
            'path'        => $path,
            'source'      => $source,
            'destination' => $destination,
        );
        if ($this->needUpdate($params->source, $params->destination)) {
            $this->trigger('pre-parse', $params);
            switch ($params->extension) {
                case 'styl':
                    $this->writeWith(new Stylus($params->source), $params->destination);
                    break;
                case 'less':
                    $this->writeWith(new Less($params->source), $params->destination);
                    break;
                default:
                    copy($params->source, $params->destination);
            }
            $this->trigger('post-parse', $params);
        }
        if ($this->dev) {
            return $params->path . '?' . time();
        }
        $this->css[] = $params->destination;
    }

    protected function uglify(&$params)
    {
        $params->concat = true;
        $params->minify = true;
        $language = $params->language;
        $path = $params->path;
        $outputFile = $language . '/' . $path . '.min.' . $language;
        $uglify = new Uglify($this->$language);
        $uglify->setModeFromPath($outputFile);
        $params->outputDirectory = $this->outputDirectory;
        $params->outputFile = $outputFile;
        $params->content = $uglify->getResult();
        $this->trigger('pre-write', $params);
        $path = $this->path($params->outputDirectory, $params->outputFile);
        $path = $this->prepareDirectory($path);
        $params->outputPath = $path;
        file_put_contents($path, $params->content);
        $this->trigger('post-write', $params);
    }

    protected function concat(&$params)
    {
        $params->concat = true;
        $params->minify = false;
        $language = $params->language;
        $path = $params->path;
        $outputFile = $language . '/' . $path . '.' . $language;
        $output = '';
        foreach ($this->$language as $path) {
            $output .= file_get_contents($path) . "\n";
        }
        $params->outputDirectory = $this->outputDirectory;
        $params->outputFile = $outputFile;
        $params->content = $output;
        $this->trigger('pre-write', $params);
        $path = $this->path($params->outputDirectory, $params->outputFile);
        $path = $this->prepareDirectory($path);
        $params->outputPath = $path;
        file_put_contents($path, $params->content);
        $this->trigger('post-write', $params);
    }

    protected function getOption($option, $defaultValue = null)
    {
        if (method_exists($this->pug, 'hasOption') && !$this->pug->hasOption($option)) {
            return $defaultValue;
        }

        try {
            return $this->pug->getOption($option);
        } catch (InvalidArgumentException $e) {
            return $defaultValue;
        }
    }

    protected function initializeRendering()
    {
        if (is_null($this->dev)) {
            $this->dev = substr($this->getOption('environment'), 0, 3) === 'dev';
        }
        $this->assetDirectory = (array) $this->assetDirectory ?: $this->getOption('assetDirectory', '');
        $this->outputDirectory = $this->outputDirectory ?: $this->getOption('outputDirectory', $this->assetDirectory[0]);

        $this->js = array();
        $this->css = array();
    }

    public function on($event, $action)
    {
        $event = strtolower(str_replace('-', '', $event));

        if (!isset($this->events[$event])) {
            $this->events[$event] = array();
        }

        $this->events[$event][] = $action;
    }

    public function trigger($event, &$params = null)
    {
        $event = strtolower(str_replace('-', '', $event));

        if (!is_object($params)) {
            $params = (object) (is_array($params) ? $params : array());
        }

        if (isset($this->events[$event])) {
            foreach ($this->events[$event] as $action) {
                if (is_callable($action)) {
                    $newParams = call_user_func($action, $params, $event, $this);
                    if (is_object($newParams)) {
                        $params = $newParams;
                    }
                }
            }
        }
    }

    public function linkExtractor($href, $rel)
    {
        if ($href && $rel === 'stylesheet') {
            $path = $this->parseStyle($href);
            if ($this->dev && $path) {
                return array(
                    'href' => $path,
                );
            }
        }
    }

    public function scriptExtractor($src)
    {
        if ($src) {
            $path = $this->parseScript($src);
            if ($this->dev && $path) {
                return array(
                    'src'  => $path,
                    'type' => 'text/javascript',
                );
            }
        }
    }

    protected function renderHtml($renderParams)
    {
        $html = '';

        $compilation = $renderParams->minify ? 'uglify' : 'concat';
        $event = $renderParams->minify ? 'minify' : 'concat';

        if (count($this->js)) {
            $html .= '<script src="' .
                $this->getOutputFile('js', $compilation, $event, $renderParams) .
                '"></script>';
        }

        if (count($this->css)) {
            $html .= '<link rel="stylesheet" href="' .
                $this->getOutputFile('css', $compilation, $event, $renderParams) .
                '">';
        }

        return $html;
    }

    private function getOutputFile($language, $compilation, $event, $renderParams)
    {
        $params = (object) array(
            'language' => $language,
            'path'     => $renderParams->arguments,
        );
        $this->trigger('pre-' . $event, $params);
        $this->$compilation($params);
        $this->trigger('post-' . $event, $params);

        return $params->outputFile;
    }

    public function __invoke($arguments, $block, $keyword)
    {
        $this->initializeRendering();

        if (!is_object($block) || !isset($block->nodes)) {
            return '';
        }

        $renderParams = (object) array(
            'minify'    => !in_array(strtolower(str_replace('-', '', $keyword)), array('concat', 'concatto')),
            'keyword'   => $keyword,
            'arguments' => $arguments,
            'block'     => $block,
        );
        $this->trigger('pre-render', $renderParams);

        $extractor = new BlockExtractor($renderParams->block);
        $extractor->registerTagExtractor('link', array($this, 'linkExtractor'), 'href', 'rel');
        $extractor->registerTagExtractor('script', array($this, 'scriptExtractor'), 'src');
        $extractor->extract();

        $renderParams->html = $this->renderHtml($renderParams);
        $this->trigger('post-render', $renderParams);

        return $renderParams->html;
    }
}
