<?php

/**
 * @file
 *
 * Watchdog Storage
 */

namespace Pyramid\Component\Watchdog;

class Storage {

    protected static $config = array(
        'storage' => 'file',
        'target'  => '.',
    );
    
    protected static $handlers = array();    
    
    public static function write($type, $data) {        
        static::getWriteHandler($type, 'ab')->write($data);        
    }

    public static function setConfig(array $config = array()) {
        $config += array(
            'storage' => 'file',
            'target'  => '.',
        );
        static::$config = $config;
    }
    
    protected static function getWriteHandler($type, $mode = 'rb') {
        static $num = 0;
        $daily = date('Ymd');
        if (!isset(static::$handlers[$daily][$type])) {
            switch (static::$config['storage']) {
                case 'file':
                    $file = static::getStorageFile($type, $daily);
                    static::$handlers[$daily][$type] = new FileStorage($file, $mode);
                    break;
                case 'database':
                    static::$handlers[$daily][$type] = new DatabaseStorage(static::$config['target']);
                    break;
                default:
                    static::$handlers[$daily][$type] = new DatabaseStorage();
            }
        }
        $num++;
        if ($num > 10) {
            $num = 0;
            $yesterday = date('Ymd',strtotime('-1 day'));
            if (isset(static::$handlers[$yesterday])) {
                unset(static::$handlers[$yesterday][$type]);
                unset(static::$handlers[$yesterday]);
            }
        }
        
        return static::$handlers[$daily][$type];
    }
    
    public static function getStorageFile($type, $daily) {
        return static::$config['target'] . "/{$type}_{$daily}.log";
    }

}
