<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\exception;

class InvalidMethodException extends \InvalidArgumentException
{
    protected $request;

    public function __construct($request, $method)
    {
        $this->request = $request;
        parent::__construct(sprintf('Unsupported HTTP method "%s" provided', $method));
    }

    public function getRequest()
    {
        return $this->request;
    }
}