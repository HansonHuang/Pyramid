<?php

/**
 * @file
 *
 * Route
 */

namespace Pyramid\Component\Route;

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
        $max = (1<<$length+1) - 2;
        $min = $strict ? (1<<$length) - 1 : 1;
        foreach (range($max,$min) as $i) {
            if ($i < (1<<$length) - 1) {
                --$length;
            }
            //thanks to wife
            $over = $i - ((1<<$length) - 1);
            $match = array();
            for ($j = 0; $j < $length; $j++) {
                $match[] = $over & (1<<$j) ? $segments[$j] : '*';
            }
            $weight = static::getWeight($match);
            $route = implode('/', $match);
            if (isset(static::$routes[$weight][$route])) {
                $return = static::$routes[$weight][$route];
                $return['path'] = implode('/', $segments);
                $return['segments'] = $segments;
                return $return;
            }
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
        foreach ($segments as $i => $segment) {
            if ($segment == '*') {
                $weight += 1 << $i;
            } elseif (substr($segment,0,1)=='{' && substr($segment,-1,1)=='}') {
                $weight += 1 << $i;
            } else {
                $weight += 1 << $i+1;
            }
        }
        return $weight;
    }
    
    //把{key}转化为*的route
    public static function changeRoute($segments) {
        foreach ($segments as $i => $segment) {
            if (substr($segment,0,1)=='{' && substr($segment,-1,1)=='}') {
                $segments[$i] = '*';
            }
        }
        return implode('/', $segments);
    }
    
    //字符串以/分段
    public static function getSegments($path) {
        return preg_split('|/|', $path, self::MAX_SEGMENTS, PREG_SPLIT_NO_EMPTY);
    }

}
