<?php

namespace Pug\Keyword\Minify;

use Jade\Nodes\Node;
use Jade\Nodes\Tag;

class BlockExtractor
{
    /**
     * @var Node
     */
    protected $block;

    /**
     * @var array
     */
    protected $extractors;

    public function __construct(Node $block)
    {
        $this->block = $block;
        $this->extractors = array();
    }

    public function registerTagExtractor($tagName, $extractor)
    {
        $this->extractors[$tagName] = array($extractor, array_slice(func_get_args(), 2));
    }

    public function extract()
    {
        $this->scrollBlock($this->block);
    }

    protected function getNodeValue($node, $key)
    {
        $attribute = $node->getAttribute($key);

        return is_array($attribute)
            ? stripslashes(substr($attribute['value'], 1, -1))
            : null;
    }

    protected function setNodeValue(Tag $node, $key, $value)
    {
        foreach ($node->attributes as &$attribute) {
            if ($attribute['name'] === $key) {
                $attribute['value'] = var_export($value, true);
            }
        }
    }

    protected function processNode(Tag $node)
    {
        if (!isset($this->extractors[$node->name])) {
            return false;
        }

        list($extractor, $attributes) = $this->extractors[$node->name];
        $arguments = array($node);
        foreach ($attributes as $attribute) {
            $arguments[] = $this->getNodeValue($node, $attribute);
        }
        $newAttributes = call_user_func_array($extractor, $arguments);
        if (!$newAttributes) {
            return true;
        }
        foreach ($newAttributes as $name => $value) {
            $this->setNodeValue($node, $name, $value);
        }

        return false;
    }

    protected function scrollBlock($block)
    {
        if (isset($block->nodes) && is_array($block->nodes)) {
            foreach ($block->nodes as $key => $node) {
                if (isset($node->block) && is_object($node->block)) {
                    $this->scrollBlock($node->block);
                }
                if (!isset($node->name) || !($node instanceof Tag)) {
                    continue;
                }
                if ($this->processNode($node)) {
                    unset($block->nodes[$key]);
                }
            }
        }
    }
}
