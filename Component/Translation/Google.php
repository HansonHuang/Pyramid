<?php

/**
 * @file
 *
 * google翻译
 */

namespace Pyramid\Component\Translation;

class Google {

    /**
     * 翻译API地址
     */
    protected $url = 'http://translate.google.cn/translate_a/single?';

    /**
     * 配置
     */
    protected $config = array(
        'sl'     => 'en',           //源语言
        'tl'     => 'zh-CN',        //目标语言
        'hl'     => 'zh-CN',        //目标语言
        'client' => 't',
        'dt'     => 't',
        'ie'     => 'UTF-8',
        'oe'     => 'UTF-8',
    );
    
    /**
     * Header
     */
    public $header = array(
        'User-Agent' => 'Mozilla/5.0 Firefox/40.0',
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
        $this->config['sl'] = $from;
        $this->config['hl'] = $from;
        return $this;
    }
    
    /**
     * 设置目标语言
     */
    public function to($to) {
        $this->config['tl'] = $to;
        return $this;
    }

    /**
     * APPKEY
     */
    public function appid($appid) {
        return $this;
    }

    /**
     * 执行翻译
     * @api
     */
    public function translate($string = '', $options = array()) {
        $params = array('q'=>$string) + $options + $this->config;
        list(, $result) = curl($this->url . http_build_query($params), $this->header);
        if (preg_match('/\[\[(\[.+?\])\],/', $result, $match)) {
            $result = preg_replace('/,+/',',', $match[1]);
        }
        if ($json = json_decode($result, true)) {
            return $json[0];
        } else {
            return $string;
        }
    }

}
