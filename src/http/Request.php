<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http;

use yaophp\http\Environment;
use yaophp\http\datagram\Datagram;
use yaophp\http\datagram\Header;
use yaophp\http\datagram\Body;
use yaophp\http\datagram\Uri;
use yaophp\http\datagram\Upload;
use yaophp\exception\InvalidMethodException;

class Request extends Datagram
{
    protected $environment;
    protected $method;
    protected $uri;
    protected $files;
    protected $method_enable = [
        'CONNECT' => 1,
        'DELETE' => 1,
        'GET' => 1,
        'HEAD' => 1,
        'OPTIONS' => 1,
        'PATCH' => 1,
        'POST' => 1,
        'PUT' => 1,
        'TRACE' => 1,
    ];
    protected $query;
    protected $request_path;
    protected $request_target;
    protected $body_parsed = false;
    protected $body_parsers;

    public function __construct(Environment $env, Uri $uri, Header $header, Body $body)
    {
        $this->environment = $env;
        $this->withMethod($env['REQUEST_METHOD']);
        $this->uri = $uri;
        $this->header = $header;
        $this->body = $body;
        
        if ($this->getMethod() === 'POST' &&
            in_array($this->getMediaType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])
        ) {
            // parsed body must be $_POST
            $this->withParsedBody($_POST);
        }
        
        
        $this->registerMediaTypeParser('application/json', function ($input) {
            $result = json_decode($input, true);
            if (!is_array($result)) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('application/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('text/xml', function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $backup_errors = libxml_use_internal_errors(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            libxml_clear_errors();
            libxml_use_internal_errors($backup_errors);
            if ($result === false) {
                return null;
            }
            return $result;
        });

        $this->registerMediaTypeParser('application/x-www-form-urlencoded', function ($input) {
            parse_str($input, $data);
            return $data;
        });

    }
    
    public function registerMediaTypeParser($mediaType, callable $callable)
    {
        if ($callable instanceof Closure) {
            $callable = $callable->bindTo($this);
        }
        $this->body_parsers[(string)$mediaType] = $callable;
    }
    
    public function withParsedBody($data)
    {
        if (!is_null($data) && !is_object($data) && !is_array($data)) {
            throw new InvalidArgumentException('Parsed body value must be an array, an object, or null');
        }
        $this->body_parsed = $data;
        return $this;
    }
    
    /*******************************************************************************
     * Input
     ******************************************************************************/
    public function input($key=null, $default = null)
    {
        $post = $this->getParsedBody();
        $get = $this->getQuery();
        if ($key === null) {
            if ($post) {
                $get = array_merge($get, (array)$post);
            }
            return $get;
        }
        $result = $default;
        if (is_array($post) && isset($post[$key])) {
            $result = $post[$key];
        } elseif (is_object($post) && property_exists($post, $key)) {
            $result = $post->$key;
        } elseif (isset($get[$key])) {
            $result = $get[$key];
        }

        return $result;
    }
    
    protected function getParsedBody()
    {
        if ($this->body_parsed !== false) {
            return $this->body_parsed;
        }

        if (!$this->body) {
            return null;
        }
        
        $mediaType = $this->getMediaType();

        // look for a media type with a structured syntax suffix (RFC 6839)
        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts)-1];
        }

        if (isset($this->body_parsers[$mediaType]) === true) {
            $body = (string)$this->getBody();
            
            $parsed = $this->body_parsers[$mediaType]($body);

            if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                throw new RuntimeException(
                    'Request body media type parser return value must be an array, an object, or null'
                );
            }
            $this->body_parsed = $parsed;
            return $this->body_parsed;
        }

        return null;
    }
    
    protected function getMediaType()
    {
        $contentType = $this->getContentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }

        return null;
    }

    public function getContentType()
    {
        $result = $this->getHeader('Content-Type');
        return $result ? $result[0] : null;
    }
    
    protected function getQuery()
    {
        if (is_array($this->query)) {
            return $this->query;
        }

        if ($this->uri === null) {
            return [];
        }
        parse_str($this->uri->getQuery(), $this->query); // <-- URL decodes data
        return $this->query;
    }
    
    /*******************************************************************************
     * File
     ******************************************************************************/
    public function file($file=null)
    {
        if ($this->files === null) {
            $this->files = Upload::createFromEnvironment($this->environment);
        }
        $result = $this->files;
        if ($file !== null) {
            $result = isset($result[$file]) ? $result[$file] : null;
        }
        return $result;
    }
    
    
    /*******************************************************************************
     * Method
     ******************************************************************************/
    public function getMethod()
    {
        return $this->method;
    }
    
    public function isMethod($method)
    {
        return $this->method === $method;
    }
    
    public function isGet()
    {
        return $this->isMethod('GET');
    }
    
    public function isPost()
    {
        return $this->isMethod('POST');
    }
    
    public function isPut()
    {
        return $this->isMethod('PUT');
    }
    
    public function isPatch()
    {
        return $this->isMethod('PATCH');
    }
    
    public function isDelete()
    {
        return $this->isMethod('DELETE');
    }

    public function isHead()
    {
        return $this->isMethod('HEAD');
    }
    
    public function isOptions()
    {
        return $this->isMethod('OPTIONS');
    }

    public function isAjax()
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }
    
    public function withMethod($method)
    {
        $this->method = $this->filterMethod($method);
        return $this;
    }
    
    protected function filterMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (!isset($this->method_enable[$method])) {
            throw new InvalidMethodException($this, $method);
        }

        return $method;
    }
    
    /*******************************************************************************
     * Uri
     ******************************************************************************/
    public function getUri()
    {
        return $this->uri;
    }
    
    public function withUri(Uri $uri, $preserveHost = false)
    {
        $this->uri = $uri;
        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $this->header->set('Host', $uri->getHost());
            }
        } else {
            if ($uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeaderLine('Host') === '')) {
                $this->header->set('Host', $uri->getHost());
            }
        }
        return $this;
    }
    
    public function getPath()
    {
        if ($this->request_path) {
            return $this->request_path;
        }
        if ($this->uri === null) {
            return '/';
        }
        $this->request_path = $this->uri->getPath();
        return $this->request_path;
    }
    
    public function getRequestTarget()
    {
        if ($this->request_target) {
            return $this->request_target;
        }

        if ($this->uri === null) {
            return '/';
        }

        $basePath = $this->uri->getBasePath();
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path, '/');

        $query = $this->uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }
        $this->request_target = $path;

        return $this->request_target;
    }
    
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $this->request_target = $requestTarget;
        return $this;
    }
}