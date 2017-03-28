<?php

/**
 * Path Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Гнератор меню";
$plugin_install['desc'][$plugin_uid]  = "выводит дерево страниц сайта";
$plugin_install['cms_version'][$plugin_uid] = "6.0";

function menugen_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('depth', '0', 'Ограничить глубину (0 - без ограничений)');
    $installer->addOption('use_title', '0', 'Заменять названия страниц на заголовок', '1;Да;0;Нет');
    $installer->addOption('add_title', '0', 'Добавлять описание страниц в title ссылок', '1;Да;0;Нет');
    $installer->addOption('home_class', 'home', 'CSS-класс начальной страницы');
    $installer->addOption('selected_class', 'selected', 'CSS-класс родителей текущей страницы');
    $installer->addOption('current_class', 'current', 'CSS-класс текущей страницы');
    $installer->addOption('home_page_id', '0', 'ID начальной страницы (0 - корневой элемент)');
        
    return $installer->Install();
}
?>