<?php

/**
 * Path Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Меню раздела";
$plugin_install['desc'][$plugin_uid]  = "отображает дочерние страницы с описанием";

function sectionmenu_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('reverse', '0',  'Отображать в обратном порядке', '1;Да;0;Нет');
    
    return $installer->Install();
}
?>