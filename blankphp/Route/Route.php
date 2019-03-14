<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/10
 * Time: 21:32
 */

namespace Blankphp\Route;

use Blankphp\Application;
use \Blankphp\Kernel\Contract\Container;
use Blankphp\Route\Contract\Route as Contract;
use Blankphp\Route\Traits\ResolveSomeDepends;


class Route implements Contract
{
    use ResolveSomeDepends;
    public static $verbs = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
    protected $route;
    protected $app;
    protected $container;
    protected $controller;
    protected $controllerNamespace;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function get($uri, $action)
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function delete($uri, $action)
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function put($uri, $action)
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function post($uri, $action)
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function any($uri, $action)
    {
        return $this->addRoute(self::$verbs, $uri, $action);
    }

    public function addRoute($methods, $uri, $action)
    {
        foreach ($methods as $method) {
            $this->route[$method][$uri] = $action;
        }
        return $this;
    }

    public function middleware($group)
    {
        $this->controllerNamespace = $group;
        return $this;
    }

    public function group($file)
    {
        require $file;
    }

    public function setNamespace($namespace)
    {
        $this->controllerNamespace = $namespace;
        return $this;
    }

    public function findRoute($request)
    {
        //判断方法
        $method = $request->method;
        //获取访问的uri
        $uri = $request->uri;
        if (isset($this->route[$method][$uri])) {
            //获取控制器
            $controller = $this->getController($this->route[$method][$uri]);
            return $controller;
        }
        throw new \Exception('该路由暂无控制器', 5);
    }

    public function getController($controller)
    {
        //如过传递的是闭包
        if ($controller instanceof \Closure)
            return array('Closure', $controller);
        //如果不是闭包
        $controller = explode('@', $controller);
        $controllerName = !is_null($controller[0]) ? $this->controllerNamespace . '\\' . $controller[0] : '';
        $method = !is_null($controller[1]) ? $controller[1] : '';
        if (!is_null($controllerName) || !is_null($method))
            return array($controllerName, $method);
        throw new \Exception('控制器方法错误', 4);
    }


    public function runController($controller, $method)
    {
        $parameters = $this->resolveClassMethodDependencies(
            [], $controller, $method
        );
        if ($controller === 'Closure')
            return $method(...array_values($parameters));
        //解决方法的依赖
        $controller = $this->app->build($controller);
        //获取控制器的对象,返回结果
            return $controller->{$method}(...array_values($parameters));
    }


    public function run($request)
    {
        //路由分发
        return $this->runController(...$this->findRoute($request));
    }


}