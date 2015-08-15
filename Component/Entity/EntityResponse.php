<?php

/**
 * @file
 *
 * EntityResponse
 */

namespace Pyramid\Component\Entity;

class EntityResponse {

    /**
     * 数据主体
     */
    public $data;
    
    /**
     * 应用级信息
     */
    public $variables = array();
    
    /**
     * 状态码
     */
    public $status;

    /**
     * 状态描述
     */
    public $message = '';

    /**
     * 常用状态描述数组
     */
    public static $messages = array(
        0   => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        204 => 'No Content',
        205 => 'Reset Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        409 => 'Conflict',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
    );


    /**
     * 析构函数
     *
     * @param mixed   $data
     * @param array   $variables
     * @param integer $status
     * @param string  $message
     *
     * @api
     */
    public function __construct($data = null, $variables = array(), $status = 0, $message = '') {
        $this->setData($data);
        $this->setVariables($variables);
        $this->setStatus($status, $message);              
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
    public function setStatus($status, $message = '') {
        $this->status = (int) $status;
        if ($message === '') {
            $this->message = isset(self::$messages[$status]) ? self::$messages[$status] : '';
        } else {
            $this->message = (string) $message;
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
        return $this->message;
    }

    /**
     * 设置响应正文
     *
     * @api
     */
    public function setData($data) {
        $this->data = $data;

        return $this;
    }

    /**
     * 获取响应正文
     *
     * @api
     */
    public function getData() {
        return $this->data;
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
        if ($value === null) {
            unset($this->variables[$key]);
        } else {
            $this->variables[$key] = $value;
        }
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
     * 拼装结果
     *
     * @api
     */
    public function process() {
        return array(
            'status'    => $this->status,
            'message'   => $this->message,
            'data'      => $this->data,
            'variables' => $this->variables,
        );
    }

    /**
     * 魔术方法
     *
     * @api
     */
    public function __toString() {
        return var_export($this->process(), true);
    }

    //自身迭代
    public static function create($data = null, $variables = array(), $status = 0, $message = '') {
        return new static($data, $variables, $status, $message);
    }

}
