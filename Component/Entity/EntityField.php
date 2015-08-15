<?php

/**
 * @file
 *
 * EntityField
 */

namespace Pyramid\Component\Entity;

/**
 * EntityField
 */
abstract class EntityField {

    //执行新增
    const FIELD_INSERT = 1;
    
    //执行更新
    const FIELD_UPDATE = 2;
    
    //执行数据读取后拼装
    public static function attachLoad($entityType, $entities) {
        if (empty($entities)) {
            return ;
        }
        self::fieldLoad($entityType, $entities);
    }

    //执行数据读取
    public static function fieldLoad($entityType, $entities) {
        $info = EntityConfigurator::getEntityInfo($entityType);
        $ids = array_keys($entities);
        foreach ($info['cck'] as $field_name => $data) {
            $table = self::getFieldTableName($field_name);
            //走主键查询
            if (empty($info['wideType'])) {
                $query = db_select($table, 't')
                            ->fields('t')
                            ->condition('entity_type', $entityType)
                            ->condition('entity_id', $ids,'IN')
                            ->orderBy('delta');
            }
            else {
                 $query = db_select($table, 't')
                            ->fields('t')
                            ->condition('entity_id', $ids,'IN');
            }
            $results = $query->execute();
            foreach ($results as $row) {
                $item = array();
                foreach ($data['columns'] as $column => $spec) {
                    $column_name = self::getColumnName($column, $field_name);
                    $item[$column] = $row->$column_name;
                }
                $entities[$row->entity_id]->{$field_name}[] = $item;
            }
            //确保set每个field_name
            foreach ($entities as $id => $entity) {
                if (!property_exists($entity, $field_name)) {
                    $entity->{$field_name} = array();
                }
            }
        }
    }
    
    //执行数据添加/修改
    public static function fieldWrite($entityType, $entity, $op = self::FIELD_INSERT) {
        $info = EntityConfigurator::getEntityInfo($entityType);
        foreach ($info['cck'] as $field_name => $data) {
            //没有这个字段的数据,不做任何操作
            if (!property_exists($entity, $field_name)) {
                continue;
            }
            $table = self::getFieldTableName($field_name);
            $entityId = $entity->{$info['primaryKey']};
            //如果操作为update,并且新旧记录一致,则跳过
            if ($op == self::FIELD_UPDATE && property_exists($entity, 'origEntity')) {
                if (property_exists($entity->origEntity, $field_name) 
                    && $entity->{$field_name} === $entity->origEntity->{$field_name}) {
                    continue;
                }
            }
            //操作为update,先删除现有记录
            if ($op == self::FIELD_UPDATE) {
                db_delete($table)
                   ->condition('entity_type', $entityType)
                   ->condition('entity_id', $entityId)
                   ->execute();
            }
            //取匹配field_name的数据
            $items = $entity->{$field_name};
            //如果没有field_name数据,则不进行操作
            if (empty($items) || !is_array($items)) {
                continue;
            }
            //待更新的字段列表
            $columns = array('entity_type', 'entity_id', 'delta');
            foreach ($data['columns'] as $column => $val) {
                $columns[] = self::getColumnName($column, $field_name);
            }
            //指定Insert的表和字段
            $query = db_insert($table)->fields($columns);            
            //为每个field_name的field指定新增值
            $delta = 0;            
            foreach ($items as $item) {
                $record = array(
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'delta'       => $delta,
                );
                foreach ($data['columns'] as $column => $spec) {
                    $record[self::getColumnName($column, $field_name)] = self::convertDataType(
                        isset($item[$column]) ? $item[$column] : null,
                        $spec
                    );
                }
                $query->values($record);
                $delta++;
            }
            $query->execute();
        }
    }
    
    //执行数据删除
    public static function fieldDelete($entityType, $entityId) {
        $info = EntityConfigurator::getEntityInfo($entityType);
        foreach ($info['cck'] as $field_name => $data) {
            $table = self::getFieldTableName($field_name);
            db_delete($table)
                ->condition('entity_type', $entityType)
                ->condition('entity_id', $entityId)
                ->execute();
        }
    }
    
    //单表写数据
    public static function writeTable($table, $item, $primary_keys = array()) {
        $schema = $table;
        $fields = array();
        if (is_string($primary_keys)) {
            $primary_keys = array($primary_keys);
        }
        foreach ($schema as $field => $spec) {
            if (!empty($spec['increment'])) {
                $serial = $field;
            }
            if (in_array($field, $primary_keys)) {
                continue;
            }
            if (!property_exists($item, $field)) {
                continue;
            }
            $fields[$field] = $item->$field;
        }
        if (empty($fields)) {
            return ;
        }
        //没有指定keys,则新增
        if (empty($primary_keys)) {
            $query_return = db_insert($table)->fields($fields)->execute();
            if (isset($serial)) {
                $item->$serial = $query_return;
            }
            //赋其它字段默认值
            foreach ($schema as $field => $spec) {
                if (isset($spec['default']) && !property_exists($item, $field)) {
                    $item->$field = $spec['default'];
                }
            }
        }
        //指定了keys,执行更新
        else {
            $query = db_update($table)->fields($fields);
            foreach ($primary_keys as $key) {
                $query->condition($key, $item->$key);
            }
            $query_return = $query->execute();
        }
    }
    
    //获取field_name的实际表名
    protected static function getFieldTableName($field_name) {
        return 'data_' . $field_name;
    }
    
    //获取column的实际字段名
    protected static function getColumnName($column, $field_name) {
        return $field_name . '_' . $column;
    }
    
    //数据类型简单转换处理
    protected static function convertDataType($value, $spec) {
        if (isset($spec['type'])) {
            if (strpos($spec['type'], 'int') !== false) {
                return is_null($value) ? $spec['default'] : (int) $value;
            }
            elseif ($spec['type'] == 'decimal') {
                return is_null($value) ? $spec['default'] : number_format((float) $value, $spec['decimal'], '.', '');
            }
            elseif (strpos($spec['type'], 'char') !== false) {
                return is_null($value) ? $spec['default'] : trim((string) $value);
            }
            elseif (!empty($spec['not null'])) {
                return is_null($value) ? $spec['default'] : trim($value);
            }            
        }
        
        return trim($value);
    }

}
