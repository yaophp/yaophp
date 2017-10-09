<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\service;

use yaophp\App;
use yaophp\service\LarDb;
use Illuminate\Database\Eloquent\Model as LaravelModel;

abstract class LarModel extends LaravelModel
{
    protected static $model_prefix;
    protected static $model_config;
    protected static $model_names = [];
    public $timestamps = false;


    public function __construct(array $attributes = array())
    {
        $this->modelInit();
        parent::__construct($attributes);
    }
    
    protected function modelInit()
    {
        if (static::$model_config === null) {
            $db = App::instance()->get(LarDb::class);
            static::$model_config = $config = $db->getDatabaseConfig();
            static::$model_prefix = isset($config['prefix']) ? $config['prefix'] : '';
        }
        $this->parseModelName();
    }


    public function parseModelName()
    {
        if (!$this->table) {
            $str = basename(str_replace('\\', '/', get_class($this)));
            if (!isset(static::$model_names[$str])) {
                static::$model_names[$str] = static::$model_prefix .
                        strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', $str));
            }
            $this->table = static::$model_names[$str];
        }
    }
    
    public static function selectJoin(array $selects, array $joins)
    {
        $builder = call_user_func_array(static::class.'::select', $selects);
        foreach ($joins as $join) {
            $builder = call_user_func_array(static::class.'::join'.$join, [$builder]);
        }
        return $builder;
    }
}