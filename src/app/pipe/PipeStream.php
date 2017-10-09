<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\app\pipe;

class PipeStream implements \ArrayAccess, \IteratorAggregate
{
    protected $data;

    protected $closed = false;

    public function __construct()
    {
        $this->data = [];
    }

    public function isClosed() {
        return $this->closed;
    }
    
    public function close()
    {
        $this->closed = true;
        return $this;
    }
    
    public function __get($property)
    {
        return $this->offsetGet($property);
    }
    
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if ($this->closed) {
            throw new \Exception('pipeStream had close and not writeable ');
        }
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($this->closed) {
            throw new \Exception('pipeStream had close and not writeable ');
        }
        unset($this->data[$offset]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}