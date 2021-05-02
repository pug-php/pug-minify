<?php

namespace Pug\Keyword\Minify;

use NodejsPhpFallback\Less;
use NodejsPhpFallback\Stylus;

class StyleParser implements AssetParser
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function parse()
    {
        switch ($this->params->extension) {
            case 'styl':
                return new Stylus($this->params->source);

            case 'less':
                return new Less($this->params->source);

            default:
                return $this->params->source;
        }
    }
}
