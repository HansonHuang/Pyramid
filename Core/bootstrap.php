<?php

/**
 * @file
 *
 * 核心框架启动
 *
 * -----------------------
 * 启动流程:
 * step1 环境设置
 * step2 内核
 * step3 公共代码资源
 * step4 缓存支持
 * step5 数据库支持
 * step6 应用级配置
 * step7 SESSION 
 * step8 完全启动
 * -----------------------
 *
 */

use Pyramid\Component\ClassLoader\ClassLoader;
use Pyramid\Component\Utility\Timer;

/**
 * 最低PHP版本要求
 */
const PHP_REQUIRED = '5.3.8';

/**
 * 框架启动
 */
function bootstrap($code = 255) {
    $boots = array(
        '1'   => 'bootstrap_configuration',
        '2'   => 'bootstrap_kernel',
        '4'   => 'bootstrap_common',
        '8'   => 'bootstrap_cache',
        '16'  => 'bootstrap_database',
        '32'  => 'bootstrap_variable',
        '64'  => 'bootstrap_session',
        '128' => 'bootstrap_full',
    );
    class_loader();
    foreach ($boots as $key => $func) {
        if ($code & $key) {
            $func();
        }
    }
}

//step1 环境设置
function bootstrap_configuration() {
    if (version_compare(PHP_VERSION, PHP_REQUIRED, '<')) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 PHP_VERSION');
        exit;
    }
    if (get_magic_quotes_gpc() || get_magic_quotes_runtime()) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 500 magic_quotes');
        exit;
    }
    date_default_timezone_set('PRC');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cache_limiter', '');        
    timer_start();
    set_error_handler('pyramid_error_handler');
    set_exception_handler('pyramid_exception_handler');
}

//step2 内核
function bootstrap_kernel() {
    require_once __DIR__ . '/kernel.php';
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/logger.php';
}

//step3 公共代码资源
function bootstrap_common() {
    require_once __DIR__ . '/common.php';
}

//step4 缓存支持
function bootstrap_cache() {
    require_once __DIR__ . '/cache.php';
}

//step5 数据库支持
function bootstrap_database() {
    require_once __DIR__ . '/database.php';
}

//step6 应用级配置
function bootstrap_variable() {
    require_once __DIR__ . '/variable.php';
}

//step7 SESSION
function bootstrap_session() {
    require_once __DIR__ . '/session.php';
}

//step8 完全启动
function bootstrap_full() {
    require_once __DIR__ . '/entity.php';
    require_once __DIR__ . '/theme.php';
}

//类自动注册器
function class_loader() {
    static $loader;    

    if (!isset($loader)) {
        $dir = dirname(dirname(__DIR__));
        require_once $dir . '/Pyramid/Component/ClassLoader/ClassLoader.php';
        $loader = new ClassLoader();
        $loader->registerNamespace('Pyramid', $dir);
        $loader->registerPrefix('Twig_', $dir.'/Pyramid/Vendor');
        $loader->register();
    }
    
    return $loader;
}

//错误接管
function pyramid_error_handler($error_level, $message, $filename, $line, $context) {
    static $types = array(
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core error',
        E_CORE_WARNING      => 'Core warning',
        E_COMPILE_ERROR     => 'Compile error',
        E_COMPILE_WARNING   => 'Compile warning',
        E_USER_ERROR        => 'User error',
        E_USER_WARNING      => 'User warning',
        E_USER_NOTICE       => 'User notice',
        E_STRICT            => 'Strict warning',
        E_RECOVERABLE_ERROR => 'Recoverable fatal error',
        E_DEPRECATED        => 'Deprecated function',
        E_USER_DEPRECATED   => 'User deprecated function',
    );

    if ($error_level & error_reporting()) {
        logger()->warn($types[$error_level].' '.$message.' (at line '.$line.' in file '.$filename.')');
    }

    return true;
}

//异常接管
function pyramid_exception_handler($e) {
    try {
        logger()->warn($e->getMessage().' (at line '.$e->getLine().' in file '.$e->getFile().')');
    }
    catch (Exception $exception) {}  
}

/**
 * 开始计时器
 *
 * @param $name
 *   计时名
 */
if (!function_exists('timer_start')) {
    function timer_start($name = 'default') {
        Timer::start($name);
    }
}

/**
 * 读取计时器
 *
 * @param $name
 *   计时名
 *
 * @return
 *  时间(ms)
 */
if (!function_exists('timer_read')) {
    function timer_read($name = 'default') {
        return Timer::read($name);
    }
}

/**
 * 停止计时器
 *
 * @param $name
 *   计时名
 *
 * @return array
 *   时间数组(ms)
 */
if (!function_exists('timer_stop')) {
    function timer_stop($name = 'default') {
        return Timer::stop($name);
    }
}