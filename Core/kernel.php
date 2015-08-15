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
        $route = Route::match($request->getPathInfo());
        $request->route = new RouteBag($route);
        $this->init($request);
        $this->afterRoute($request);
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
            return new Response('', 204);
        } else {
            return new Response($response);
        }
    }

    /**
     * 对路由解析之后的再处理
     */
    public function afterRoute($request) {

    }
    
    /**
     * 注册应用
     */
    public function registerProject($project, $dir, $prefix = '') {
        class_loader()->registerNamespace($project, $dir);
        $files = file_scan($dir.'/'.$project, "|(\w+)/\\1.php$|is", array('fullpath'=>true,'minDepth'=>2));
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

    /**
     * 载入外部文件或目录
     */
    public function import($dir, $regx = '', $options = array()) {
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
