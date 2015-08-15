<?php

/**
 * @file
 *
 * google翻译
 */

namespace Pyramid\Component\Translation;

class Translation {

    /**
     * 翻译API地址
     */
    protected $url = 'http://translate.google.cn/translate_a/t?';

    /**
     * 翻译的字符
     */
    protected $text = '';

    /**
     * 请求的参数设置
     */
    protected $options = array(
        'client' => 't',            //翻译模块
        'sl'     => 'en',           //源语言
        'tl'     => 'zh-CN',        //目标语言
        'ie'     => 'UTF-8',        //输入数据编码
        'oe'     => 'UTF-8',        //输出数据编码
    );

    /**
     * 翻译
     */
    public function translate($string = '', $options = array()) {
        if ($string) {
            $this->text = $string;
        }
        return $this->httpRequest(array('q' => $this->text) + $options + $this->options);
    }

    /**
     * 设置翻译字符
     */
    public function setText($string) {
        $this->text = $string;
        return $this;
    }

    /**
     * 设置源语言
     */
    public function setSourceLang($lang) {
        $this->options['sl'] = $lang;
        return $this;
    }

    /**
     * 设置目标语言
     */
    public function setTargetLang($lang) {
        $this->options['tl'] = $lang;
        return $this;
    }

    //执行http请求翻译
    protected function httpRequest($params) {
        $ch  = curl_init();
        $opt = array(
            CURLOPT_URL             => $this->url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => false,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => http_build_query($params),
        );
        curl_setopt_array($ch, $opt);
        $content = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $content = '';
        }
        curl_close($ch);
        if (preg_match('/\[(\[\[.+?\]\]),/', $content, $match)) {
            $content = $this->escape($match[1]);
            return json_decode($content, true);
        } else {
            return false;
        }
    }

    //转换为UTF-8 unicode
    protected function escape($str, $encoding='UTF8') {
        $return = '';
        for ($x=0; $x < mb_strlen($str, $encoding); $x++) {
            $string = mb_substr($str, $x, 1, $encoding);
            if (strlen($string) > 1) {
                $return .= '\u' . bin2hex(mb_convert_encoding($string, 'UCS-2', $encoding));
            } else {
                $return .= $string;
            }
        }

        return $return;
    }

}
