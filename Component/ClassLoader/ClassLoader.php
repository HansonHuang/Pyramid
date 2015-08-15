<?php

/**
 * @file
 *
 * 类自动加载器
 *
 * 支持PSR-0规范类自动加载
 * 支持传统模式类自动加载
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
 */

namespace Pyramid\Component\ClassLoader;

/**
 * @usage
 *
 * 命名空间模式
 *   $loader = new ClassLoader();
 *   $loader->registerNamespace('Pyramid', __DIR__);
 *   $loader->register();
 * new Pyramid\Component\xxx
 * 将自动加载 __DIR__/Pyramid/Component/xxx.php
 *
 * 传统模式
 *   $loader = new ClassLoader();
 *   $loader->registerPrefix('Pyramid', __DIR__);
 *   $loader->register();
 * new Pyramid_Class_User
 * 将自动加载 __DIR__/Pyramid/Class/User.php
 */
class ClassLoader {
    /**
     * 命名空间映射
     */
    private $namespaces = array();

    /**
     * 命名空间默认路径
     */
    private $namespaceFallbacks = array();

    /**
     * 前缀映射
     */
    private $prefixes = array();

    /**
     * 前缀映射默认路径
     */
    private $prefixFallbacks = array();

    /**
     * 获取命名空间映射
     *
     * @return array
     */
    public function getNamespaces() {
        return $this->namespaces;
    }

    /**
     * 注册命名空间映射
     */
    public function registerNamespace($namespace, $paths) {
        $this->namespaces[$namespace] = (array) $paths;
    }

    /**
     * 注册多个命名空间映射
     */
    public function registerNamespaces(array $namespaces) {
        foreach ($namespaces as $namespace => $locations) {
            $this->namespaces[$namespace] = (array) $locations;
        }
    }

    /**
     * 注册命名空间默认路径
     */
    public function registerNamespaceFallback($dir) {
        $this->namespaceFallbacks[] = $dir;
    }

    /**
     * 注册多个命名空间默认路径
     */
    public function registerNamespaceFallbacks(array $dirs) {
        $this->namespaceFallbacks = $dirs;
    }

    /**
     * 返回前缀映射
     *
     * @return array
     */
    public function getPrefixes() {
        return $this->prefixes;
    }

    /**
     * 添加前缀映射
     *
     * @param string $prefix
     *   前缀
     * @param array|string $paths
     *   目录路径
     */
    public function registerPrefix($prefix, $paths) {
        //第一个参数为空,则注册默认路径
        if (!$prefix) {
            foreach ((array) $paths as $path) {
                $this->prefixFallbacks[] = $path;
            }
        }
        //已有的则合并
        elseif (isset($this->prefixes[$prefix])) {
            $this->prefixes[$prefix] = array_merge(
                $this->prefixes[$prefix],
                (array) $paths
            );
        }
        //默认映射数组
        else {
            $this->prefixes[$prefix] = (array) $paths;
        }
    }

    /**
     * 添加多个前缀映射
     *
     * @param array $prefixes
     */
    public function registerPrefixes(array $prefixes) {
        foreach ($prefixes as $prefix => $path) {
            $this->addPrefix($prefix, $path);
        }
    }

    /**
     * 注册自动加载
     *
     * @param Boolean $prepend
     *   是否优先
     */
    public function register($prepend = false) {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * 注销自动加载
     */
    public function unregister() {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * 加载类
     *
     * @param string $class
     *   类名
     *
     * @return null|true
     */
    public function loadClass($class) {        
        if ($file = $this->findFile($class)) {
            require_once $file;
            return true;
        }
    }

    /**
     * 根据类名找文件
     *
     * @param string $class
     *   类名
     *
     * @return string|null
     */
    public function findFile($class) {
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }
        //如果是namespace模式
        if (($pos = strrpos($class, '\\')) !== false) {
            $namespace = substr($class, 0, $pos);
            $className = substr($class, $pos + 1);
            $normalClass = strtr($namespace, '\\', '/') . '/' . strtr($className, '_', '/') . '.php';
            foreach ($this->namespaces as $ns => $dirs) {
                if (strpos($namespace, $ns) !== 0) {
                    continue;
                }
                foreach ($dirs as $dir) {
                    $file = $dir . '/' . $normalClass;
                    if (is_file($file)) {
                        return $file;
                    }
                }
            }
            foreach ($this->namespaceFallbacks as $dir) {
                $file = $dir . '/' . $normalClass;
                if (is_file($file)) {
                    return $file;
                }
            }
        }
        //传统模式
        else {
            $normalClass = strtr($class, '_', '/') . '.php';
            foreach ($this->prefixes as $prefix => $dirs) {
                if (strpos($class, $prefix) !== 0) {
                    continue;
                }
                foreach ($dirs as $dir) {
                    $file = $dir . '/' . $normalClass;
                    if (is_file($file)) {
                        return $file;
                    }
                }
            }
            foreach ($this->prefixFallbacks as $dir) {
                $file = $dir . '/' . $normalClass;
                if (is_file($file)) {
                    return $file;
                }
            }
        }
    }

}
