<?php

/**
 * @file
 *
 * EmptyCache
 */

namespace Pyramid\Component\Entity\Cache;

class EmptyCache {

    public function resetCache($ids = null) {
        return true;
    }
    
    public function setCache($entities) {
        return true;
    }
    
    public function getCache($ids, $conditions = array()) {
        return array();
    }

}
