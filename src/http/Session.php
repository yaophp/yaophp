<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\http\Cookie;
use yaophp\http\session\Redis;

class Session
{
    protected $key = 'SSID';
    protected $id;
    protected $lifetime = 60*30;
    protected $active;
    protected $expire;
    protected $cookie;

    public function __construct(Redis $handler)
    {
        if ($this->lifetime > ini_get('session.gc_maxlifetime')) {
            ini_set('session.gc_maxlifetime', $this->lifetime);
        }
        
        if ($this->expire) {
            ini_set('session.gc_maxlifetime', $this->expire);
            ini_set('session.cookie_lifetime', $this->expire);
        }
        session_set_save_handler($handler);
        session_name($this->key);
        session_cache_limiter(false);
        $this->start();
    }
    
    public function __toString()
    {
        return json_encode($_SESSION, JSON_UNESCAPED_UNICODE);
    }
    
    public function isUpdate()
    {
        return $this->update;
    }
    
    public function getId()
    {
        if (!$this->id) {
            $this->id = session_id();
        }
        return $this->id;
    }
    
    public function resetId($delete_data=false)
    {
        session_regenerate_id($delete_data);
        $this->id = session_id();
    }
    
    public function newSession($id=null)
    {
        session_unset();
        session_destroy();
        $this->id = session_id($id);
        session_start();
    }

    public function set($key, $value)
    {
        if (strpos($key, '.')) {
            list($collect, $k) = explode('.', $key, 2);
            $_SESSION[$collect][$k] = $value;
        } else {
            $_SESSION[$key] = $value;
        }
    }
    
    public function get($key, $default=null)
    {
        if (strpos($key, '.')) {
            list($collect, $k) = explode('.', $key, 2);
            return isset($_SESSION[$collect][$k]) ? $_SESSION[$collect][$k] : $default;
        } else {
            return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
        }
    }
    
    public function delete($key)
    {
        if (strpos($key, '.')) {
            list($collect, $k) = explode('.', $key, 2);
            unset($_SESSION[$collect][$k]);
        } else {
            unset($_SESSION[$key]);
        }
    }
    
    public function clear($client=false)
    {
        if ($client) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    }
    
    protected function refresh()
    {
        $this->active = session_status() === PHP_SESSION_ACTIVE;
        if ($this->active) {
        }
    }
    
    protected function start()
    {
        if (!$this->active) {
            session_start();
        }
    }
}