<?php

/**
 * ExecTime Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Время выполнения";
$plugin_install['desc'][$plugin_uid]  = "отображает время генерации страницы и количество запросов к БД";

function exectime_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addEvent('core_init');
    $installer->addEvent('core_finished');
    
    return $installer->Install();
}
?>