<?php

/**
 * Bloggy Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Последние новости";
$plugin_install['desc'][$plugin_uid]  = "выводит список последних новостей";

function lastnews_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    if(!plugin_exists('news')) {
        $E->addError("Невозможно установить '".$plugin_install['title'][$plugin_uid]."'", "Не найден плагин 'Новости' (news). Сначала нужно установить плагин для создания/управления новостями.");
        return false;   
    }
    
    $page_id = $DB->getField("SELECT p.id FROM ".$DB->T('_page_materials')." AS pm JOIN ".$DB->T('_material')." AS m ON pm.material_id=m.id JOIN ".$DB->T('_pages')." AS p ON pm.page_id=p.id WHERE m.type='plugin' AND m.data='news' ORDER BY (p.order > 0) DESC, p.parent_id, p.order LIMIT 1");
            
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
    
    $installer->addOption('limit',        3,        'Количество выводимых новостей');    
    $installer->addOption('date_format',  'd F Y',  'Формат даты/времени новости');    
    $installer->addOption('news_page_id', $page_id, 'Старинца с плагином "Новости"', $DB->TF('_pages', 'id').';'.$DB->TF('_pages', 'name'));
    $installer->addOption('all_groups',   1,        'Показывать', '1;Новости из всех групп;0;Только из выбранной группы');
    $installer->addOption('group_id',     0,        'Выбранная группа новостей', $DB->TF('news_groups', 'id').';'.$DB->TF('news_groups', 'name'));
    
    return $installer->Install();
}
?>