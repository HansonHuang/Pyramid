<?php

/**
 * @file
 *
 * CCK生成 field_config
 */

exit;

//载入框架和配置文件
require_once dirname(__DIR__) . '/Pyramid/Pyramid.php';
require_once dirname(__DIR__) . '/config.php';

auto_field_config();

function auto_field_config() {
    $connect = db_getconnection();
    $schemas = db_schema()->getSchema();
    $fields  = db_select('field_config','f')
                    ->fields('f')
                    ->execute()
                    ->fetchAll();
    foreach ($fields as $v) {
        $field = $connect->replacePrefix('{'.'data_' . $v->field_name.'}');
        if (isset($schemas[$field])) {
            $config = array();
            foreach ($schemas[$field] as $k => $vv) {
                if (in_array($k, array('entity_type', 'entity_id', 'delta'))) {
                    continue;
                }
                $config[str_replace($v->field_name.'_', '', $k)] = $vv;
            }
            $data = array('columns' => $config,);
            db_update('field_config')
                ->fields(array('data' => serialize($data),))
                ->condition('id', $v->id)
                ->execute();
        }
    }
    echo 'done!';
}