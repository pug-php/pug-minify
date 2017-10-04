<?php

namespace Pug\Keyword\Minify;

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
        if (is_array($attribute)) {
            $attribute = strval($attribute['value']);
        }

        return is_string($attribute)
            ? stripslashes(substr($attribute, 1, -1))
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
                if (isset($node->block) && is_object($node->block)) {
                    $this->scrollBlock($node->block);
                } elseif (isset($node->nodes) && count($node->nodes)) {
                    $this->scrollBlock($node);
                }
                if (isset($node->name) && ($node instanceof \Jade\Nodes\Tag || $node instanceof \Phug\Formatter\Element\MarkupElement)) {
                    if (method_exists($node, 'getOriginNode')) {
                        $node = $node->getOriginNode();
                    }
                    if ($this->processNode($node)) {
                        continue;
                    }
                    $nodes[$key] = $node;
                }
            }
            $block->nodes = $nodes;
        }
    }
}
