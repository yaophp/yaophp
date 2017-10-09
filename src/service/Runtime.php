<?php
namespace yaophp\service;

use yaophp\app\Config;

class Runtime
{

    protected $targets; // routes configs logs caches...
    protected $driver; // file memcached redis todo
    protected $debug;
    protected $mode;
    protected $modeFile;
    protected $path;

    protected $datas = [];

    public function __construct(Config $config)
    {
        $this->mode = $config->get('debug') ? 'DEV' : 'PROD';
        $this->path = $config->get('path.runtime') ?: \dirname(getcwd()) . DIRECTORY_SEPARATOR . 'runtime';
        $this->modeFile = $this->path .  DIRECTORY_SEPARATOR . $this->mode;
        if (!is_dir($this->path)) {
            mkdir($this->path, 0744);
        }
        if (!is_file($this->modeFile)) {
            \touch($this->modeFile);
        }
        if ($this->mode == 'DEV') {
            $file = $this->modeFile = $this->path .  DIRECTORY_SEPARATOR . 'PROD';
            is_file($file) && unlink($file);
        }
    }


    public function get($target, $key, $default=null)
    {
        $file = $this->parseTarget($target, $key);
        if (is_file($file) && !$this->isNeedReFresh($file)) {
            return include $file;
        }
        return null;
    }

    public function set($target, $key, $value)
    {
        $export = var_export($value, true);
        $result = "<?php return $export;";
        file_put_contents($this->parseTarget($target, $key),  $result);
    }

    protected function parseTarget($target, $key)
    {
        return $this->path .  DIRECTORY_SEPARATOR . 
        str_replace(['/', '\\'], '.', \get_class($target)) . $key . $this->mode . '.php';

    }

    protected function isNeedReFresh($file)
    {
        if ($this->mode == 'DEV') {
            return true;
        }
        
        if (!is_file($this->modeFile)) {
            \touch($this->modeFile);
            return true;
        }
        return fileatime($file) < filectime($this->modeFile);
    }
}