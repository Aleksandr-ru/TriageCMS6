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
 
class pagemenuPlugin extends Plugin
{
    const uid = 'pagemenu';
    
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
        
        $tpl = $this->loadTemplate("pagemenu.tmpl.htm");
        
        $classname = $this->getOption('selclass', 'selected');
        $show_item2 = $this->getOption('show_item2', 1);
        $mode = $this->getOption('mode', 'auto');
        $num_children = $PAGE->getNumChildren();
        $parent_id = $PAGE->getParentId();
        
        switch($mode) {
            case 'one':
                $item1_parent = $num_children ? $PAGE->getId() : $parent_id; 
                $show_item2 = false;
                break;
            
            case 'auto':
            default:
                if($num_children) {
                    $item1_parent = $parent_id;
                    $item2_parent = $PAGE->getId();                     
                } else {
                    $item1_parent = $DB->getField("SELECT `parent_id` FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($parent_id));
                    $item2_parent = $parent_id;  
                }
                $show_item2 = true;
        }
        
        $sql = "SELECT `id`, `name`, `target`, (`order` = 0) AS ord FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($item1_parent)." AND (`order`>0 OR `id`=".$DB->F($PAGE->getId()).") ORDER BY ord, `order`";
        $DB->query($sql);
        while(list($p1_id, $p1_name, $p1_target) = $DB->fetch()) {
            $tpl->setCurrentBlock("item1");            
            $tpl->setVariable("ITEM1_CLASS", $p1_id == $PAGE->getId() ? $classname : '');
            $tpl->setVariable("ITEM1_NAME", $p1_name);
            $tpl->setVariable("ITEM1_HREF", $PAGE->fullpath($p1_id));
            $tpl->setVariable("ITEM1_TARGET", $p1_target);
            if($show_item2 && $item1_parent != $item2_parent && $p1_id == $item2_parent) {
                $sql = "SELECT `id`, `name`, `target`, (`order` = 0) AS ord FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($item2_parent)." AND (`order`>0 OR `id`=".$DB->F($PAGE->getId()).") ORDER BY ord, `order`";
                $DB->query($sql);
                while(list($p2_id, $p2_name, $p2_target) = $DB->fetch()) {
                    $tpl->setCurrentBlock("item2");            
                    $tpl->setVariable("ITEM2_CLASS", $p2_id == $PAGE->getId() ? $classname : '');
                    $tpl->setVariable("ITEM2_NAME", $p2_name);
                    $tpl->setVariable("ITEM2_HREF", $PAGE->fullpath($p2_id));
                    $tpl->setVariable("ITEM2_TARGET", $p2_target);
                    $tpl->parse("item2");
                }
                $DB->free();
            }
            $tpl->parse("item1");
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