<?php

/**
 * @file
 *
 * Entity
 */

use Pyramid\Component\Entity\EntityController;
use Pyramid\Component\Entity\EntityConfigurator;
use Pyramid\Component\Entity\EntityField;
use Pyramid\Component\Entity\EntityRequest;
use Pyramid\Component\Entity\EntityResponse;

//注册Entity
function entity_register($entityType, array $info = array()) {
    EntityConfigurator::register($entityType, $info);
}

//读取Entity
function entity_load($entityType, $ids = array(), $conditions = array(), $fromQuery = false) {
    return entity($entityType)->load($ids, $conditions, $fromQuery);
}

//读取Entity对象
function entity_object($entityType, $ids = array(), $conditions = array(), $fromQuery = false) {
    return entity($entityType)->loadObject($ids, $conditions, $fromQuery);
}

//添加Entity
function entity_insert($entityType, $entity) {
    return entity($entityType)->insert($entity);
}

//更新Entity
function entity_update($entityType, $entity) {
    return entity($entityType)->update($entity);
}

//删除Entity
function entity_delete($entityType, $entity) {
    return entity($entityType)->delete($entity);
}

//Entity Merge
function entity_merge($entityType, $entity, $conditions = array()) {
    return entity($entityType)->merge($entity, $conditions);
}

//Entity Info
function entity_info($entityType) {
    return entity($entityType)->info();
}

//Entity cache
function entity_cache($entityType) {
    return entity($entityType)->cacheBin();
}

//Entity的控制器类单例
function entity($entityType) {
    static $controllers = array();
    
    if (!isset($controllers[$entityType])) {
        $info  = EntityConfigurator::getEntityInfo($entityType);
        $class = $info['controller'];
        $controllers[$entityType] = new $class($entityType);
    }
    
    return $controllers[$entityType];
}

/*
 * 过滤不符合要求的Entity cck数据
 *
 * @param object $entity
 * @param string $field
 * @param array  $columns
 *
 * e.g.
 *   entity_filter($entity, 'field_corporation_alas', array('value'=>array('')))
 *   当field_corporation_alas的value为空时,过滤掉该条记录
 */
function entity_filter($entity, $field, $columns) {
    if (empty($entity->$field)) {
        return ;
    }
    if (is_array($entity->$field)) {
        foreach ($entity->$field as $k => $v) {
            if (!is_array($v)) {
                unset($entity->{$field}[$k]);
                continue;
            }
            foreach ($columns as $column => $values) {
                if (!isset($v[$column])) {
                    unset($entity->{$field}[$k]);
                    continue 2;
                }
                if (in_array(trim($v[$column]), $values)) {
                    unset($entity->{$field}[$k]);
                    continue 2;
                }
            }
        }
    }
    else {
        unset($entity->$field);
    }
}

//Entity API 调用
function entity_invoke($api, array $args = array()) {
    static $apis;
    if (!isset($apis)) {
        
    }
    $api = strtr($api, '_', '\\');
    if (isset($apis[$api])) {
        $class   = $apis[$api]['class'];
        $method  = $apis[$api]['method'];
        $request = new EntityRequest($args);
        $result  = $class::$method($request);
        if ($result instanceof EntityResponse) {
            return $result;
        } else {
            return new EntityResponse($result);
        }
    }

    return new EntityResponse(null, array(), 404);
}

//Entity Request
function entity_request($args = array()) {
    return new EntityRequest($args);
}

//Entity Response
function entity_response($data = null, $variables = array(), $status = 0, $message = '') {
    return new EntityResponse($data, $variables, $status, $message);
}
