<?php

/**
 * @file
 *
 * FileLogger
 */

namespace Pyramid\Component\Logger;

use Exception;

class FileLogger {

    /*
     * 文件句柄
     * @var resource
     */
    protected $handler;
    
    /*
     * 配置
     * @var array
     */
    protected $config = array('file'=>'log');
    
    /*
     * 等级
     * @var string
     */
    protected $level = 0;
    
    /*
     * 是否可写入
     * @var bool
     */
    protected $writeAble = false;
    
    /*
     * 当前的日期作为文件名的一部分
     * @var string
     */
    protected $dateline = '';

    /**
     * 析构函数
     */
    public function __construct($config = array()) {
        if (is_array($config)) {
            $this->config = $config + $this->config;
        }
        $this->dateline = date('Ymd');
        $logfile = $this->config['file'] . '.' . $this->dateline . '.log';
        $this->handler = fopen($logfile, 'ab');
        if (is_resource($this->handler)) {
            $this->writeAble = true;
        }
        if (isset($config['level'])) {
            $this->level = (int) $config['level'];
        }
    }

    /**
     * 是否需要生成新的日志文件
     */
    public function needsRehandle() {
        if (date('Ymd') != $this->dateline) {
            $this->handler && fclose($this->handler);
            $this->dateline = date('Ymd');
            $logfile = $this->config['file'] . '.' . $this->dateline . '.log';
            $this->handler = fopen($logfile, 'ab');
            if (is_resource($this->handler)) {
                $this->writeAble = true;
            } else {
                $this->writeAble = false;
            }
        }

        return $this;
    }

    /**
     * 致命错误
     */
    public function fatal($string) {
        if ($this->level & Logger::FATAL) {
            $this->write($string, 'FATAL');
        }
    }
    
    /**
     * 一般错误
     */
    public function error($string) {
        if ($this->level & Logger::ERROR) {
            $this->write($string, 'ERROR');
        }
    }
    
    /**
     * 应用警告
     */
    public function warn($string) {
        if ($this->level & Logger::WARN) {
            $this->write($string, 'WARN');
        }
    }
    
    /**
     * 基本信息
     */
    public function info($string) {
        if ($this->level & Logger::INFO) {
            $this->write($string, 'INFO');
        }
    }
    
    /**
     * Debug
     */
    public function debug($string) {
        if ($this->level & Logger::DEBUG) {
            $this->write($string, 'DEBUG');
        }
    }
    
    /**
     * Trace
     */
    public function trace($string) {
        if ($this->level & Logger::TRACE) {
            $this->write($string, 'TRACE');
        }
    }

    /**
     * Log
     */
    public function log($string) {
        $this->write($string, 'LOG');
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        $this->handler && fclose($this->handler);
    }

    //执行写日志
    protected function write($string, $levelName = '') {
        if (!$this->writeAble) {
            return false;
        }
        $this->needsRehandle();
        flock($this->handler, LOCK_EX);
        fwrite($this->handler, sprintf("%s [%s] %s\n", date('c'), $levelName, $string));
        flock($this->handler, LOCK_UN);
    }

}
