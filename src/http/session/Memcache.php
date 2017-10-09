<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\session;

class Memcache extends Handler
{
    
    public function open($save_path, $name)
    {
        ;
    }


    public function load($session_id)
    {
        ;
    }
    
    public function save($session_id, $session_data, $expire)
    {
        ;
    }
    
    public function getExpire($session_id)
    {
        ;
    }
    
    public function gc($maxlifetime)
    {
        ;
    }
    
    public function destroy($session_id)
    {
        ;
    }
    
    public function close()
    {
        ;
    }
    
}