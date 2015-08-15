<?php

/**
 * @file
 *
 * EntityRequest
 */

namespace Pyramid\Component\Entity;

class EntityRequest {
    
    /**
     * Parameters数据
     *
     * @var array
     */
    public $params = array();

    /**
     * 析构函数
     *
     * @param mixed   $content
     * @param integer $status
     * @param array   $headers
     *
     * @api
     */
    public function __construct($input = array()) {
        $this->params = $input;
    }
    
    /**
     * 是否有指定名称的元素
     *
     * @return bool
     *
     * @api
     */
    public function hasParameter($name) {
        return isset($this->params[$name]);
    }
    
    /**
     * 获取指定名称的元素
     *
     * @return mixed|null
     *
     * @api
     */
    public function getParameter($name, $default = null) {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }
    public function get($name, $default = null) {
        return $this->getParameter($name, $default);
    }
    
    /**
     * 获取指定名称的多个元素
     *
     * @return mixed|null
     *
     * @api
     */
    public function getParameters($names = null) {
        if (is_array($names)) {
            foreach ($names as $name) {
                $return[$name] = $this->getParameter($name);
            }
            return $return;
        } elseif (!isset($names)) {
            return $this->params;
        }
    }
    
    /**
     * 设置指定名称的元素
     *
     * @api
     */
    public function setParameter($name, $value = null) {
        if (is_null($value)) {
            unset($this->params[$name]);
        } else {
            $this->params[$name] = $value;
        }
        
        return $this;
    }
    
    /**
     * 设置指定名称的一批元素
     *
     * @api
     */
    public function setParameters(array $values) {
        $this->params = $values + $this->params;
        
        return $this;
    }

    /**
     * 重置parameters
     *
     * @api
     */
    public function replace(array $params = array()) {
        $this->params = $params;
    }

    /**
     * 清空parameters
     *
     * @api
     */
    public function flush() {
        $this->params = array();
    }
    
    /**
     * 读取所有的keys
     *
     * @api
     */
    public function keys() {
        return array_keys($this->params);
    }
    
    /**
     * 魔术方法
     *
     * @api
     */
    public function __toString() {
        return var_export($this->params, true);
    }
    
    //自身迭代
    public static function create($input = array()) {
        return new static($input);
    }
    
}
