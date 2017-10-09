<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\route;

use yaophp\app\Pipe;
use yaophp\app\pipe\PipeStream;
use RuntimeException;
use InvalidArgumentException;

class RouteGroup extends Pipe
{
    protected $prefix = '';
    protected $prefix_parent = '';
    protected $group_parent;
    protected $index = 0;
    protected $binded = false;

    public function __construct($prefix, $prefix_parent, $group_parent=null)
    {
        parent::__construct();
        $this->list->add($this->index, function(){
            throw new RuntimeException('use bindRouteObject() method and replace this');
        });
        
        $this->prefix = $prefix;
        $this->prefix_parent = $prefix_parent;
        if ($group_parent && !$group_parent instanceof RouteGroup) {
            throw new InvalidArgumentException('group_parent arg must be instance of RouteGroup');
        }
        $this->group_parent = $group_parent;
        
    }
    
    public function __invoke(PipeStream $parent_stream, array $args = array())
    {
        if ($this->group_parent && !$this->binded) {
            $this->binded = true;
            $run = $this->bindToParent();
            return $run($parent_stream, $args);
        }
        return parent::__invoke($parent_stream, $args);
    }

        public function getPrefixNow()
    {
        return $this->prefix_parent . $this->prefix;
    }
    
    public function getPrefixParent()
    {
        return $this->prefix_parent;
    }
    
    public function getGroupParent()
    {
        return $this->group_parent;
    }
    
    public function first($call)
    {
        $this->index += 1;
        return parent::first($call);
    }
    
    public function bindRunable(callable $route_object)
    {
        $group = clone $this;
        $group->list->offsetSet($this->index, $route_object);
        return $group;
    }
    
    protected function bindToParent()
    {
        $obj = clone $this;
        $obj->binded = true;
        if ($this->group_parent) {
            return $obj->group_parent->bindRunable($obj);
        }
        return $obj;
    }
}