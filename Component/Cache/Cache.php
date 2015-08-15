<?php

/**
 * @file
 *
 * 缓存类
 */

namespace Pyramid\Component\Cache;

abstract class Cache {
    
    const OPT_PREFIX_KEY = -1002;

    /**
     * 缓存链接
     *
     * @var array
     */
    static protected $connections = array();
    
    /**
     * 缓存配置
     *
     * @var array
     */
    static protected $cacheInfo = array();
    
    /**
     * 是否已经手工设置过配置
     *
     * @var bool
     */
    static protected $isConfiguration = false;
    
    //设置连接信息
    final public static function setConfig($target, array $config = array()) {
        if (is_string($target)) {
            self::$cacheInfo[$target] = $config;            
        }
        elseif (is_array($target)) {
            self::$cacheInfo = $target + self::$cacheInfo;
        }
        self::$isConfiguration = true;
    }
    
    //获取链接
    final public static function getConnection($target = 'default') {
        if (!isset(self::$connections[$target])) {
            self::$connections[$target] = self::openConnection($target);
        }

        return self::$connections[$target];
    }
    
    //打开链接
    final public static function openConnection($target) {
        static $mem;
        if (!isset($mem)) {
            if (class_exists('\\Memcached')) {
                $mem = 'Pyramid\Component\Cache\MemcachedCache';
            } elseif (class_exists('\\Memcache')) {
                $mem = 'Pyramid\Component\Cache\MemcacheCache';
            } else {
                $mem = 'Pyramid\Component\Cache\EmptyCache';
            }
        }
        
        if (empty(self::$cacheInfo)) {
            self::parseConnectionInfo();
        }
        $connection = new $mem();
        if (is_array(self::$cacheInfo[$target]['servers'])) {
            $connection->addServers(self::$cacheInfo[$target]['servers']);
        }
        else {
            $connection->addServer('127.0.0.1', 11211, 0);
        }
        if (!empty(self::$cacheInfo[$target]['prefix'])) {
            $connection->setOption(self::OPT_PREFIX_KEY, self::$cacheInfo[$target]['prefix']);
        }

        return $connection;
    }
    
    //关闭链接
    public static function closeConnection($target = null) {
        if (isset($target)) {
            if (isset(self::$connections[$target])) {
                self::$connections[$target]->quit();
                self::$connections[$target] = null;
                unset(self::$connections[$target]);
            }            
        }
        else {
            foreach (self::$connections as $target => $connection) {
                self::$connections[$target]->quit();
                self::$connections[$target] = null;
                unset(self::$connections[$target]);
            }
        }
    }
    
    //解析配置信息
    final public static function parseConnectionInfo() {
        global $caches;
        if (!self::$isConfiguration) {
            $cacheInfo = is_array($caches) ? $caches : array();        
            self::$cacheInfo = $cacheInfo;
        }
    }

}
