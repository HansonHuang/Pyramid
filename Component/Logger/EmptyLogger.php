<?php

/**
 * @file
 *
 * EmptyLogger
 */

namespace Pyramid\Component\Logger;

class EmptyLogger {
    function __call($method, $param) {}
}
