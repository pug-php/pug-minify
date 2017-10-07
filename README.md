# Pug-Minify
[![Latest Stable Version](https://poser.pugx.org/pug-php/pug-minify/v/stable.png)](https://packagist.org/packages/pug-php/pug-minify)
[![Build Status](https://travis-ci.org/pug-php/pug-minify.svg?branch=master)](https://travis-ci.org/pug-php/pug-minify)
[![StyleCI](https://styleci.io/repos/64454439/shield?style=flat)](https://styleci.io/repos/64454439)
[![Test Coverage](https://codeclimate.com/github/pug-php/pug-minify/badges/coverage.svg)](https://codecov.io/github/pug-php/pug-minify?branch=master)
[![Code Climate](https://codeclimate.com/github/pug-php/pug-minify/badges/gpa.svg)](https://codeclimate.com/github/pug-php/pug-minify)

One keyword to minify them all (the assets: JS, CSS, Stylus, Less, Coffee,
React) in your pug-php template.

## Usage

```php
<?php

use Pug\Keyword\Minify;
use Pug\Pug;

// Create a new Pug instance:
$pug = new Pug(array(
    'assetDirectory'  => 'path/to/the/asset/sources',
    'outputDirectory' => 'web',
));
// Or if you already instanciate it, just set the options:
$pug->setOptions(array(
    'assetDirectory'  => 'path/to/the/asset/sources',
    'outputDirectory' => 'web',
));
$minify = new Minify($pug);
$pug->addKeyword('minify', $minify);
$pug->addKeyword('concat', $minify);

$pug->render('my/template.pug');
```

You can link the *Minify* instance to any keyword. Just remind that if you
use ```concat``` or ```concat-to```, the files will only be concatened and
not minified, for any other keyword, they will be both concatened and
minified.

By default concat and minification are not enable to allow you to debug it
easier, in production you should set the `environment` option:
```php
$pug->setOption('environment', 'production');
```

If you still use pug-php 2, `production` is the default, you must set it
this way in your development environment:
```php
$pug->setCustomOption('environment', 'development');
```

Note that in pug-php 2, you must use `->setCustomOption` and
`->setCustomOptions` for `assetDirectory`, `outputDirectory`
and `environment` options. With pug-php 3, you can now use
`->setOption` and `->setOptions` for any option.

This will just transform (for stylus, less, coffee, etc.) and copy your
assets to the output directory.

Now let's see what your template should look like:
```pug
doctype 5
html
  head
    title Foo
    minify top
      script(src="foo/test.js")
      script(src="coffee/test.coffee")
      script(src="react-pug/test.jsxp" type="text/babel")
      link(rel="stylesheet" href="foo/test.css")
      link(rel="stylesheet" href="less/test.less")
      link(rel="stylesheet" href="stylus/test.styl")
      meta(name="foo" content="bar")
  body
    h1 Foobar
    minify bottom
      script(src="react/test.jsx" type="text/babel")
      script(src="coffee-pug/test.cofp")
      //- some comment
```

In production, all ```script``` and ```link``` (with a stylesheet rel)
tags of each **minify** block will be merged into one tag pointing to a
minified version of all of them like this:
```pug
doctype 5
html
  head
    title Foo
    script(src="js/top.min.js")
    link(rel="stylesheet" href="css/top.min.css")
    meta(name="foo" content="bar")
  body
    h1 Foobar
    script(src="js/bottom.js")
    //- some comment
```

The generated files **js/top.min.js**, **css/top.min.css** and
**js/bottom.js** are stored in the **outputDirectory** you specifed
with the option. So you just must ensure ```src="foo/bar.js"```
will target **{outputDirectory}/foo/bar.js**.

**Important**: to improve performance in production, enable the
Pug cache by setting the **cache** option to a writable directory,
examples:
```php
$pug->setOption('cache', 'var/cache/pug');
$pug->setOption('cache', sys_get_temp_dir());
```
And clear this cache directory when your assets change or when you
deploy new ones.

As the Pug cache feature allow to render the pug code only once,
and so the assets, we do not incorporate a specific caching option
in the *Minify* keyword.
