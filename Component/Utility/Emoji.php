<?php

namespace Pyramid\Component\Utility;

class Emoji {
  
    /**
     * 转义mb4
     */
    public static function encode($text) {
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
    public static function decode($text) {
        return preg_replace_callback("/<(%\w{2}){4,6}>/", function($match) {
                    return urldecode(trim($match[0], '<>'));
                }, $text);
    }

}
