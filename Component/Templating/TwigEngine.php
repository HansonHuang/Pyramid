<?php

/*
 * @file
 *
 * TwigEngine
 */
 
namespace Pyramid\Component\Templating;

class TwigEngine implements EngineInterface {
    
    /**
     * 模版文件
     */
    protected $template;
    
    /**
     * 模版数据
     */
    protected $parameters = array();
    
    /**
     * 整体环境包
     */
    protected $environment;
    
    /**
     * 析构函数
     */
    public function __construct($environment) {
        $this->environment = $environment;
    }
    
    /**
     * 渲染模版
     */
    public function render($name, array $parameters = array()) {
        $this->setTemplate($name);
        $this->setParameters($parameters);
        return $this->load($name)->render($parameters);
    }
    
    /**
     * 渲染并直接输出
     */
    public function display($name, array $parameters = array()) {
        $this->load($name)->display($parameters);
    }
    
    /**
     * 模版是否存在
     */
    public function exists($name) {
        if ($name instanceof \Twig_Template) {
            return true;
        }
        $loader = $this->environment->getLoader();
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists((string) $name);
        }
        try {
            $loader->getSource((string) $name);
        } catch (\Twig_Error_Loader $e) {
            return false;
        }

        return true;
    }
    
    /**
     * 设置模版
     */
    public function setTemplate($name) {
        $this->template = $name;
        return $this;
    }
    
    /**
     * 设置数据
     */
    public function setParameters(array $parameters = array()) {
        $this->parameters = $parameters;
        return $this;
    }
    
    /**
     * 设置数据
     */
    public function setParameter($key, $value = null) {
        $this->parameters[$key] = $value;
        return $this;
    }

    /**
     * 获取数据
     */
    public function getParameters($names = null) {
        if ($names && is_array($names)) {
            foreach ($names as $name) {
                $return[$name] = isset($this->parameters[$name]) ? $this->parameters[$name] : null;
            }
            return $return;
        } elseif (!isset($names)) {
            return $this->parameters;
        }
    }

    /**
     * 获取数据
     */
    public function getParameter($name, $default = null) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
    }

    /**
     * 添加全局变量
     */
    public function addGlobal($key, $value) {
        $this->getEnvironment()->addGlobal($key, $value);
        return $this;
    }
    
    /**
     * 获取Environment
     */
    public function getEnvironment() {
        return $this->environment;
    }

        //魔术方法
    public function __toString() {
        return $this->load($this->template)->render($this->parameters);
    }

    //获取tamplate实例
    protected function load($name) {
        if ($name instanceof \Twig_Template) {
            return $name;
        }
        try {
            return $this->environment->loadTemplate((string) $name);
        } catch (\Twig_Error_Loader $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

}
