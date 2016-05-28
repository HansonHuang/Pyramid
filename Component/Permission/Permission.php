<?php

/**
 * @file
 *
 * Permission
 */

namespace Pyramid\Component\Permission;

/*
 [
    permission => array(
        'bundle'        => '分类',
        'title'         => '标题',
        'description'   => '描述',
        'quantity'      => '标量化',
        'inherited'     => '被继承',
        'warning'       => '警告信息',
    )
  ]
 */
abstract class Permission {
    
    /**
     * 权限列表
     *
     * @var array
     */
    protected static $permissions = array();
    
    /**
     * 注册权限
     * 
     * @param array $permissions
     * @param string $module
     */
    public static function register($permission, array $permissions = array()) {
        if (is_array($permission)) {
            self::$permissions += $permission;
        } else {
            self::$permissions[$permission] = $permissions;
        }
    }
    
    /**
     * 获取权限
     *
     * @param $permission array|string|null
     * @return array | $permission
     */
    public static function get($permission = null) {
        if (is_null($permission)) {
            return self::$permissions;
        } elseif (is_array($permission)) {
            return array_intersect_key(self::$permissions, $permission);
        } else {
            return isset(self::$permissions[$permission]) ? self::$permissions[$permission] : $permission;
        }
    }

}
