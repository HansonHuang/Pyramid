<?php

/**
 * @file
 *
 * Tar
 */
 
namespace Pyramid\Component\Archiver;

class Tar {
    
    public static function add($filename, $content) {
        $tar = '';
        $filename = ltrim($filename, '/');
        $binary_first = pack('a100a8a8a8a12A12',
            $filename,                                  //file name
            '100644 ',                                  //file mode
            '     0 ',                                  //uid
            '     0 ',                                  //gid
            sprintf('%11s ', decoct(strlen($content))), //file size
            sprintf('%11s', decoct(time()))             //mtime
        );
        $binary_last = pack('a1a100a6a2a32a32a8a8a155a12',
            '', //link flag
            '', //link name
            '', //magic
            '', //version
            '', //uname
            '', //gname
            '', //devmajor
            '', //devminor
            '', //prefix
            ''
        );
        $checksum = 0;
        for ($i = 0; $i < 148; $i++) {
            $checksum += ord(substr($binary_first, $i, 1));
        }
        for ($i = 148; $i < 156; $i++) {
            $checksum += ord(' ');
        }
        for ($i = 156, $j = 0; $i < 512; $i++, $j++) {
            $checksum += ord(substr($binary_last, $j, 1));
        }
        $tar .= $binary_first;
        $tar .= pack('a8', sprintf('%6s ', decoct($checksum)));
        $tar .= $binary_last;
        $i = 0;
        while ('' != $buffer = substr($content, ($i++)*512, 512)) {
            $tar .= pack('a512', $buffer);
        }

        return $tar;
    }

}
