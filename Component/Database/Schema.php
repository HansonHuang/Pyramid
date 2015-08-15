<?php

/**
 * @file
 *
 * Schema
 */

namespace Pyramid\Component\Database;

class Schema extends Query {
    
    /**
     * Schema信息
     *
     * @var array
     */
    protected $schema = array();
    
    /**
     * 析构函数
     */
    public function __construct(Connection $connection, $options) {
        parent::__construct($connection, $options);
        if (!empty($options['database'])) {
            $this->explainSchema($options);
        }
    }
    
    /**
     * 获取Schema信息
     */
    public function getSchema() {
        return $this->schema;
    }
    
    /**
     * 执行获取Schema
     */
    public function explainSchema($options) {
        $info = $this->connection->query('
                SELECT
                    table_name, column_name, column_default, is_nullable, data_type, numeric_scale, column_comment, column_key, extra
                FROM information_schema.columns
                WHERE table_schema = :database
                ', array(':database' => $options['database']))->fetchAll();
        foreach ($info as $v) {
            $this->schema[$v->table_name][$v->column_name] = array(
                'description' => $v->column_comment,
                'type'        => $v->data_type,
                'default'     => $v->column_default,                    
                'not null'    => $v->is_nullable == 'NO',
                'key type'    => $v->column_key, //PRI MUL UNI
                'increment'   => $v->extra == 'auto_increment',
                'decimal'     => $v->numeric_scale ?: 0,
            );
        }

        return $this;
    }

    /**
     * 表是否存在
     */
    public function tableExists($table) {
        $table = $this->connection->replacePrefix('{' . $table . '}');        
        return isset($this->schema[$table]);
    }
    
    /**
     * 字段是否存在
     */
    public function fieldExists($table, $column) {
        $table = $this->connection->replacePrefix('{' . $table . '}');        
        return isset($this->schema[$table][$column]);
    }
    
    /**
     * 索引是否存在
     */
    public function indexExists($table, $name) {
        $row = $this->connection->query('SHOW INDEX FROM {' . $table . "} WHERE key_name = '$name'")->fetchAssoc();
        return isset($row['Key_name']);
    }
    
    /**
     * 创建表
     */
    public function createTable($name, $table) {
        if ($this->tableExists($name)) {
            throw new SchemaException("$name already exists.");
        }
        if (is_string($table)) {
            $this->connection->query($table);
        }
        //todo table is array
    }
    
    /**
     * 修改表名
     */
    public function renameTable($table, $new_name) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        if ($this->tableExists($new_name)) {
            throw new SchemaException("$new_name already exists.");
        }
        return $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO `{' . $new_name .'}`');
    }
    
    /**
     * 删除表
     */
    public function dropTable($table) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        $this->connection->query('DROP TABLE {' . $table . '}');
        return true;
    }
    
    /**
     * 添加字段
     */
    public function addField($table, $field, $spec) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        if ($this->fieldExists($table, $field)) {
            throw new SchemaException("$table.$field already exists.");
        }
        if (is_string($spec)) {
            $this->connection->query($spec);
        }
        //todo spec is array
    }
    
    /**
     * 删除字段
     */
    public function dropField($table, $field) {
        if (!$this->fieldExists($table, $field)) {
            return false;
        }
        $this->connection->query('ALTER TABLE {' . $table . '} DROP `' . $field . '`');
        return true;
    }
    
    /**
     * 变更字段
     */
    public function changeField($table, $field, $field_new, $spec) {
        if (!$this->fieldExists($table, $field)) {
            throw new SchemaException("$table.$field doesn't exists.");
        }
        if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
            throw new SchemaException("$table.$field_new already exists.");
        }
        if (is_string($spec)) {
            $this->connection->query($spec);
        }
        //todo spec is array
    }
    
    /**
     * 设置字段默认值
     */
    public function fieldSetDefault($table, $field, $default) {
        if (!$this->fieldExists($table, $field)) {
            throw new SchemaException("$table.$field doesn't exists.");
        }
        if (!isset($default)) {
            $default = 'null';
        } else {
            $default = is_string($default) ? "'$default'" : $default;
        }
        $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` SET DEFAULT ' . $default);
    }
    
    /**
     * 设置字段无默认值
     */
    public function fieldSetNoDefault($table, $field) {
        if (!$this->fieldExists($table, $field)) {
            throw new SchemaException("$table.$field doesn't exists.");
        }
        $this->connection->query('ALTER TABLE {' . $table . '} ALTER COLUMN `' . $field . '` DROP DEFAULT');
    }
    
    /**
     * 添加主键
     */
    public function addPrimaryKey($table, $fields) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        if ($this->indexExists($table, 'PRIMARY')) {
            throw new SchemaException("$table primary key already exists.");
        }
        $this->connection->query('ALTER TABLE {' . $table . '} ADD PRIMARY KEY (' . $this->createKeySql($fields) . ')');
    }
    
    /**
     * 删除主键
     */
    public function dropPrimaryKey($table) {
        if (!$this->indexExists($table, 'PRIMARY')) {
            return false;
        }
        $this->connection->query('ALTER TABLE {' . $table . '} DROP PRIMARY KEY');
        return true;
    }
    
    /**
     * 添加唯一索引
     */
    public function addUniqueKey($table, $name, $fields) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        if ($this->indexExists($table, $name)) {
            throw new SchemaException("$table INDEX $name already exists.");
        }
        $this->connection->query('ALTER TABLE {' . $table . '} ADD UNIQUE KEY `' . $name . '` (' . $this->createKeySql($fields) . ')');
    }
    
    /**
     * 删除唯一索引
     */
    public function dropUniqueKey($table, $name) {
        if (!$this->indexExists($table, $name)) {
            return false;
        }
        $this->connection->query('ALTER TABLE {' . $table . '} DROP KEY `' . $name . '`');
        return true;
    }
    
    /**
     * 添加索引
     */
    public function addIndex($table, $name, $fields) {
        if (!$this->tableExists($table)) {
            throw new SchemaException("$table doesn't exists.");
        }
        if ($this->indexExists($table, $name)) {
            throw new SchemaException("$table INDEX $name already exists.");
        }
        $this->connection->query('ALTER TABLE {' . $table . '} ADD INDEX `' . $name . '` (' . $this->createKeySql($fields) . ')');
    }
    
    /**
     * 删除索引
     */
    public function dropIndex($table, $name) {
        if (!$this->indexExists($table, $name)) {
            return false;
        }
        $this->connection->query('ALTER TABLE {' . $table . '} DROP INDEX `' . $name . '`');
        return true;
    }

    //拼缀索引SQL
    protected function createKeySql($fields) {
        $return = array();
        foreach ((array)$fields as $field) {
            if (is_array($field)) {
                $return[] = '`' . $field[0] . '`(' . $field[1] . ')';
            } else {
                $return[] = '`' . $field . '`';
            }
        }
        return implode(', ', $return);
    }

}

class SchemaException extends \Exception {}
