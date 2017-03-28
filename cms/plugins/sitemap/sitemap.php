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
 
class sitemapPlugin extends Plugin
{
    const uid = 'sitemap';
    
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
        global $PAGE, $_BASE;
        
        $tpl = $this->loadTemplate("sitemap.tmpl.htm");
        
        $depth = $this->getOption('depth', 0);        
        
        $tpl->setVariable('SITEMAP', $this->buildSitemapRecursive($depth));
        
        return $tpl->get();
    }
    
    protected function buildSitemapRecursive($max_depth, $parent_id = 0, $depth = 1)
    {
        global $PAGE, $DB, $_BASE;
        
        if($max_depth && $depth > $max_depth) return '';
        
        $use_title = $this->getOption('use_title', 0);  
        $ret = '';
        
        $sql = "SELECT `id`, `name`, `title`, `meta_description`, `is_home` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id)." AND `order` ORDER BY `order`";
        $DB->query($sql);
        if($DB->num_rows()) {
            $t1 = str_repeat("\t", $depth);
            $t2 = str_repeat("\t", $depth+1);
            
            $ret .= "$t1<ul>\r\n";
            while(list($id, $name, $title, $description, $is_home) = $DB->fetch()) {
                $class = $is_home ? 'home' : '';
                $href = $PAGE->fullpath($id);
                $name = str_replace(array("'", '"'), '', $use_title ? ($title ? $title : $name) : $name);
                $desc = str_replace(array("'", '"'), '', $description);
                
                $ret .= "$t2<li><a class='$class' href='$href' title='$desc'>$name</a>\r\n";
                $ret .= $this->buildSitemapRecursive($max_depth, $id, $depth+1);
                $ret .= "$t2</li>\r\n";
            }
            $ret .= "$t1</ul>\r\n";
        }
        $DB->free();
        
        return $ret;
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