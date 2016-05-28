<?php

/**
 * @file
 *
 * Entity封装
 */

namespace Pyramid\Component\Entity;

use Pyramid\Component\Database\Database;
use stdClass;
use Exception;

/**
 * Entity 控制器
 *
 * @notice before不在事务内,执行于最前
 * @notice after在事务内,最后执行
 */
class EntityController {
    
    //entity类型
    protected $entityType;

    //entity的配置信息
    protected $entityInfo;
    
    //entity主键
    protected $primaryKey;
    
    //cache handler
    protected $cacheBin;
    
    /**
     * 析构函数
     *
     * @param string $entityType
     */
    public function __construct($entityType) {
        $this->entityType  = $entityType;        
        $this->entityInfo  = EntityConfigurator::getEntityInfo($entityType);
        $this->primaryKey  = $this->entityInfo['primaryKey'];
        $this->cacheBin    = $this->entityInfo['cacheBin'];
    }
    
    /**
     * 读取Entity
     *
     * @return array
     */
    public function load($ids = array(), $conditions = array(), $fromQuery = false) {
        if ($fromQuery) {
            return $this->loadFromQuery($ids, $conditions);
        }
        if (is_numeric($ids)) {
            $ids = array($ids);
        }
        //是否传入了ids
        $has_ids  = !empty($ids) ? array_flip($ids) : false;
        $entities = array();
        //优先从缓存读取
        $entities += $this->cacheBin->getCache($ids, $conditions);
        //没有从缓存读到信息的ids列表
        $ids = array_keys(array_diff_key(array_flip($ids), $entities));
        //通过Query读取基表数据;如果没有传入ids,但设置了conditions,则总是查询一次主表
        if ($ids || ($conditions && !$has_ids)) {
            $query = $this->buildQuery($ids, $conditions);
            $query_entities = $query->execute()->fetchAllAssoc($this->primaryKey);            
        }
        //对Query的数据再拼装
        if (!empty($query_entities)) {
            //没有传入ids,尝试用主键取缓存;传入了ids,肯定需要读取副表信息
            if (!$has_ids) {
                $query_entities  = array_diff_key($query_entities, $entities);
                $cached_entities = $this->cacheBin->getCache(array_keys($query_entities));
                $entities += $cached_entities;
                $query_entities = array_diff_key($query_entities, $cached_entities);
            }
            $this->attachLoad($query_entities);
            $entities += $query_entities;            
            $this->cacheBin->setCache($query_entities);
        }
        //确保ids的顺序
        if ($has_ids) {
            $has_ids = array_intersect_key($has_ids, $entities);
            foreach ($entities as $id => $entity) {
                $has_ids[$id] = $entity;
            }
            $entities = $has_ids;
        }
        //执行后期处理
        $this->afterLoad($entities);
        
        return $entities;
    }
    
    /**
     * 读取一个Entity对象
     *
     * @return object|false
     */
    public function loadObject($ids = array(), $conditions = array(), $fromQuery = false) {
        $entities = $this->load($ids, $conditions, $fromQuery);
        return reset($entities);
    }

    /**
     * 新增Entity
     */
    public function insert($entity) {
        $this->beforeSave($entity, 'INSERT');
        $fields = array();
        foreach ($this->entityInfo['baseSchema']['fields'] as $field => $val) {
            $field = trim($field, '`');
            if (property_exists($entity, $field)) {
                if (is_scalar($entity->$field)) {
                    $fields[$field] = trim($entity->$field);
                } else {
                    $fields[$field] = $entity->$field;
                }
            }
        }
        if (empty($fields)) {
            return ;
        }
        $options  = array('return' => Database::RETURN_INSERT_ID);
        $transaction = db_transaction();
        try {
            $entityId = db_insert($this->entityInfo['baseTable'], $options)
                        ->fields($fields)
                        ->execute();
            $entity->{$this->primaryKey} = $entityId;
            EntityField::fieldWrite($this->entityType, $entity, EntityField::FIELD_INSERT);
            $this->afterSave($entity, 'INSERT');
        }
        catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
        
        return $entity;
    }

    /**
     * 更新Entity
     */
    public function update($entity) {
        $this->beforeSave($entity, 'UPDATE');
        //没有主键值,抛出异常
        if (!property_exists($entity, $this->primaryKey)) {
            throw new Exception('Entity for update has no primaryKey (' . $this->entityType .')');
        }
        $entityId = $entity->{$this->primaryKey};
        $origEntity = $this->load(array($entityId));
        $origEntity = reset($origEntity);
        //无此Entity纪录,抛出异常
        if (empty($origEntity)) {
            throw new Exception('Entity for update is not exists (' . $this->entityType .':'.$entityId .')');
        }
        $entity->origEntity = $origEntity;
        $fields = array();
        //更新变动的数据
        foreach ($this->entityInfo['baseSchema']['fields'] as $field => $val) {
            $field = trim($field, '`');
            if (property_exists($entity, $field) && $field != $this->primaryKey) {
                if (is_scalar($entity->$field)) {
                    $value = trim($entity->$field);
                }
                if ($value != $origEntity->$field) {
                    $fields[$field] = $value;
                }
            }
        }
        $transaction = db_transaction();
        try {
            if (!empty($fields)) {
                db_update($this->entityInfo['baseTable'])
                    ->fields($fields)
                    ->condition($this->primaryKey, $entityId)
                    ->execute();
            }
            EntityField::fieldWrite($this->entityType, $entity, EntityField::FIELD_UPDATE);
            $this->afterSave($entity, 'UPDATE');
        }
        catch (Exception $e) {
            $transaction->rollback();
            throw $e;
        }
        $this->cacheBin->resetCache(array($entityId));
        
        return $entity;
    }

    /**
     * 删除Entity
     */
    public function delete($entity) {
        $this->beforeDelete($entity);
        if (is_numeric($entity)) {
            $entityId = $entity;
        } else {
            $entityId = $entity->{$this->primaryKey};
        }
        $transaction = db_transaction();
        try {
            db_delete($this->entityInfo['baseTable'])
                ->condition($this->primaryKey, $entityId)
                ->execute();
            EntityField::fieldDelete($this->entityType, $entityId);
            $this->afterDelete($entityId);
        }
        catch (Exception $e) {
            $transaction->rollback();
            throw $e;
            return false;
        }
        $this->cacheBin->resetCache(array($entityId));
        
        return true;
    }
    
    /**
     * Merge Entity
     */
    public function merge($entity, $conditions = array()) {
        if (empty($conditions)) {
            return $this->insert($entity);
        }
        //处理query,转换select的字段为 select 1 
        $query  = $this->buildQuery(array(), $conditions);
        $tables = &$query->getTables();
        foreach ($tables as $alias => &$table) {
            unset($table['all_fields']);
        }
        $fields = &$query->getFields();
        foreach (array_keys($fields) as $field) {
            unset($fields[$field]);
        }
        $query->addField('base', $this->primaryKey);
        if ($entityId = $query->execute()->fetchField()) {
            $entity->{$this->primaryKey} = $entityId;
            $this->update($entity);
        } else {
            $this->insert($entity);
        }
        
        return $entity;
    }
    
    /**
     * Entity Info
     */
    public function info() {
        $return = new stdClass;
        foreach ($this->entityInfo['baseSchema']['fields'] as $k => $v) {
            $return->{$k} = $v['description'];
        }
        foreach ($this->entityInfo['cck'] as $k => $v) {
            $element = array();
            foreach ($v['columns'] as $field => $spec) {
                $element[$field] = isset($spec['description']) ? $spec['description'] : (string) $spec;
            }
            $return->{$k} = array($element);
        }
        
        return $return;
    }
    
    /**
     * 返回cache handler对象
     */
    public function cacheBin() {
        return $this->cacheBin;
    }
    
    /**
     * 直接从数据库读取
     */
    public function loadFromQuery($ids = array(), $conditions = array()) {
        if (is_numeric($ids)) {
            $ids = array($ids);
        }
        $entities = array();
        if ($ids || $conditions) {
            $query = $this->buildQuery($ids, $conditions);
            $query_entities = $query->execute()->fetchAllAssoc($this->primaryKey);            
        }
        if (!empty($query_entities)) {
            $this->attachLoad($query_entities);
            $entities += $query_entities;
        }
        
        return $entities;
    }
    
    /**
     * 创建Entity的Query语句
     *
     * @param array $ids
     * @param array $conditions
     *
     * @return $query
     */
    protected function buildQuery($ids, $conditions = array()) {
        $query = db_select($this->entityInfo['baseTable'], 'base');
        $query->fields('base');
        //主键条件
        if ($ids) {
            $query->condition('base.' . $this->primaryKey, $ids, 'IN');
        }
        //其他的主表字段条件
        if ($conditions) {
            foreach ($conditions as $field => $value) {
                if (is_array($value)) {
                    $value['operator'] = isset($value['operator']) ? $value['operator'] : '=';
                    $query->condition('base.' . $field, $value['value'], $value['operator']);
                }
                else {
                    $query->condition('base.' . $field, $value);
                }
            }
        }

        return $query;
    }
    
    /**
     * 执行主数据读取后的副数据拼装
     *
     * @notice 此拼装在缓存之前
     * @param array $query_entities
     */
    protected function attachLoad(&$query_entities) {
        EntityField::attachLoad($this->entityType, $query_entities);        
    }

    /**
     * 执行数据读取后的再处理
     *
     * @param array $query_entities
     */
    protected function afterLoad(&$query_entities) {}
    
    /**
     * 执行数据保存前的再处理
     *
     * @param object $entity
     * @param string $type
     */
    protected function beforeSave($entity, $type = 'UPDATE') {}
    
    /**
     * 执行数据保存后的再处理
     *
     * @param object $entity
     * @param string $type
     */
    protected function afterSave($entity, $type = 'UPDATE') {}
    
    /**
     * 执行数据删除后的再处理
     *
     * @param $entityId
     */
    protected function afterDelete($entityId) {}
    
    /**
     * 执行数据删除前的再处理
     *
     * @param object $entity
     */
    protected function beforeDelete($entity) {}
    
}
