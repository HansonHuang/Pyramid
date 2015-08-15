<?php

/**
 * @file
 *
 * 缓存
 */

use Pyramid\Component\Cache\Cache;

/**
 * 设置缓存信息
 *
 * @param string $target
 * @param array  $config
 */
function cache_config($target, array $config = array()) {
    Cache::setConfig($target, $config);
}

/**
 * 获取缓存类
 *
 * @param string $target
 *   缓存目标
 */
function cache($target = 'default') {
    return Cache::getConnection($target);
}

/*
 * @usage
 *
 * 存储一个元素
 *   cache()->set($key, $value, $expiration = 0);
 *
 * 检索一个元素
 *   cache()->get($key);
 *
 * 删除一个元素
 *   cache()->delete($key, $time = 0);
 *
 * 存储多个元素
 *   cache()->setMulti($items, $expiration = 0);
 *
 * 检索多个元素
 *   cache()->getMulti($keys);
 *
 * 删除多个元素
 *   cache()->deleteMulti($keys, $time = 0);
 *
 * 作废缓存中的所有元素
 *   cache()->flush($delay = 0);
 *
 *
 */
