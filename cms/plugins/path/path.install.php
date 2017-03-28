<?php

/**
 * Path Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Путь";
$plugin_install['desc'][$plugin_uid]  = "отображает путь по сайту до текущей страницы";

function path_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('append_root', '0', 'Добавить/переименовать корневой элемент', '1;Да;0;Нет');
    $installer->addOption('name_root',   'Главная',  'Название корневого элемента');
    $installer->addOption('abs_urls',    '0', 'Абсолютные ссылки', '1;Да;0;Нет');
    $installer->addOption('use_title',   '0', 'Заменять названия страниц на заголовок', '1;Да;0;Нет');
    
    return $installer->Install();
}
?>