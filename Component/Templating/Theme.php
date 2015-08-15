<?php

/*
 * @file
 *
 * Theme
 
 $engines = array(
    'default' => array(
        'engine'      => 'Pyramid\Component\Templating\TwigEngine',
        'loader'      => 'Twig_Loader_Filesystem',
        'loaderArgs'  => array(),
        'environment' => 'Twig_Environment',
        'envArgs'     => array(),
    )
 );
 */
 
namespace Pyramid\Component\Templating;

class Theme {
    
    /*
     * 模板引擎存放
     *
     * @var array
     */
    protected static $engines = array();
    
    /**
     * 配置信息
     *
     * @var array()
     */
    protected static $configuration = array();
    
    /*
     * 获取engine实例
     */
    public static function getEngine($target = 'default') {
        if (!isset(self::$engines[$target])) {
            self::$engines[$target] = self::initEngine($target);
        }

        return self::$engines[$target];
    }
    
    /*
     * 初始化engine实例
     */
    public static function initEngine($target) {
        if (empty(self::$engines)) {
            self::parseEnginesInfo();
        }
        if (!empty(self::$configuration[$target]['loader'])) {
            $classLoader = self::$configuration[$target]['loader'];
        } else {
            $classLoader  = 'Twig_Loader_Filesystem';
        }
        if (!empty(self::$configuration[$target]['environment'])) {
            $classEnv = self::$configuration[$target]['environment'];
        } else {
            $classEnv  = 'Twig_Environment';
        }
        if (!empty(self::$configuration[$target]['engine'])) {
            $classEngine = self::$configuration[$target]['engine'];
        } else {
            $classEngine  = 'Pyramid\Component\Templating\TwigEngine';
        }
        $loader = new $classLoader(self::$configuration[$target]['loaderArgs']);
        $env    = new $classEnv($loader, self::$configuration[$target]['envArgs']);
        $engine = new $classEngine($env);

        return $engine;
    }
    
    /*
     * 处理engines配置
     */
    public static function parseEnginesInfo() {
        global $engines;
        $es = is_array($engines) ? $engines : array();
        $es += array('default'=>array('loaderArgs'=>array(),'envArgs'=>array()));
        self::$configuration = $es;
    }

}
