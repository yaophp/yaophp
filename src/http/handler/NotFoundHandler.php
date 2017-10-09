<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\handler;

use yaophp\http\Response;

class NotFoundHandler
{
    
    public function handle()
    {
        $response = new Response;
        $response->withStatus(404);
        $response->getBody()->write('404 not found !!!');
        exit($response->output());
    }
}