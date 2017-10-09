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

use yaophp\http\Environment;
use yaophp\http\datagram\Body;
use yaophp\app\Event;

class Provider
{

    public function __construct(App $app)
    {
        $app->shared(Environment::class, function(){
            return Environment::mock($_SERVER);
        });
        
        $app->normal(Body::class, function(){
            return new Body;
        });
        
        $app->alias(['Event' => Event::class]);
        
    }
}