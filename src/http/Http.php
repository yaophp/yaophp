<?php

/*
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\http\Dispatch;
use yaophp\http\Request;
use yaophp\http\Route;
use yaophp\http\handler\NotFoundHandler;
use yaophp\http\handler\NotAllowedHandler;
use yaophp\http\handler\FoundHandler;

class Http
{
    protected $dispatch;
    protected $request;
    protected $route;

    public function __construct(Dispatch $dispatch, Request $request, Route $route)
    {
        $this->request = $request;
        $this->dispatch = $dispatch;
        $this->route = $route;
    }

    public function __invoke()
    {
        return $this->run();
    }

    public function run()
    {
        $method = $this->request->getMethod();
        $path = $this->request->getPath();
        $routeInfo = $this->dispatch->dispatch($method, $path);
        switch ($routeInfo[0]) {
            case Dispatch::NOT_FOUND:
                $handler = new NotFoundHandler;
                $handler->handle();
                break;
            case Dispatch::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                $handler = new NotAllowedHandler;
                $handler->handle();
                break;
            case Dispatch::FOUND:
                $handler = $routeInfo[1];
                $args = $routeInfo[2];
                $found = new FoundHandler($this->route);
                return $found->handle($handler, $args);
            default :
                exit;
        }
    }

}
