<?php

/**
 * @file
 *
 * Utility
 */

namespace Pyramid\Component\WeChat;

class Utility {
    
    //curl
    public static function http($url, $params = array()) {
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
        curl_setopt_array($ch, $option);
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

}