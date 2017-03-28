<?php

/**
 * Шаблон конфигурации CMS
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('CMS_VERSION', '6.2.a');

@ini_set('magic_quotes_gpc', 0);
	
$_config = array(
		'document_root' => $_SERVER['DOCUMENT_ROOT'],
		'http_base'     => 'http://'.$_SERVER['HTTP_HOST'].'/',
        'indexphp_size' => 0,
        'cookie_prefix' => 'cms_',
        'table_prefix'  => 'cms_',
        'db_host'       => 'localhost',
        'db_name'       => '',
        'db_login'      => 'root',
        'db_password'   => '',
        'db_class'      => 'DB_MySQL', // на будущее
        'ora_db'        => '',
        'ora_schema'    => '',
        'ora_user'      => '',
        'ora_password'  => ''
	);

define('CMS_MATERIAL_TYPE_TEXT',   'text');
define('CMS_MATERIAL_TYPE_HTML',   'html');
define('CMS_MATERIAL_TYPE_CSS',    'css');
define('CMS_MATERIAL_TYPE_JS',     'javascript');
define('CMS_MATERIAL_TYPE_PLUGIN', 'plugin');

/* глобализуем часть конфигурации, не редактируйте строки ниже! */    

$_ROOT = $_config['document_root'];
$_BASE = $_config['http_base'];
?>