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
 
class menugenPlugin extends Plugin
{
    const uid = 'menugen';
    
    protected $path_ids = array();
    protected $max_depth = 1;
    protected $home_id = 0;
    protected $use_title = 0; 
    protected $add_title = 0;  
    protected $home_class = 'home';
    protected $selected_class = 'selected';
    protected $current_class = 'current';
    
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
        
        $tpl = $this->loadTemplate("menugen.tmpl.htm");
        
        $this->max_depth = $this->getOption('depth', $this->max_depth);   
        $this->home_id = $this->getOption('home_page_id', $this->home_id);   
        $this->use_title = $this->getOption('use_title', $this->use_title); 
        $this->add_title = $this->getOption('add_title', $this->add_title);  
        $this->home_class = $this->getOption('home_class', $this->home_class);
        $this->selected_class = $this->getOption('selected_class', $this->selected_class);
        $this->current_class = $this->getOption('current_class', $this->current_class);     
        
        $this->path_ids = $PAGE->getPathIds();
        
        $tpl->setVariable('MENU', $this->buildMenuRecursive($this->home_id));
        
        return $tpl->get();
    }
    
    protected function buildMenuRecursive($parent_id = 0, $depth = 1)
    {
        global $PAGE, $DB, $_BASE;
        
        if($this->max_depth && $depth > $this->max_depth) return '';
    
        $ret = '';
        
        $sql = "SELECT `id`, `name`, `title`, `meta_description`, `is_home` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id)." AND `order` ORDER BY `order`";
        $DB->query($sql);
        if($DB->num_rows()) {
            $t1 = str_repeat("\t", $depth);
            $t2 = str_repeat("\t", $depth+1);
            
            $ret .= "$t1<ul>\r\n";
            while(list($id, $name, $title, $description, $is_home) = $DB->fetch()) {
                $class = array();
                if((!$this->home_id && $is_home) || ($id==$this->home_id)) $class[] = $this->home_class;
                if(in_array($id, $this->path_ids)) $class[] = $this->selected_class;
                if($PAGE->getId() == $id) $class[] = $this->current_class;
                $class = implode(' ', $class);
                
                $href = $PAGE->fullpath($id);
                $name = str_replace(array("'", '"'), '', $this->use_title ? ($title ? $title : $name) : $name);
                $desc = $this->add_title ? str_replace(array("'", '"'), '', $description) : '';
                
                $ret .= "$t2<li class='$class'><a href='$href' title='$desc'>$name</a>\r\n";
                $ret .= $this->buildMenuRecursive($id, $depth+1);
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