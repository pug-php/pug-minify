<?php

namespace Pug\Keyword\Test\Minify;

use PHPUnit\Framework\TestCase;
use Pug\Keyword\Test\Minify\Fixture\BlockExtractorTester;

class BlockExtractorTest extends TestCase
{
    /**
     * @group t
     */
    public function testScrollBlock()
    {
        require_once __DIR__ . '/Fixture/BlockExtractorTester.php';

        $fakeBlock = (object) array(
            'nodes' => array(
                'foo' => (object) array(
                    'block' => (object) array(),
                ),
            ),
        );
        $extractor = new BlockExtractorTester($fakeBlock);

        $this->assertSame(array(), $extractor->getBlockNodes());

        $fakeBlock = (object) array(
            'nodes' => array(
                'foo' => (object) array(
                    'nodes' => array((object) array()),
                ),
            ),
        );
        $extractor = new BlockExtractorTester($fakeBlock);

        $this->assertSame(array(), $extractor->getBlockNodes());

        $fakeBlock = (object) array(
            'nodes' => array(
                'foo' => (object) array(
                    'nodes' => array(),
                ),
            ),
        );
        $extractor = new BlockExtractorTester($fakeBlock);

        $this->assertSame(array(), $extractor->getBlockNodes());
    }
}
