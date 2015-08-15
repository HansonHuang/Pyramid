<?php

/**
 * @file
 *
 * Logger
 */

use Pyramid\Component\Logger\Logger;

/**
 * 配置Logger类
 *
 * @param string $target
 */
function logger_config($target, $config = array()) {
    return Logger::setConfig($target, $config);
}

/**
 * 获取Logger类
 *
 * @param string $target
 */
function logger($target = 'default') {
    return Logger::getLogger($target);
}

/*
 * @usage
 *
 * 致命错误
 *   logger()->fatal($string);
 *
 * 错误
 *   logger()->error($string);
 *
 * 警告
 *   logger()->warn($string);
 *
 * 常规
 *   logger()->info($string);
 *
 * DEBUG
 *   logger()->debug($string);
 *
 * 调试
 *   logger()->trace($string);
 *
 * 日志
 *   logger()->log($string);
 */
