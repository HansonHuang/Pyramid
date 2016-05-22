<?php

/**
 * @file
 *
 * Utility
 */

namespace Pyramid\Component\WeChat;

class Utility {
    
    /**
     * curl
     *
     * $cert {
            CURLOPT_SSLCERT: xxx.pem
            CURLOPT_SSLKEY: xxx.pem
            CURLOPT_CAINFO: xxx.pem
        }
        openssl rsa -in apiclient_key.pem -out newkey.pem
     */
    public static function http($url, $params = array(), $cert = array()) {
        $ch = curl_init();
        $option = array(
            CURLOPT_URL             => $url,
            CURLOPT_HTTPHEADER      => array(),
            CURLOPT_HEADER          => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 60,
        );
        if (count($params)) {
            $option[CURLOPT_POST] = true;
            $option[CURLOPT_POSTFIELDS] = $params;
        }
        if (stripos($url, 'https://') === 0) {
            $option[CURLOPT_SSL_VERIFYPEER] = false;
            $option[CURLOPT_SSL_VERIFYHOST] = false;
        }
        if (class_exists('\CURLFile')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
        } elseif (defined('CURLOPT_SAFE_UPLOAD')) {
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        }
        curl_setopt_array($ch, $option);
        if ($cert) {
            curl_setopt_array($ch, $cert);
        }
        $content = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $content = '';
        }
        curl_close($ch);
        return $content;
    }
    
    //获取公众号AccessToken
    public static function getAccessToken($wechat) {
        $suffix = $wechat->getConfig('wid', '0');
        return variable()->get('wechat_access_token'.$suffix, false);
    }

    //保存公众号AccessToken
    public static function setAccessToken($token, $wechat) {
        $suffix = $wechat->getConfig('wid', '0');
        return variable()->set('wechat_access_token'.$suffix, $token);
    }

    //获取企业号AccessToken
    public static function getCorpAccessToken($wechat) {
        $suffix = $wechat->getConfig('appid', '0');
        return variable()->get('wechatcorp_access_token'.$suffix, false);
    }

    //设置企业号AccessToken
    public static function setCorpAccessToken($token, $wechat) {
        $suffix = $wechat->getConfig('appid', '0');
        return variable()->set('wechatcorp_access_token'.$suffix, $token);
    }

    //获取公众号JsTicket
    public static function getJsTicket($wechat) {
        $suffix = $wechat->getConfig('wid', '0');
        return variable()->get('wechat_js_ticket'.$suffix, false);
    }

    //保存公众号JsTicket
    public static function setJsTicket($ticket, $wechat) {
        $suffix = $wechat->getConfig('wid', '0');
        return variable()->set('wechat_js_ticket'.$suffix, $ticket);
    }

    //多字节不转unicode
    public static function json_encode($data) {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            return urldecode(json_encode(self::urlencode($data)));
        } else {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    //转urlencode
    public static function urlencode($data) {
        if (is_object($data)) {
            $data = (array) $data;
        }
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[urlencode($k)] = self::urlencode($v);
            }
        } else {
            $data = urlencode($data);
        }

        return $data;
    }

    //拼装XML
    public static function buildXML($array) {
        $xmlData = '';
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $k = 'item';
            }
            if (is_array($v) || is_object($v)) {
                $xmlData .= "<$k>" . self::buildXML((array) $v) . "</$k>";
            } else {
                $v = preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/u", '', $v);
                $v = str_replace(array('<![CDATA[',']]>'), array('< ![CDATA[',']] >'), $v);
                $xmlData .= "<$k><![CDATA[" . $v . "]]></$k>";
            }
        }

        return $xmlData;
    }

    //解析XML
    public static function extractXML($xml = false) {
        if ($xml === false) {
            return false;
        }
        if (!($xml->children())) {
            return (string) $xml;
        }
        foreach ($xml->children() as $child) {
            $name = $child->getName();
            if (count($xml->$name) == 1) {
                $element[$name] = self::extractXML($child);
            } else {
                $element[][$name] = self::extractXML($child);
            }
        }

        return $element;
    }

    //带签名的结果
    public static function makeSign(array $data, $key = '') {
        ksort($data);
        $data['sign'] = self::getSign($data, $key);
        return $data;
    }
    
    //获取签名
    public static function getSign(array $data, $key = '') {
        unset($data['sign']);
        ksort($data);
        $buff = '';
        foreach ($data as $k=>$v) {
            if (!is_array($v) && $v != '') {
                $buff .= $k . '=' . $v . '&';
            }
        }
        $buff = trim($buff, '&');
        return strtoupper(md5($buff.'&key='.$key));
    }

}
