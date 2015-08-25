<?php

/**
 * @file
 *
 * Route
 */

namespace Pyramid\Component\Route;

/**
 * 返回的格式
    {
      baseroute  : 注册时的路由
      matchroute : 实际匹配的路由
      weight     : 权重
      callback   : 回调函数
      segments   : 请求的路径数组
      path       : 请求的路径
    }
 */
abstract class Route {

    /**
     * 最大分段数
     */
    const MAX_SEGMENTS = 8;

    /**
     * 路由数据表
     */
    protected static $routes = array();

    /**
     * 注册route
     */
    public static function register($route, $info) {
        if (is_array($route)) {
            foreach ($route as $r) {
                self::registerRoute($r, $info);
            }
        } else {
            self::registerRoute($route, $info);
        }
    }

    /**
     * 执行注册Route
     */
    public static function registerRoute($route, $info) {
        $segments = static::getSegments($route);
        $route    = implode('/', $segments);
        $weight   = static::getWeight($segments);
        $matchroute = static::changeRoute($segments);
        if (!isset(static::$routes[$weight][$matchroute])) {
            if (is_array($info)) {
                $info += array(
                    'baseroute'  => $route,
                    'matchroute' => $matchroute,
                    'weight'     => $weight,
                );
            } else {
                $info = array(
                    'baseroute'  => $route,
                    'matchroute' => $matchroute,
                    'weight'     => $weight,
                    'callback'   => $info,
                );
            }
            static::$routes[$weight][$matchroute] = $info;
        }
    }

    /**
     * 匹配route
     */
    public static function match($path, $strict = false) {
        $segments = static::getSegments($path);
        $length = count($segments);
        $max = (1<<$length) - 1;
        $min = $strict ? 1<<max(0,$length-1) : 1;
        foreach (range($max,$min) as $i) {
            if ($i < (1<<$length-1)) {
                --$length;
            }
            $match = array();
            for ($j = 0; $j < $length; $j++) {
                $match[] = $i & (1<<$length-$j-1) ? $segments[$j] : '*';
            }
            $weight = static::getWeight($match);
            $route = implode('/', $match);
            if (isset(static::$routes[$weight][$route])) {
                $info = static::$routes[$weight][$route];
                return static::prepareRoute($info, $segments);
            }
        }
        if (isset(static::$routes[0]['*'])) {
            $info = static::$routes[0]['*'];
            return static::prepareRoute($info, $segments);
        }
    }

    /**
     * 读route
     */
    public static function get($route = null) {
        if (!isset($route)) {
            return static::$routes;
        } else {
            $segments = static::getSegments($route);
            $route = static::changeRoute($segments);
            foreach (static::$routes as $routes) {
                if (isset($routes[$route])) {
                    return $routes[$route];
                }
            }
        }
    }

    /**
     * 删route
     */
    public static function delete($route) {
        $segments = static::getSegments($route);
        $route = static::changeRoute($segments);
        foreach (static::$routes as $weight => $routes) {
            if (isset($routes[$route])) {
                unset(static::$routes[$weight][$route]);
            }
        }
    }

    /**
     * 计算权重
     */
    public static function getWeight($segments) {
        $weight = 0;
        $count  = count($segments);
        foreach ($segments as $i => $segment) {
            if ($segment == '*') { continue; }
            if (substr($segment,0,1)=='{' && substr($segment,-1,1)=='}') { continue; }
            $weight += 1 << ($count-$i-1);
        }
        return $weight;
    }
    
    /**
     * 把{key}转化为*
     */
    public static function changeRoute($segments) {
        foreach ($segments as $i => $segment) {
            if (substr($segment,0,1)=='{' && substr($segment,-1,1)=='}') {
                $segments[$i] = '*';
            }
        }
        return implode('/', $segments);
    }
    
    /**
     * 返回之前增加一些数据
     */
    public static function prepareRoute($info, $segments) {
        $info['segments'] = $segments;
        $info['path'] = implode('/', $segments);
        return $info;
    }
    
    //字符串以/分段
    public static function getSegments($path) {
        return preg_split('|/|', $path, self::MAX_SEGMENTS, PREG_SPLIT_NO_EMPTY);
    }

}
