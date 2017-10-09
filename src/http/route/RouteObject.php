<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\route;

use yaophp\App;
use yaophp\app\Pipe;
use yaophp\http\Request;
use yaophp\exception\PipeException;
use yaophp\exception\RouteException;
use yaophp\app\pipe\PipeStream;


class RouteObject extends Pipe
{
    protected $id;
    protected $route;
    protected $handler;
    protected $args;
    protected $index = 0;
    protected $requestMethod;

    public function __construct($route, $handler)
    {
        parent::__construct();
        $this->route = $route;
        $this->handler = $handler;
        $this->list->add($this->index, function () {
            throw new RouteException('insert RouteObject to replace this holder');
        });

    }
    
    public function first($call)
    {
        $this->index += 1;
        return parent::first($call);
    }
    
    public function __invoke(PipeStream $parent_result,  array $args = [])
    {
        $args = $this->getArgs();
        array_unshift($args, $parent_result);
        if (is_array($this->handler)) {
            list($class, $action) = $this->handler;
        } elseif (is_callable($this->handler)) {
            $class = $this->handler;
            $action = '__invoke';
        } elseif (is_string($this->handler)) {
            if (!class_exists($this->handler)) {
                throw new PipeException("route map class $this->handler not existsed");
            }
            $class = $this->handler;
            $action = App::instance()->get(Request::class)->getMethod();
        } else {
            throw new RouteException("Route handler type must "
                    . "be Closure or [class, action]  or class string");
        }
        $this->list->offsetSet($this->index, function() use($class, $action, $args){
            return App::instance()->invokeMethod($class, $action, $args);
        });
        return parent::__invoke($parent_result, $this->args);
    }
    
    
    public function setArgs(array $args)
    {
        $this->args = $args;
        return $this;
    }
    
    public function getArgs()
    {
        if ($this->args === null) {
            // todo parseArgs from url;
            $this->args = [];
        }
        return $this->args;
    }
    
    public function url(array $data=[], array $queryParams=[])
    {
        list($routeDatas, $args) = App::instance()->get(RouteParser::class)->parseRouteArray($this->route);
        $segments = [];
        foreach($routeDatas as $routItem) {
            if ($routItem != RouteParser::DELIMITER) {
                $segments[] = $routItem;
            } else {
                if ($args) {
                    $name = \array_shift($args);
                    if (!isset($data[$name])) {
                        throw new \InvalidArgumentException('Missing data for URL segment: ' . $name);
                    }
                    $segments[] = $data[$name];
                }
            }
        }
        $url = implode('/', $segments);
        
        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $url;

    }
    
}