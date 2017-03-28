<?php

/**
 * Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Промо-ротатор";
$plugin_install['desc'][$plugin_uid]  = "выводит баннеры в промо-блок";

function promotor_install()
{
    global $DB, $E, $_ROOT, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    if(!mkdir($_ROOT.'/files/promotor')) {
        $E->addWarning('Не удалось создать папку!', "Создайте папку 'files/promotor' вручную.");
    }
    if(!chmod($_ROOT.'/files/promotor', 0777)) {
        $E->addWarning('Не удалось установить права на папку!', "Установите права 777 на папку 'files/promotor' вручную.");
    }
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('promotor')." (
      `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(250) NOT NULL,
      `href` varchar(250) NOT NULL DEFAULT '#',
      `file_ext` varchar(10) DEFAULT NULL,
      `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `shows` int(10) unsigned NOT NULL DEFAULT '0',
      `clicks` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='промо-баннеры';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('max_banners', 3, 'Максимальное количетсво отображаемых баннеров (0 - все)');
    $installer->addOption('count_clicks', 0, 'Считать клики по баннерам', '1;Да;0;Нет');
        
    return $installer->Install();
}
?>