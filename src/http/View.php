<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\app\Config;
use yaophp\http\Route;

class View
{
    protected $loader;
    protected $engine;
    protected $data = [];
    protected $suffix = '';

    public function __construct(Config $config)
    {
        $this->loader = new \Twig_Loader_Filesystem($config->get('template.path'));
        $this->engine = new \Twig_Environment($this->loader, [
            'debug' => $config->get('debug'),
            'cache' => $config->get('template.cache_path')
        ]);
        $func = new \Twig_SimpleFunction('url', function($class_method, array $querys=[], array $params=[]){
            return Route::url(\explode('@', $class_method), $querys, $params);
        });
        $this->engine->addFunction($func);
        $this->suffix = $config->get('template.suffix');
    }
    
    public function assign($data, $value='')
    {
        if (is_array($data)) {
            $this->data = array_merge($this->data, $data);
        } else {
            $this->data[$data] = $value;
        }
    }
    
    
    /*
     * 有输出，有返回
     */
    public function render($template, array $data=[])
    {
        if ($data) {
            $this->assign($data);
        }
        $str = $this->engine->render($this->getTpl($template), $this->data);
        echo $str;
        return $str;
    }
    
    /*
     * 无输出，有返回
     */
    public function fetch($template, array $data=[])
    {
        if ($data) {
            $this->assign($data);
        }
        return $this->engine->render($this->getTpl($template), $this->data);
    }
    
    /*
     * 有输出，无返回
     */
    public function display($template, array $data=[])
    {
        if ($data) {
            $this->assign($data);
        }
        return $this->engine->display($this->getTpl($template), $this->data);
    }
    
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->engine, $name], $arguments);
    }
    
    protected function getTpl($template)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $template) . $this->suffix;
    }
}