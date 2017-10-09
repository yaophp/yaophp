<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */

namespace yaophp\http\datagram;

use InvalidArgumentException;
use yaophp\http\Environment;

class Uri
{
    protected $scheme = '';
    protected $user = '';
    protected $password = '';
    protected $host = '';
    protected $port = null;
    protected $path = '';
    protected $base_path = '';
    protected $query = '';
    protected $fragment = '';


    public function __construct(Environment $env, $uri='')
    {
        if ($uri) {
            $this->createFromString($uri);
        } else {
            $this->createFromEnvironment($env);
        }
    }
    
    public function __toString()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->getBasePath();
        $path = $this->getPath();
        $query = $this->getQuery();
        $fragment = $this->getFragment();

        $path = $basePath . '/' . ltrim($path, '/');

        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . $path
            . ($query ? '?' . $query : '')
            . ($fragment ? '#' . $fragment : '');
    }
    
    
    /********************************************************************************
     * create
     *******************************************************************************/
    protected function createFromString($str)
    {
        $parts = parse_url($str);
        $scheme = isset($parts['scheme']) ? $parts['scheme'] : '';
        $user = isset($parts['user']) ? $parts['user'] : '';
        $pass = isset($parts['pass']) ? $parts['pass'] : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? $parts['port'] : null;
        $path = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

        return $this->create($scheme, $host, $port, $path, $query, $fragment, $user, $pass);
    }
    
    protected function createFromEnvironment(Environment $env)
    {
        // Scheme
        $isSecure = $env->get('HTTPS');
        $scheme = (empty($isSecure) || $isSecure === 'off') ? 'http' : 'https';

        // Authority: Username and password
        $username = $env->get('PHP_AUTH_USER', '');
        $password = $env->get('PHP_AUTH_PW', '');

        // Authority: Host
        if ($env->has('HTTP_HOST')) {
            $host = $env->get('HTTP_HOST');
        } else {
            $host = $env->get('SERVER_NAME');
        }

        // Authority: Port
        $port = (int)$env->get('SERVER_PORT', 80);
        if (preg_match('/^(\[[a-fA-F0-9:.]+\])(:\d+)?\z/', $host, $matches)) {
            $host = $matches[1];

            if ($matches[2]) {
                $port = (int) substr($matches[2], 1);
            }
        } else {
            $pos = strpos($host, ':');
            if ($pos !== false) {
                $port = (int) substr($host, $pos + 1);
                $host = strstr($host, ':', true);
            }
        }

        // Path
        $requestScriptName = parse_url($env->get('SCRIPT_NAME'), PHP_URL_PATH);
        $requestScriptDir = dirname($requestScriptName);

        // parse_url() requires a full URL. As we don't extract the domain name or scheme,
        // we use a stand-in.
        $requestUri = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_PATH);

        $basePath = '';
        $virtualPath = $requestUri;
        if (stripos($requestUri, $requestScriptName) === 0) {
            $basePath = $requestScriptName;
        } elseif ($requestScriptDir !== '/' && stripos($requestUri, $requestScriptDir) === 0) {
            $basePath = $requestScriptDir;
        }

        if ($basePath) {
            $virtualPath = str_replace('//', '/', substr($requestUri, strlen($basePath)));
//            $virtualPath = ltrim(substr($requestUri, strlen($basePath)), '/');
        }

        // Query string
        $queryString = $env->get('QUERY_STRING', '');
        if ($queryString === '') {
            $queryString = parse_url('http://example.com' . $env->get('REQUEST_URI'), PHP_URL_QUERY);
        }

        // Fragment
        $fragment = '';

        // Build Uri
        $uri = $this->create($scheme, $host, $port, $virtualPath, $queryString, $fragment, $username, $password);
        if ($basePath) {
            $uri = $uri->withBasePath($basePath);
        }

        return $uri;
    }
    
    protected function create(
        $scheme,
        $host,
        $port = null,
        $path = '/',
        $query = '',
        $fragment = '',
        $user = '',
        $password = ''
    )
    {
        $this->scheme = $this->filterScheme($scheme);
        $this->host = $host;
        $this->port = $this->filterPort($port);
        $this->path = empty($path) ? '/' : $this->filterPath($path);
        $this->query = $this->filterQuery($query);
        $this->fragment = $this->filterQuery($fragment);
        $this->user = $user;
        $this->password = $password;
        return $this;
    }


    /********************************************************************************
     * Scheme
     *******************************************************************************/
    public function getScheme()
    {
        return $this->scheme;
    }
    
    public function withScheme($scheme)
    {
        $this->scheme = $this->filterScheme($scheme);
        return $this;
    }
    
    protected function filterScheme($scheme)
    {
        static $valid = [
            '' => true,
            'https' => true,
            'http' => true,
        ];

        if (!is_string($scheme) && !method_exists($scheme, '__toString')) {
            throw new InvalidArgumentException('Uri scheme must be a string');
        }

        $scheme = str_replace('://', '', strtolower((string)$scheme));
        if (!isset($valid[$scheme])) {
            throw new InvalidArgumentException('Uri scheme must be one of: "", "https", "http"');
        }

        return $scheme;
    }
    
    
    /********************************************************************************
     * Authority
     *******************************************************************************/
    public function getAuthority()
    {
        $userInfo = $this->getUserInfo();
        $host = $this->getHost();
        $port = $this->getPort();

        return ($userInfo ? $userInfo . '@' : '') . $host . ($port !== null ? ':' . $port : '');
    }
    
    public function getUserInfo()
    {
        return $this->user . ($this->password ? ':' . $this->password : '');
    }
    
    public function withUserInfo($user, $password = null)
    {
        $this->user = $user;
        $this->password = $password ? $password : '';
        return $this;
    }
    
    public function getHost()
    {
        return $this->host;
    }
    
    public function withHost($host)
    {
        $this->host = $host;
        return $this;
    }
    
    public function getPort()
    {
        return $this->port && !$this->hasStandardPort() ? $this->port : null;
    }
    
    public function withPort($port)
    {
        $this->port = $this->filterPort($port);
        return $this;
    }
    
    protected function hasStandardPort()
    {
        return ($this->scheme === 'http' && $this->port === 80) || ($this->scheme === 'https' && $this->port === 443);
    }
    
    protected function filterPort($port)
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            return $port;
        }

        throw new InvalidArgumentException('Uri port must be null or an integer between 1 and 65535 (inclusive)');
    }
    
    /********************************************************************************
     * Path
     *******************************************************************************/
    public function getPath()
    {
        return $this->path;
    }
    
    public function withPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Uri path must be a string');
        }

        $this->path = $this->filterPath($path);

        // if the path is absolute, then clear basePath
        if (substr($path, 0, 1) == '/') {
            $this->base_path = '';
        }

        return $this;
    }
    
    public function getBasePath()
    {
        return $this->base_path;
    }
    
    public function withBasePath($basePath)
    {
        if (!is_string($basePath)) {
            throw new InvalidArgumentException('Uri path must be a string');
        }
        if (!empty($basePath)) {
            $basePath = '/' . trim($basePath, '/'); // <-- Trim on both sides
        }

        if ($basePath !== '/') {
            $this->base_path = $this->filterPath($basePath);
        }

        return $this;
    }
    
    protected function filterPath($path)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }
    
    /********************************************************************************
     * Query
     *******************************************************************************/
    public function getQuery()
    {
        return $this->query;
    }
    
    public function withQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new InvalidArgumentException('Uri query must be a string');
        }
        $this->query = $this->filterQuery(ltrim((string)$query, '?'));

        return $this;
    }
    
    protected function filterQuery($query)
    {
        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $query
        );
    }
    
    /********************************************************************************
     * Fragment
     *******************************************************************************/
    public function getFragment()
    {
        return $this->fragment;
    }
    
    public function withFragment($fragment)
    {
        if (!is_string($fragment) && !method_exists($fragment, '__toString')) {
            throw new InvalidArgumentException('Uri fragment must be a string');
        }
        $this->fragment = $this->filterQuery(ltrim((string)$fragment, '#'));

        return $this;
    }
    
    public function getBaseUrl()
    {
        $scheme = $this->getScheme();
        $authority = $this->getAuthority();
        $basePath = $this->getBasePath();

        if ($authority && substr($basePath, 0, 1) !== '/') {
            $basePath = $basePath . '/' . $basePath;
        }

        return ($scheme ? $scheme . ':' : '')
            . ($authority ? '//' . $authority : '')
            . rtrim($basePath, '/');
    }
}