<?php

namespace Pug\Keyword\Minify;

use Jade\Nodes\Node;
use Jade\Nodes\Tag;
use Phug\Formatter\Element\MarkupElement;

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

    public function __construct($block)
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
        while (is_object($attribute) && method_exists($attribute, 'getValue')) {
            $attribute = $attribute->getValue();
        }
        if (is_array($attribute)) {
            $attribute = strval($attribute['value']);
        }

        return is_string($attribute)
            ? stripslashes(preg_replace('/^[\'"](.*)[\'"]$/', '$1', $attribute))
            : null;
    }

    protected function setNodeValue($node, $key, $value)
    {
        if (method_exists($node, 'getAttributes')) {
            foreach ($node->getAttributes() as $attribute) {
                if ($attribute->getName() === $key) {
                    $attribute->setValue($value);
                }
            }

            return;
        }

        foreach ($node->attributes as &$attribute) {
            if ((is_array($attribute) || $attribute instanceof \ArrayAccess) && isset($attribute['name']) && $attribute['name'] === $key) {
                $attribute['value'] = var_export($value, true);
            }
        }
    }

    protected function processNode($node)
    {
        $name = method_exists($node, 'getName') ? $node->getName() : $node->name;
        if (!isset($this->extractors[$name])) {
            return false;
        }

        list($extractor, $attributes) = $this->extractors[$name];
        $arguments = array();
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
            $nodes = array();
            foreach ($block->nodes as $key => $node) {
                $subBlock = $this->getBlockToScroll($node);

                if ($subBlock) {
                    $this->scrollBlock($subBlock);
                }

                $node = $this->tryToProceedNode($node);

                if ($node) {
                    $nodes[$key] = $node;
                }
            }
            $block->nodes = $nodes;
        }
    }

    private function getBlockToScroll($node)
    {
        if (isset($node->block) && is_object($node->block)) {
            return $node->block;
        }

        if (isset($node->nodes) && count($node->nodes)) {
            return $node;
        }

        return null;
    }

    private function tryToProceedNode($node)
    {
        if (
            (isset($node->name) && ($node instanceof Tag || $node instanceof MarkupElement)) &&
            !$this->processNode($node)
        ) {
            return $node;
        }

        return null;
    }
}
