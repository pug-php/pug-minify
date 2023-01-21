<?php

namespace Pug\Keyword\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Pug\Keyword\Minify;
use Pug\Keyword\Test\Minify\Fixture\MinifyTester;
use Pug\Keyword\Test\Minify\Fixture\Pug2;
use Pug\Pug;

class MinifyTest extends TestCase
{
    protected function getTempDir()
    {
        return sys_get_temp_dir() . '/minify-test';
    }

    protected function cleanTempDir()
    {
        $this->removeDirectory($this->getTempDir());
    }

    protected function removeDirectory($directory)
    {
        if (is_file($directory)) {
            return unlink($directory);
        }
        if (!is_dir($directory)) {
            return;
        }
        foreach (scandir($directory) as $file) {
            if ($file !== '.' && $file !== '..') {
                $this->removeDirectory($directory . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    protected static function clean($text)
    {
        $text = preg_replace('/(>)(<[a-z])/', "\$1\n\$2", $text);
        $text = preg_replace('/\s{2,}<\//', "\n</", $text);
        $text = str_replace(', ', ',', $text);
        $text = str_replace('0.', '.', $text);

        return $text;
    }

    protected static function assertSimilar($expected, $actual, $message = '')
    {
        self::assertSame(
            self::clean($expected),
            self::clean($actual),
            $message
        );
    }

    protected function simpleHtml($html)
    {
        $html = trim(str_replace("\r", '', $html));
        $html = preg_replace('/\?\d+/', '', $html);
        $html = preg_replace('/\s+\n/', "\n", $html);
        $html = preg_replace('/(<\/\w+>)([ \t]*)</', "\$1\n\$2<", $html);
        $html = preg_replace('/(>)[ \t]*(<(meta|link|script))/', "\$1\n\$2", $html);
        $html = preg_replace('/^([ \t]*)/m', '', $html);

        return $html;
    }

    protected function fileGetAsset($file)
    {
        $javascript = file_get_contents($file);
        $javascript = trim(str_replace("\r", '', $javascript));
        $javascript = preg_replace('/^([ \t]*)/m', '', $javascript);
        $javascript = preg_replace('/\n{2,}/', "\n", $javascript);
        $javascript = preg_replace('/"use strict";\n?/', '', $javascript);
        $javascript = str_replace(";", ";\n", $javascript);

        return $javascript;
    }

    protected function renderFile($pug, $file)
    {
        $method = method_exists($pug, 'renderFile')
            ? array($pug, 'renderFile')
            : array($pug, 'render');

        return call_user_func($method, $file);
    }

    public function testDevelopment()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'        => true,
            'assetDirectory'     => __DIR__,
            'outputDirectory'    => $outputDirectory,
            'environment'        => 'development',
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/dev.html'));

        self::assertSimilar($expected, $html);

        $file = $outputDirectory . '/coffee/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/coffee/test.js'), $javascript);

        $file = $outputDirectory . '/coffee-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/coffee-pug/test.js'), $javascript);

        $file = $outputDirectory . '/react/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/react/test.js'), $javascript);

        $file = $outputDirectory . '/react-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/react-pug/test.js'), $javascript);

        $file = $outputDirectory . '/js/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/test.js'), $javascript);

        $file = $outputDirectory . '/less/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/less/test.css'), $style);

        $file = $outputDirectory . '/stylus/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/stylus/test.css'), $style);

        $file = $outputDirectory . '/css/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/css/test.css'), $style);

        $this->cleanTempDir();
    }

    public function testProductionWithMinify()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'        => true,
            'assetDirectory'     => __DIR__,
            'outputDirectory'    => $outputDirectory,
            'environment'        => 'production',
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-minify.html'));

        self::assertSimilar($expected, $html);

        $file = $outputDirectory . '/js/top.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/top.min.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/bottom.min.js'), $javascript);

        $file = $outputDirectory . '/css/top.min.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/css/top.min.css'), $style);

        $this->cleanTempDir();
    }

    /**
     * @testWith ["test-concat"]
     *           ["relative/test-root"]
     */
    public function testProductionWithConcat($path)
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'environment'        => 'production',
            'prettyprint'        => true,
            'assetDirectory'     => __DIR__,
            'outputDirectory'    => $outputDirectory,
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('concat', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/' . $path . '.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-concat.html'));

        self::assertSimilar($expected, $html);

        $file = $outputDirectory . '/js/top.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/top.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/bottom.js'), $javascript);

        $file = $outputDirectory . '/css/top.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/css/top.css'), $style);

        $this->cleanTempDir();
    }

    public function testMultipleAssetDirectories()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'environment'        => 'production',
            'prettyprint'        => true,
            'assetDirectory'     => array(dirname(__DIR__), __DIR__, __DIR__ . '/js'),
            'outputDirectory'    => $outputDirectory,
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('concat', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/test-concat.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-concat.html'));

        self::assertSimilar($expected, $html);

        $file = $outputDirectory . '/js/top.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/top.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/bottom.js'), $javascript);

        $file = $outputDirectory . '/css/top.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/css/top.css'), $style);

        $this->cleanTempDir();
    }

    public function testRelativePath()
    {
        if (!class_exists('Phug\\Util\\SourceLocationInterface')) {
            self::markTestSkipped('SourceLocationInterface needed to calculate relative path');
        }

        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'environment'        => 'production',
            'prettyprint'        => true,
            'assetDirectory'     => array(dirname(__DIR__), __DIR__, __DIR__ . '/js'),
            'outputDirectory'    => $outputDirectory,
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/relative/test-inside.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/relative/test-inside.html'));

        self::assertSimilar($expected, $html);

        $file = $outputDirectory . '/js/top.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(
            "console.log(\"sub/test\");console.log(\"sub/test2\");",
            str_replace(array("\n", "\r"), '', $javascript)
        );

        $file = $outputDirectory . '/css/top.min.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar('body{#foo { color:red}} body{.foo { color:lime}', trim($style));

        $this->cleanTempDir();
    }

    /**
     * @group hooks
     */
    public function testHooks()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'        => true,
            'assetDirectory'     => __DIR__,
            'outputDirectory'    => $outputDirectory,
            'environment'        => 'production',
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);
        $minify->on('pre-write', function ($params) {
            $params->content = str_replace('42', '43', $params->content);

            return $params;
        });
        $minify->on('post-parse', function ($params) {
            $contents = file_get_contents($params->destination);
            $contents = str_replace('Hello', 'Bye', $contents);
            file_put_contents($params->destination, $contents);
        });
        $minifications = 0;
        $customCalled = false;
        $minify->on('pre-minify', function ($params, $event, $ref) use (&$minifications, &$customCalled) {
            if ($ref instanceof Minify) {
                $minifications++;
            }
            if (isset($params->custom)) {
                $customCalled = true;
            }
        });
        $minify->on('post-minify', function ($params) {
            $params->outputPath .= '.copy';

            return $params;
        });
        $minify->on('pre-render', function ($params) {
            if ($params->arguments === 'top') {
                $params->arguments = 'up';

                return $params;
            }
        });
        $minify->on('post-render', function ($params) {
            $params->html .= "\n<!-- Bye -->";

            return $params;
        });
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/hooks.html'));

        self::assertSimilar($expected, $html);
        self::assertSimilar(3, $minifications, 'CSS and JS on the top + JS on the bottom should trigger the pre-minify event 3 times.');

        $file = $outputDirectory . '/js/up.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(static::fileGetAsset(__DIR__ . '/js/up.min.js.copy'), $javascript);

        $file = $outputDirectory . '/js/bottom.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(str_replace('Hello', 'Bye', static::fileGetAsset(__DIR__ . '/js/bottom.min.js')), $javascript);

        $file = $outputDirectory . '/css/up.min.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        self::assertSimilar(str_replace('Hello', 'Bye', static::fileGetAsset(__DIR__ . '/css/up.min.css')), $style);

        $this->cleanTempDir();

        $params = array(
            'custom' => 'foobar',
        );
        $this->assertFalse($customCalled, 'Hooks should be callable from outside the rendring.');
        $minify->trigger('pre-minify', $params);
        $this->assertTrue($customCalled, 'Hooks should be callable from outside the rendring.');
    }

    /**
     * @group issues
     */
    public function testIssue4()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'environment'        => 'production',
            'prettyprint'        => true,
            'assetDirectory'     => array(dirname(__DIR__), __DIR__, __DIR__ . '/js'),
            'outputDirectory'    => $outputDirectory,
            'execution_max_time' => 300000,
        ));
        $minify = new Minify($pug);

        self::assertSame('', $minify(array(), '', 'uglify'));

        $minify->on('post-minify', function ($params) {
            $params->outputFile = '/' . $params->outputFile;

            return $params;
        });
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($this->renderFile($pug, __DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/issue4.html'));

        self::assertSimilar($expected, $html);

        $this->cleanTempDir();
    }

    public function testConstructException()
    {
        $message = null;

        try {
            new Minify((object) array());
        } catch (InvalidArgumentException $exception) {
            $message = $exception->getMessage();
        }

        self::assertSame('Allowed pug engine are Jade\\Jade, Pug\\Pug or Phug\\Renderer, stdClass given.', $message);
    }

    public function testGetOption()
    {
        require_once __DIR__ . '/Minify/Fixture/MinifyTester.php';
        require_once __DIR__ . '/Minify/Fixture/Pug2.php';

        $minify = new MinifyTester(new Pug2());

        self::assertNull($minify->callGetOption('testOption'));
    }
}
