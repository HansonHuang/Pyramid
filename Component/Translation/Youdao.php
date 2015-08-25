<?php

/**
 * @file
 *
 * Youdao翻译
 * @see http://fanyi.youdao.com/openapi?path=web-mode
 */

namespace Pyramid\Component\Translation;

class Youdao {

    /**
     * 翻译API地址
     */
    protected $url = 'http://fanyi.youdao.com/openapi.do?';

    /**
     * 配置
     */
    protected $config = array(
        'keyfrom'  => 'KEYFROM',
        'key'      => 'KEY',
        'type'     => 'data',
        'doctype'  => 'json',
        'version'  => '1.1',
    );
    
    /**
     * 析构函数
     */
    public function __construct($config = array()) {
        $this->config = $config + $this->config;
        return $this;
    }

    /**
     * 设置某项配置
     */
    public function setConfig($name, $value) {
        $this->config[$name] = $value;
        return $this;
    }

    /**
     * 设置源语言
     */
    public function from($from) {
        return $this;
    }
    
    /**
     * 设置目标语言
     */
    public function to($to) {
        return $this;
    }

    /**
     * APPKEY
     */
    public function appid($key, $keyfrom = '') {
        $this->config['key'] = $key;
        $this->config['keyfrom'] = $keyfrom;
        return $this;
    }

    /**
     * 执行翻译
     * @api
     */
    public function translate($string, $options = array()) {
        $params = array('q'=>$string) + $options + $this->config;
        list(, $result) = curl($this->url . http_build_query($params));
        if ($json = json_decode($result, true) && isset($json['translation'][0])) {
            return $json['translation'][0];
        } else {
            return $string;
        }
    }

}
