<?php

/**
 * @file
 *
 * 配置类
 */

namespace Pyramid\Component\Config;

/**
 * 配置类
 */
class Config {
    /**
     * 配置
     *
     * @var array
     */
    protected $data = array();

    /**
     * 启动时的配置
     *
     * @var array
     */
    protected $runtimeData = array();

    /**
     * 是否已加载
     *
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * 读配置
     *
     * @param string $key
     */
    public function get($key = '', $default = null) {
        if (!$this->isLoaded) {
            $this->load();
        }
        if (empty($key)) {
            return $this->data;
        } else {
            if (!strpos($key, '.')) {
                return isset($this->data[$key]) ? $this->data[$key] : $default;
            } else {
                $parts  = explode('.', $key);
                $return = $this->data;
                foreach ($parts as $part) {
                    if(!isset($return[$part])) {
                        return $default;
                    } else {
                        $return = $return[$part];
                    }
                }
                return $return;
            }
        }
    }

    /**
     * 写配置
     */
    public function set($key, $value) {
        if (!$this->isLoaded) {
            $this->load();
        }
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * 删除
     */
    public function delete($key) {
        if (!$this->isLoaded) {
            $this->load();
        }
        unset($this->data[$key]);

        return $this;
    }

    /**
     * 存储多个
     */
    public function setMulti(array $items) {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }
        
        return $this;
    }
    
    /**
     * 检索多个
     *
     * return array|null
     */
    public function getMulti(array $keys) {
        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }
        
        return $return;
    }
    
    /**
     * 删除多个
     */
    public function deleteMulti(array $keys) {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        
        return $this;
    }
    
    /**
     * 是否有这个元素
     */
    public function hasKey($key) {
        return isset($this->data[$key]) ? true : false;
    }

    /**
     * 清空配置
     */
    public function flush() {
        $this->data = array();

        return $this;
    }

    /**
     * 装载配置
     */
    public function load() {
        $this->isLoaded = false;
        //todo
        $this->isLoaded = true;

        return $this;
    }

}
