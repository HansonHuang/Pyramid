<?php

namespace Pyramid\Component\Utility;

class LCS {
    
    /**
     * 获取LCS
     */
    public static function getLCS($string1, $string2, $encoding = 'UTF-8') {
        $len1  = mb_strlen($string1, $encoding);
        $len2  = mb_strlen($string2, $encoding);
        $array = array_fill(0, $len1+1, array_fill(0,$len2+1,0));
        for ($i = $len1 - 1; $i >= 0; $i--) {
            for ($j = $len2 - 1; $j >= 0; $j--) {
                if (mb_substr($string1, $i, 1, $encoding) == mb_substr($string2, $j, 1, $encoding)) {
                    $array[$i][$j] = $array[$i+1][$j+1] + 1;
                } else {
                    $array[$i][$j] = max($array[$i+1][$j], $array[$i][$j+1]);
                }
            }
        }
        
        $return = '';
        $i = $j = 0;
        while ($i < $len1 && $j < $len2) {
            if (mb_substr($string1, $i, 1, $encoding) == ($s = mb_substr($string2, $j, 1, $encoding))) {
                $return .= $s;
                $i++;
                $j++;
            } elseif ($array[$i+1][$j] >= $array[$i][$j+1]) {
                $i++;
            } else {
                $j++;
            }
        }
        
        return $return;
    }
    
    /**
     * 计算相似度
     */
    public static function getSimilar($string1, $string2, $encoding = 'UTF-8') {
        $len1 = mb_strlen($string1, $encoding);
        $len2 = mb_strlen($string2, $encoding);
        $len  = mb_strlen(self::getLCS($string1, $string2, $encoding), $encoding);
        return round($len*2/($len1+$len2),4);
    }

}
