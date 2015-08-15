<?php

/**
 * @file
 *
 * 常用函数
 */

use Pyramid\Component\Database\Database;
use Pyramid\Component\Password\Password;
use Pyramid\Component\Utility\Xss;
use Pyramid\Component\Utility\String;
use Pyramid\Component\Route\Route;
use Pyramid\Component\Uuid\Uuid;
use Pyramid\Component\Permission\Permission;
use Pyramid\Component\Redis\Connection as RedisConnection;

//注册权限
function permission_register($module, array $permissions = array()) {
    Permission::register($permissions, $module);
}

//获取权限
function permission_get($module = null) {
    return Permission::get($module);
}

//注册route
function route_register($route, $callback) {
    Route::register($route, $callback);
}

//匹配route
function route_match($path, $strict = false) {
    return Route::match($path, $strict);
}

//获取route
function route_get($route) {
    return Route::get($route);
}

//删除route
function route_delete($route) {
    return Route::delete($route);
}

//设置Redis配置
function redis_config($target, array $config = array()) {
    RedisConnection::setConfig($target, $config);
}

//获取redis实例
function redis($target = 'default') {
    return RedisConnection::getConnection($target);
}

//增强型var_export
function pyramid_var_export($var, $return = false, $prefix = '') {
    static $func = __FUNCTION__;
    if (is_array($var)) {
        if (empty($var)) {
            $output = 'array()';
        } else {
            $output = "array(\n";
            foreach ($var as $key => $value) {
                $output .= '  ' . $func($key, true) . ' => ' . $func($value, true, '  ') . ",\n";
            }
            $output .= ')';
        }
    } elseif (is_bool($var)) {
        $output = $var ? 'true' : 'false';
    } elseif (is_string($var)) {
        if (strpos($var, "\n") !== false || strpos($var, "'") !== false) {
            $var = str_replace(array('\\', '"', "\n", "\r", "\t"), array('\\\\', '\"', '\n', '\r', '\t'), $var);
            $output = '"' . $var . '"';
        } else {
            $output = "'" . $var . "'";
        }
    } elseif (is_object($var) && get_class($var) === 'stdClass') {
        $output = '(object) ' . $func((array) $var, true, $prefix);
    } else {
        $output = var_export($var, true);
    }

    if ($prefix) {
        $output = str_replace("\n", "\n$prefix", $output);
    }
    
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

//随机函数
function random($length = 6, $chars = '0123456789') {
    $hash = '';
    $max  = strlen($chars) - 1;
    while ($length-- && $length >= 0) {
        $hash .= $chars[mt_rand(0, $max)];
    }
  
    return $hash;
}

//格式化大小
function get_size($size, $dec = 2) {
    $units = array('B','K','M','G','T','P','E','Z','Y');
	$count = 0;
	while ($size >= 1024) {
		$size /= 1024;
		$count++;
	}
    
	return round($size,$dec) . ' '. $units[$count];
}

//是否运行在cli模式
function is_cli() {
  return (!isset($_SERVER['SERVER_SOFTWARE']) 
           && (PHP_SAPI == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

//是否是UTF8编码
function is_utf8($text) {
    return String::isUTF8($text);
}

//是否为合法email地址
function is_email($email) {
    return String::isEmail($email);
}

//Xss过滤
function xss_filter($string, $allowedTags = array(), $allowedStyleProperties = array()) {
    static $xss;
    if (!isset($xss)) {
        $xss = new Xss();
    }
    return $xss->filter($string, $allowedTags, $allowedStyleProperties);
}

//组装树结构
function data_to_tree($data, $root = 0, $id = 'tid', $pid = 'parent', $child = 'child') {
    $tree  = array();
    $unset = array();
    foreach ($data as $tid => $v) {
        $parentId = $v[$pid];
        if ($root == $parentId) {
            $tree[] = &$data[$tid];
        } elseif (isset($data[$parentId])) {
            $data[$parentId][$child][] = &$data[$tid];
            $unset[] = $tid;
        }
    }
    if (!$tree) {
        foreach($unset as $tid) {
            unset($data[$tid]);
        }
        return $data;
    }
    return $tree;
}

//给定一个数字,转化为包含2^N的数组
function number_to_bin($n) {
    $return = array();
    if ($n <= 0) {
        return (array) $n;
    }
    $i = 1;
    while ($i <= $n) {
        if ($i & $n) {
            $return[] = $i;
        }
        $i *= 2;
    }

    return $return;
}

//uuid
function uuid() {
    return Uuid::generate();    
}

//file scan
function file_scan($dir, $regx, $options = array(), $depth = 1) {
    $options += array(
        'nomask'   => '/(\.\.?|CSV)$/',
        'recurse'  => true,
        'minDepth' => 1,
        'maxDepth' => 10,
        'fullpath' => false,
    );
    $files = array();
    if (is_dir($dir) && $depth <= $options['maxDepth'] && ($handle = opendir($dir))) {
        while (false !== ($filename = readdir($handle))) {
            if (!preg_match($options['nomask'], $filename) && $filename[0] != '.') {
                $subdir = $dir . '/' . $filename;
                if (is_dir($subdir) && $options['recurse']) {
                    $files = array_merge(file_scan($subdir, $regx, $options, $depth + 1), $files);
                } elseif ($depth >= $options['minDepth']) {
                    if (preg_match($regx, $filename) || ($options['fullpath'] && preg_match($regx, $subdir))) {
                        $files[] = array(
                            'dirname'  => $dir,
                            'basename' => $filename,
                            'file'     => $dir . '/' . $filename,
                        );
                    }
                }
            }
        }
        closedir($handle);
    }
    return $files;
}

//file scan & include
function file_include($dir, $regx, $options = array()) {
    $files = file_scan($dir, $regx, $options);
    foreach ($files as $f) {
        require_once $f['file'];
    }
}

//curl
function curl($url, $headers = array(), $params = array(), $proxy = '') {
    $header = array();
    $headers += array('Expect' => '');
    foreach ($headers as $k => $v) {
        $header[] = $k . ': ' . $v;
    }
    $ch = curl_init();
    $option = array(
        CURLOPT_URL             => $proxy ? $proxy . urlencode($url) : $url,
        CURLOPT_HTTPHEADER      => $header,
        CURLOPT_HEADER          => true,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_CONNECTTIMEOUT  => 30,
        CURLOPT_TIMEOUT         => 240,
    );
    if (count($params)) {
        $option[CURLOPT_POST] = true;
        $option[CURLOPT_POSTFIELDS] = http_build_query($params);
    }
    if (stripos($url, 'https') === 0) {
        $option[CURLOPT_SSL_VERIFYPEER] = false;
        $option[CURLOPT_SSL_VERIFYHOST] = false;
    }
    curl_setopt_array($ch, $option);
    $content = curl_exec($ch);
    if (curl_errno($ch) > 0) {
        $content = "HTTP/1.1 501 ERROR\r\n\r\n";
    }
    curl_close($ch);
    return explode("\r\n\r\n", $content, 2);
}

//string的value...
function java_arg(array $args) {
    static $func = __FUNCTION__;
    foreach ($args as $k => $v) {
        switch (gettype($v)) {
            case 'string':
                break;
            case 'integer':
            case 'double':
                $args[$k] = (string) $v;
                break;            
            case 'array':
                $args[$k] = $func($v);
                break;
            case 'object':
                $args[$k] = $func((array) $v);
                break;
            case 'boolean':                          
            case 'resource':
            case 'null':
                unset($args[$k]);
                break;
            default:
                unset($args[$k]);
        }
    }

    return $args;
}

//高亮
function highlight(&$source, $words, $element = 'highlight') {
    static $func = __FUNCTION__;
    if (is_scalar($source)) {
        $regexp = '/(' . preg_replace('/\s+/', '|', preg_quote($words,'/')) . ')/iu';
        $source = preg_replace($regexp, '<'.$element.'>$1</'.$element.'>', $source);
    } elseif (is_array($source)) {
        foreach ($source as $k => $v) {
            $func($source[$k], $words, $element);
        }
    } elseif (is_object($source)) {
        foreach ($source as $k => $v) {
            $func($source->$k, $words, $element);
        }
    }
}

//获取数组或对象的唯一标识
function identity($value) {
    static $func = __FUNCTION__;
    if (is_object($value)) {
        $value = (array) $value;
    } else if (!is_array($value)) {
        $value = array($value);
    }
    ksort($value);
    $tmp = '';
    foreach ($value as $k=>$v) {
        if (is_array($v) || is_object($v)) {
            $tmp .= $k . $func($v);
        } else {
            $tmp .= $k . (string) $v;
        }
    }

    return md5($tmp);
}

//生成一个流水号
function uniquesn($prefix = '') {
    list ($usec, $sec) = explode(' ', microtime());
    return $prefix . date('YmdHis', $sec) . sprintf('%06d',$usec * 1000000);
}


/**
 * ===============================
 * 一些类似array_函数的object_操作
 * ===============================
 */

//合并两个对象的键值
function object_merge($object1, $object2) {
    return (object) ((array) $object1 + (array) $object2);
}

//获取对象中指定keys的values
function object_intersect_key($object, $keys = array()) {
    //没有定义keys,直接返回
    if (empty($keys)) {
        return $object;
    }
    //解析需要的keys和层级,字段信息
    $needs = array();
    foreach ($keys as $key) {
        preg_match('|(?<key>\w+)(?<num>\[\d*\])?(?<fields>\([\w,]+\))?|', $key, $match);
        $needs[$match['key']] = $match;
    }
    //转换为array
    $data = (array) $object;
    foreach ($data as $k => $v) {
        if (isset($needs[$k])) {
            //有[],则认为是关联数组
            if (isset($needs[$k]['num']) && $needs[$k]['num']) {
                $length = trim($needs[$k]['num'], '[]');
                if ($length) {
                    $data[$k] = array_slice($v, 0, $length);
                }
                if (isset($needs[$k]['fields'])) {
                    $ks = explode(',', trim($needs[$k]['fields'],'()'));
                    foreach ($data[$k] as $kk => $vv) {
                        $data[$k][$kk] = array_intersect_key($vv, array_flip($ks));
                    }
                }
            }
            //无[],则认为是索引数组
            elseif (isset($needs[$k]['fields'])) {
                $ks = explode(',', trim($needs[$k]['fields'],'()'));
                $data[$k] = array_intersect_key($v, array_flip($ks));
            }            
        } else {
            unset($data[$k]);
        }
    }
    
    return (object) $data;
}


/**
 * ========================
 * 一些兼容高版本PHP的函数
 * ========================
 */

/**
 * array_column (php5.5)
 */
function pyramid_array_column($input, $columnKey, $indexKey = null) {
    $return = array();
    if (!is_array($input)) {
        return $return; 
    }
    if ($indexKey === null) {
        foreach ($input as $v) {
            if (is_object($v) && isset($v->$columnKey)) {
                $return[] = $v->$columnKey;
            } elseif (isset($v[$columnKey])) {
                $return[] = $v[$columnKey];
            }
        }
    } else {
        foreach ($input as $v) {
            if (isset($v[$columnKey])) {
                $return[$v[$indexKey]] = $v[$columnKey];
            }
        }
    }
    
    return $return;
}
if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null) {
        return pyramid_array_column($input, $columnKey, $indexKey);
    }
}

/**
 * password_hash (php5.5)
 */
function pyramid_password_hash($password, $algo = 1, $options = array()) {
    return Password::hash($password);
}

/**
 * password_verify (php5.5)
 */
function pyramid_password_verify($password, $hash) {
    return Password::verify($password, $hash);
}

/**
 * password_needs_rehash (php5.5)
 */
function pyramid_password_needs_rehash($hash, $algo = 1) {
    return Password::needsRehash($hash);
}

/**
 * gzdecode (php5.4)
 */
if (!function_exists('gzdecode')) {
    function gzdecode($data) {
        return gzinflate(substr($data,10,-8)); 
    }
}
