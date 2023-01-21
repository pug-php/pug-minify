<?php

namespace Pug\Keyword;

use InvalidArgumentException;
use NodejsPhpFallback\Uglify;
use Pug\Keyword\Minify\AssetParser;
use Pug\Keyword\Minify\BlockExtractor;
use Pug\Keyword\Minify\InJsPugParser;
use Pug\Keyword\Minify\Path;
use Pug\Keyword\Minify\ScriptParser;
use Pug\Keyword\Minify\StyleParser;

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

    protected function prepareDirectory($path)
    {
        $path = (string) $path;
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        return $path;
    }

    protected function parsePugInJs($code, $indent = '', $classAttribute = null)
    {
        $parser = new InJsPugParser(array(
            'classAttribute' => $classAttribute,
            'singleQuote'    => $this->getOption('singleQuote'),
            'prettyprint'    => $this->getOption('prettyprint'),
        ));

        return $parser->parse($code, $indent);
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

    /**
     * @param string      $path
     * @param string      $newExtension
     * @param string|null $relativePath
     *
     * @return string[]
     */
    protected function getPathInfo($path, $newExtension, $relativePath = null)
    {
        $source = $this->getSourcePath($path, $relativePath);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $path = substr($path, 0, -strlen($extension)) . $newExtension;
        $destination = $this->prepareDirectory(new Path($this->outputDirectory, $path));

        return array($extension, $path, (string) $source, $destination);
    }

    /**
     * @param string      $path
     * @param string|null $relativePath
     *
     * @return Path
     */
    protected function getSourcePath($path, $relativePath = null)
    {
        if ($relativePath) {
            $relativeSource = new Path($path);
            $relativeSource = $relativeSource->relativeTo($relativePath);

            if (file_exists((string) $relativeSource)) {
                return $relativeSource;
            }
        }

        return new Path($this->assetDirectory, $path);
    }

    protected function needUpdate($source, $destination)
    {
        return !$this->dev || !file_exists($destination) || filemtime($source) >= filemtime($destination);
    }

    protected function prepareSource($params)
    {
        switch ($params->extension) {
            case 'jsxp':
                $params->contents = preg_replace_callback(
                    '/(?<!\s)(\s*)::`(([^`]+|(?<!`)`(?!`))*?)`(?!`)/',
                    array($this, 'parsePugInJsx'),
                    file_get_contents($params->source)
                );
                break;

            case 'cofp':
                $params->contents = preg_replace_callback(
                    '/(?<!\s)(\s*)::"""([\s\S]*?)"""/',
                    array($this, 'parsePugInCoffee'),
                    file_get_contents($params->source)
                );
                break;
        }
    }

    protected function parseScript($path, $relativePath = null)
    {
        list($extension, $path, $source, $destination) = $this->getPathInfo($path, 'js', $relativePath);
        $params = (object) array(
            'extension'   => $extension,
            'type'        => 'script',
            'path'        => $path,
            'source'      => $source,
            'destination' => $destination,
        );

        if ($this->needUpdate($params->source, $params->destination)) {
            $this->prepareSource($params);
            $this->trigger('pre-parse', $params);
            $parser = new ScriptParser($params, $destination);
            $this->handleParsing($params, $parser);
            $this->trigger('post-parse', $params);
        }

        if ($this->dev) {
            return $params->path . '?' . time();
        }

        $this->js[] = $destination;

        return null;
    }

    protected function parseStyle($path, $relativePath = null)
    {
        list($extension, $path, $source, $destination) = $this->getPathInfo($path, 'css', $relativePath);
        $params = (object) array(
            'extension'   => $extension,
            'type'        => 'style',
            'path'        => $path,
            'source'      => $source,
            'destination' => $destination,
        );

        if ($this->needUpdate($params->source, $params->destination)) {
            $this->trigger('pre-parse', $params);
            $this->handleParsing($params, new StyleParser($params));
            $this->trigger('post-parse', $params);
        }

        if ($this->dev) {
            return $params->path . '?' . time();
        }

        $this->css[] = $params->destination;

        return null;
    }

    protected function handleParsing($params, AssetParser $parser)
    {
        $result = $parser->parse();

        if (is_string($result)) {
            copy($result, $params->destination);

            return;
        }

        if (!is_array($result)) {
            $result = array($result, $params->destination);
        }

        $this->writeWith($result[0], $result[1]);
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
        $path = new Path($params->outputDirectory, $params->outputFile);
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
        $path = new Path($params->outputDirectory, $params->outputFile);
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
        $this->assetDirectory = (array) ($this->assetDirectory ?: $this->getOption('assetDirectory', ''));
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
            $this->triggerEventActions($event, $params);
        }
    }

    private function triggerEventActions($event, &$params = null)
    {
        foreach ($this->events[$event] as $action) {
            if (is_callable($action)) {
                $this->triggerEventAction($action, $event, $params);
            }
        }
    }

    private function triggerEventAction($action, $event, &$params = null)
    {
        $newParams = call_user_func($action, $params, $event, $this);

        if (is_object($newParams)) {
            $params = $newParams;
        }
    }

    public function linkExtractor($href, $rel, $relativePath = null)
    {
        if ($href && $rel === 'stylesheet') {
            $path = $this->parseStyle($href, $relativePath);
            if ($this->dev && $path) {
                return array(
                    'href' => $path,
                );
            }
        }
    }

    public function scriptExtractor($src, $relativePath = null)
    {
        if ($src) {
            $path = $this->parseScript($src, $relativePath);
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
