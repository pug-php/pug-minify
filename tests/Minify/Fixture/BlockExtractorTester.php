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
}
