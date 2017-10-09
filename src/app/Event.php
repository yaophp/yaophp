<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\app;

use yaophp\app\Pipe;
use yaophp\app\pipe\PipeStream;

Class Event
{
    protected static $events = [];
    
    protected static function on($key)
    {
        if (!is_string($key)) {
            throw new \RuntimeException("Event key must be string");
        }
        if (!isset(static::$events[$key])) {
            static::$events[$key] = new Pipe;
        }
        return static::$events[$key];
    }
    
    public static function __callStatic($name, $arguments)
    {
        $pipe = static::on($name);
        if ($arguments) {
            foreach ($arguments as $call) {
                if ($call instanceof PipeStream) {
                    return $pipe($call);
                }
                $call && $pipe->then($call);
            }
            return $pipe;
        }
        return $pipe->start();
    }
    
    public function __call($name, $arguments)
    {
        return static::__callStatic($name, $arguments);
    }
    
}