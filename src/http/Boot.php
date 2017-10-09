<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\App;
use yaophp\app\Config;
use yaophp\app\Event;
use yaophp\http\Http;
use yaophp\http\Route;
use yaophp\http\Provider;

class Boot
{
    protected $app;
    protected static $booted;
    protected $pathApp;

    public function __construct(App $app, Provider $provider, Config $config)
    {
        if (static::$booted) {
            throw new \RuntimeException("app had been booted once");
        }
        $this->pathApp = $config->get('path.app');
        $this->app = $app;
        $fileProvider = $this->isFile($this->pathApp, 'provider');
        $fileRoute = $this->isFile($this->pathApp, 'route');
        $fileProvider && $this->provider($fileProvider);
        $this->pipe($app);
        $this->event();
        $fileRoute && include($fileRoute);
        static::$booted = true;
    }

    protected function isFile($path, $file)
    {
        $f = $path . DIRECTORY_SEPARATOR . $file . '.php';
        return \is_file($f) ? $f : false;
    }
    
    protected function provider($file)
    {
        $providers = require $file;
        foreach ($providers['interface'] as $interface => $implement) {
            $this->app->setInterface($interface, $implement);
        }
        foreach ($providers['shared'] as $class => $call) {
            $this->app->shared($class, $call);
        }
        foreach ($providers['normal'] as $class => $call) {
            $this->app->normal($class, $call);
        }
        $this->app->alias($providers['alias']);
    }
    
    protected function pipe(App $app)
    {
        $app->newPipe($app->get(Http::class));
        $app->newPipe($app->get(Route::class));
    }
    
    protected function event()
    {
        // \app\service\model\Comment::saved(function(){
        //     echo 'hahahahha';
        // });
    }
    
}