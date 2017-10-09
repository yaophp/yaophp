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

class NotAllowedHandler
{
    public function handle()
    {
        $r = new Response;
        $r->withStatus(403);
        $r->getBody()->write('403 forbidden!!!');
        exit($r->output());
    }
}