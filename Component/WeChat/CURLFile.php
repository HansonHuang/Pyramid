<?php

/**
 * @file
 *
 * CURLFile
 * php5.5+
 */

namespace Pyramid\Component\WeChat;

class CURLFile {
    
    public static function realpath($file) {
        if (class_exists('\CURLFile')) {
            return new \CURLFile(realpath($file));
        } else {
            return '@' . realpath($file);
        }
    }

}
