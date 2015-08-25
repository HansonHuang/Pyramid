<?php

/**
 * @file
 *
 * Baidu翻译
 * @see http://developer.baidu.com/console#app/project
 */

namespace Pyramid\Component\Translation;

class Baidu {

    /**
     * 翻译API地址
     */
    protected $url = 'http://openapi.baidu.com/public/2.0/bmt/translate';
    
    /**
     * 配置
     */
    protected $config = array(
        'client_id' => '2tfhZCM3xwia61Luv6SunG9E',
        'from'      => 'en',
        'to'        => 'zh',
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
        $this->config['from'] = $from;
        return $this;
    }
    
    /**
     * 设置目标语言
     */
    public function to($to) {
        $this->config['to'] = $to;
        return $this;
    }

    /**
     * APPKEY
     */
    public function appid($appid) {
        $this->config['client_id'] = $appid;
        return $this;
    }

    /**
     * 执行翻译
     * @api
     */
    public function translate($string, $options = array()) {
        $params = array('q'=>$string) + $options + $this->config;
        list(, $result) = curl($this->url, array(), $params);
        if (($json = json_decode($result, true)) && !empty($json['trans_result'][0]['dst'])) {
            return $json['trans_result'][0]['dst'];
        } else {
            return $string;
        }
    }

}
