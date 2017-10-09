<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\handler;

use yaophp\http\Route;

class FoundHandler
{
    protected $route;
    public function __construct(Route $route)
    {
        $this->route = $route;
    }
    
    public function handle($handler, $args)
    {
        return $this->route->handle($handler, $args);
    }
}