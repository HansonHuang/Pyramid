<?php

/**
 * @file
 *
 * Permission
 */

namespace Pyramid\Component\Permission;

/*
    permission => array(
        'title'         => '标题',
        'description'   => '描述',
        'module'        => '模块',
        'quantity'      => '标量化',
        'inherited'     => '被继承',
        'warning'       => '警告信息',
    )
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
    public static function register(array $permissions = array(), $module = '') {
        foreach ($permissions as $permission => $v) {
            if (!isset($permissions[$permission]['module'])) {
                $permissions[$permission]['module'] = $module;
            }
        }
        self::$permissions += $permissions;
    }
    
    /**
     * 获取权限
     *
     * @param array|string|null $module
     * @return array
     */
    public static function get($module = null) {
        if (isset($module) && is_string($module)) {
            $return = array();
            foreach (self::$permissions as $permission => $v) {
                if ($v['module'] == $module) {
                    $return[$permission] = $v;
                }
            }
            return $return;
        } elseif (isset($module) && is_array($module)) {
            $return = array();
            foreach (self::$permissions as $permission => $v) {
                if (in_array($v['module'], $module)) {
                    $return[$permission] = $v;
                }
            }
            return $return;
        } else {
            return self::$permissions;
        }
    }
    
    /**
     * 获取模块列表
     *
     * @return array
     */
    public static function getModules() {
        $modules = array();
        foreach (self::$permissions as $permission => $v) {
            if ($v['module']) {
                $modules[$v['module']] = 1;
            }
        }
        return array_keys($modules);
    }

}
