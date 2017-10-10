<?php
namespace yaophp\http;

use yaophp\http\Request;
use yaophp\http\Response;
use yaophp\exception\HttpException;

class Api
{
    protected $request;
    protected $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function success($data='')
    {
        $result = [
            'code' => 1,
            'data' => $data
        ];
        $this->response->withJson($result);
        $this->response->output();
    }

    public function error($data='')
    {
        $result = [
            'code' => 0,
            'data' => $data
        ];
        $this->response->withJson($result);
        $this->response->output();
        exit;
    }

    public function ajax($data)
    {
        $this->response->withJson($data);
        $this->response->output();
    }

    public function notFound($msg='not found')
    {
        $this->response->withStatus(404);
        $this->error($msg);
    }

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