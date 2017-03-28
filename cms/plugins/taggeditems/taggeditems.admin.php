<?php

/**
 * Plugin Admin interface
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010 
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
require_once("$_ROOT/cms/classes/ITM.php");

class taggeditemsPluginAdmin extends Plugin
{
    const uid = 'taggeditems';
            
    /**
     * <plugin_name>PluginAdmin::__construct() стандартный конструктор
     * 
     * @return экземпляр класса
     */
    function __construct()
    {
        parent::__construct(self::uid);
    }
    
    /**
     * <plugin_name>PluginAdmin::get() основная функция модуля управления
     * вызывается при входе в управление плагином
     * 
     * @return string text/html какоторый будет выведен в броузер клиента
     */
    function get()
    {                
        if(defined("AJAX")) {
            switch($_GET['event'])
            {             
                case "lists":
                    return $this->ajax_lists();
                case "grouppings":
                    return $this->ajax_grouppings($_GET['list_id']);                
                case "tags":
                    return $this->ajax_tags($_GET['groupping_id']);
                case "addgroupping":
                    return $this->ajax_addgroupping($_POST['list_id'], $_POST['name']) ? 'OK' : 'Ошибка добавления!';
                case "addtag":
                    return $this->ajax_addtag($_POST['groupping_id'], $_POST['name']) ? 'OK' : 'Ошибка добавления!';
                case "editgroupping":
                    return $this->ajax_editgroupping($_POST['groupping_id'], $_POST['name']) ? 'OK' : 'Ошибка изменения!';
                case "edittag":
                    return $this->ajax_edittag($_POST['tag_id'], $_POST['name']) ? 'OK' : 'Ошибка изменения!';
                case "deltag":
                    return $this->ajax_deltag($_POST['tag_id']) ? 'OK' : 'Ошибка удаления!';
                case "delgroupping":
                    return $this->ajax_delgroupping($_POST['groupping_id']) ? 'OK' : 'Ошибка удаления! Убедитесь, что внутри группировки нет тэгов!';
            }
            return "No event";
        }
        
        switch($_GET['event'])
        {           
            case "items":
                return $this->show_items($_GET['list_id']);
            case "new":
                return $this->new_item($_GET['list_id']);
            case "save":
                return $this->save_items();
            default:
                return $this->show_lists();
        }
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_load()
     * вызывается при загрузке редактора материалов со ссылкой на плагин
     * 
     * @param MaterialEx $material
     * @see classes/MaterialEx.php
     * @return string html форма, которая выводится в редактор материала
     */
    function materialeditor_load($material)
    {   
        global $_ROOT;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.materialeditor.html");
        $tpl->touchBlock('js');        
        return $tpl->get();
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_save()
     * вызывается после сохранения материала, если в редакторе была ссылка на плагин
     * 
     * @param MaterialEx $material $material
     * @see classes/MaterialEx.php
     * @param bool $is_new признак, что это только что созданный материал
     * @return bool результат обработки (сохранения своих данных плагином)
     * если возвращается false, то <plugin_name>PluginAdmin::materialeditor_error() должна возвращать описание ошибки  
     */
    function materialeditor_save($material, $is_new = false)
    {                       
        global $DB;
                
        $sql = "INSERT INTO ".$DB->T('ti_lists')." (`name`, `material_id`) VALUES(".$DB->F($material->getName()).", ".$DB->F($material->getId()).") ON DUPLICATE KEY UPDATE `name`=".$DB->F($material->getName());
        $DB->query($sql);   
                
        return true;
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_error() 
     * вызывается после сохранения материала, если <plugin_name>PluginAdmin::materialeditor_save() вернула false     
     * 
     * @return string описание ошибки произошедшей в <plugin_name>PluginAdmin::materialeditor_save() или false если ошибок не было 
     */
    function materialeditor_error()
    {
        return false;
    }
    
    /*-----------------------------------------------------------------------------------------------------------------------------------------------*/
    
    private function show_lists()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.list.html");
                
        $tpl->setVariable("PLUGIN_TITLE", $this->getTitle());
        $tpl->setVariable("GROUP_ID", $this->getOption('mat_group_id'));
        return $tpl->get();
    }
    
    private function ajax_lists()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.lists.html");
        
        $sql = "SELECT l.id, m.id, l.name, m.active, (select count(*) from ".$DB->T('ti_items')." as i where i.list_id=l.id) AS cnt FROM ".$DB->T('_material')." AS m JOIN ".$DB->T('ti_lists')." AS l ON m.id=l.material_id ORDER BY m.active DESC, l.name";
        $DB->query($sql);
        while(list($id, $material_id, $name, $active, $count) = $DB->fetch()) {
            $tpl->setCurrentBlock("row");
            $tpl->setVariable("ID", $id);
            $tpl->setVariable("MATERIAL_ID", $material_id);
            $tpl->setVariable("NAME", $name);
            $tpl->setVariable("COUNT", $count);
            $class = "";
            if($id == $_POST['list_id']) $class .= 'selected ';
            if(!$active) $class .= 'disabled ';
            $tpl->setVariable("CLASS", $class);
            $tpl->parse("row");
        }
        $DB->free();            
        return $tpl->get();
    }
    
    private function ajax_grouppings($list_id)
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.grouppings.html");
        
        $sql = "SELECT g.id, g.name FROM ".$DB->T('ti_grouppings')." AS g WHERE g.list_id=".$DB->F($list_id)." ORDER BY g.name";
        $DB->query($sql);
        while(list($id, $name) = $DB->fetch()) {
            $tpl->setCurrentBlock("row");            
            $tpl->setVariable("ID", $id);
            $tpl->setVariable("NAME", $name);            
            if($id == $_POST['groupping_id']) $tpl->setVariable("CLASS", 'selected');
            $tpl->parse("row");
        }
        $DB->free();            
        return $tpl->get();
    }
    
    private function ajax_tags($grouppings_id)
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.tags.html");
        
        $sql = "SELECT t.id, t.name FROM ".$DB->T('ti_tags')." AS t WHERE t.groupping_id=".$DB->F($grouppings_id)." ORDER BY t.name";
        $DB->query($sql);
        while(list($id, $name) = $DB->fetch()) {
            $tpl->setCurrentBlock("row");            
            $tpl->setVariable("ID", $id);
            $tpl->setVariable("NAME", $name);            
            if($id == $_POST['tag_id']) $tpl->setVariable("CLASS", 'selected');
            $tpl->parse("row");
        }
        $DB->free();            
        return $tpl->get();
    }
    
    private function ajax_addgroupping($list_id, $name)
    {
        global $_ROOT, $DB;
        if(!$list_id || !$name) return false;
        $sql = "INSERT INTO ".$DB->T('ti_grouppings')." (`name`, `list_id`) VALUES(".$DB->F($name).", ".$DB->F($list_id).")";
        $DB->query($sql);            
        return $DB->errno() ? false : true;
    }
    
    private function ajax_addtag($groupping_id, $name)
    {
        global $_ROOT, $DB;
        if(!$groupping_id || !$name) return false;
        $sql = "INSERT INTO ".$DB->T('ti_tags')." (`name`, `groupping_id`) VALUES(".$DB->F($name).", ".$DB->F($groupping_id).")";
        $DB->query($sql);            
        return $DB->errno() ? false : true;
    }
    
    private function ajax_editgroupping($groupping_id, $name)
    {
        global $_ROOT, $DB;
        if(!$groupping_id || !$name) return false;
        $sql = "UPDATE ".$DB->T('ti_grouppings')." SET `name`=".$DB->F($name)." WHERE `id`=".$DB->F($groupping_id);
        $DB->query($sql);            
        return $DB->errno() ? false : true;
    }
    
    private function ajax_edittag($tag_id, $name)
    {
        global $_ROOT, $DB;
        if(!$tag_id || !$name) return false;
        $sql = "UPDATE ".$DB->T('ti_tags')." SET `name`=".$DB->F($name)." WHERE `id`=".$DB->F($tag_id);
        $DB->query($sql);            
        return $DB->errno() ? false : true;
    }
    
    private function ajax_deltag($tag_id)
    {
        global $_ROOT, $DB;
        if(!$tag_id) return false;
        
        $sql = "DELETE FROM ".$DB->T('ti_item_tags')." WHERE `tag_id`=".$DB->F($tag_id);
        $DB->query($sql);
        
        $sql = "DELETE FROM ".$DB->T('ti_tags')." WHERE `id`=".$DB->F($tag_id);
        $DB->query($sql);
                    
        return $DB->errno() ? false : true;
    }
    
    private function ajax_delgroupping($groupping_id)
    {
        global $_ROOT, $DB;
        if(!$groupping_id) return false;
        if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('ti_tags')." WHERE `groupping_id`=".$DB->F($groupping_id))) return false;
        
        $sql = "DELETE FROM ".$DB->T('ti_grouppings')." WHERE `id`=".$DB->F($groupping_id);
        $DB->query($sql);            
        return $DB->errno() ? false : true;
    }
    
    private function show_items($list_id)
    {
        global $_ROOT, $_BASE, $DB;
        
        $no_image = $this->getOption('no_image_src');
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.items.html");
                
        $tpl->setVariable("PLUGIN_TITLE", $this->getTitle());
        $list = $DB->getRow("SELECT * FROM ".$DB->T('ti_lists')." WHERE `id`=".$DB->F($list_id), true);
        $tpl->setVariable("LIST_NAME", $list['name']);
        $tpl->setVariable("LIST_ID", $list['id']);
        $tpl->setVariable("LIST_MATERIAL_ID", $list['material_id']);
        
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
            $tpl->setVariable("GROUPPING_NAME", $groupping_name);
            $tpl->parse("groupping");
        }
        
        $sql = "SELECT `id`, `name`, `desc`, `href`, `file_id`, (`id`=".$DB->F(@$_GET['new_id']).") AS is_new FROM ".$DB->T('ti_items')." WHERE `list_id`=".$DB->F($list_id)." ORDER BY is_new DESC, `name`";
        $DB->query($sql);
        while(list($item_id, $item_name, $item_desc, $item_href, $file_id) = $DB->fetch()) {
            $item_tags = $DB->getCell("SELECT `tag_id` FROM ".$DB->T('ti_item_tags')." WHERE `item_id`=".$DB->F($item_id));
            
            $tpl->setCurrentBlock("item");
            $tpl->setVariable("ITEM_ID", $item_id);
            $tpl->setVariable("ITEM_NAME", $item_name);
            $tpl->setVariable("ITEM_DESC", $item_desc);
            $tpl->setVariable("ITEM_HREF", $item_href);
            $tpl->setVariable("ITEM_HREF2", preg_match("/^#(\d+)#.+$/", $item_href, $regs) ? Page::fullpath($regs[1]) : $item_href);
            
            $tpl->setVariable("ITEM_IMG", make_base($_BASE).($file_id ? getFileHref($file_id) : $no_image));
                        
            foreach($grouppings as $groupping_id=>$groupping_name) {
                $tpl->setCurrentBlock("groupping2");
                $tpl->setVariable("GROUPPING_NAME", $groupping_name);
                
                foreach($groupping_tags[$groupping_id] as $tag_id=>$tag_name) {
                    $tpl->setCurrentBlock("tag");
                    $tpl->setVariable("TAG_ID", $tag_id);
                    $tpl->setVariable("TAG_NAME", $tag_name);
                    if(in_array($tag_id, $item_tags)) $tpl->setVariable("TAG_CHK", "checked");
                    $tpl->setVariable("T_ITEM_ID", $item_id);
                    $tpl->parse("tag");
                }
                
                $tpl->parse("groupping2");
            }
            
            $tpl->parse("item");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function new_item($list_id)
    {
        global $DB;
        if($list_id) {
            $id = $DB->new_id($DB->T('ti_items'));
            $DB->query("INSERT INTO ".$DB->T('ti_items')." (`name`, `desc`, `href`, `list_id`, `file_id`) VALUES('Новый элемент $id', 'Только что добавлен', '#', ".$DB->F($list_id).", 0)");                            
        }
        return cpl_redirect("plugin.php?plugin_uid=taggeditems&event=items&list_id=$list_id&new_id=$id", true);
    }
    
    private function save_items()
    {
        global $DB;
        $list_id = $_POST['list_id'];
                
        //Debugger::dump($_POST);
        //Debugger::dump($_FILES);        
        // max_file_uploads = 20 (PHP_INI_SYSTEM) Available since PHP 5.2.12.
        if(ini_get('max_file_uploads') && (ini_get('max_file_uploads') < sizeof($_POST['item_id']))) {
            trigger_error('PHP_INI_SYSTEM max_file_uploads = '.ini_get('max_file_uploads').' is less than items in list = '.sizeof($_POST['item_id']).', some item pictures may not be uploaded!', E_USER_WARNING);    
        }        
           
        foreach($_POST['item_id'] as $item_id) {
            $DB->query("DELETE FROM ".$DB->T('ti_item_tags')." WHERE `item_id`=".$DB->F($item_id));
            if(isset($_POST['del']) && is_array($_POST['del']) && in_array($item_id, $_POST['del'])) {
                $DB->query("DELETE FROM ".$DB->T('ti_items')." WHERE `id`=".$DB->F($item_id));
            } else {
                $new_name = $_POST['item_name'][$item_id] ? $_POST['item_name'][$item_id] : "Без названия $item_id";
                $sql = "UPDATE ".$DB->T('ti_items')." SET `name`=".$DB->F($new_name).", `desc`=".$DB->F($_POST['item_desc'][$item_id], true).", `href`=".$DB->F($_POST['item_href'][$item_id])." WHERE `id`=".$DB->F($item_id);
                $DB->query($sql);
                
                if(isset($_POST['tags'][$item_id])) foreach($_POST['tags'][$item_id] as $tag_id) {
                    $sql = "INSERT INTO ".$DB->T('ti_item_tags')." (`item_id`, `tag_id`) VALUES(".$DB->F($item_id).", ".$DB->F($tag_id).")";
                    $DB->query($sql);
                }
                
                if($_POST['list_material_id'] && $_FILES['item_file']['name'][$item_id] && ($_FILES['item_file']['error'][$item_id]===0)) {                    
                    if($file_id = uploadMaterialFile($_POST['list_material_id'], $_FILES['item_file']['name'][$item_id], $_FILES['item_file']['tmp_name'][$item_id], $_FILES['item_file']['size'][$item_id], $_FILES['item_file']['type'][$item_id])) {
                        $sql = "UPDATE ".$DB->T('ti_items')." SET `file_id`=".$DB->F($file_id)." WHERE `id`=".$DB->F($item_id);
                        $DB->query($sql);
                    }                    
                }
            }
        }
        
        if(isset($_POST['addnew']) && $_POST['addnew']) return $this->new_item($list_id);
        
        return cpl_redirect("plugin.php?plugin_uid=taggeditems&event=items&list_id=$list_id", true);
    }
}

?>