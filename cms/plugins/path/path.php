<?php

/**
 * Путь по сайту 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class pathPlugin extends Plugin
{
    const uid = 'path';
    
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
        
        $tpl = $this->loadTemplate("path.tmpl.htm");
        
        $append_root = $this->getOption('append_root', 0);
        $name_root = $this->getOption('name_root');
        $abs_urls = $this->getOption('abs_urls', 0);
        $use_title = $this->getOption('use_title', 0); 
        
        $path_ids = $PAGE->getPathIds();
        
        $path = array();
        foreach($path_ids as $page_id) {
            $sql = "SELECT `name`, `title` FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($page_id);
            list($page_name, $page_title) = $DB->getRow($sql);
            
            $path[] = array( $use_title && $page_title ? $page_title : $page_name, $PAGE->path($page_id) );
        }
        if($path[0][1] == '' && $append_root && $name_root) {
            $path[0] = array($name_root, '');
        } elseif($append_root && $name_root) {
            array_unshift($path, array(htmlspecialchars($name_root), ''));
        }
        
        for($i=0; $i<sizeof($path); $i++) {
            list($page_name, $href) = $path[$i];
            $tpl->setCurrentBlock("path");
            $tpl->setVariable("NAME", $page_name);
            $tpl->setVariable("HREF", $abs_urls ? preg_replace("@\.html/$@", ".html", make_base($href)) : $href);
            $tpl->parse("path");
            
            if($i < sizeof($path)-1) {
				$tpl->setCurrentBlock("path");
				$tpl->touchBlock("separator");
				$tpl->parse("path");
			}
        }
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