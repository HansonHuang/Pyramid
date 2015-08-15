<?php

/**
 * @file
 *
 * Entity配置器
 */

namespace Pyramid\Component\Entity;

/**
 * Entity 配置器
 */
abstract class EntityConfigurator {
    
    /**
     * 所有Entity的配置信息
     *
     * @var array
     */
    protected static $entityInfo = array();
    
    //默认info配置信息
    protected static $defaultInfo = array(
        'controller' => 'Pyramid\\Component\\Entity\\EntityController',   //默认控制器
        'cacheBin'   => 'Pyramid\\Component\\Entity\\Cache\\StaticCache', //默认缓存器
        'primaryKey' => 'id',     //必须: 基表主键
        'baseTable'  => 'entity', //必须: 基表名
        'wideType'   => false,    //可选: true时只要entity_id匹配
        //基表字段
        //'baseSchema' => array(
            ////fields => array(
                ////字段名=>信息
            ////)
        //),
        //副表列表
        //'cck'        => array(
            ////副表名=> array(
                ////columns => array(
                    ////字段名=>信息
                ////)
            ////)
        //),  
    );
    
    /**
     * 注册Entity
     */
    public static function register($entityType, array $info = array()) {
        if (isset(self::$entityInfo[$entityType])) {
            throw new EntityException('Entity Type ' . $entityType . ' is already existed.');
        }
        self::$entityInfo[$entityType] = $info + self::$defaultInfo;        
        //设置缓存器 
        if (!self::$entityInfo[$entityType]['cacheBin']) {
            self::$entityInfo[$entityType]['cacheBin'] = new Cache\EmptyCache($entityType);
        } elseif (is_string(self::$entityInfo[$entityType]['cacheBin'])) {
            $class = self::$entityInfo[$entityType]['cacheBin'];
            self::$entityInfo[$entityType]['cacheBin'] = new $class($entityType);
        }
    }
    
    //获取EntityInfo
    public static function getEntityInfo($entityType) {
        if (!isset(self::$entityInfo[$entityType])) {
            throw new EntityException('No such Entity Type ' . $entityType);
        }
        if (!isset(self::$entityInfo['baseSchema'])) {
            self::$entityInfo[$entityType]['baseSchema']['fields'] = self::getEntityPrimaryFields($entityType);
        }
        if (!isset(self::$entityInfo[$entityType]['cck'])) {
            self::$entityInfo[$entityType]['cck'] = self::getEntityCustomFields($entityType);
        }
        
        return self::$entityInfo[$entityType];
    }
    
    //获取所有EntityInfo
    public static function getEntityInfos() {
        foreach (self::$entityInfo as $entityType => $value) {
            if (!isset(self::$entityInfo[$entityType]['cck'])) {
                self::$entityInfo[$entityType]['cck'] = self::getEntityCustomFields($entityType);
            }
        }
        
        return self::$entityInfo;
    }
    
    //获取Entity的副表信息
    public static function getEntityCustomFields($entityType) {
        $return = array();
        $fields = db_select('field_config', 'c')
                    ->fields('c', array('field_name', 'data'))
                    ->condition('entity_type', $entityType)
                    ->condition('active', 1)
                    ->execute()
                    ->fetchAllAssoc('field_name');
        foreach ($fields as $field_name => $value) {
            if ($value->data) {
                $return[$field_name] = unserialize($value->data);
            }
        }
        
        return $return;
    }
    
    //获取Entity的主表信息
    public static function getEntityPrimaryFields($entityType) {
        static $schemas;
        if (is_null($schemas)) {
            $schemas = db_schema()->getSchema();
        }
        $table = self::$entityInfo[$entityType]['baseTable'];
        $table = db_getconnection()->replacePrefix('{' . $table . '}');
        return isset($schemas[$table]) ? $schemas[$table] : array();
    }

}
