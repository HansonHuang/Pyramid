<?php

/**
 * @file
 *
 * 翻译
 */

namespace Pyramid\Component\Translation;

class Translation {

    /**
     * 翻译器
     */
    protected $translator;
    
    /**
     * 自定义翻译kv
     */
    protected $customs = array();

    /**
     * 析构函数
     */
    public function __construct($vendor = 'Pyramid\Component\Translation\Baidu', $config = array()) {
        $this->setVendor($vendor, $config);
    }
    
    /**
     * 设置翻译器
     */
    public function setVendor($vendor, $config = array()) {
        $this->translator = new $vendor($config);
    }
    
    /**
     * 设置翻译器
     */
    public function setCustom($src, $dst) {
        $this->customs[$src] = $dst;
    }

    /**
     * 翻译
     */
    public function translate($string = '', $options = array()) {
        if (isset($this->customs[$string])) {
            return $this->customs[$string];
        } else {
            return $this->translator->translate($string, $options);
        }
    }

}
