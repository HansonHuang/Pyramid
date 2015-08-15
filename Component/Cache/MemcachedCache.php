<?php

/**
 * @file
 *
 * @notice Memcached is required.
 * @see http://www.php.net/manual/zh/class.memcached.php
 */

namespace Pyramid\Component\Cache;

use Memcached;

class MemcachedCache extends Memcached {

    public function hasKey($key) {
        if (!$this->get($key) && $this->getResultCode() == self::RES_NOTFOUND) {
            return false;
        }
        return true;
    }

}
