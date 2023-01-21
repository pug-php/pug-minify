<?php

namespace Pug\Keyword\Minify;

class Path
{
    private $parts;

    public function __construct()
    {
        $this->parts = array_filter(func_get_args());
    }

    /**
     * @param string $relativePath
     *
     * @return static
     */
    public function relativeTo($relativePath)
    {
        $path = new static(dirname($relativePath));
        $path->parts = array_merge($path->parts, $this->parts);

        return $path;
    }

    public function __toString()
    {
        $parts = $this->parts;

        if (isset($parts[0]) && is_array($parts[0])) {
            $bases = $parts[0];
            $copy = $parts;
            $parts[0] = $bases[0];

            $this->substituteBasePath($parts, $bases, $copy);
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    private function substituteBasePath(&$parts, $bases, $copy)
    {
        foreach ($bases as $base) {
            $copy[0] = $base;

            if (file_exists(implode(DIRECTORY_SEPARATOR, $copy))) {
                $parts[0] = $base;

                break;
            }
        }
    }
}
