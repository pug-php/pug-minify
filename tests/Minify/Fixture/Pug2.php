<?php

namespace Pug\Keyword\Test\Minify\Fixture;

use InvalidArgumentException;
use Pug\Pug;

class Pug2 extends Pug
{
    public function hasOption($name)
    {
        if ($name === 'testOption' || !method_exists('Pug\\Pug', 'hasOption')) {
            return true;
        }

        return parent::hasOption($name);
    }

    public function getOption($name)
    {
        if ($name === 'testOption') {
            throw new InvalidArgumentException('Option not found');
        }

        return parent::getOption($name);
    }
}
