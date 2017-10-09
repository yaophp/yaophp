<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\session;

use yaophp\http\Session;

abstract class Handler implements \SessionHandlerInterface
{
    protected $key = 'SESSION:';
    protected $session;
    protected $expire;

    protected function parseId($session_id)
    {
        return $this->key . $session_id;
    }

    protected function needRefresh($session_data)
    {
        $lifetime = ini_get('session.gc_maxlifetime');
        if ($this->session != $session_data) {
            $this->expire = $lifetime;
            return true;
        }
        if ($this->expire && $this->expire <= $lifetime / 2) {
            $this->expire = $lifetime;
            return true;
        }
        return false;
    }


    public function write($session_id, $session_data)
    {
        if ($this->needRefresh($session_data)) {
            $this->save($session_id, $session_data, $this->expire);
        }
    }
    
    public function read($session_id)
    {
        $this->session = $this->load($session_id);
        if ($this->session && !$this->expire) {
            $this->expire = $this->getExpire($session_id);
        }
        return $this->session;
    }
    
    abstract public function save($session_id, $session_data, $expire);
    abstract public function load($session_id);
    abstract public function getExpire($session_id);
    
}