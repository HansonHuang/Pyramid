<?php

/**
 * @file
 *
 * Image
 */

namespace Pyramid\Component\Image;

use Exception;

class Image {

    /**
     * 比例系数
     * @var int
     */
    public $aspect = 1;
    
    /**
     * 目标图资源
     * @var resource $dst_im
     */
    protected $dst_im;
    
    /**
     * 源图资源
     * @var resource $src_im
     */
    protected $src_im;

    /**
     * 是否需要交换src_im和dst_im
     */
    protected $needsExchange = false;

    /**
     * 源图信息
     * @var array $info
     */
    protected $info = array();
    
    /**
     * 背景设置
     */
    protected $background = array(255,255,255,0);
    
    /**
     * 是否为可靠的图片格式
     */
    protected $is_image = true;
    
    /**
     * 析构函数
     */
    public function __construct($img, $isBinary = false) {
        if ($img) {
            if (!$isBinary) {
                $img = file_get_contents($img);                
            }
            $this->info['ext'] = $this->extension(substr($img,0,256));
            $this->src_im = @imagecreatefromstring($img);
            if ($this->src_im === false) {
                $this->is_image = false;
                throw new Exception('unvalidated image source.');
            }
        }
    }
    
    /**
     * 是否为可靠的图片
     */
    public function isImage() {
        return $this->is_image;
    }
    
    /**
     * 销毁资源以释放内存
     */
    public function __destruct() {
        $this->dst_im && imagedestroy($this->dst_im);
        $this->src_im && imagedestroy($this->src_im);
        $this->dst_im = null;
        $this->src_im = null;
    }
    
    /**
     * 返回图像宽度
     */
    public function getWidth() {
        return imagesx($this->src_im);
    }
    
    /**
     * 返回图像高度
     */
    public function getHeight() {
        return imagesy($this->src_im);
    }
    
    /**
     * 剪切
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @param int $pct
     */
    public function crop($x = 0, $y = 0, $width = 0, $height = 0, $pct = 100) {
        $this->exchange();
        list($r, $g, $b, $a) = $this->background;
        $src_width  = imagesx($this->src_im);
        $src_height = imagesy($this->src_im);
        $width  = $width ? $width : $src_width - $x;
        $height = $height ? $height : $src_height - $y;
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $bg = imagecolorallocatealpha($im, $r, $g, $b, $a);
        imagefill($im, 0, 0, $bg);
        $width  = min($width, $src_width - $x);
        $height = min($height, $src_height - $y);
        imagecopymerge($im, $this->src_im, 0, 0, $x, $y, $width, $height, $pct);        
        $this->dst_im = $im;
        $this->needsExchange = true;
        
        return $this;
    }
    
    /**
     * 旋转
     * @param int $degrees
     * @param int $bgd_color
     */
    public function rotate($degrees = 0, $bgd_color = 0xFFFFFF) {
        $this->exchange();
        $this->dst_im = imagerotate($this->src_im, $degrees, $bgd_color);
        $this->needsExchange = true;
        return $this;
    }
    
    /**
     * 缩放
     * @param int $width
     * @param int $height
     * @param bool $tensile false:定比拉伸
     */
    public function scale($width = 0, $height = 0, $tensile = false) {
        $this->exchange();
        list($r, $g, $b, $a) = $this->background;        
        $src_width  = imagesx($this->src_im);
        $src_height = imagesy($this->src_im);
        $aspect = $src_height / $src_width;
        if ($tensile && $width && $height) {        
        } elseif (($width && !$height) || ($width && $height && $aspect < $height / $width)) {
            $height = round($width * $aspect);
            $this->aspect = $src_width / $width;
        } else {
            $width = round($height / $aspect);
            $this->aspect = $src_height / $height;
        }
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $bg = imagecolorallocatealpha($im, $r, $g, $b, $a);
        imagefill($im, 0, 0, $bg);
        imagecopyresampled($im, $this->src_im, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        $this->dst_im = $im;
        $this->needsExchange = true;
        
        return $this;
    }
    
    /**
     * 缩放
     * @param float $scale
     */
    public function zoom($scale = 1) {
        $this->exchange();
        list($r, $g, $b, $a) = $this->background;
        $src_width  = imagesx($this->src_im);
        $src_height = imagesy($this->src_im);
        $width  = floor($src_width  * $scale);
        $height = floor($src_height * $scale);
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        $bg = imagecolorallocatealpha($im, $r, $g, $b, $a);
        imagefill($im, 0, 0, $bg);
        imagecopyresampled($im, $this->src_im, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        $this->dst_im = $im;
        $this->needsExchange = true;
        
        return $this;
    }
    
    /**
     * 将目标图资源设置为源图资源
     */
    public function exchange() {
        if ($this->needsExchange) {
            $tmp = $this->src_im;
            $this->src_im = $this->dst_im;
            $this->dst_im = $tmp;
            $this->needsExchange = false;
        }
        return $this;
    }
    
    //输出jpeg
    public function jpeg($filename = null, $quality = 80) {
        return $this->output('imagejpeg', $filename, $quality);
    }
    
    //输出gif
    public function gif($filename = null) {
        return $this->output('imagegif', $filename);
    }
    
    //输出png
    public function png($filename = null) {
        return $this->output('imagepng', $filename);
    }
    
    //输出
    public function output($func) {
        if (!function_exists($func)) {
            return false;
        }
        $args = func_get_args();
        array_shift($args);
        if (isset($args[0])) {
            return $func($this->dst_im, $args[0], isset($args[1]) ? $args[1] : null);
        }
        else {
            ob_start();
            $func($this->dst_im, null, isset($args[1]) ? $args[1] : null);
            $content = ob_get_clean();
            return $content;
        }
    }
    
    //获取信息
    public function info() {
        $this->info['width']  = imagesx($this->src_im);
        $this->info['height'] = imagesy($this->src_im);
        return $this->info; 
    }
    
    //获取源图资源
    public function getSource() {
        return $this->src_im;
    }
    
    //设置源图资源
    public function setSource($im) {
        $this->src_im = $im;
        return $this;
    }
    
    //获取目标图资源
    public function getDestination() {
        return $this->dst_im;
    }
    
    //设置目标图资源
    public function setDestination($im) {
        $this->dst_im = $im;
        return $this;
    }
    
    //设置背景
    public function setBackgroud(array $bg = array()) {
        $this->background = $bg;
        return $this;
    }
    
    //根据获取扩展名
    public function extension($binaryData) {
        $bin  = substr($binaryData, 0, 2);
        $str  = @unpack('C2chars', $bin);
        $code = intval($str['chars1'] . $str['chars2']);
        $ext  = '';
        switch ($code) {
            case 7790:  $ext = 'exe';   break;
            case 7784:  $ext = 'midi';  break;
            case 8297:  $ext = 'rar';   break;
            case 8075:  $ext = 'zip';   break;
            case 255216:$ext = 'jpg';   break;
            case 7173:  $ext = 'gif';   break;
            case 6677:  $ext = 'bmp';   break;
            case 13780: $ext = 'png';   break;
        }
        if ($str['chars1'] == '-1' && $str['chars2'] == '-40') {
            $ext = 'jpg';
        } elseif ($str['chars1'] == '-119' && $str['chars2'] == '80') {
            $ext = 'png';
        }
        
        return $ext;
    }
    
}
