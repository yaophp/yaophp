<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\http\Globals;

class Cookie{
	protected static $_instance;
	protected static $timeBase;

	public function __construct(){
		static::$timeBase = time();
	}

	public function get($key){
		return Globals::get($_COOKIE, $key, '');
	}

	public function set($key, $value, $expires){
        $expire_at = static::$timeBase + $expires;
		setcookie($key, $value, (int)$expire_at, '/');
	}
}