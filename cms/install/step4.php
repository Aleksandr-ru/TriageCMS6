<?php

/**
 * Установщик CMS - шаг 4, завершение установки
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2013
 */
 
define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../classes/Debugger.php");
require_once(dirname(__FILE__)."/../config.default.php");

//echo "OK";
//exit;
//print_r($_POST);
// [db_class] => DB_MySQL [db_host] => localhost [db_port] => 3306 [db_login] => root [db_password] => qqq123 [db_name] => q [db_create] => 1 [table_prefix] => cms_ [document_root] => D:\Webroot\cmstest.x.utf8 [http_base] => http://cms6.webhost/ [cookie_prefix] => cms_ [user_login] => q [user_pass1] => q [user_pass2] => q [user_email] => form3@aa.bb

function show_errors($err) 
{
    if(sizeof($err)) {
        foreach($err as $i=>$e) $err[$i] = "<li>".htmlspecialchars($e)."</li>";
        echo '<div class="error"><b>Невозможно завершить установку</b><ul>'.implode("\r\n", $err).'</ul></div>';
        exit ;  
    } 
}

$err = array();

if($_POST['db_port']) $_POST['db_host'] .= ':'.$_POST['db_port'];

if(isset($_POST['db_create']) && $_POST['db_create']) {
    $dbh = mysql_connect( $_POST['db_host'], $_POST['db_login'], $_POST['db_password']);
    $sql = "CREATE DATABASE `".addslashes($_POST['db_name'])."` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
    mysql_query($sql);
    if(mysql_errno()) $err[] = "Не удалось создать базу, сервер сообщает: ".mysql_error();
    show_errors($err);
}

/* ------------------------------------------------------------------------------------------------- */

if($fh = fopen($_POST['document_root'].'/cms/config.php', 'w')) {
    fputs($fh, "<?php\r\n");
    fputs($fh, "/**\r\n");
    fputs($fh, " * Конфигурация CMS\r\n");
    fputs($fh, " * \r\n");
    fputs($fh, " * @package Triage CMS v.6\r\n");
    fputs($fh, " * @author Rebel\r\n");
    fputs($fh, " * @copyright 2011\r\n");
    fputs($fh, " */\r\n");
    fputs($fh, "require_once(dirname(__FILE__).'/config.default.php');\r\n");
    fputs($fh, "\$_config = array_merge(\$_config, array(\r\n");
    fputs($fh, "'document_root' => '".$_POST['document_root']."',\r\n");
    fputs($fh, "'http_base'     => '".$_POST['http_base']."',\r\n");
    fputs($fh, "'indexphp_size' => ".filesize($_POST['document_root'].'/index.php').",\r\n");
    fputs($fh, "'cookie_prefix' => '".$_POST['cookie_prefix']."',\r\n");
    fputs($fh, "'table_prefix'  => '".$_POST['table_prefix']."',\r\n");
    fputs($fh, "'db_host'       => '".$_POST['db_host']."',\r\n");
    fputs($fh, "'db_name'       => '".$_POST['db_name']."',\r\n");
    fputs($fh, "'db_login'      => '".$_POST['db_login']."',\r\n");
    fputs($fh, "'db_password'   => '".$_POST['db_password']."',\r\n");
    fputs($fh, "'db_class'      => '".$_POST['db_class']."',\r\n");    
    if($_POST['is_oracle']) {        
        fputs($fh, "'ora_db'        => '".$_POST['ora_db']."',\r\n");
        fputs($fh, "'ora_schema'    => '".$_POST['ora_schema']."',\r\n");
        fputs($fh, "'ora_user'      => '".$_POST['ora_login']."',\r\n");
        fputs($fh, "'ora_password'  => '".$_POST['ora_password']."'\r\n");
    } else {
        fputs($fh, "'ora_db'        => '',\r\n");
        fputs($fh, "'ora_schema'    => '',\r\n");
        fputs($fh, "'ora_user'      => '',\r\n");
        fputs($fh, "'ora_password'  => ''\r\n");
    }
    fputs($fh, "));\r\n");
    fputs($fh, "/* глобализуем часть конфигурации, не редактируйте строки ниже! */  \r\n");
    fputs($fh, "\$_ROOT = \$_config['document_root'];\r\n");
    fputs($fh, "\$_BASE = \$_config['http_base'];\r\n");
    fputs($fh, "?>\r\n");
} else {
    $err[] = "Не удалось записать конфигурацию в файл '".($_POST['document_root'].'/cms/config.php')."'";
    show_errors($err);
}

require_once(dirname(__FILE__)."/../classes/DB.php");

/* ------------------------------------------------------------------------------------------------- */

$db = new DB_MySQL();
if($db->errno()) {
    $err[] = "Не удалось соединиться с базой, сервер сообщает: ".$db->error();
    show_errors($err);
}

$sql = array();

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_events')." (
  `event` varchar(200) character set utf8 NOT NULL default '',
  `plugin_uid` varchar(100) character set utf8 NOT NULL default '',
  PRIMARY KEY  (`event`,`plugin_uid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='обработчики событий';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_files')." (
  `id` int(11) unsigned NOT NULL auto_increment,
  `mat_id` int(10) unsigned NOT NULL default '0',
  `orig_name` varchar(200) character set utf8 NOT NULL default '',
  `clean_name` varchar(200) NOT NULL default '',
  `mime_type` varchar(100) character set utf8 NOT NULL default '',
  `size` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `mat_id` (`mat_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='файлы';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_groups')." (
  `id` int(4) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='группы доступа/безопасности';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_log')." (
  `datetime` datetime NOT NULL default '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL default '0',
  `text` varchar(255) character set utf8 NOT NULL default '',
  `tag` varchar(100) character set utf8 NOT NULL default '',
  KEY `user_id` (`user_id`),
  KEY `datetime` (`datetime`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='лог работы системы';";
/*
$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_material')." (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(200) character set utf8 NOT NULL default '',
  `type` enum('text','html','css','javascript','plugin') character set utf8 NOT NULL default 'text',
  `data` text character set utf8 NOT NULL,
  `access_group` int(4) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  `group_id` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='материалы сайта';";
*/
$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_material')." (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(200) character set utf8 NOT NULL default '',  
  `access_group` int(4) NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '0',
  `group_id` int(4) unsigned NOT NULL default '0',
  `text` text CHARACTER SET utf8,
  `html` text CHARACTER SET utf8,
  `css` text CHARACTER SET utf8,
  `javascript` text CHARACTER SET utf8,  
  `plugin` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY  (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='материалы сайта';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_material_groups')." (
  `id` int(4) unsigned NOT NULL auto_increment,
  `name` varchar(200) character set utf8 NOT NULL default '',
  `hidden` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `hidden` (`hidden`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='группы материалов';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_page_materials')." (
  `page_id` int(5) unsigned NOT NULL default '0',
  `material_id` int(10) unsigned NOT NULL default '0',
  `place_number` tinyint(3) unsigned NOT NULL default '0',
  `order` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`page_id`,`material_id`,`place_number`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='материалы страниц';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_pages')." (
  `id` int(5) unsigned NOT NULL auto_increment,
  `parent_id` int(5) unsigned NOT NULL default '0',
  `name` varchar(200) character set utf8 NOT NULL default '',
  `title` varchar(200) character set utf8 default NULL,
  `meta_keywords` varchar(200) character set utf8 default NULL,
  `meta_description` varchar(200) character set utf8 default NULL,
  `key` varchar(100) character set utf8 NOT NULL default '',
  `order` int(5) NOT NULL default '0',
  `template_id` int(3) unsigned NOT NULL default '0',
  `is_home` tinyint(1) unsigned NOT NULL default '0',
  `redirect` varchar(200) character set utf8 default NULL,
  `target` varchar(100) character set utf8 default NULL,
  `access_group` int(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `page_key` (`parent_id`,`key`),
  KEY `order` (`order`),
  KEY `is_home` (`is_home`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='страницы';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_plugin_options')." (
  `plugin_uid` varchar(100) NOT NULL default '',
  `material_id` int(10) unsigned NOT NULL default '0',
  `name` varchar(50) NOT NULL default '',
  `value` varchar(200) NOT NULL default '',
  `desc` varchar(200) default NULL,
  `sprav` varchar(250) default NULL,
  PRIMARY KEY  (`plugin_uid`,`material_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='опции плагинов';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_plugins')." (
  `uid` varchar(100) character set utf8 NOT NULL default '',
  `title` varchar(50) character set utf8 NOT NULL default '',
  `desc` varchar(100) character set utf8 default NULL,
  `active` tinyint(1) unsigned NOT NULL default '0',
  `access_group` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`uid`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='плагины';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_settings')." (
  `name` varchar(50) NOT NULL default '',
  `value` varchar(100) NOT NULL default '',
  `desc` varchar(200) default NULL,
  `sprav` varchar(200) default NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='настройки системы';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_templates')." (
  `id` int(3) unsigned NOT NULL auto_increment,
  `name` varchar(200) character set utf8 NOT NULL default '',
  `file` varchar(200) character set utf8 NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='шаблоны';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_user_groups')." (
  `user_id` int(10) unsigned NOT NULL default '0',
  `group_id` int(4) unsigned NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='пользователи в группах';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_users')." (
  `id` int(10) unsigned NOT NULL auto_increment,
  `login` varchar(50) character set utf8 NOT NULL default '',
  `password` varchar(32) character set utf8 NOT NULL default '',
  `email` varchar(200) character set utf8 NOT NULL default '',
  `active` tinyint(1) unsigned NOT NULL default '0',
  `super` tinyint(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='пользователи';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_variables')." (
  `name` varchar(200) character set utf8 NOT NULL default '',
  `page_id` int(5) unsigned NOT NULL default '0',
  `material_id` int(10) NOT NULL default '0',
  PRIMARY KEY  (`name`,`page_id`),
  KEY `page_id` (`page_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='переменные страниц';";

$sql[] = "CREATE TABLE IF NOT EXISTS ".$db->T('_trashcan')." (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_name` varchar(200) NOT NULL,
  `name` varchar(200) NOT NULL,
  `table` varchar(200) NOT NULL,
  `data_serialized` text NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='корзина';";

foreach($sql as $q) {
    $db->query($q);
    if($db->errno()) { $err[] = "Не удалось создать таблицу, сервер сообщает: ".$db->error(); }    
}
show_errors($err);

/* ------------------------------------------------------------------------------------------------- */

$sql = array();

$sql[] = "INSERT INTO ".$db->T('_groups')." VALUES (1, 'Зарегистрированные пользователи');";
$sql[] = "INSERT INTO ".$db->T('_groups')." VALUES (2, 'Администраторы');";
$sql[] = "INSERT INTO ".$db->T('_groups')." VALUES (3, 'Редакторы');";
$sql[] = "INSERT INTO ".$db->T('_users')." VALUES (NULL, ".$db->F($_POST['user_login']).", ".$db->F($_POST['user_pass1']).", ".$db->F($_POST['user_email']).", 1, 1);";

$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('cpl_access_group', '1', 'Группа доступа к панели управления сайтом', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('struct_edit_group', '3', 'Группа доступа к редактированию структуры сайта', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('material_edit_group', '3', 'Группа доступа к редактированию материалов', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('log_level', '2', 'Уровень журналирования системы', '0;ничего;1;системеые события;2;все события');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('plugin_conf_group', '2', 'Группа доступа к конфигурации плагинов', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('user_edit_group', '2', 'Группа доступа к управлению пользователями и группами', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('settings_edit_group', '2', 'Группа доступа к настройкам системы', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('templates_edit_group', '3', 'Группа доступа к редактированию шаблонов', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('use_codemirror', '1', 'Использовать редактор CodeMirror (подсветка кода)', '1;да;0;нет');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('use_tinymce', '1', 'Использовать редактор TinyMCE (визуальный редактор HTML)', '1;да;0;нет');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('filemanager_group', '2', 'Группа доступа к управлению файлами', '".($_config['table_prefix'])."_groups.id;".($_config['table_prefix'])."_groups.name');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('site_title', 'Triage CMS', 'Название сайта', NULL);";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('gravatar', 'monsterid', 'Пользователи: использовать Gravatar', '404;Не использовать;mm;mm (mystery-man);identicon;identicon (geometric pattern);monsterid;monsterid (generated monster);wavatar;wavatar (generated faces)');";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('gravatar_size', '80', 'Пользователи: размер Gravatar (1 - 512 пикселей)', NULL);";
$sql[] = "INSERT INTO ".$db->T('_settings')." VALUES ('gravatar_rating', 'g', 'Пользователи: рейтинг Gravatar', 'g;g - any audience;pg;pg - rude gestures or mild violence;r;r - harsh profanity, intense violence, nudity;x;x - hardcore sex or extremely violence');";

if(isset($_POST['demo_data'])) {
    $sql[] = "INSERT INTO ".$db->T('_templates')." VALUES (1, 'Пример шаблона', 'template.html');";
    $sql[] = "INSERT INTO ".$db->T('_pages')." (`id`, `parent_id`, `name`, `title`, `meta_keywords`, `meta_description`, `key`, `order`, `template_id`, `is_home`, `redirect`, `target`, `access_group`) VALUES (1, 0, 'Добро пожаловать в Triage CMS!', 'Добро пожаловать', NULL, NULL, 'index', 1, 1, 1, NULL, NULL, 0);";
}

foreach($sql as $q) {
    $db->query($q);
    if($db->errno()) { $err[] = "Не удалось выполнить запрос, сервер сообщает: ".$db->error(); }    
}
show_errors($err);

if(isset($_POST['write_protect'])) write_protect_recursive();

function write_protect_recursive($path = '')
{
    if(trim($path, '/') == 'cms/templates') return false;
    
    $fullpath = $_POST['document_root'].'/'.$path;
    $dir = scandir($fullpath);
    foreach($dir as $d) if($d != '.' && $d != '..') {
        if(is_file($fullpath.'/'.$d)) @chmod($fullpath.'/'.$d, 0440); // 440 -r--r-----; 444-r--r--r--
        elseif(is_dir($fullpath.'/'.$d)) write_protect_recursive($path.'/'.$d);
    }
}

echo "OK";
?>