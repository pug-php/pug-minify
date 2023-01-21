<?php

namespace Pug\Keyword\Test\Minify;

use PHPUnit\Framework\TestCase;
use Pug\Keyword\Test\Minify\Fixture\BlockExtractorTester;
use Pug\Keyword\Test\Minify\Fixture\FakeNode;

class BlockExtractorTest extends TestCase
{
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

        self::assertSame(array(), $extractor->getBlockNodes());

        $fakeBlock = (object) array(
            'nodes' => array(
                'foo' => (object) array(
                    'nodes' => array((object) array()),
                ),
            ),
        );
        $extractor = new BlockExtractorTester($fakeBlock);

        self::assertSame(array(), $extractor->getBlockNodes());

        $fakeBlock = (object) array(
            'nodes' => array(
                'foo' => (object) array(
                    'nodes' => array(),
                ),
            ),
        );
        $extractor = new BlockExtractorTester($fakeBlock);

        self::assertSame(array(), $extractor->getBlockNodes());
    }

    public function testGetNodeValue()
    {
        require_once __DIR__ . '/Fixture/BlockExtractorTester.php';

        $extractor = new BlockExtractorTester((object) array());

        self::assertSame('fake=42', $extractor->getNodeAttributeValue());
    }

    public function testSetNodeValue()
    {
        require_once __DIR__ . '/Fixture/FakeNode.php';
        require_once __DIR__ . '/Fixture/BlockExtractorTester.php';

        $fakeNode = new FakeNode();
        $extractor = new BlockExtractorTester((object) array());
        $extractor->changeValue($fakeNode);

        self::assertSame("'after'", $fakeNode->getBarValue());
    }

    public function testPathForWrongNode()
    {
        require_once __DIR__ . '/Fixture/FakeNode.php';
        require_once __DIR__ . '/Fixture/BlockExtractorTester.php';

        $extractor = new BlockExtractorTester((object) array());

        self::assertNull($extractor->getPathForWrongNode());
    }
}
