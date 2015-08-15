<?php

/**
 * @file
 *
 * 数据库常用封装
 */

use Pyramid\Component\Database\Database;
use Pyramid\Component\Database\Condition;

/**
 * 设置数据库连接信息
 *
 * @param string $target
 * @param array  $config
 */
function db_config($target, array $config = array()) {
    Database::setConfig($target, $config);
}

/**
 * 获取数据库连接对象
 *
 * @param string $target
 * @param array  $config
 */
function db_getconnection($options = array()) {
    if (empty($options['target'])) {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target']);
}

/**
 * @usage
 *
 * db_query('SELECT id FROM table WHERE id = :id', array(':id'=>1))
 *   ->fetchAssoc();
 */
function db_query($query, array $args = array(), array $options = array()) {
    if (empty($options['target'])) {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->query($query, $args, $options);
}

/**
 * @usage
 *
 * db_select('table', 'alias')
 *   ->fields('alias')
 *   ->condition('id', 1)
 *   ->execute()
 *   ->fetchAssoc();
 */
function db_select($table, $alias = null, array $options = array()) {
    if (empty($options['target'])) {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->select($table, $alias, $options);
}

/**
 * @usage
 *
 * db_insert('table')
 *   ->fields(array(
 *      'name' => 'value',
 *   ))
 *   ->execute();
 */
function db_insert($table, array $options = array()) {
    if (empty($options['target']) || $options['target'] == 'slave') {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->insert($table, $options);
}

/**
 * @usage
 *
 * db_update('table')
 *   ->fields(array(
 *      'name' => 'value',
 *   ))
 *   ->condition('id', 1)
 *   ->execute();
 */
function db_update($table, array $options = array()) {
    if (empty($options['target']) || $options['target'] == 'slave') {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->update($table, $options);
}

/**
 * @usage
 *
 * db_delete('table')
 *   ->condition('id', 1)
 *   ->execute();
 */
function db_delete($table, array $options = array()) {
    if (empty($options['target']) || $options['target'] == 'slave') {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->delete($table, $options);
}

/**
 * @usage
 *
 * db_merge('table')
 *   ->key(array(
 *     'id' => 1,
 *   ))
 *   ->fields(array(
 *      'name' => 'value',
 *   ))
 *   ->execute();
 */
function db_merge($table, array $options = array()) {
    if (empty($options['target']) || $options['target'] == 'slave') {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->merge($table, $options);
}

/**
 * @usage
 *
 * $tran = db_transaction();
 * try {
 *    execute some query ..
 * }
 * catch(Exception $e) {
 *    $tran->rollback();
 * }
 */
function db_transaction($name = null, array $options = array()) {
    if (empty($options['target'])) {
        $options['target'] = 'default';
    }
    return Database::getConnection($options['target'])->startTransaction($name);
}

/**
 * @usage
 *
 * db_like($value) . '%'
 */
function db_like($string) {
    return Database::getConnection()->escapeLike($string);
}

/**
 * @usage
 *
 * db_or()->condition('id',1)->condition('name', 'value')
 */
function db_or() {
    return new Condition('OR');
}

/**
 * @usage
 *
 * db_and()->condition('id',1)->condition('name', 'value')
 */
function db_and() {
    return new Condition('AND');
}

/**
 * @usage
 *
 * db_xor()->condition('id',1)->condition('name', 'value')
 */
function db_xor() {
    return new Condition('XOR');
}

/**
 * @usage
 *
 * db_condition()->condition('id',1)->condition('name', 'value')
 */
function db_condition($conjunction) {
    return new Condition($conjunction);
}

/**
 * 关闭链接
 */
function db_close(array $options = array()) {
    if (empty($options['target'])) {
        $options['target'] = null;
    }
    Database::closeConnection($options['target']);
}

/**
 * 开始query日志
 */
function db_start_log($log_key = 'default', $target = 'default') {
    Database::startLog($log_key, $target);
}

/**
 * 读取query日志
 */
function db_get_log($log_key = 'default', $target = 'default') {
    return Database::getLog($log_key, $target);
}


/**
 * @usage
 *
 * db_schema()->getSchema()
 */
function db_schema($target = 'default') {
    $options = Database::getConfig($target);    
    if (empty($options)) {
        $options['target'] = 'default';
    } else {
        $options['target'] = $target;
    }
    return Database::getConnection($options['target'])->schema($options);
}

