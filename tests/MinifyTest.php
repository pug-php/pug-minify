<?php

use Pug\Keyword\Minify;
use Pug\Pug;

class MinifyTest extends PHPUnit_Framework_TestCase
{
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

        return $javascript;
    }

    public function testDevelopment()
    {
        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => sys_get_temp_dir(),
            'environment'     => 'development',
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/dev.html'));

        $this->assertSame($expected, $html);

        $file = sys_get_temp_dir() . '/coffee/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/coffee/test.js'), $javascript);

        $file = sys_get_temp_dir() . '/coffee-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/coffee-pug/test.js'), $javascript);

        $file = sys_get_temp_dir() . '/react/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/react/test.js'), $javascript);

        $file = sys_get_temp_dir() . '/react-pug/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/react-pug/test.js'), $javascript);

        $file = sys_get_temp_dir() . '/js/test.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/test.js'), $javascript);

        $file = sys_get_temp_dir() . '/less/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/less/test.css'), $style);

        $file = sys_get_temp_dir() . '/stylus/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/stylus/test.css'), $style);

        $file = sys_get_temp_dir() . '/css/test.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/test.css'), $style);
    }

    public function testProductionWithMinify()
    {
        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => sys_get_temp_dir(),
            'environment'     => 'production',
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('minify', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-minify.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-minify.html'));

        $this->assertSame($expected, $html);

        $file = sys_get_temp_dir() . '/js/top.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/top.min.js'), $javascript);

        $file = sys_get_temp_dir() . '/js/bottom.min.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/bottom.min.js'), $javascript);

        $file = sys_get_temp_dir() . '/css/top.min.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/top.min.css'), $style);
    }

    public function testProductionWithConcat()
    {
        $pug = new Pug(array(
            'prettyprint'     => true,
            'assetDirectory'  => __DIR__,
            'outputDirectory' => sys_get_temp_dir(),
        ));
        $minify = new Minify($pug);
        $pug->addKeyword('concat', $minify);
        $html = static::simpleHtml($pug->render(__DIR__ . '/test-concat.pug'));
        $expected = static::simpleHtml(file_get_contents(__DIR__ . '/prod-concat.html'));

        $this->assertSame($expected, $html);

        $file = sys_get_temp_dir() . '/js/top.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/top.js'), $javascript);

        $file = sys_get_temp_dir() . '/js/bottom.js';
        $javascript = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/js/bottom.js'), $javascript);

        $file = sys_get_temp_dir() . '/css/top.css';
        $style = static::fileGetAsset($file);
        unlink($file);
        $this->assertSame(static::fileGetAsset(__DIR__ . '/css/top.css'), $style);
    }
}
