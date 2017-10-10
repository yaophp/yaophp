<?php

namespace yaophp\http;

use yaophp\App;
use yaophp\app\Pipe;
use yaophp\lib\Dicts;
use yaophp\http\route\RouteParser;
use yaophp\http\route\RouteGroup;
use yaophp\http\route\RouteObject;
use yaophp\exception\RouteException;

class Route
{
    protected static $instance;
    protected static $routeParser;
    protected static $prefix = '';
    protected static $group;
    protected static $routeObject;
    protected static $routeObjects;

    public function __construct(RouteParser $routeParser)
    {
        static::$routeParser = $routeParser;
        static::$routeObjects = new Dicts;
    }

    public function __invoke()
    {
        if (!static::$routeObject instanceof Pipe) {
            throw new RouteException("Route haven't been dispatched or handled ");
        }
        $args = func_get_args();
        return call_user_func_array([static::$routeObject, '__invoke'], $args);
    }

    public static function instance()
    {
        if (!static::$instance) {
            $app = App::instance();
            $r = static::$instance = new static($app->get(RouteParser::class));
            if (!$app->has(Route::class)) {
                $app->shared(Route::class, function() use($r){
                    return $r;
                });
            }
        }
        return static::$instance;
    }

    public static function get($route, $handler)
    {
        static::map('GET', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function post($route, $handler)
    {
        static::map('POST', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function put($route, $handler)
    {
        static::map('PUT', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function delete($route, $handler)
    {
        static::map('DELETE', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function patch($route, $handler)
    {
        static::map('PATCH', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function head($route, $handler)
    {
        static::map('HEAD', $route, $handler);
        return static::mapObject($route, $handler);
    }

    public static function match(array $methods, $route, $class)
    {
        $handler = [$class];
        foreach($methods as $method) {
            static::map($method, $route, $handler);
        }
        return static::mapObject($route, $handler);
    }

    public static function any($route, $class)
    {
        $handler = [$class];
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD'];
        foreach($methods as $method) {
            static::map($method, $route, $handler);
        }
        return static::mapObject($route, $handler);
    }

    public static function source($route, $class)
    {
        return static::group($route, function($route) use($class){
            $route->get('', [$class, 'index']);
            $route->post('/', [$class, 'post']);
            $route->match(['GET', 'PUT', 'DELETE'] , '/:id', $class);
        });
    }

    public static function group($prefix, callable $callable)
    {
        static::instance();
        $group = new RouteGroup($prefix, static::$prefix, static::$group);
        static::$prefix = $group->getPrefixNow();
        static::$group = $group;
        $callable(static::$instance);
        static::$prefix = $group->getPrefixParent();
        static::$group = $group->getGroupParent();
        return $group;
    }

    public static function domain($domain, callable $callable)
    {

    }

    public static function url($handler, array $query=[], array $params=[])
    {
        if (\is_string($handler) && \strpos($handler, '\\') !== false) {
            $handler = [$handler];
        }
        $key = static::getHandlerKey($handler);
        if (!($result = static::$routeObjects[$key])) {
            throw new RouteException("$key handler not match any route");
        }
        list($routeObject, $group) = $result;
        return $routeObject->url($query, $params);
    }

    public static function handle($handler, array $args)
    {
        $key = static::getHandlerKey($handler);
        list($routeObject, $group) = static::$routeObjects[$key];
        $routeObject->setArgs($args);
        if ($group instanceof RouteGroup) {
            $routeObject = $group->bindRunable($routeObject);
        }
        static::$routeObject = $routeObject;
    }

    protected static function map($method, $route, $handler)
    {
        if (!is_array($handler) && !\is_string($handler)) {
            throw new \RuntimeException('route handler only support for str or array type');
        }
        static::instance();
        $route = static::$prefix . $route;
        static::$routeParser->add($method, $route, $handler);
    }

    protected static function mapObject($route, $handler) {
        static::instance();
        $route = static::$prefix . $route;
        $routeObject = new RouteObject($route, $handler);
        $key =  static::getHandlerKey($handler);
        static::$routeObjects[$key] = [$routeObject, static::$group];
        return $routeObject;
    }

    protected static function getHandlerKey($handler)
    {
        if (is_array($handler)) {
            return \implode('\\', $handler);
        } else if (\is_string($handler)) {
            return ltrim(\str_replace(['/', '@'], '\\', $handler), '\\');
        }
        throw new RouteException("Route handler type must "
        . "be [class, action] or [class]  or class@action string");
    }
    
}