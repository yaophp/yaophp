<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\app;

use SplDoublyLinkedList;
use yaophp\app\pipe\PipeStream;
use yaophp\exception\PipeException;

class Pipe
{
    protected $list;
    protected $stream;

    public function __construct()
    {
        $this->list = new SplDoublyLinkedList;
        $this->stream = new PipeStream;
    }
    
    public function __invoke(PipeStream $parentStream, array $args=[])
    {
        return $this->run($parentStream, $args);
    }
    
    public function first($call)
    {
        $this->list->unshift($call);
        return $this;
    }
    
    public function then($call)
    {
        $this->list->push($call);
        return $this;
    }

    public function start()
    {
        return $this->run();
    }
    
    protected function buildCallable($callable)
    {
        return function($args) use ($callable) {
            if (!is_array($args)) {
                $args = [$args];
            }
            return call_user_func_array($callable, $args);
        };
    }

    protected function parseCall($call)
    {
        if (is_string($call)) {
            if (strpos($call, '::')) {
                return $call;
            }
            $call = [$call, '__invoke'];
        }
        if (is_array($call)) {
            list($class, $method) = $call;
            $instance = \yaophp\App::instance()->get($class);
            return [$instance, $method];
        }
        if (is_callable($call)) {
            return $call;
        }
        $type = gettype($call);
        throw new PipeException("pipe not support for $type type");
    }

    protected function run($parentStream = null, array $args=[])
    {
        $this->stream = $parentStream ?: new PipeStream;
        array_unshift($args, $this->stream);
        for ($this->list->rewind(); $this->list->valid(); $this->list->next()) {
            $call = $this->parseCall($this->list->current());
            $result = call_user_func_array($call, $args);
            if (!$result){
                continue;
            }
            if ($result instanceof \Generator) {
                $temp = $result->send($this->stream);
                $result->valid() && $this->then(function()use($result){return $result;});
                $result = $temp;
            }
            if ($result instanceof PipeStream) {
                $this->stream = $result;
                if ($this->stream->isClosed()) {
                    return $this->stream;
                }
            }
        }
        return $this->stream;
    }
}