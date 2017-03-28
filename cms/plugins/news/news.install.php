<?php

/**
 * Bloggy Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Новости";
$plugin_install['desc'][$plugin_uid]  = "управляет новостями и архвиом новостей";

function news_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('news')." (
        `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
        `material_id` INT( 10 ) UNSIGNED NOT NULL ,
        `timestamp` DATETIME NOT NULL ,
        `short_text` TEXT NULL DEFAULT NULL ,
        `picture_file_id` INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
        PRIMARY KEY ( `id` ),
        UNIQUE KEY (`material_id`),
        KEY ( `timestamp` ) 
        ) ENGINE = MYISAM DEFAULT CHARSET = utf8 COMMENT = 'новости';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('news_groups')." (
        `id` int( 4 ) unsigned NOT NULL AUTO_INCREMENT ,
        `name` varchar( 100 ) NOT NULL DEFAULT '',
        `rss` tinyint( 1 ) unsigned NOT NULL DEFAULT '1',
        `is_hidden` tinyint( 1 ) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY ( `id` ) 
        ) ENGINE = MYISAM DEFAULT CHARSET = utf8 COMMENT = 'группы новостей';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('news_in_groups')." (
          `news_id` int(10) unsigned NOT NULL DEFAULT '0',
          `group_id` int(4) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`news_id`,`group_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='новости в группах';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    }
    
    $group_name = 'Новости по-умолчанию';
    $sql = "INSERT INTO ".$DB->T('news_groups')." (`name`, `rss`, `is_hidden`) VALUES (".$DB->F($group_name).", '1', '0');";
    $DB->query($sql);
    if($DB->errno()) {
        $E->addWarning("Установка плагина '".$plugin_install['title'][$plugin_uid]."'", "Не удалось создать группу новостей, создайте ее вручную.");        
    } else {
        $news_group_id = $DB->insert_id();
        cmsLogObject("Добавлена группа новостей '".$group_name."' (id: ".$news_group_id.")", "plugin", $plugin_install['title'][$plugin_uid]);    
    }
        
    $group_name = 'Новости';
    $sql = "INSERT INTO ".$DB->T('_material_groups')." (`name`, `hidden`) VALUES(".$DB->F($group_name).", 1)";
    $DB->query($sql);
    if($DB->errno()) {
        $E->addWarning("Установка плагина '".$plugin_install['title'][$plugin_uid]."'", "Не удалось создать группу материалов, создайте ее вручную.");
        $group_id = 0;
    } 
    else {
        $group_id = $DB->insert_id();
        cmsLogObject("Добавлена группа материалов '".$group_name."' (id: ".$group_id.")", "matgrp", $group_id);    
    }
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('mat_group_id',         $group_id,  'Новости: группа материалов', $DB->TF('_material_groups', 'id').';'.$DB->TF('_material_groups', 'name'));    
    $installer->addOption('date_format',          'd F Y',    'Новости: формат даты/времени новости');
    $installer->addOption('set_title',            0,          'Просмотр новости: заменять заголовок страницы', '1;Да;0;Нет');
    $installer->addOption('default_archive',      0,          'Новости: показывать по-умаолчанию', '1;Архив новостей;0;Последнюю новость');
    $installer->addOption('selected_year_class',  'selected', 'Архив новостей: CSS-класс выбранного года');
    $installer->addOption('selected_group_class', 'selected', 'Архив новостей: CSS-класс выбранной группы');
    $installer->addOption('all_groups_name',      '(Все)',    'Архив новостей: название для "все новости"');
    
    return $installer->Install();
}
?>