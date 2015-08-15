<?php

/*
 * @file
 *
 * Response
 */
 
namespace Pyramid\Component\HttpFoundation;

class Response {

    /**
     * 响应头信息
     */
    public $headers = array();

    /**
     * 数据主体
     */
    public $content;
    
    /**
     * 状态码
     */
    public $status;

    /**
     * 状态描述
     */
    public $statusText = '';

    /**
     * 协议
     */
    public $version = '1.0';

    /**
     * 编码
     */
    public $charset = 'UTF-8';
    
    /**
     * Variable
     */
    public $variables = array();
    
    /**
     * Format, FormatWrapper
     */
    public $format, $formatWrapper;

    /**
     * 常用状态描述数组
     */
    public static $statusTexts = array(
        0   => 'OK',
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing', 
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    );


    /**
     * 析构函数
     *
     * @param mixed   $content
     * @param integer $status
     * @param array   $headers
     *
     * @api
     */
    public function __construct($content = '', $status = 200, $headers = array(), $statusText = null) {
        $this->setContent($content);
        if (!$statusText && is_string($headers)) {
            $this->setStatus($status, $headers);
        } else {
            $this->setStatus($status, $statusText);
            $this->setHeaders($headers);
        }
    }

    /**
     * 设置输出格式 json xml html
     */
    public function format($format, $formatWrapper = '') {
        $this->format = strtolower($format);
        $this->formatWrapper = $formatWrapper;
        return $this;
    }

    /**
     * 设置状态码和状态描述
     *
     * @param integer $status
     *    状态码
     * @param string $message
     *    状态描述
     *
     * @api
     */
    public function setStatus($status, $message = null) {
        $this->status = (int) $status;
        if ($message === null) {
            $this->statusText = isset(self::$statusTexts[$status]) ? self::$statusTexts[$status] : '';
        } else {
            $this->statusText = (string) $message;
        }

        return $this;
    }

    /**
     * 获取状态码
     *
     * @api
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * 获取状态描述
     *
     * @api
     */
    public function getStatusText() {
        return $this->statusText;
    }

    /**
     * 设置响应正文
     *
     * @api
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * 获取响应正文
     *
     * @api
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * 设置头信息
     *
     * @api
     */
    public function setHeaders(array $headers) {
        $this->headers = $headers + $this->headers;
        return $this;
    }
    
    /**
     * 设置头信息
     *
     * @api
     */
    public function setHeader($key, $value) {
        if ($value === null) {
            unset($this->headers[$key]);
        } else {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    /**
     * 获取头信息
     *
     * @api
     */
    public function getHeaders() {
        return $this->headers;
    }
    
    /**
     * 获取头信息某个元素
     *
     * @api
     */
    public function getHeader($key, $default = null) {
        return isset($this->headers[$key]) ? $this->headers[$key] : $default;
    }

       /**
     * 设置variables信息
     *
     * @api
     */
    public function setVariables($variables = array()) {
        $this->variables = (array) $variables + $this->variables;
        return $this;
    }
    
    /**
     * 设置variable信息
     *
     * @api
     */
    public function setVariable($key, $value = null) {
        $this->variables[$key] = $value;
        return $this;
    }
    
    /**
     * 获取variables信息
     *
     * @api
     */
    public function getVariables() {
        return $this->variables;
    }
    
    /**
     * 获取某条variables信息
     *
     * @api
     */
    public function getVariable($key, $default = null) {
        return isset($this->variables[$key]) ? $this->variables[$key] : $default;
    }

    /**
     * 发送content
     *
     * @api
     */
    public function sendContent() {
        echo (string) $this->content;
        return $this;
    }
    
    /**
     * 发送headers
     *
     * @api
     */
    public function sendHeaders() {
        if (headers_sent()) {
            return $this;
        }
        header(sprintf('HTTP/%s %s %s', $this->version, $this->status, $this->statusText), true, $this->status);
        foreach ($this->headers as $name => $value) {
            header($name.': '.$value, false);
        }

        return $this;
    }

    /**
     * 发送结果
     *
     * @api
     */
    public function send($callback = null) {
        switch ($this->format) {
            case 'json':
            case 'jsonp':
                $this->sendJson(0, $callback, $this->formatWrapper);
                break;
            case 'xml':
                $this->sendXML($callback);
                break;
            default:
                $this->sendDefault($callback);
        }
        
        return $this;
    }

    /**
     * 默认的发送
     *
     * @api
     */
    public function sendDefault($callback = null) {
        if ($callback && is_callable($callback)) {
            $callback($this);
        } else {
            $this->sendHeaders();
            $this->sendContent();
        }
        static::finishRequest();

        return $this;
    }

    /**
     * 发送JSON结果
     *
     * @api
     */
    public function sendJson($options = 0, $callback = null, $jsonp = '') {
        if (headers_sent()) {
            return $this;
        }
        $this->setHeader('Content-type', 'application/json; charset=utf-8');
        header(sprintf('HTTP/%s %s %s', $this->version, 200, 'OK'), true, 200);
        foreach ($this->headers as $name => $value) {
            header($name.': '.$value, false);
        }
        $result = array(
                    'status'    => $this->status,
                    'message'   => $this->statusText,
                    'data'      => $this->content,
                    'variables' => (object) $this->variables,
                );
        if ($callback && is_callable($callback)) {
            $result = $callback($result);
        }
        $result = json_encode($result, $options);
        if ($jsonp) {
            echo $jsonp.'('.$result.')';
        } else {
            echo $result;
        }
        static::finishRequest();

        return $this;
    }

    /**
     * 发送XML结果
     *
     * @api
     */
    public function sendXML($callback = null) {
        if (headers_sent()) {
            return $this;
        }
        $this->setHeader('Content-type', 'application/xml; charset=utf-8');
        header(sprintf('HTTP/%s %s %s', $this->version, 200, 'OK'), true, 200);
        foreach ($this->headers as $name => $value) {
            header($name.': '.$value, false);
        }
        $result = array(
                    'status'    => $this->status,
                    'message'   => $this->statusText,
                    'data'      => $this->content,
                    'variables' => (object) $this->variables,
                );
        if ($callback && is_callable($callback)) {
            $result = $callback($result);
        }
        echo '<?xml version="1.0" encoding="utf-8"?><root>', self::arrayToXml($result), '</root>';
        static::finishRequest();

        return $this;
    }

    /**
     * 设置状态描述数据
     *
     */
    public function setStatusTexts($statusTexts = array()) {
        self::$statusTexts = $statusTexts + self::$statusTexts;
        if ($this->statusText === '') {
            $this->setStatus($this->status);
        }
    }

    /**
     * 魔术方法
     *
     * @api
     */
    public function __toString() {
        $output = sprintf('HTTP/%s %s %s', $this->version, $this->status, $this->statusText)."\r\n";
        foreach ($this->headers as $name => $value) {
            $output .= $name.': '.$value."\r\n";;
        }
        return $output . "\r\n" . $this->getContent();
    }

    //自身迭代
    public static function create($content = '', $status = 200, $headers = array(), $statusText = null) {
        return new static($content, $status, $headers, $statusText);
    }

    //关闭输出缓冲
    public static function closeOutputBuffers($targetLevel, $flush) {
        $status = ob_get_status(true);
        $level  = count($status);
        while ($level-- > $targetLevel
            && (!empty($status[$level]['del'])
                || (isset($status[$level]['flags'])
                    && ($status[$level]['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)
                    && ($status[$level]['flags'] & ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE))
                )
            )
        ) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    //结束响应
    public static function finishRequest() {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif ('cli' !== PHP_SAPI) {
            static::closeOutputBuffers(0, true);
            flush();
        }
    }

    //array to xml
    public static function arrayToXml($data) {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $xml = '';
        foreach($data as $k => $v) {
            if (is_numeric($k)) {
                $k = 'item id="' . $k . '"';
            }
            $xml .= "<$k>";
            if (is_array($v) || is_object($v)) {
                $xml .= self::arrayToXml($v);
            } else {
                $v = preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/u", '', $v);
                $v = str_replace(array('<![CDATA[',']]>'), array('< ![CDATA[',']] >'), $v);
                $xml .= "<![CDATA[" . $v . "]]>";
            }
            list ($k, ) = explode(' ', $k);
	        $xml .= "</$k>";
        }

        return $xml;
    }

}
