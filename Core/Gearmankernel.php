<?php

/**
 * @file
 *
 * Kernel
 */

use Pyramid\Component\Reflection\Reflection;
use Pyramid\Component\Gearman\Daemon;
use Pyramid\Component\Gearman\Worker;
use Pyramid\Component\Gearman\Client;
use Pyramid\Component\Gearman\Request;
use Pyramid\Component\Gearman\Response;

//Request
function request($request = NULL) {
    return new Request($request);
}

//Response
function response($data = NULL, $variables = array(), $headers = array(), $status = 0, $message = NULL) {
    return new Response($data, $variables, $headers, $status, $message);
}

/**
 * Gearman客户端调用
 *
 * @param string $api
 *   job名 类似 ToBusiness_Industry_load
 * @param string $args
 *   类似前端call的数据
 */
function gearman_invoke($api, array $args = array(), $target = 'client') {
    $start  = microtime(TRUE);
    $api    = strtr($api, '\\', '_');
    $result = gearman_client($target, $api)->doNormal($api, serialize($args));
    $return = $result ? unserialize($result) : FALSE;
    $timer  = round((microtime(TRUE) - $start) * 1000, 2);
    $jobstr = $api;
    if (isset($args['request']['c'])) {
        $jobstr .= '_' . $args['request']['c'];
    }
    if (isset($args['request']['m'])) {
        $jobstr .= '_' . $args['request']['m'];
    }
    logger('daemon')->info('[ClientCall] ' . $jobstr . ' takes ' . $timer . ' ms');
    $data      = isset($return['response']['results']) ? $return['response']['results'] : NULL;
    $variables = isset($return['response']['variables']) ? (array) $return['response']['variables'] : array();
    $headers   = isset($return['header']) ? (array) $return['header'] : array();
    $status    = isset($return['response']['err_no']) ? $return['response']['err_no'] : 404;
    $message   = isset($return['response']['err_msg']) ? $return['response']['err_msg'] : NULL;
    
    return new Response($data, $variables, $headers, $status, $message);
}

/**
 * 代码级的模块调用
 *
 * @param string $api
 *   job名 类似 ToBusiness_Industry_load
 * @param string $args
 *   类似前端call的数据
 *
 */
function module_invoke($api, array $args = array()) {
    static $functions;
    if (!isset($functions)) {
        $functions = GearmanKernel::getFunctions();
    }    
    $api = strtr($api, '_', '\\');
    if (isset($functions[$api])) {
        $start   = microtime(TRUE);
        $class   = $functions[$api]['class'];
        $method  = $functions[$api]['name'];        
        $request = new Request($args);
        try {
            $result = $class::$method($request);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response = new Response($result);
            }
        } catch (AccessException $e) {
            $response = new Response(NULL, array(), array(), $e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            $response = new Response(NULL, array(), array(), $e->getCode() ?: 409, $e->getMessage());
        }
        $timer = round((microtime(TRUE) - $start) * 1000, 2);
        logger('daemon')->info('(InsideCall) ' . $api . ' takes ' . $timer . ' ms');
    } else {
        $response = new Response(NULL, array(), array(), 404);
    }
    
    if (isset($args['request']['o']) and $keys = $args['request']['o']) {
        $data = $response->getData();
        if (is_object($data)) {
            $data = object_intersect_key($data, $keys);
        } elseif (GearmanKernel::digitalArray($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = object_intersect_key($v, $keys);
            }
        }
        $response->setData($data);
    }

    return $response;
}

/**
 * 获取Gearman实例
 *
 * @usage
 * gearman_client($target='client', $api='')->invoke($api, $args);
 * gearman_client($target='client', $api='')->doNormal($api, $args);
 * gearman_client($target='client', $api='')->doBackground($api, $args);
 */
function gearman_client($target = 'client', $api = '') {
    static $clients = array();
    static $workers = array();
    static $modtime = 0;
    static $gmcfile;
    if (!isset($gmcfile)) {
        $gmcfile = config()->get('conf.gmconfig', false);
    }
    $diff = $gmcfile ? filemtime($gmcfile) - $modtime : 0;    
    if ($gmcfile && $diff > 0) {
        $json    = file_get_contents($gmcfile);
        $workers = json_decode($json, true);
        $modtime = filemtime($gmcfile);
        $clients = array();
        logger()->info('gmconfig has been reseted ................');
    }
    if (isset($clients[$api])) {
        return $clients[$api];
    } elseif (isset($workers[$api])) {
        $servers = array();
        foreach ($workers[$api]['host'] as $v) {
            $servers[] = explode(':', $v);
        }
        $clients[$api] = new Client(array(
            'timeout' => substr($api,0,10) == 'ToBusiness' ? 2500 : 1000,
            'servers' => $servers,
        ));
        return $clients[$api];
    } else {
        logger()->warn($api . ' is not in gmconfig ****************');
    }
    
    if (!isset($clients[$target])) {
        $path = 'conf.gearman.' . $target;
        $config = config()->get($path);
        if(empty($config)) {
            throw new Exception('Please set the gearman client: ' . $target);
        }
        $clients[$target] = new Client($config);
    }
    
    return $clients[$target];
}

/**
 * 提供hook_alter钩子
 * ModuleName_HOOK_alter
 */
function alter($hook, &$data, &$context1 = NULL, &$context2 = NULL, &$context3 = NULL) {
    static $hooks = array();
    if (!isset($hooks[$hook])) {
        $hooks[$hook] = array();
        $modules = GearmanKernel::getModules();
        foreach ($modules as $module => $namespace) {
            if (function_exists($namespace . '\\'. $module . '_' . $hook . '_alter')) {
                $hooks[$hook][] = $namespace . '\\'. $module . '_' . $hook . '_alter';
            }
            if (function_exists($module . '_' . $hook . '_alter')) {
                $hooks[$hook][] = $module . '_' . $hook . '_alter';
            }
        }
    }
    foreach ($hooks[$hook] as $function) {
        $function($data, $context1, $context2, $context3);
    }
}

/**
 * 提供hook_invoke钩子
 * ModuleName_HOOK_invoke
 * @return array
 */
function invoke($hook) {
    static $hooks = array();
    if (!isset($hooks[$hook])) {
        $hooks[$hook] = array();
        $modules = GearmanKernel::getModules();
        foreach ($modules as $module => $namespace) {
            if (function_exists($namespace . '\\'. $module . '_' . $hook . '_invoke')) {
                $hooks[$hook][] = $namespace . '\\'. $module . '_' . $hook . '_invoke';
            }
            if (function_exists($module . '_' . $hook . '_invoke')) {
                $hooks[$hook][] = $module . '_' . $hook . '_invoke';
            }
        }
    }
    $args = func_get_args();
    unset($args[0]);
    $return = array();
    foreach ($hooks[$hook] as $function) {
        $result = call_user_func_array($function, $args);
        if (isset($result) && is_array($result)) {
            $return = array_merge_recursive($return, $result);
        } elseif (isset($result)) {
            $return[] = $result;
        }
    }

    return $return;    
}

/**
 * AccessException
 */
class AccessException extends Exception {}

/**
 * 内核
 */
abstract class GearmanKernel extends Kernel {
    
    /**
     * 项目清单
     *
     * @var array
     */
    public static $projects = array();
    
    /**
     * 模块清单
     *
     * @var array
     */
    public static $modules = array();
    
    //前缀
    public static $prefix = '';
    
    //启动配置
    public static $options = array();

    /**
     * 注册项目并创建模块列表
     *
     * @param string $project
     *   项目名:命名空间顶层名
     * @param string $dir
     *   项目目录
     */
    public static function registerProject($project, $dir, $prefix = '') {
        self::$prefix = $prefix;
        if ($modules = self::buildProjectModules($project, $dir)) {
            self::$projects[$project] = array(
                'dir'     => $dir,
                'modules' => $modules,
            );
        }
    }
    
    /**
     * 创建模块列表
     */
    public static function buildProjectModules($project, $dir) {
        $modules = array();
        if (is_dir($dir) && $handle = opendir($dir)) {
            while (FALSE !== ($filename = readdir($handle))) {
                if ($filename[0] != '.' && $filename[0] != '!' && is_dir($dir.'/'.$filename)) {
                    self::$modules[$filename] = $project . '\\' . $filename;
                    $uri = $dir . "/{$filename}/{$filename}.php";
                    if (is_file($uri)) {
                        $namespace = $project . '\\' . $filename;
                        require_once $uri;
                        $modules[$namespace] = self::parserFunctions($namespace . '\\' . $filename);
                    }
                }
            }
            closedir($handle);
        }
        
        return $modules;
    }
    
    /**
     * 获取模块列表
     */
    public static function getModules() {
        return self::$modules;
    }
    
    /**
     * 读取回调函数清单
     *
     * @api
     */
    public static function getFunctions($project = NULL, $prefix = '') {
        $return = array();
        //读取项目下的所有回调函数
        if (isset($project) && isset(self::$projects[$project])) {
            foreach (self::$projects[$project]['modules'] as $namespace => $functions) {
                $return += $functions;
            }
        }
        //读取所有的回调函数
        else {
            foreach (self::$projects as $project => $val) {
                foreach ($val['modules'] as $namespace => $functions) {
                    $return += $functions;
                }
            }
        }
        //添加wide functions
        foreach (self::$projects as $project => $val) {
            $return[$project] = array(
                'wide' => TRUE,
            );
        }
        
        if ($prefix) {
            $array = array();
            foreach ($return as $k => $v) {
                $array[$prefix.$k] = $v;
            }
            return $array;
        }
        
        return $return;
    }
    
    //执行
    public static function handler($handler = NULL) {
        if (is_cli() && (!$handler || $handler == 'cli')) {
            declare(ticks=1);
            self::initialization(TRUE);
            $servers = config()->get('conf.gearman.server');
            if (!is_array($servers)) {
                $servers = array();
            }
            $daemon = new Daemon($servers);
            $daemon->start(isset(self::$options['f']));
        }
        elseif ($handler == 'helper') {
            self::initialization();
            self::responseHelper();
        }
        else {
            self::initialization();
        }
    }
    
    //执行Gearman的callback
    public static function responseGearman($job) {
        static $functions;
        $start  = microtime(TRUE);
        if (!isset($functions)) {
            $functions = self::getFunctions(NULL, self::$prefix);
        }
        $functionName = strtr($job->functionName(), '_', '\\');
        $request = new Request($job->workload());
        //wide function
        if (isset($functions[$functionName]) && !empty($functions[$functionName]['wide'])) {
            $newFunction = $functionName;
            if ($request->class) {
                $newFunction .= '\\' . $request->class;
            }
            $newFunction .= '\\' . $request->method;
            if (isset($functions[$newFunction])) {
                $response = self::doWorkerResponse($functions[$newFunction], $request, $functionName.'('.$newFunction.')');
            }
            $functionName .= '('.$newFunction.')';
        }
        //正常function
        elseif (isset($functions[$functionName])) {
            $response = self::doWorkerResponse($functions[$functionName], $request, $functionName);
        }
        
        if (empty($response)) {
            $response = new Response(NULL, array(), array(), 404);
            logger('daemon')->warn('[Failed] Job ' . $functionName . ' is not exists');
        }
        
        //如果请求中设定了返回结果集的keys
        if ($keys = $request->getOutputParameters()) {
            $data = $response->getData();
            if (is_object($data)) {
                $data = object_intersect_key($data, $keys);
            } elseif (self::digitalArray($data)) {
                foreach ($data as $k => $v) {
                    $data[$k] = object_intersect_key($v, $keys);
                }
            }
            $response->setData($data);
        }
        
        //前端js调用时,需要把所有数字下标的数组转为顺序数字下标
        if ($request->getHeader('mold') == 'js') {
            $data = $response->getData();
            if (is_object($data)) {
                $response->setData((array) $data);
            } elseif (self::digitalArray($data)) {
                $response->setData(array_values($data));
            }
        }
        
        //获得此次Job的执行时间
        $timer = round((microtime(TRUE) - $start) * 1000, 2);
        logger('daemon')->info('<Successful> ' . $functionName . ' takes ' . $timer . ' ms' . "\n");
        
        return $response->send();
    }
    
    //执行worker,并返回response对象
    public static function doWorkerResponse($function, $request) {        
        $class  = $function['class'];
        $method = $function['name'];
        $reqnew = clone $request;
        $reqnew->setHeader('mold', NULL);
        try {
            $result = $class::$method($reqnew);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response = new Response($result);
            }
        } catch (AccessException $e) {
            $response = new Response(NULL, array(), array(), $e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            $response = new Response(NULL, array(), array(), $e->getCode() ?: 409, $e->getMessage());
        }
        
        return $response;
    }
    
    //执行Helper的返回
    public static function responseHelper() {
        $properties = array();
        foreach (self::$projects as $project => $val) {
            $properties += self::buildProjectProperties($project, $val['dir']);
        }
        
        $reflection =  new Reflection();
        echo $reflection->showHTML(self::getFunctions(), $properties);
    }
    
    //解析TestCase类
    public static function buildProjectProperties($project, $dir) {
        $reflection =  new Reflection();
        $properties = array();
        if (is_dir($dir) && $handle = opendir($dir)) {
            while (FALSE !== ($filename = readdir($handle))) {
                if ($filename[0] != '.' && $filename[0] != '!' && is_dir($dir.'/'.$filename)) {
                    $uri = $dir . "/{$filename}/TestCase.php";
                    if (is_file($uri)) {
                        $testClass = $project . "\\$filename\\TestCase";
                        $property = $reflection
                                          ->init($testClass, array('prefixProperty'=>$project."\\$filename\\"))
                                          ->parserProperties()
                                          ->getProperties();
                        $properties += $property;
                    }
                }
            }
        }
        
        return $properties;
    }

    //解析类的方法
    public static function parserFunctions($class) {
        $reflection =  new Reflection();
        if ($functions = $reflection->init($class)->parserMethods()->getFunctions()) {
            return $functions;
        }
        else {
            return array();
        }
    }
    
    //初始化配置文件
    protected static function initialization($cli = FALSE) {
        $hasFile = FALSE;
        //命令行模式,使用-c指定的配置文件
        if ($cli) {
            self::$options = getopt('c:f');
            if (isset(self::$options['c']) && is_file(self::$options['c'])) {
                require_once self::$options['c'];
                $hasFile = TRUE;
            }
        }
        //非命令行模式,默认使用项目目录下的config.php
        else {
            foreach (self::$projects as $project => $val) {
                $file = $val['dir'] . '/config.php';
                if (is_file($file)) {
                    require_once $file;
                    $hasFile = TRUE;
                    break;
                }
            }
        }
        if (!$hasFile) {
            exit('Please set the config file.' . "\n");
        }
        //写到全局config对象
        isset($conf) && config()->set('conf', $conf);
        //设置数据库
        isset($conf['databases']) && db_config($conf['databases']);
        //设置缓存
        isset($conf['caches']) && cache_config($conf['caches']);
        //设置缓存
        isset($conf['loggers']) && logger_config($conf['loggers']);
    }
    
    //数组是否带数字下标
    public static function digitalArray($data) {
        if (!is_array($data)) {
            return FALSE;
        }
        foreach ($data as $k => $v) {
            if (is_int($k)) {
                return TRUE;
            }
        }
        return FALSE;
    }
    
}
