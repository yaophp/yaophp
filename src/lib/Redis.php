<?php

namespace yaophp\lib;

use \Redis as OldRedis;

class Redis extends OldRedis
{
    protected static $settings;

    public function __construct(array $config=[])
    {
        if (!extension_loaded('redis')) {
            throw new \Exception('Redis extendsion not found/load');
        }
        parent::__construct();
        static::$settings = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'timeout' => false,
            'auth' => ''
        ], $config);
        $this->connect(static::$settings['host'], static::$settings['port']);
        if (static::$settings['auth']) {
            $this->auth(static::$settings['auth']);
        }
    }


    public function count($key, $type=null)
    {
        $map = [
            'string' => 'strlen',
            'list' => 'llen',
            'hash' => 'hlen',
            'set' => 'scard' ,
            'zset' => 'zcard'
        ];
        if ($type === null) {
            $map = [
                static::REDIS_STRING => 'strlen',
                static::REDIS_LIST => 'llen',
                static::REDIS_HASH => 'hlen',
                static::REDIS_SET => 'scard',
                static::REDIS_ZSET => 'zcard',
            ];
             $type = $this->type($key);
        }
        $method = isset($map[$type]) ? $map[$type] : null;
        return $method ? $this->$method($key) : null;
    }

    
    /*
     * 注意Redis事务是个坑
     * 她不回滚（虽然discard可以放弃）
     * 只要exec她不管对错都执行
     * watch 能监控键，当键变动后只是让事务不执行
     * so不推荐
     */
    public function withMulti($func, Array $watch=[])
    {
        if ($watch) {
            $this->watch($watch);
        }
        $this->multi();
        call_user_func($func);
        $this->exec(); //不需要unwatch
    }
    
    public function withPipeLine($func, Array $watch=[])
    {
        if ($watch) {
            $this->watch($watch);
        }
        $this->pipeLine();
        call_user_func($func);
        $result = $this->exec();
        if ($watch){
            $this->unwatch($watch);
        }
        return $result;
    }
}