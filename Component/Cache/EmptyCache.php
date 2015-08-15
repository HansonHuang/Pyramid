<?php

namespace Pyramid\Component\Cache;

class EmptyCache {
    
    public function addServer($host, $port, $weight = 0) {}
    
    public function set() {
        return true;
    }
    
    public function get() {
        return false;
    }
    
    public function delete() {
        return true;
    }
    
    public function flush() {
        return true;
    }
    
    public function close() {
        return true;
    }
    
    public function getStats() {}
    
    public function getVersion() {}
    
    public function replace() {
        return true;
    }
    
    public function quit() {
        return true;
    }
    
    public function addServers(array $servers) {}
    
    public function getMulti(array $keys) {
        return array();
    }
    
    public function setMulti(array $items, $expiration = 0) {
        return true;
    }
    
    public function deleteMulti(array $keys, $time = 0) {
        return true;
    }
    
    public function setOption($option, $value) {
        return true;
    }
    
    public function hasKey($key) {
        return false;
    }

}
