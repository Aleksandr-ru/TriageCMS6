<?php

/**
 * Путь по сайту 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class sectionmenuPlugin extends Plugin
{
    const uid = 'sectionmenu';
    
    /**
     * <plugin_name>Plugin::__construct() стандартный конструктор
     * 
     * @param integer $material_id - ИД материала для которого создается плагин
     * @return экземпляр класса
     */
    function __construct($material_id = 0)
    {
        parent::__construct(self::uid, $material_id);       
    }
    
    /**
     * <plugin_name>Plugin::get() основная функция плгина
     * вызывается при выводе плагина
     * 
     * @internal рекомендуется предусмотреть поддржку работы через AJAX
     * @return string text/html какоторый будет выведен в броузер клиента
     */
    function get()
    {                
        global $PAGE, $DB;
        
        $tpl = $this->loadTemplate("sectionmenu.tmpl.htm");
        
        $tpl->setVariable("PAGE_NAME", $PAGE->getName());
        $tpl->setVariable("PAGE_TITLE", $PAGE->getTitle());
		$tpl->setVariable("PAGE_DESC", $PAGE->getDescription());
        
        $reverse = $this->getOption('reverse', 0);
		$order = $reverse ? "DESC" : "ASC";
        
        $sql = "SELECT `id`, `name`, `title`, `meta_description`, `target` FROM ".$DB->T('_pages')." WHERE(`parent_id`=".$DB->F($PAGE->getId())." AND `order` > 0) ORDER BY `order` $order;";
		$DB->query($sql);
		while(list($item_id, $item_name, $item_title, $item_desc, $item_target) = $DB->fetch())
		{
			$tpl->setCurrentBlock("item");
			$tpl->setVariable("ITEM_ID", $item_id);
			$tpl->setVariable("ITEM_HREF", $PAGE->fullpath($item_id));			
			$tpl->setVariable("ITEM_NAME", $item_name);
			$tpl->setVariable("ITEM_TITLE", $item_title ? $item_title : $item_name);
			$tpl->setVariable("ITEM_DESC", $item_desc);
			$tpl->setVariable("ITEM_TARGET", $item_target);
			$tpl->parse("item");
		}
		$DB->free();  
        
        return $tpl->get();
    }
    
    /**
     * <plugin_name>Plugin::event() хендл событий
     * вызывается если произошло событие закрепленное за плагином
     * 
     * @param array $eventinfo - массив где первый элемент тдентификатор события, а остальные зависят от события
     * @return void
     */
    function event($eventinfo)
    {
        global $DB;
        
        list($event) = $eventinfo;
    
        switch($event)
        {
        }
    }
}
?>