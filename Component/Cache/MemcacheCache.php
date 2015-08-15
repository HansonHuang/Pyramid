<?php

/**
 * @file
 *
 * @notice Memcache is required.
 * @see http://www.php.net/manual/zh/class.memcache.php
 */

namespace Pyramid\Component\Cache;

use Memcache;

class MemcacheCache extends Memcache {

    public function quit() {
        $this->close();
    }
    
    public function addServers(array $servers) {
        foreach ($servers as $v) {
            $this->addServer($v[0], $v[1]);
        }
    }
    
    public function getMulti(array $keys) {
        $return = array();
        foreach ($keys as $key) {
            if (($data = $this->get($key)) !== false) {
                $return[$key] = $data;
            }
        }
        return $return;
    }
    
    public function setMulti(array $items, $expiration = 0) {
        foreach ($items as $key => $item) {
            $this->set($key, $item);
        }
        return true;
    }
    
    public function deleteMulti(array $keys, $time = 0) {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }
    
    public function setOption($option, $value) {
        return true;
    }

    public function hasKey($key) {
        if ($this->get($key) === false) {
            return false;
        }
        return true;
    }

}
