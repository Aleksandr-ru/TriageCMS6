<?php

/**
 * Path Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Поиск Яндекс";
$plugin_install['desc'][$plugin_uid]  = "поиск по сайту средствами Яндекс.XML";

function yandexsearch_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('xmlqueryaddr', '', 'Ваш адрес для совершения XML запроса');    
    $installer->addOption('maxpassages', '2', 'Число пассажей текста с найденными словами', '1;1;2;2;3;3;4;4;5;5');
    $installer->addOption('host', '', 'Хост для поиска (пусто = текущий)');
        
    return $installer->Install();
}
?>