<?php

/**
 * @file
 *
 * 应用配置
 */

use Pyramid\Component\Variable\Variable;

/**
 * 获取配置类
 */
function variable() {
    static $object;
    
    if (!isset($object)) {
        $object = new Variable();
    }

    return $object;
}

/*
 * @usage
 *
 * 存储一个配置
 *   variable()->set($key, $value);
 *
 * 检索一个配置
 *   variable()->get($key);
 *
 * 删除一个配置
 *   variable()->delete($key);
 *
 * 存储多个配置
 *   variable()->setMulti($items);
 *
 * 检索多个配置
 *   variable()->getMulti($keys);
 *
 * 删除多个配置
 *   variable()->deleteMulti($keys);
 *
 * 是否有这个元素
 *   config()->hasKey($key);
 *
 */
