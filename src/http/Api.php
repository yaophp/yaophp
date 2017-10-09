<?php
namespace yaophp\http;

use yaophp\exception\HttpException;

class Api
{
    public function get()
    {
        throw new \HttpException('http request method not allow');
    }
    public function post()
    {
        throw new \HttpException('http request method not allow');
    }
    public function put()
    {
        throw new \HttpException('http request method not allow');
    }
    public function delete()
    {
        throw new \HttpException('http request method not allow');
    }
    public function patch()
    {
        throw new \HttpException('http request method not allow');
    }
    public function head()
    {
        throw new \HttpException('http request method not allow');
    }
}