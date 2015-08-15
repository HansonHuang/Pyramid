<?php

/**
 * @file
 *
 * Redis Connection
 */

namespace Pyramid\Component\Redis;

use Exception;

abstract class Connection {

    /**
     * 链接
     *
     * @var array
     */
    protected static $connections = array();
    
    /**
     * 配置
     *
     * @var array
     */
    protected static $redisInfo = array();
    
    /**
     * 是否已经手工设置过配置
     *
     * @var bool
     */
    protected static $isConfiguration = false;
    
    //设置连接信息
    final public static function setConfig($target, array $config = array()) {
        if (is_string($target)) {
            self::$redisInfo[$target] = $config;            
        }
        elseif (is_array($target)) {
            self::$redisInfo = $target + self::$redisInfo;
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
        if (empty(self::$redisInfo)) {
            self::parseConnectionInfo();
        }
        $connection = new Redis();
        if (isset(self::$redisInfo[$target])) {
            $info = self::$redisInfo[$target];
        } else {
            $info = array(
                'host'      => '127.0.0.1',
                'port'      => 6379,
                'timeout'   => 0,
                'database'  => 0,
                'password'  => '',
                'options'   => array(
                    
                ),
            );
        }
        try {
            $connection->connect($info['host'], $info['port'], $info['timeout']);
            if ($info['password']) {
                $connection->auth($info['password']);
            }
            $connection->select($info['database']);
            foreach ($info['options'] as $k => $v) {
                $connection->setOption($k, $v);
            }
        } catch (Exception $e) {
            $connection = null;
        }
        
        return $connection;
    }
    
    //关闭链接
    public static function closeConnection($target = null) {
        if (isset($target)) {
            if (isset(self::$connections[$target])) {
                self::$connections[$target]->close();
                self::$connections[$target] = null;
                unset(self::$connections[$target]);
            }
        }
        else {
            foreach (self::$connections as $target => $connection) {
                self::$connections[$target]->close();
                self::$connections[$target] = null;
                unset(self::$connections[$target]);
            }
        }
    }
    
    //解析配置信息
    final public static function parseConnectionInfo() {
        global $redises;
        if (!self::$isConfiguration) {
            $redisInfo = is_array($redises) ? $redises : array();        
            self::$redisInfo = $redisInfo;
        }
    }

}
