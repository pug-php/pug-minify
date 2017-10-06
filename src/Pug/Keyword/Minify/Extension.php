<?php

namespace Pug\Keyword\Minify;

use Phug\AbstractExtension;
use Phug\Compiler\Event\NodeEvent;
use Pug\Keyword\Minify;

class Extension extends AbstractExtension
{
    /**
     * @var Minify
     */
    private $keyword;

    public function __construct(Minify $keyword)
    {
        $this->keyword = $keyword;
    }

    public function getEvents()
    {
        return [
            'on_node' => [$this, 'onNode'],
        ];
    }

    public function onNode(NodeEvent $event)
    {
        $node = $event->getNode();
        var_dump($node);
        exit;
    }
}
