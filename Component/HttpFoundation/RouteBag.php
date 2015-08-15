<?php

/*
 * @file
 *
 * RouteBag
 */
 
namespace Pyramid\Component\HttpFoundation;

use Pyramid\Component\Route\Route;

class RouteBag extends ParameterBag {
    
    /*
     * 常用数据
     * @var array
     */
    protected $route = array(
        'baseroute'  => '',
        'matchroute' => '',
        'weight'     => 0,
        'callback'   => false,
        'path'       => '',
        'segments'   => array(),
    );
    
    /**
     * 析构函数
     *
     * @param array  $route
     */
    public function __construct($route = array()) {
        $this->prepare($route);
    }

    //获取键值
    function get($key = null, $default = null) {
        if ($key === null) {
            return $this->route;
        }
        return isset($this->route[$key]) ? $this->route[$key] : $default; 
    }
    
    //初始化route
    protected function prepare($route) {
        $this->route  = (array) $route + $this->route;
        $this->params = $this->route['segments'];
        $segments = Route::getSegments($this->route['baseroute']);
        foreach ($segments as $i => $segment) {
            if (substr($segment,0,1)=='{' && substr($segment,-1,1)=='}') {
                $key = trim($segment, '{}');
                $this->setParameter($key, $this->getParameter($i));
            }
        }
    }

}
