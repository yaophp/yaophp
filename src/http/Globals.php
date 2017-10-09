<?php

namespace yaophp\http;

class Globals
{
    public static function get(array $globals, $key, $default='')
    {
        return isset($globals[$key]) ? $globals[$key] : $default;
    }
}