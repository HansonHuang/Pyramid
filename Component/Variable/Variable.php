<?php

/**
 * @file
 *
 * 应用配置类
 */

namespace Pyramid\Component\Variable;

use Exception;

/**
 * 配置类

    CREATE TABLE `{variables}` (
        `name` VARCHAR(128) NOT NULL,
        `value` LONGTEXT NULL,
        PRIMARY KEY (`name`)
    )
    COMMENT='variables'
    COLLATE='utf8_general_ci'
    ENGINE=InnoDB;

 */
class Variable {

    /**
     * 配置
     *
     * @var array
     */
    protected $data = array();

    /**
     * 是否已加载
     *
     * @var bool
     */
    protected $isLoaded = false;

    /**
     * 析构函数
     */
    public function __construct() {
        $this->load();
    }

    /**
     * 读配置
     *
     * @param string $key
     */
    public function get($key = '', $default = null) {
        if (empty($key)) {
            return $this->data;
        } else {
            return isset($this->data[$key]) ? $this->data[$key] : $default;
        }
    }

    /**
     * 写配置
     */
    public function set($key, $value) {
        $this->data[$key] = $value;
        db_merge('variables')
            ->key(array(
                'name' => $key
            ))
            ->fields(array(
                'name'  => $key,
                'value' => serialize($value)
            ))
            ->execute();
        return $this;
    }

    /**
     * 删除
     */
    public function delete($key) {
        unset($this->data[$key]);
        db_delete('variables')->condition('name', $key)->execute();
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
     * 装载配置
     */
    public function load() {
        if (!$this->isLoaded) {
            try {
                $this->data = db_select('variables', 'v')
                            ->fields('v', array('name', 'value'))
                            ->execute()
                            ->fetchAllKeyed();
                foreach ($this->data as $k => $v) {
                    $this->data[$k] = unserialize($v);
                }
                $this->isLoaded = true;
            } catch (Exception $e) {
                throw new Exception('Table {variables} is not exists.');
            }
        }
        return $this;
    }

}
