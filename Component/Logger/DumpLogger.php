<?php

/**
 * @file
 *
 * DumpLogger
 */

namespace Pyramid\Component\Logger;

class DumpLogger {
    
    function __call($method, $param) {
        echo $method, ':', implode('', $param), "\n";
    }

}
