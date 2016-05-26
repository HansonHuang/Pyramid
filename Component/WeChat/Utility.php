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
    public static function buildXML($array, $root = 'xml') {
        return "<{$root}>" . self::arrayToXml($array) . "</{$root}>";
    }
    
    //解析XML字串
    public static function parseXML($xmlString) {
        $xml = @simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        return self::extractXML($xml);
    }

    //解析XML对象
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
    
    //替换微信消息的emoji为图片
    public static function replaceEmoji($string = '') {
        static $url = 'https://res.wx.qq.com/mpres/htmledition/images/icon/emotion/';
        static $emoji = array( "/::)" => "0", "/::~" => "1", "/::B" => "2", "/::|" => "3", "/:8-)" => "4", "/::<" => "5", "/::$" => "6", "/::X" => "7", "/::Z" => "8", "/::(" => "9", "/::'(" => "9", "/::-|" => "10", "/::@" => "11", "/::P" => "12", "/::D" => "13", "/::O" => "14", "/::(" => "15", "/::+" => "16", "/:--b" => "17", "/::Q" => "18", "/::T" => "19", "/:,@P" => "20", "/:,@-D" => "21", "/::d" => "22", "/:,@o" => "23", "/::g" => "24", "/:|-)" => "25", "/::!" => "26", "/::L" => "27", "/::>" => "28", "/::,@" => "29", "/:,@f" => "30", "/::-S" => "31", "/:?" => "32", "/:,@x" => "33", "/:,@@" => "34", "/::8" => "35", "/:,@!" => "36", "/:!!!" => "37", "/:xx" => "38", "/:bye" => "39", "/:wipe" => "40", "/:dig" => "41", "/:handclap" => "42", "/:&-(" => "43", "/:B-)" => "44", "/:<@" => "45", "/:@>" => "46", "/::-O" => "47", "/:>-|" => "48", "/:P-(" => "49", "/::'|" => "50", "/:X-)" => "51", "/::*" => "52", "/:@x" => "53", "/:8*" => "54", "/:pd" => "55", "/:<W>" => "56", "/:beer" => "57", "/:basketb" => "58", "/:oo" => "59", "/:coffee" => "60", "/:eat" => "61", "/:pig" => "62", "/:rose" => "63", "/:fade" => "64", "/:showlove" => "65", "/:heart" => "66", "/:break" => "67", "/:cake" => "68", "/:li" => "69", "/:bome" => "70", "/:kn" => "71", "/:footb" => "72", "/:ladybug" => "73", "/:shit" => "74", "/:moon" => "75", "/:sun" => "76", "/:gift" => "77", "/:hug" => "78", "/:strong" => "79", "/:weak" => "80", "/:share" => "81", "/:v" => "82", "/:@)" => "83", "/:jj" => "84", "/:@@" => "85", "/:bad" => "86", "/:lvu" => "87", "/:no" => "88", "/:ok" => "89", "/:love" => "90", "/:<L>" => "91", "/:jump" => "92", "/:shake" => "93", "/:<O>" => "94", "/:circle" => "95", "/:kotow" => "96", "/:turn" => "97", "/:skip" => "98", "/:oY" => "99", "/:#-0" => "100", "/:hiphot" => "101", "/:kiss" => "102", "/:<&" => "103", "/:&>" => "104",);
        foreach ($emoji as $k => $v) {
            if (strpos($string, $k) !== false) {
                $string = str_replace($k, '<img src="'.$url.$v.'.gif" width="24" height="24" />',$string);
            }
        }
        return $string;
    }

    //数组转换成xml
    protected static function arrayToXml($array) {
        $xmlData = '';
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $k = 'item';
            }
            if (is_array($v) || is_object($v)) {
                $xmlData .= "<$k>" . self::arrayToXml((array) $v) . "</$k>";
            } else {
                $v = preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/u", '', $v);
                $v = str_replace(array('<![CDATA[',']]>'), array('< ![CDATA[',']] >'), $v);
                $xmlData .= "<$k><![CDATA[" . $v . "]]></$k>";
            }
        }

        return $xmlData;
    }

}
