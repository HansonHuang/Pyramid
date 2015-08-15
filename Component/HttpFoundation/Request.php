<?php

/*
 * @file
 *
 * Request
 */
 
namespace Pyramid\Component\HttpFoundation;

use LogicException;

class Request {
    
    //POST
    public $post;
    
    //GET
    public $get;
    
    //SERVER
    public $server;
    
    //FILES
    public $files;
    
    //COOKIE
    public $cookies;
    
    //params
    public $params;
    
    //route
    public $route;
    
    //method
    public $method;
    
    //CONTENT
    protected $content;
    
    //SESSION
    protected $session;
    
    /**
     * 析构函数
     */
    public function __construct($get = array(), $post = array(), $params = array(), $cookies = array(), $files = array(), $server = array(), $content = null) {
        $this->initialize($get, $post, $params, $cookies, $files, $server, $content);
    }
    
    /**
     * 初始化属性
     */
    public function initialize($get, $post, $params, $cookies, $files, $server, $content) {
        $this->get     = new ParameterBag($get);
        $this->post    = new ParameterBag($post);
        $this->cookies = new ParameterBag($cookies);
        $this->params  = new ParameterBag($params);
        $this->server  = new ServerBag($server);
        $this->files   = new ParameterBag($files);
        $this->content = $content;
    }
    
    /**
     * 获取内容
     *
     * @return resource|string
     *
     * @api
     */
    public function getContent($asResource = false) {
        if (false === $this->content || (true === $asResource && null !== $this->content)) {
            throw new LogicException('getContent() can only be called once when using the resource return type.');
        }
        if (true === $asResource) {
            $this->content = false;
            return fopen('php://input', 'rb');
        }
        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }
    
    /*
     * 从globals创建request
     */
    public static function createFromGlobals() {
        return new static($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
    }

    /*
     * 获取一个元素
     * 优先顺序为: params get post
     */
    public function get($key, $default = null) {
        return $this->params->getParameter($key, $this->get->getParameter($key, $this->post->getParameter($key, $default)));
    }
    
    /*
     * 获取一个元素
     * 优先顺序为: params get post
     */
    public function getParameter($key, $default = null) {
        return $this->get($key, $default);
    }

    /*
     * 获取一批元素
     * 优先顺序为: params post get
     */
    public function getParameters($keys = null) {
        if (is_array($keys)) {
            return $this->params->getParameters($keys) + $this->post->getParameters($keys) + $this->get->getParameters($keys);
        } else {
            return $this->params->getParameters() + $this->post->getParameters() + $this->get->getParameters();
        }
    }

    /*
     * 是否有该元素
     */
    public function hasParameter($key) {
        return $this->params->hasParameter($key) || $this->post->hasParameter($key) || $this->get->hasParameter($key);
    }
    
    /*
     * 设置元素
     */
    public function set($key, $value = null) {
        $this->params->setParameter($key, $value);
        return $this;
    }
    
    /*
     * 设置元素
     */
    public function setParameter($key, $value = null) {
        return $this->set($key, $value);
    }
    
    /*
     * 设置元素
     */
    public function setParameters($values) {
        $this->params->setParameters($values);
        return $this;
    }

    /*
     * 清除元素
     */
    public function clearKey($key) {
        $this->params->setParameter($key, null);
        $this->get->setParameter($key, null);
        $this->post->setParameter($key, null);
        return $this;
    }

    /*
     * 获取执行的脚本文件
     */
    public function getScriptName() {
        return $this->server->getParameter('SCRIPT_NAME', $this->server->getParameter('ORIG_SCRIPT_NAME', ''));
    }

    /*
     * 获取客户端IP
     */
    public function getIp() {
        return $this->server->getInfo('ip');
    }

    /*
     * 获取PathInfo
     * 总是以/开头
     */
    public function getPathInfo() {
        return $this->server->getInfo('pathinfo');
    }
    
    /*
     * 获取BasePath
     */
    public function getBasePath() {
        return $this->server->getInfo('basepath');
    }
    
    /*
     * 获取BaseUrl
     */
    public function getBaseUrl() {
        return $this->server->getInfo('baseurl');
    }

    /*
     * 获取Scheme
     */
    public function getScheme() {
        return $this->server->getInfo('scheme');
    }
    
    /*
     * 获取Host
     */
    public function getHttpHost() {
        return $this->server->getInfo('host');
    }
    
    /*
     * 获取Port
     */
    public function getPort() {
        return $this->server->getInfo('port');
    }
    
    /*
     * 获取Scheme和HttpHost
     */
    public function getSchemeAndHttpHost() {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }
    
    /*
     * 获取path
     */
    public function getUriForPath($path = '', $qs = '') {
        $uri = $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $path;
        if ($qs && (is_array($qs) || is_object($qs))) {
            $qs = http_build_query($qs);
        }
        if ($qs) {
            return $uri . (strpos($uri,'?') ? '&' : '?') . $qs;
        } else {
            return $uri;
        }
    }
    
    /*
     * 获取uri
     */
    public function getUri() {
        if (null !== $qs = $this->getQueryString()) {
            $qs = '?' . $qs;
        }
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo() . $qs;
    }
    
    /*
     * 获取url
     */
    public function getUrl() {
        return $this->getSchemeAndHttpHost() . $this->getBaseUrl() . $this->getPathInfo();
    }

    /*
     * 获取QueryString
     */
    public function getQueryString() {
        $qs = static::normalizeQueryString($this->server->getParameter('QUERY_STRING'));
        return '' === $qs ? null : $qs;
    }

    /*
     * 获取method
     */
    public function getMethod() {
        $method   = strtoupper($this->server->getParameter('REQUEST_METHOD', 'GET'));
        $override = $this->server->getParameter('X-HTTP-METHOD-OVERRIDE');
        if ($method === 'POST' && $override) {
            return $this->method = strtoupper($override);
        } else {
            return $this->method = $method;
        }
    }

    /*
     * 设置请求的方式
     */
    public function setMethod($method) {
        $this->method = strtoupper($method);
        return $this->server->setParameter('REQUEST_METHOD', $this->method);
    }

    /*
     * 格式QueryString
     */
    public static function normalizeQueryString($qs) {
        if ('' == $qs) {
            return '';
        }
        $parts = array();
        $order = array();
        foreach (explode('&', $qs) as $param) {
            if ('' === $param || '=' === $param[0]) {
                continue;
            }
            $keyValuePair = explode('=', $param, 2);
            $parts[] = isset($keyValuePair[1]) ?
                rawurlencode(urldecode($keyValuePair[0])).'='.rawurlencode(urldecode($keyValuePair[1])) :
                rawurlencode(urldecode($keyValuePair[0]));
            $order[] = urldecode($keyValuePair[0]);
        }
        array_multisort($order, SORT_ASC, $parts);
        return implode('&', $parts);
    }

}
