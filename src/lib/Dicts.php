<?php

/*
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\lib;

class Dicts implements \ArrayAccess, \IteratorAggregate
{
    protected $data = [];

    public function get($key, $default=null)
    {
        if (!isset($this->data[$key])) {
            $this->data[$key] = $default;
        }
        return $this->data[$key];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
    
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }


    public function offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
