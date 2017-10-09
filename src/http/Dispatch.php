<?php

namespace yaophp\http;

use yaophp\http\route\RouteParser;

class Dispatch
{
    const FOUND = 0;
    const NOT_FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    protected $routeParser;

    public function __construct(RouteParser $routeParser)
    {
        $this->routeParser = $routeParser;
    }

    public function dispatch($method, $path)
    {
        $handler_args = $this->routeParser->has($method, $path);
        if (!$handler_args) {
            return [$this->routeParser->getError(), '', ''];
        }
        return [static::FOUND, $handler_args['handler'], $handler_args['args']];
    } 
}