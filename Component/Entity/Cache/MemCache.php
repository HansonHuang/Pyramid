<?php

/**
 * @file
 *
 * Memcached
 */

namespace Pyramid\Component\Entity\Cache;

class MemCache {
    
    protected $cachePrefix = '';
    
    public function __construct($entityType) {
        $this->cachePrefix = 'entity:' . $entityType . ':';
    }
    
    public function resetCache($ids = null) {
        if (!empty($ids)) {
            foreach ($ids as $id) {
                cache()->delete($this->getCid($id));
            }
        }
        //todo flush cache only for this entity type
    }

    public function setCache($entities) {
        foreach ($entities as $id => $entity) {
            cache()->set($this->getCid($id), $entity);
        }
    }

    public function getCache($ids, $conditions = array()) {
        $entities = array();
        foreach ($ids as $id) {
            $cache = cache()->get($this->getCid($id));
            if ($cache !== false) {
                $entities[$id] = $cache;
            }
        }
        if ($conditions) {
            foreach ($entities as $id => $entity) {
                if (empty($entity)) {
                    continue;
                }
                $entity_values = (array) $entity;
                if (array_diff_assoc($conditions, $entity_values)) {
                    unset($entities[$id]);
                }
            }
        }
        
        return $entities;
    }
    
    protected function getCid($id) {
        return $this->cachePrefix . $id;
    }

}
