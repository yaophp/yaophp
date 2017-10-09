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
use yaophp\exception\PipeException;
use yaophp\exception\RouteException;
use yaophp\app\pipe\PipeStream;
use FastRoute\RouteParser\Std;

class RouteObject222 extends Pipe
{
    protected $id;
    protected $route;
    protected $handler;
    protected $args;
    protected $index = 0;

    public function __construct($id, $method, $route, $handler)
    {
        parent::__construct();
        $this->id = $id;
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
        } else if (is_string($this->handler)) {
            if (!class_exists($this->handler)) {
                throw new PipeException("class $this->handler not existsed");
            }
            $class = $this->handler;
            $action = '__invoke';
        } else {
            throw new RouteException("Route id: $this->id handler type must "
                    . "be Closure or class@method string");
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
        $routeDatas = App::instance()->get(Std::class)->parse($this->route);
        // $routeDatas is an array of all possible routes that can be made. There is
        // one routedata for each optional parameter plus one for no optional parameters.
        //
        // The most specific is last, so we look for that first.
        $routeDatas = array_reverse($routeDatas);

        $segments = [];
        foreach ($routeDatas as $routeData) {
            foreach ($routeData as $item) {
                if (is_string($item)) {
                    // this segment is a static string
                    $segments[] = $item;
                    continue;
                }

                // This segment has a parameter: first element is the name
                if (!array_key_exists($item[0], $data)) {
                    // we don't have a data element for this segment: cancel
                    // testing this routeData item, so that we can try a less
                    // specific routeData item.
                    $segments = [];
                    $segmentName = $item[0];
                    break;
                }
                $segments[] = $data[$item[0]];
            }
            if (!empty($segments)) {
                // we found all the parameters for this route data, no need to check
                // less specific ones
                break;
            }
        }

        if (empty($segments)) {
            throw new \InvalidArgumentException('Missing data for URL segment: ' . $segmentName);
        }
        $url = implode('', $segments);

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $url;
    }
    
}