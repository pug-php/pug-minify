<?php

namespace Pug\Keyword\Test\Minify\Fixture;

use Pug\Keyword\Minify;

class MinifyTester extends Minify
{
    public function callGetOption($name)
    {
        return $this->getOption($name);
    }
}
