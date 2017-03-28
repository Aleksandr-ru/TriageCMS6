<?php

/**
 * Plugin interface 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class taggeditemsPlugin extends Plugin
{
    const uid = 'taggeditems';
    
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
        global $DB, $_ROOT, $_BASE, $PAGE;
        
        $list_id = $this->getOption("curr_list", 0);
        $no_image = $this->getOption("no_image_src");
        
        $list = $DB->getRow("SELECT l.id, l.name, m.id AS material_id, m.active FROM ".$DB->T('_material')." AS m JOIN ".$DB->T('ti_lists')." AS l ON l.material_id=m.id WHERE l.id=".$DB->F($list_id), true);
        if(!$list_id || !$list['id'] || !$list['material_id'] || !$list['active']) {
            trigger_error("No list or material inactive for id=$list_id", E_USER_NOTICE);
            return false;
        }
        
        $tpl = $this->loadTemplate("list.tmpl.htm");
        $tpl->setVariable("LIST_NAME", $list['name']);
        $tpl->setVariable("LIST_ID", $list['id']);
        $material = new Material($list['material_id']);
        $tpl->setVariable("LIST_DESC", $material->parse());
        
        $grouppings = array();
        $sql = "SELECT `id`, `name` FROM ".$DB->T('ti_grouppings')." WHERE `list_id`=".$DB->F($list_id)." ORDER BY `name`";
        $DB->query($sql);
        while(list($groupping_id, $groupping_name) = $DB->fetch()) $grouppings[$groupping_id] = $groupping_name;
        $DB->free();
        
        $groupping_tags = array();
        $sql = "SELECT t.id, t.name, g.id FROM ".$DB->T('ti_tags')." AS t JOIN ".$DB->T('ti_grouppings')." AS g ON t.groupping_id=g.id WHERE g.list_id=".$DB->F($list_id)." ORDER BY g.name, t.name";
        $DB->query($sql);
        while(list($tag_id, $tag_name, $groupping_id) = $DB->fetch()) $groupping_tags[$groupping_id][$tag_id] = $tag_name;
        $DB->free();
        
        foreach($grouppings as $groupping_id=>$groupping_name) {
            $tpl->setCurrentBlock("groupping");
            $tpl->setVariable("GROUPPING_ID", $groupping_id);
            $tpl->setVariable("GROUPPING_NAME", $groupping_name);
            
            foreach($groupping_tags[$groupping_id] as $tag_id=>$tag_name) {
                $tpl->setCurrentBlock("tag");
                $tpl->setVariable("TAG_ID", $tag_id);
                $tpl->setVariable("TAG_NAME", $tag_name);                               
                $tpl->parse("tag");
            }            
            $tpl->parse("groupping");
        }
        
        $sql = "SELECT `id`, `name`, `desc`, `href`, `file_id` FROM ".$DB->T('ti_items')." WHERE `list_id`=".$DB->F($list_id)." ORDER BY `name`";
        $DB->query($sql);
        if(!$DB->num_rows()) trigger_error("No items for list id=$list_id", E_USER_NOTICE);
        while(list($item_id, $item_name, $item_desc, $item_href, $file_id) = $DB->fetch(false, false)) {            
            $tpl->setCurrentBlock("item");
            $tpl->setVariable("ITEM_ID", $item_id);
            $tpl->setVariable("ITEM_NAME", htmlspecialchars($item_name));
            $tpl->setVariable("ITEM_DESC", $item_desc);
            //$tpl->setVariable("ITEM_HREF", $item_href);
            $tpl->setVariable("ITEM_HREF", preg_match("/^#(\d+)#.+$/", $item_href, $regs) ? $PAGE->fullpath($regs[1]) : htmlspecialchars($item_href));
            $tpl->setVariable("ITEM_IMG", make_base($_BASE).($file_id ? getFileHref($file_id) : $no_image));
            
            $sql = "SELECT it.tag_id, t.name FROM ".$DB->T('ti_item_tags')." AS it JOIN ".$DB->T('ti_tags')." AS t ON it.tag_id=t.id WHERE it.item_id=".$DB->F($item_id)." ORDER BY t.name";
            $item_tags = $DB->getCell2($sql);    
            foreach($item_tags as $tag_id=>$tag_name){
                $tpl->setCurrentBlock("item_tag");
                $tpl->setVariable("ITAG_ID", $tag_id);
                $tpl->setVariable("ITAG_NAME", $tag_name);
                $tpl->setVariable("ITAG_ITEM_ID", $item_id);
                $tpl->parse("item_tag");
            }
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