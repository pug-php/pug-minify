<?php

namespace Pug\Keyword\Test\Minify\Fixture;

use Pug\Keyword\Minify\BlockExtractor;

class BlockExtractorTester extends BlockExtractor
{
    public function getBlockNodes()
    {
        $this->scrollBlock($this->block);

        return $this->block->nodes;
    }

    public function getNodeAttributeValue()
    {
        require_once __DIR__ . '/FakeNode.php';

        return $this->getNodeValue(new FakeNode(), 'fake');
    }

    public function changeValue($node)
    {
        $this->setNodeValue($node, 'bar', 'after');
    }

    public function getPathForWrongNode()
    {
        return $this->getNodePath(new \stdClass());
    }
}
