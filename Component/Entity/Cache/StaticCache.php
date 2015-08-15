<?php

/**
 * @file
 *
 * PHP StaticCache
 */

namespace Pyramid\Component\Entity\Cache;

class StaticCache {

    protected $entityCache = array();
    
    public function resetCache($ids = null) {
        if (!empty($ids)) {
            foreach ($ids as $id) {
                unset($this->entityCache[$id]);
            }
        }
        else {
            $this->entityCache = array();
        }
    }

    public function setCache($entities) {
        $this->entityCache += $entities;
    }

    public function getCache($ids, $conditions = array()) {
        $entities = array();
        if (!empty($this->entityCache)) {
            if ($ids) {
                $entities += array_intersect_key($this->entityCache, array_flip($ids));
            }
            elseif ($conditions) {
                $entities = $this->entityCache;
            }
        }
        if ($conditions) {
            foreach ($entities as $id => $entity) {
                $entity_values = (array) $entity;
                if (array_diff_assoc($conditions, $entity_values)) {
                    unset($entities[$id]);
                }
            }
        }
        
        return $entities;
    }

}
