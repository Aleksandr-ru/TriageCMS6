<?php

/**
 * Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Тэгиорованные списки";
$plugin_install['desc'][$plugin_uid]  = "управляет списками элементов с группировками по тегам";

function taggeditems_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('ti_lists')." (
        `id` INT( 3 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `name` VARCHAR( 200 ) NOT NULL ,
        `material_id` INT( 10 ) UNSIGNED NOT NULL,
        UNIQUE KEY `material_id` (`material_id`)
        ) ENGINE = MYISAM ;";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('ti_grouppings')." (
        `id` INT( 4 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `name` VARCHAR( 200 ) NOT NULL ,
        `list_id` INT( 3 ) UNSIGNED NOT NULL ,
        INDEX ( `list_id` ) 
        ) ENGINE = MYISAM ;";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('ti_items')." (
        `id` INT( 6 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `name` VARCHAR( 200 ) NOT NULL ,
        `desc` TEXT NULL ,
        `href` VARCHAR( 250 ) NOT NULL ,
        `list_id` INT( 3 ) UNSIGNED NOT NULL ,
        `file_id` INT ( 11 ) UNSIGNED NOT NULL,
        INDEX ( `list_id` ) 
        ) ENGINE = MYISAM ;";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('ti_tags')." (
        `id` INT( 5 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `name` VARCHAR( 200 ) NOT NULL ,        
        `groupping_id` INT( 4 ) UNSIGNED NOT NULL ,
        INDEX ( `groupping_id` ) 
        ) ENGINE = MYISAM ;";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('ti_item_tags')." (
        `item_id` INT( 6 ) UNSIGNED NOT NULL ,
        `tag_id` INT( 5 ) UNSIGNED NOT NULL ,
        PRIMARY KEY ( `item_id` , `tag_id` ) 
        ) ENGINE = MYISAM ;";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    
    $group_name = $plugin_install['title'][$plugin_uid];
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
        
    $installer->addOption('curr_list',    0, 'Выбранный список', $DB->TF('ti_lists', 'id').';'.$DB->TF('ti_lists', 'name'));    
    $installer->addOption('no_image_src', 'images/spacer.gif', 'Изображение по-умолчанию', '');
    $installer->addOption('mat_group_id', $group_id, 'Группа материалов', $DB->TF('_material_groups', 'id').';'.$DB->TF('_material_groups', 'name'));
    
    return $installer->Install();
}
?>