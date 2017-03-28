<?php

/**
 * Sendemail Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Отправка сообщения";
$plugin_install['desc'][$plugin_uid]  = "отправляет сообщение по электронной почте";

function sendemail_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('sendemail_addr')." (
        `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `material_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
        `name` VARCHAR( 250 ) NOT NULL ,
        `email` VARCHAR( 200 ) NOT NULL ,
        `order` INT( 4 ) NOT NULL DEFAULT '0',
        INDEX ( `material_id` ) 
        ) ENGINE = MYISAM COMMENT = 'адреса для отправки писем';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('sendemail_fields')." (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `material_id` int(10) unsigned NOT NULL DEFAULT '0',
          `name` varchar(250) NOT NULL,
          `type` enum('text','textarea','radio','checkbox','select') NOT NULL DEFAULT 'text',
          `default` varchar(250) DEFAULT NULL,
          `regexp` varchar(250) DEFAULT NULL,
          `required` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `is_subj` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `order` int(4) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `material_id` (`material_id`)
        ) ENGINE=MyISAM COMMENT='дополнительные поля для email';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    }
        
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('email',       '', 'Адрес e-mail для сообщений (адрес;имя;... если несколько)');
    $installer->addOption('use_replyto', 0,  'Использовать адрес отправителя в поле ReplyTo', '1;Да;0;Нет');
    $installer->addOption('use_from', 0,  'Использовать адрес отправителя в поле From', '1;Да;0;Нет');
    
    return $installer->Install();
}
?>