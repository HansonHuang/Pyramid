<?php

/**
 * @file
 *
 * Transliteration 多国语言
 */

namespace Pyramid\Component\Transliteration;

class Transliteration {

    /**
     * ASCII头字典
     */
    protected static $languageOverrides = array();
    
    /**
     * 通用映射字典
     */
    protected static $genericMap = array();

    /**
     * 执行转换
     * string $string   需要转换的字符
     * string $langcode 转换成哪国语言 en|de|dk|eo|kg
     * string $unknown_character 未知字符的替换字符
     */
    public static function transliterate($string, $langcode = 'en', $unknown_character = '') {
        $result = '';
        foreach (preg_split('//u', $string, 0, PREG_SPLIT_NO_EMPTY) as $character) {
            $code = self::ordUTF8($character);
            if ($code == -1) {
                $result .= $unknown_character;
            }
            else {
                $result .= self::replace($code, $langcode, $unknown_character);
            }
        }

        return $result;
    }

    //返回字符的UTF ASCII
    protected static function ordUTF8($character) {
        $first_byte = ord($character[0]);
        if (($first_byte & 0x80) == 0) {
            return $first_byte;
        }
        if (($first_byte & 0xe0) == 0xc0) {
            return (($first_byte & 0x1f) << 6) + (ord($character[1]) & 0x3f);
        }
        if (($first_byte & 0xf0) == 0xe0) {
            return (($first_byte & 0x0f) << 12) + ((ord($character[1]) & 0x3f) << 6) + (ord($character[2]) & 0x3f);
        }
        if (($first_byte & 0xf8) == 0xf0) {
            return (($first_byte & 0x07) << 18) + ((ord($character[1]) & 0x3f) << 12) + ((ord($character[2]) & 0x3f) << 6) + (ord($character[3]) & 0x3f);
        }
        
        return -1;
    }

    //执行转换
    protected static function replace($code, $langcode, $unknown_character) {
        if ($code < 0x80) {
            return chr($code);
        }
        if (!isset(self::$languageOverrides[$langcode])) {
            self::readLanguageOverrides($langcode);
        }
        if (isset(self::$languageOverrides[$langcode][$code])) {
            return self::$languageOverrides[$langcode][$code];
        }
        $bank = $code >> 8;
        if (!isset(self::$genericMap[$bank])) {
            self::readGenericData($bank);
        }
        $code = $code & 0xff;

        return isset(self::$genericMap[$bank][$code]) ? self::$genericMap[$bank][$code] : $unknown_character;
    }

    //加载覆盖写字典
    protected static function readLanguageOverrides($langcode) {
        $file = __DIR__ . '/data/' . preg_replace('[^a-zA-Z\-]', '', $langcode) . '.php';
        if (is_file($file)) {
            include $file;
        }
        if (!isset($overrides) || !is_array($overrides)) {
            $overrides = array($langcode => array());
        }
        self::$languageOverrides[$langcode] = $overrides[$langcode];
    }
    
    //加载通用映射字典
    protected static function readGenericData($bank) {
        $file = __DIR__ . '/data/x' . sprintf('%02x', $bank) . '.php';
        if (is_file($file)) {
            include $file;
        }
        if (!isset($base) || !is_array($base)) {
            $base = array();
        }

        self::$genericMap[$bank] = $base;
    }

}
