<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\datagram;

abstract class Datagram
{
    protected $protocol = '1.1';
    protected $protocol_enable = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];
    protected $header;
    protected $body;

    
    /*************************
     * header
     *************************/
    public function getProtocol()
    {
        return $this->protocol;
    }
    
    public function withProtocol($version)
    {
        if (!isset($this->protocol_enable[$version])) {
            throw new InvalidArgumentException(
                'Invalid HTTP version. Must be one of: '
                . implode(', ', array_keys($this->protocol_enable))
            );
        }
        $this->protocol = $version;
        return $this;
    }
    
    /*************************
     * header
     *************************/
    public function withHeader($name, $value)
    {
        $this->header->set($name, $value);
        return $this;
    }
    
    public function withAddedHeader($name, $value)
    {
        $this->header->addMsg($name, $value);
        return $this;
    }
    
    public function getHeaderAll()
    {
        return $this->header->all();
    }
    
    public function getHeaders()
    {
        return $this->header->all();
    }
    
    public function getHeaderLine($name)
    {
        return implode(',', $this->header->get($name, []));
    }
    
    public function hasHeader($name)
    {
        return $this->header->has($name);
    }
    
    public function getHeader($name)
    {
        return $this->header->get($name, []);
    }
    
    public function withoutHeader($name)
    {
        $this->header->remove($name);
        return $this;
    }
    
    /*************************
     * body
     *************************/
    public function getBody()
    {
        return $this->body;
    }
    
    public function withBody(Stream $stream)
    {
        $this->body->write($stream);
        return $this;
    }
}