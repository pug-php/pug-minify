<?php

namespace Pug\Keyword\Minify;

use Pug\Pug;

class InJsPugParser
{
    private $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function parse($code, $indent = '')
    {
        $code = str_replace("\r", '', $code);

        if (preg_match('/^[ \t]*\n/', $code)) {
            $code = explode("\n", $code, 2);
            $code = $code[1];

            if (preg_match('/^[ \t]+/', $code, $match)) {
                $indent = $match[0];
            }
        }

        $code = preg_replace('/(^' . preg_quote($indent) . '|(?<=\n)' . preg_quote($indent) . ')/', '', $code);

        $pug = new Pug($this->options);

        return $pug->render($code);
    }
}
