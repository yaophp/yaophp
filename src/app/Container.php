<?php

/* 
 * +----------------------------------------------------------------------
 * | yao-[ FOR SAVING TIME ].
 * +----------------------------------------------------------------------
 * | Author: yao <YaoPHP@163.com> 2017
 * +----------------------------------------------------------------------
 */
    
/*
 * 注意：所有依赖注入的类默认都使用缓存的共享实例 $this->get()
 * 如果是要依赖一个独立的实例，手动$this->getNew();
 * 
 */

namespace yaophp\app;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use ReflectionException;
use InvalidArgumentException;
use yaophp\lib\Dicts;
use yaophp\exception\ContainerException;

class Container
{
    const NORMAL = 1;
    const SHARE = 2;
    
    protected static $instance;
    protected static $normal;
    protected static $shared;
    protected static $alias;
    protected static $interface;
    protected static $constructors = [];

    protected function __construct()
    {
        static::$normal = new Dicts;
        static::$shared = new Dicts;
        static::$alias = new Dicts;
        static::$interface = new Dicts;
    }
    
    protected function __clone()
    {
        $class = get_class($this);
        throw new RuntimeException("not support for clone $class");
    }
    
    public function __toString()
    {
        ;
    }
    
    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }
        return static::$instance;
    }
    
    public function has($class)
    {
        return static::$shared[$class];
    }

    
    public function setInterface($interface, $class)
    {
        $old = static::$interface->get($interface);
        if ($old && $old != $class) {
            unset(static::$normal[$interface]);
            unset(static::$shared[$interface]);
        }
        static::$interface[$interface] = $class;
        return $this;
    }
    
    public function hasInterface($interface)
    {
        return static::$interface[$interface];
    }
    
    public function getInterfaceInstance($interface)
    {
        if (!($class = static::$interface->get($interface))) {
            throw new ContainerException("interface: $interface had not"
                    . " binded any implemented class");
        }
        $instance = $this->get($class);
        if (! $instance instanceof $interface) {
            throw new ContainerException("interface: $interface binded a bad class "
                    . "that not implements it");
        }
        return $instance;
    }

    public function get($class)
    {
        $class = static::$alias[$class] ?: $class;
        $obj = static::$shared[$class] ?: static::$normal[$class] ?: null;
        if (!$obj) {
            $obj = static::$shared[$class] = $this->newClass($class);
        } elseif ($obj instanceof \Closure) {
            // $obj = call_user_func($obj);
            $obj = $this->invokeMethod($obj, '__invoke', []);
        }
        return $obj;
    }
    
    public function getNew($class, array $args=[])
    {
        return $this->newClass($class, $args);
    }
    
    public function register($class, \Closure $closure)
    {
        // static::$shared[$class] = call_user_func($closure);
        static::$shared[$class] = $this->invokeMethod($closure, '__invoke', []);
        return static::$instance;
    }
    
    public function shared($class, \Closure $closure)
    {
        // static::$shared[$class] = call_user_func($closure);
        static::$shared[$class] = $this->invokeMethod($closure, '__invoke', []);
        return static::$instance;
    }

    public function normal($class, \Closure $closure)
    {
        static::$normal[$class] = $closure;
        return static::$instance;
    }
    
    public function alias(array $class_alias)
    {
        foreach($class_alias as $class => $alias) {
            static::$alias[$alias] = $class;
        }
        return static::$instance;
    }

    public function alias2(array $alias_class)
    {
        foreach ($alias_class as $alias => $class) {
            static::$alias[$alias] = $class;
        }
        return static::$instance;
    }
    
    protected function newClass($class, array $args=[])
    {
        try {
            $ref_class = new ReflectionClass($class);
            if (!$ref_class->isInstantiable()) {
                return $this->reflectNewClassLastTry($ref_class, $args);
            } elseif (!$ref_class->hasMethod('__construct')) {
                return new $class;
            }
            $parse_args = $this->parseMethod($class, '__construct', $args);
            return $ref_class->newInstanceArgs($parse_args);
        } catch (ReflectionException $e) {
            // todo
            throw $e;
        }
    }

    protected function reflectNewClassLastTry(ReflectionClass $ref_class, array $args)
    {
        $class = $ref_class->name;
        if ($ref_class->isInterface()) {
            return $this->getInterfaceInstance($class);
        } elseif (($method = $this->reflectOtherConstructor($ref_class, static::$constructors))) {
            $parse_args = $this->parseMethod($class, $method, $args);
            return call_user_func_array([$class, $method], $parse_args);
        } elseif ($ref_class->isAbstract()) {
            throw new ContainerException("$class is abstract class");
        }
        throw new ContainerException("$class not allowed to get an instance");
    }
    
    protected function reflectOtherConstructor(ReflectionClass $ref_class, array $constructors=[])
    {
        foreach($constructors as $method) {
            if ($ref_class->hasMethod($method)) {
                $ref_method = $ref_class->getMethod($method);
                if ($ref_method->isStatic() && $ref_method->isPublic()) {
                    return $method;
                }
            }
        }
        $m = 'public __construct ';
        if ($constructors) {
            $m .= implode(' or static ', $constructors);
        } else {
            $m .= 'or add other static method to get instance';
        }
        throw new ContainerException("class $ref_class->name has no $m");
    }

    
    public function invokeMethod($class, $method, array $args=[])
    {
        $obj = is_object($class) ? $class : $this->get($class);
        return call_user_func_array(
                    [$obj, $method],
                    $this->parseMethod($class, $method, $args)
                );
    }
    
    protected function parseMethod($class, $method, array $args=[])
    {
        $ref_method = new ReflectionMethod($class, $method);
        $inject = [];
        foreach ($ref_method->getParameters() as $params) {
            $cls = $params->getClass();
            if ($cls) {
                if ($cls->name == 'Closure') {
                    throw new RuntimeException("$class has Closure param  !!!");
                }
                $inject[] = $this->get($cls->name);
            } else {
                $name = $params->getName();
                if (!$args) {
                    if (!$params->isOptional()) {
                        throw new InvalidArgumentException("$class ->$method() needs"
                                . " a not null \$$name ");
                    }
                    break;
                }
                if (isset($args[$name])) {
                    $inject[] = $args[$name];
                    unset($args[$name]);
                } else {
                    $inject[] = array_shift($args);
                }
            }
        }
        return array_merge($inject, $args);
    }
}