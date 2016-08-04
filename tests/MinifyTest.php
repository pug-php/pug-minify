<?php

use Pug\Keyword\Minify;
use Pug\Pug;

class MinifyTest extends PHPUnit_Framework_TestCase
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

    protected function simpleHtml($html)
    {
        $html = trim(str_replace("\r", '', $html));
        $html = preg_replace('/\?\d+/', '', $html);
        $html = preg_replace('/\s+\n/', "\n", $html);
        $html = preg_replace('/(<\/\w+>)([ \t]*)</', "\$1\n\$2<", $html);
        $html = preg_replace('/^([ \t]*)/m', '', $html);

        return $html;
    }

    protected function fileGetAsset($file)
    {
        $javascript = file_get_contents($file);
        $javascript = trim(str_replace("\r", '', $javascript));
        $javascript = preg_replace('/^([ \t]*)/m', '', $javascript);
        $javascript = preg_replace('/\n{2,}/', "\n", $javascript);

        return $javascript;
    }

    public function testDevelopment()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => $outputDirectory,
            'environment'     => 'development',
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/dev.html'));

        $this->assertSame($expected, $html);

        $file = $outputDirectory . '/coffee/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/coffee/test.js'), $javascript);

        $file = $outputDirectory . '/coffee-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/coffee-pug/test.js'), $javascript);

        $file = $outputDirectory . '/react/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/react/test.js'), $javascript);

        $file = $outputDirectory . '/react-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/react-pug/test.js'), $javascript);

        $file = $outputDirectory . '/js/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/test.js'), $javascript);

        $file = $outputDirectory . '/less/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/less/test.css'), $style);

        $file = $outputDirectory . '/stylus/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/stylus/test.css'), $style);

        $file = $outputDirectory . '/css/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/test.css'), $style);

        $this->cleanTempDir();
    }

    public function testProductionWithMinify()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => $outputDirectory,
            'environment'     => 'production',
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-minify.html'));

        $this->assertSame($expected, $html);

        $file = $outputDirectory . '/js/top.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/top.min.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/bottom.min.js'), $javascript);

        $file = $outputDirectory . '/css/top.min.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/top.min.css'), $style);

        $this->cleanTempDir();
    }

    public function testProductionWithConcat()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => $outputDirectory,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('concat', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-concat.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-concat.html'));

        $this->assertSame($expected, $html);

        $file = $outputDirectory . '/js/top.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/top.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/bottom.js'), $javascript);

        $file = $outputDirectory . '/css/top.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/top.css'), $style);

        $this->cleanTempDir();
    }

    public function testMultipleAssetDirectories()
    {
        $this->cleanTempDir();
        $outputDirectory = $this->getTempDir();

        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => array(dirname(__DIR__), __DIR__, __DIR__ . '/js'),
            'outputDirectory' => $outputDirectory,
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('concat', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-concat.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-concat.html'));

        $this->assertSame($expected, $html);

        $file = $outputDirectory . '/js/top.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/top.js'), $javascript);

        $file = $outputDirectory . '/js/bottom.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/bottom.js'), $javascript);

        $file = $outputDirectory . '/css/top.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/top.css'), $style);

        $this->cleanTempDir();
    }
}
