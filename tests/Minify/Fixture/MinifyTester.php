<?php

namespace Pug\Keyword\Test\Minify\Fixture;

use InvalidArgumentException;
use Pug\Keyword\Minify;
use Pug\Pug;

class MinifyTester extends Minify
{
    public function callGetOption($name)
    {
        return $this->getOption($name);
    }
}
