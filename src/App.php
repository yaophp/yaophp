<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

 namespace yaophp;

 use SplObjectStorage;
 use yaophp\app\Config;
 use yaophp\app\Pipe;
 use yaophp\app\Container;
 
class App extends Container
{
    const VERSION = '2.0.1';
    protected $pipe;
    protected $pipeStorages;
    protected static $fileConfig = '';

    protected function __construct()
    {
        parent::__construct();
        $this->pipe = new Pipe;
        $this->pipeStorages = new SplObjectStorage();
        static::$constructors = ['instance', 'getInstance'];
        $app = static::$instance;
        $this->register(App::class, function() use ($app){
           return $app; 
        });
        if ($file = static::$fileConfig) {
            $this->shared(Config::class, function() use($file){
                return new Config($file);
            });
        }
    }

    public static function instance($fileConfig='')
    {
        if ($fileConfig) {
            static::$fileConfig = $fileConfig;
        }
        return parent::instance();
    }

    public function hasPipe(callable $call)
    {
        return isset($this->pipeStorages[$call]);
    }
    
    public function newPipe(callable $call)
    {
        if (!isset($this->pipeStorages[$call])) {
            $pipe = new Pipe;
            $pipe->then($call);
            $this->then($pipe);
            $this->pipeStorages[$call] = $pipe;
        }
        return $this->pipeStorages[$call];
    }
    
    public function getPipeListForDebug()
    {
        return clone $this->pipeStorages;
    }
    
    public function then(callable $call)
    {
        $this->pipe->then($call);
        return $this;
    }
    
    public function first(callable $call)
    {
        $this->pipe->first($call);
        return $this;
    }
    
    public function start($bootClass=null)
    {
        $boot = $bootClass ?: \yaophp\http\Boot::class;
        $this->get($boot);
        try {
            $result = $this->pipe->start();
        } catch (\Exception $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw $e;
        }
        return $result;
    }
    
}