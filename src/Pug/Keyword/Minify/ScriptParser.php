<?php

namespace Pug\Keyword\Minify;

use NodejsPhpFallback\CoffeeScript;
use NodejsPhpFallback\React;

class ScriptParser implements AssetParser
{
    private $params;

    private $destination;

    public function __construct($params, $destination)
    {
        $this->params = $params;
        $this->destination = $destination;
    }

    public function parse()
    {
        switch ($this->params->extension) {
            case 'jsxp':
                return array(new React($this->params->contents), $this->destination);

            case 'jsx':
                return new React($this->params->source);

            case 'cofp':
                return new Coffeescript($this->params->contents);

            case 'coffee':
                return new Coffeescript($this->params->source);

            default:
                return $this->params->source;
        }
    }
}
