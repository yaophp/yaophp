<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\session;

class Redis extends Handler
{
    protected $driver;
    protected $key = 'SESSION:';

    public function __construct(\yaophp\lib\Redis $redis)
    {
        $this->driver = $redis;
    }
    
    public function getExpire($session_id)
    {
        return $this->driver->ttl($this->parseId($session_id));
    }

    public function open($save_path, $name)
    {
        return true;
    }
    
    public function load($session_id)
    {
        return $this->driver->get($this->parseId($session_id));
    }
    
    public function save($session_id, $session_data, $expire)
    {
        $key = $this->parseId($session_id);
        return $this->driver->setex($key, $expire, $session_data);
    }
    
    public function destroy($session_id)
    {
        $this->driver->delete($this->parseId($session_id));
    }
    
    public function close()
    {
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}