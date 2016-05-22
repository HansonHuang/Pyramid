<?php

namespace Pyramid\Component\Utility;

class StringTool {

    /**
     * 转义HTML
     */
    public static function checkPlain($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 反转义HTML
     */
    public static function decodeEntities($text) {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 是否为UTF-8编码
     */
    public static function isUTF8($text) {
        if (strlen($text) == 0) {
            return true;
        }
        return (preg_match('/^./us', $text) == 1);
    }
    
    /**
     * 是否为合法Email
     */
    public static function isEmail($email) {
        return preg_match("/^(\w+)(\.[\w\-]+)*@(\w+)(\.[\w\-]+)*(\.[a-z]{2,4})$/i", $email);
    }
    
    /**
     * 过滤链接协议字符
     */
    public static function filterBadProtocol($string) {
        $string = static::decodeEntities($string);
        return static::checkPlain(static::stripDangerousProtocols($string));
    }
    
    /**
     * 过滤非法Uri
     */
    public static function stripDangerousProtocols($uri) {
        $allowed_protocols = array('http' => true, 'https' => true);
        do {
            $before = $uri;
            $colonpos = strpos($uri, ':');
            if ($colonpos > 0) {
                $protocol = substr($uri, 0, $colonpos);
                if (preg_match('![/?#]!', $protocol)) {
                    break;
                }
                if (!isset($allowed_protocols[strtolower($protocol)])) {
                    $uri = substr($uri, $colonpos + 1);
                }
            }
        } while ($before != $uri);

        return $uri;
    }
  
    /**
     * 转义mb4
     */
    public static function encodeEmoji($text) {
        $tmp = '';
        $len = mb_strlen($text, 'UTF-8');
        for ($x = 0; $x < $len; $x++) {
            $string = mb_substr($text, $x, 1, 'UTF-8');
            if (strlen($string) >= 4) {
                $tmp .= '<' . urlencode($string) . '>';
            } else {
                $tmp .= $string;
            }
        }
        return $tmp;
    }

    /**
     * 反转义mb4
     */
    public static function decodeEmoji($text) {
        return preg_replace_callback("/<(%\w{2}){4,6}>/", function($match) {
                    return urldecode(trim($match[0], '<>'));
                }, $text);
    }

    /**
     * 10进制转N进制 N=[2,62]
     */
    public static function dec2x($str, $d = 62) {
        $return = '';
        while($str > 0) {
            $s = $str % $d;
            if ($s > 35) {
                $s = chr($s+61);
            } elseif ($s > 9) {
                $s = chr($s + 55);
            }
            $return .= $s;
            $str = floor($str/$d);
        }
        return strrev($return);
    }

    /**
     * N进制转10进制
     */
    public static function x2dec($str, $d = 62) {
        $return = $num = 0;
        $len = strlen($str);
        for($i=0;$i<$len;$i++) {
            $num = ord($str{$i});
            if ($num > 96) {
                $num -= 61;
            } elseif ($num > 64) {
                $num -= 55;
            } else {
                $num -= 48;
            }
            $return += $num * pow($d, $len-1-$i);
        }
        return $return;
    }

    /**
     * 短网址
     */
    public static function shorturl($url) {
        $str = sprintf('%u', crc32($url));
        return self::dec2x($str);
    }

}
