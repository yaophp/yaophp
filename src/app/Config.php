<?php

namespace yaophp\app;

class Config
{
    protected static $configs = [];
    protected static $configFound = [];

    public function __construct($file='')
    {
        $configFile = $file ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        if (!is_file($configFile)) {
            throw new \RuntimeException("config file [$configFile] not exists");
        } else {
            static::$configs = array_merge(static::$configs, include($configFile));
        }
        //todo optimize cache find key when it is not dug mod (cachefile/nosql)
    }
    
    public function get($key)
    {
        if (!key_exists($key, static::$configFound)) {
            static::$configFound[$key] = $this->parseKey(explode('.', $key), static::$configs);
        }
        return static::$configFound[$key];
    }
    
    protected function parseKey(array $key_queue, $configs)
    {
        if ($key_queue) {
            $key = array_shift($key_queue);
            return isset($configs[$key]) ? $this->parseKey($key_queue, $configs[$key]) : null;
        } else {
            return $configs;
        }

    }
    
}