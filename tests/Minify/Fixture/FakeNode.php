<?php

namespace Pug\Keyword\Test\Minify\Fixture;

class FakeNode
{
    public $attributes = array(
        array(
            'name'  => 'bar',
            'value' => 'before',
        ),
    );

    public function getAttribute($key)
    {
        return array('value' => $key . '=42');
    }

    public function getBarValue()
    {
        return $this->attributes[0]['value'];
    }
}
