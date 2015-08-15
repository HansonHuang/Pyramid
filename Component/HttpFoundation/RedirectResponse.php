<?php

/*
 * @file
 *
 * Response
 */
 
namespace Pyramid\Component\HttpFoundation;

class RedirectResponse extends Response {

    /**
     * 跳转页面
     */
    public $redirectUrl;

    /**
     * 跳转时间
     */
    public $redirectTime = 0;

    /**
     * 析构函数
     *
     * @api
     */
    public function __construct($url, $time = 0, $content = '', $headers = array()) {
        $this->setStatus(302);
        $this->setContent($content);
        $this->setHeaders($headers);
        $this->setTargetUrl($url, $time);
    }

    /**
     * 设置跳转URL和时间
     *
     * @api
     */
    public function setTargetUrl($url, $time = 0) {
        if ($time == 0 && !headers_sent()) {
            $this->setHeader('Location', $url);
        } else {
            $this->setStatus(200);
        }
        $this->redirectUrl  = $url;
        $this->redirectTime = $time;
        return $this;
    }
    
    /**
     * 设置跳转时间
     *
     * @api
     */
    public function setRedirectTime($time = 0) {
        $this->redirectTime = $time;
        return $this;
    }
    
    /**
     * 获取跳转页面URL
     *
     * @api
     */
    public function getTargetUrl() {
        return $this->redirectUrl;
    }

    /**
     * 发送content
     *
     * @api
     */
    public function sendContent() {
        echo '<meta http-equiv="refresh" content="'.$this->redirectTime.';url=\''.$this->redirectUrl.'\'" />';
        echo $this->content;
        return $this;
    }

    //自身迭代
    public static function create($url, $time = 0, $content = '', $headers = array()) {
        return new static($url, $time, $content, $headers);
    }

}
