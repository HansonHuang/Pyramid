<?php

/**
 * @file
 *
 * Uuid
 */

namespace Pyramid\Component\Uuid;

class Uuid {

    /**
     * 生成一个uuid
     */
    public static function generate() {
        static $generator;
        
        if (!isset($generator)) {
            if (function_exists('uuid_create') && !function_exists('uuid_make')) {
                $class = 'Pyramid\Component\Uuid\Pecl';
            } elseif (function_exists('com_create_guid')) {
                $class = 'Pyramid\Component\Uuid\Com';
            } else {
                $class = 'Pyramid\Component\Uuid\Php';
            }
            $generator = new $class();
        }
        
        return $generator->generate();
    }
    
    /**
     * 验证是否符合uuid规范
     */
    public static function isValid($uuid) {
        return preg_match("/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/", $uuid);
    }

}
