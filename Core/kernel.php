<?php

/**
 * @file
 *
 * Kernel
 */

use Pyramid\Component\HttpFoundation\Request;
use Pyramid\Component\HttpFoundation\Response;
use Pyramid\Component\HttpFoundation\RouteBag;
use Pyramid\Component\Reflection\ReflectionClass;
use Pyramid\Component\Route\Route;

class Kernel {

    /**
     * Request实例
     */
    public $request;
    
    /**
     * 条件和执行
     */
    static $dispatches = array();
    
    /**
     * 初始化
     */
    public function init($request) {
        $this->request = $request;
        theme()->getEnvironment()->addGlobal('app', $this);
    }

    /**
     * 执行handle 并返回response
     */
    public function handle(Request $request) {
        $this->prepareCli($request);
        $ipath = $this->getTruePath($request->getPathInfo());
        $route = Route::match($ipath);
        $request->route = new RouteBag($route);
        $this->init($request);
        try {
            $this->afterRoute($request);
        } catch (Exception $e) {
            return new Response($e->getMessage(), 406);
        }
        if ($route && is_object($route['callback']) && get_class($route['callback']) == 'Closure') {
            $response = $route['callback']($request);
        } elseif ($route) {
            $response = call_user_func($route['callback'], $request);
        } else {
            return new Response('', 404);
        }
        if ($response instanceof Response) {
            return $response;
        } elseif($response === null) {
            return new Response('', 206);
        } else {
            return new Response($response);
        }
    }

    /**
     * 获取内部真实路由名
     */
    public function getTruePath($externalPath) {
        return $externalPath;
    }

    /**
     * 对路由解析之后的再处理
     */
    public function afterRoute($request) {
        foreach (static::$dispatches as $do) {
            if (is_object($do) && get_class($do) == 'Closure') {
                $do($request);
            } else {
                call_user_func($do, $request);
            }
        }
    }
    
    /**
     * 添加路由对应的执行动作
     */
    public static function dispatch($do) {
        static::$dispatches[] = $do;
    }

    /**
     * 注册应用
     */
    public function registerProject($project, $dir, $prefix = '') {
        class_loader()->registerNamespace($project, $dir);
        $files = file_include($dir.'/'.$project, "|(\w+)/\\1.php$|is", array('fullpath'=>true,'minDepth'=>2));
        foreach ($files as $f) {
            list ($module,) = explode('.', $f['basename']);
            $r = new ReflectionClass("{$project}\\{$module}\\{$module}");
            foreach ($r->getMethods() as $method=>$m) {
                if (!empty($m['comments']['route'])) {
                    $routes = $this->mergePathPrefix($m['comments']['route'], $prefix);
                    route_register($routes, "{$project}\\{$module}\\{$module}::{$method}");
                }
            }
        }
    }

    public function registerModule($project, $dir, $prefix = '') {
        $this->registerProject($project, $dir, $prefix);
    }
    
    /**
     * 载入并注册自动加载Entity
     */
    public function registerEntity($namespace, $dir, $regx = '', $options = array()) {
        class_loader()->registerNamespace($namespace, $dir);
        $options += array('fullpath'=>true, 'minDepth'=>2);
        file_include($dir.'/'.$namespace, "|(\w+)/\\1.php$|is", $options);
    }

    /**
     * 载入外部文件或目录
     */
    public static function import($dir, $regx = '', $options = array()) {
        if (is_file($dir)) {
            require_once $dir;
        } else {
            file_include($dir, $regx, $options);
        }
    }

    /**
     * 命令行模式调用
     *
     * @param -r url resource  "/abc/def"
     * @param -p query string  "a=1&b=2"
     */
    public function prepareCli($request) {
        if (is_cli() && $options = getopt("r:p:")) {
            if (isset($options['r']) && $options['r'] !== '') {
                $request->server->info['pathinfo'] = $options['r'];
            }
            if (isset($options['p']) && $options['p'] !== '') {
                parse_str($options['p'], $param);
                $request->setParameters($param);
            }
        }
    }
    
    //合并路由前缀
    protected function mergePathPrefix($paths, $prefix) {
        if (is_array($paths)) {
            foreach ($paths as $i => $path) {
                $paths[$i] = rtrim($prefix, '/') . '/' . ltrim($path, '/');
            }
        } elseif (is_string($paths)) {
            $paths = rtrim($prefix, '/') . '/' . ltrim($paths, '/');
        }

        return $paths;
    }

}
