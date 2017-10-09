<?php
namespace yaophp\http\route;

use yaophp\http\Dispatch;
use yaophp\service\Runtime;

class RouteParser
{
    const DELIMITER = ':';
    protected $uniques = [];
    protected $temps = [];
    protected $routes = [
        'normal' => [],
        'reg' => []
    ];
    protected $done = false;
    protected $runtime;

    protected $error;

    public function __construct(Runtime $runtime)
    {
        $this->runtime = $runtime;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getAll()
    {
        $delimiter = static::DELIMITER;
        if ($this->done) {
            return $this->routes;
        }
        if (!($data = $this->runtime->get($this, 'ROUTE_OPTIMIZE_CACHE'))) {
            foreach($this->temps as $methd_route) {
                list($method, $route, $handler) = $methd_route;
                $unique = \preg_replace("/$delimiter" . "[A-Za-z0-9_]+/", $delimiter, $method . $route);
                if (isset($this->uniques[$unique])) {
                    throw new \RuntimeException("route $unique had exists");
                } else {
                    $this->uniques[$unique] = '';
                }
                if (\strpos($route, static::DELIMITER) > -1) {
                    $this->addReg($method, $route, $handler);
                } else {
                    $this->addNormal($method, $route, $handler);
                }
            }
            $this->runtime->set($this, 'ROUTE_OPTIMIZE_CACHE', $this->routes);
        } else {
            $this->routes = $data;
        }
        $this->done = true;
        return $this->routes;
    }

    public function add($method, $route, $handler)
    {
        $this->temps[] = [$method, $route, $handler];
    }

    public function has($method, $route)
    {
        $this->getAll();
        return $this->parseNormal($method, $route) ?: $this->parseReg($method, $route);
    }

    public function parseRouteArray($route)
    {
        $this->getAll();
        $routeArr = \explode('/', \ltrim($route, '/'));
        return $this->referParse($this->routes['normal'], [$route]) ?: $this->referParse($this->routes['reg'], $routeArr);
    }

    protected function addNormal($method, $route, $handler)
    {
        $this->referSet($this->routes['normal'], [$route], $method, $handler);
    }

    protected function parseNormal($method, $route)
    {
        return $this->referGet($this->routes['normal'], [$route], $method);
    }

    protected function addReg($method, $route, $handler)
    {
        $routeArr = \explode('/', \ltrim($route, '/'));
        $this->referSet($this->routes['reg'], $routeArr, $method, $handler);
    }

    protected function parseReg($method, $route)
    {
        $routeArr = \explode('/', \ltrim($route, '/'));
        return $this->referGet($this->routes['reg'], $routeArr, $method);

    }

    protected function referSet(& $array, array $keys, $method, $handler, $args=[])
    {
        if ($keys) {
            $k = \array_shift($keys);
            if (\strpos($k, static::DELIMITER) === 0) {
                $keyMap = str_replace(static::DELIMITER, '', $k);
                array_push($args, $keyMap);
                $k = static::DELIMITER;
            }
            if (!isset($array[$k])) {
                $array[$k] = [];
            }
            return $this->referSet($array[$k], $keys, $method, $handler, $args);
        }
        $fucker = $this->fucker($array, $args);
        $array[$fucker]['args'] = $args;
        $array[$fucker][$method] = $handler;
        return ;
    }

    protected function referGet(& $array, array $keys, $method, $args=[])
    {
        if ($keys) {
            $k = \array_shift($keys);
            if (isset($array[$k])) {
                return $this->referGet($array[$k], $keys, $method, $args);
            } elseif (isset($array[static::DELIMITER])) {
                $args[] = $k;
                return $this->referGet($array[static::DELIMITER], $keys, $method, $args);
            } else {
                $this->error = Dispatch::NOT_FOUND;
                return null; // NOT_FOUND
            }
        }
        $fucker = $this->fucker($array);
        if (!isset($array[$fucker][$method])) {
            $this->error = Dispatch::METHOD_NOT_ALLOWED;
            return null; // METHOD_NOT_ALLOW
        }
        if (count($array[$fucker]['args']) !== count($args)) {
            $this->error = Dispatch::NOT_FOUND;
            return null;
        }
        foreach($args as $v) {
            if (!$v) {
                $this->error = Dispatch::NOT_FOUND;
                return null;
            }
        }
        return [
            'handler' => $array[$fucker][$method],
            'args' => array_combine($array[$fucker]['args'], $args)
        ];
    }


    protected function referParse(& $array, array $keys, $output=[0 => [], 1 => []])
    {
        if ($keys) {
            $k = \array_shift($keys);
            if (isset($array[$k])) {
                $output[0][] = $k;
                return $this->referParse($array[$k], $keys, $output);
            } elseif (isset($array[static::DELIMITER])) {
                $args[] = $k;
                $output[0][] = static::DELIMITER;
                return $this->referParse($array[static::DELIMITER], $keys, $output);
            } else {
                $this->error = Dispatch::NOT_FOUND;
                return null; // NOT_FOUND
            }
        }
        $fucker = $this->fucker($array);
        $output[1] = $array[$fucker]['args'];
        return $output;

    }

    protected function fucker(& $array)
    {
        $fucker = static::DELIMITER . 'fucker';
        if (!isset($array[$fucker])) {
            $array[$fucker] = [];
        }
        return $fucker;
    }
}