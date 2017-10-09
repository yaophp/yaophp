<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\service;

use yaophp\app\Config;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;

class LarDb extends Manager
{
    protected static $database_config;

    public function __construct(Container $container=null, Config $config, $file='')
    {
        parent::__construct($container);
        $f = $file ?: $config->get('path.app') . DIRECTORY_SEPARATOR . 'database.php';
        if (!is_file($f)) {
            throw new \RuntimeException("database config file $f for laravel db not exists");
        }
        static::$database_config = include $f;
        $this->addConnection(static::$database_config);
        $this->setAsGlobal();
        $this->bootEloquent();
    }
    
    public function getDatabaseConfig()
    {
        return static::$database_config;
    }

    public function getPdo(){
        $config = static::$database_config;
        return [
            'dsn' => sprintf("%s:dbname=%s;host=%s", $config['driver'], $config['database'], $config['host'])
            , 'username' => $config['username']
            , 'password' => $config['password']
        ];
    }
}