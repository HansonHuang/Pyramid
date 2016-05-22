<?php

/**
 * @file
 *
 * 一些配置的参考
 */


//配置: session
$sessions = array(
    'prefix' => '',
    'expire' => 0,
    'class'  => 'Pyramid\Component\Session\PhpSession',
    'path'   => '', //phpsess存储路径
);

//配置: 数据库
$databases = array(
    'default' => array(
        'host'      => '127.0.0.1',
        'port'      => 3306,
        'database'  => 'dbname',
        'username'  => 'user',
        'password'  => 'pass',
        'prefix'    => '',
        //'charset'   => 'utf8mb4', //MySQL5.5.3+
    ),
);

//配置: 日志记录 (默认EmptyLogger)
$loggers = array(
    'default' => array(
        'class' => 'Pyramid\Component\Logger\FileLogger',
        'level' => 'debug',
        'file' => '/tmp/log_default',
    ),
    'system' => array(
        'class' => 'Pyramid\Component\Logger\FileLogger',
        'level' => 'debug',
        'file' => '/tmp/log_system',
    ),
);

//配置: 模板引擎 (默认twig)
$engines = array(
    'default' => array(
        'engine'      => 'Pyramid\Component\Templating\PhpEngine',
        'loader'      => 'Pyramid\Component\Templating\Php\Loader',
        'environment' => 'Pyramid\Component\Templating\Php\Environment',
        'loaderArgs' => array(), //array(模板文件路径, 其他路径)
        'envArgs'    => array(), //@see Pyramid\Vendor\Twig\Environment.php L90-98
    ),
);

//配置: redises
$redises = array(
    'default' => array(
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'timeout'   => 0,
        'database'  => 0,
        'password'  => '',
        'options'   => array(),
    ),
);

//配置: memcache(d)缓存
$caches = array(
    'default' => array(
        'prefix'  => '',
        'servers' => array(
            array('127.0.0.1', 11211, 0),
        ),
    ),
);
