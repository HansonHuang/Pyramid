<?php

/**
 * @file
 *
 * Logger
    $loggers = array(
        'default' => array(
            'level' => 'info',
            'file'  => 'log',
            'class' => 'Pyramid\Component\Logger\FileLogger',
        )
    )
 */

namespace Pyramid\Component\Logger;

class Logger {

    //等级
    const FATAL = 1;
    const ERROR = 2;
    const WARN  = 4;
    const INFO  = 8;
    const DEBUG = 16;
    const TRACE = 32;

    protected static $levels = array(
        'fatal' => self::FATAL,
        'error' => self::ERROR,
        'warn'  => self::WARN,
        'info'  => self::INFO,
        'debug' => self::DEBUG,
        'trace' => self::TRACE,
    );
    
    /*
     * Logger实例存放器
     *
     * @var array
     */
    protected static $loggers = array();
    
    /**
     * Logger配置信息
     *
     * @var array()
     */
    protected static $configuration = array();
    
    /*
     * 获取Logger实例
     */
    public static function getLogger($target = 'default') {
        if (!isset(self::$loggers[$target])) {
            self::$loggers[$target] = self::initLogger($target);
        }

        return self::$loggers[$target];
    }
    
    /*
     * 获取Logger实例
     */
    public static function initLogger($target) {
        if (empty(self::$configuration)) {
            self::parseLoggerInfo();
        }
        if (isset(self::$configuration[$target]['class'])) {
            $class  = self::$configuration[$target]['class'];
        } else {
            $class  = 'Pyramid\Component\Logger\EmptyLogger';
        }
        $logger = new $class(self::$configuration[$target]);

        return $logger;
    }
    
    /*
     * 处理logger配置
     */
    public static function parseLoggerInfo() {
        global $loggers;
        $logs = is_array($loggers) ? $loggers : array();
        $logs += array('default'=>array());
        foreach ($logs as $key => $log) {
            if (isset($log['level']) && is_string($log['level'])) {
                $logs[$key]['level'] = 2 * self::$levels[$log['level']] - 1;
            }
        }
        self::$configuration = $logs;
    }

    /*
     * 直接logger配置
     */
    public static function setConfig($target, array $config = array()) {
        if (is_string($target)) {
            self::$configuration[$target] = $config;            
        } elseif (is_array($target)) {
            self::$configuration = $target + self::$configuration;
        }
        foreach (self::$configuration as $key => $log) {
            if (isset($log['level']) && is_string($log['level'])) {
                self::$configuration[$key]['level'] = 2 * self::$levels[$log['level']] - 1;
            }
        }
    }

}
