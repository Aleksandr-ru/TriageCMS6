<?php

/**
 * Path Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Меню страницы";
$plugin_install['desc'][$plugin_uid]  = "отображает меню для текущей страницы и 1 уровень вглубь или вверх";

function pagemenu_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('selclass', 'selected',  'CSS-класс выделенной страницы');
    $installer->addOption('mode', 'auto',  'Режим', 'auto;Автоматически;one;Один уровень');
    
    return $installer->Install();
}
?>