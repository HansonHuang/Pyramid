<?php

/**
 * @file
 *
 * 配置
 */

use Pyramid\Component\Config\Config;

/**
 * 获取配置类
 *
 * @param string $target
 *   配置目标
 */
function config($target = 'default') {
    static $objects = array();
    
    if (!isset($objects[$target])) {
        $objects[$target] = new Config();
    }

    return $objects[$target];
}

/*
 * @usage
 *
 * 存储一个配置
 *   config()->set($key, $value);
 *
 * 检索一个配置
 *   config()->get($key);
 *
 * 删除一个配置
 *   config()->delete($key);
 *
 * 存储多个配置
 *   config()->setMulti($items);
 *
 * 检索多个配置
 *   config()->getMulti($keys);
 *
 * 删除多个配置
 *   config()->deleteMulti($keys);
 *
 * 作废配置中的所有元素
 *   config()->flush();
 *
 * 是否有这个元素
 *   config()->hasKey($key);
 *
 */
