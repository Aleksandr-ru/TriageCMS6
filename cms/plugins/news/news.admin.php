<?php

/**
 * Управление модулем отображения блога
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010 
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
require_once("$_ROOT/cms/classes/ITM.php");

class newsPluginAdmin extends Plugin
{
    const uid = 'news';
    private $material_save_error = "";
        
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
        //$href = $_SERVER['PHP_SELF']."?plugin_uid=".$this->getUid()."&var=val";
        
        //return "this is <a href='$href'>admin</a> output :) <br> <textarea class='visualExtbr'></textarea> <br> <textarea style='width: 500px; height: 400px;' class='visualSimple'></textarea> <br> <textarea class='codeEditor'></textarea> <br> <textarea class='tabbyEditor'></textarea>";
        //return $this->show_posts();
    
        if(defined("AJAX")) {
            switch($_GET['event'])
            {
                case "picture_options":
                    return "<option value='0'>(не выбрано)</option>\r\n".$this->getPictureOptions($_POST['material_id'], $_POST['sel_id']);
                case "onoff":
                    return $this->onoff($_GET['material_id'], $_POST['off']) ? 'OK' : 'Ошибка переключения';
                case "grouponoff":
                    return $this->groupOnOff($_POST['group_id'], $_POST['off']) ? 'OK' : 'Ошибка переключения';
                case "grouprss":
                    return $this->groupRss($_POST['group_id'], $_POST['rss']) ? 'OK' : 'Ошибка изменения';
                case "editgroup":
                    return $this->groupUpdate($_POST['group_id'], $_POST['name']) ? 'OK' : 'Ошибка изменения';                    
                case "delgroup":
                    return $this->delGroup($_POST['group_id']) ? 'OK' : 'Ошибка добавления';
                case "addgroup":
                    return $this->addGroup($_POST['name']) ? 'OK' : 'Ошибка добавления';                    
            }
            return "No event";
        }
        
        switch($_GET['event'])
        {
            case "groups":
                return $this->show_groups();            
            case "news":
            default:
                return $this->show_news();
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
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid()."/");
        $tpl->loadTemplatefile("admin.materialeditor.html");
        
        if($material->getId()) {
            $news = $DB->getRow("SELECT * FROM ".$DB->T('news')." WHERE `material_id`=".$DB->F($material->getId()), true);
            $tpl->setVariable("NEWS_ID", $news['id']);
            $tpl->setVariable("NEWS_ID_TEXT", $news['id']);
            $tpl->setVariable("NEWS_DATE", $news['timestamp']);
            $tpl->setVariable("NEWS_SHORT", $news['short_text']);
            $groups = $DB->getCell("SELECT `group_id` FROM ".$DB->T('news_in_groups')." WHERE `news_id`=".$DB->F($news['id']));
        } else {            
            $tpl->setVariable("NEWS_ID", 0);
            $tpl->setVariable("NEWS_ID_TEXT", "новый");
            $tpl->setVariable("NEWS_DATE", date("Y-m-d H:i:s"));

            $groups = array();
        }
        $tpl->setVariable("PIC_OPTIONS", $this->getPictureOptions($material->getId(), $news['picture_file_id']));
        
        $sql = "SELECT `id`, `name`, `is_hidden` FROM ".$DB->T('news_groups')." ORDER BY `is_hidden`, `name`";
        $DB->query($sql);
        while(list($group_id, $group_name, $group_hidden) = $DB->fetch()) {                    
            $tpl->setCurrentBlock("group");
            $tpl->setVariable("GROUP_ID", $group_id);
            $tpl->setVariable("GROUP_NAME", $group_name);
            $tpl->setVariable("GROUP_CLASS", $group_hidden ? "hidden" : "");
            if(in_array($group_id, $groups)) $tpl->setVariable("GROUP_CHK", "checked");
            $tpl->parse("group");
        }
        
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
        global $DB, $USER;
        
        if($material->getType() != 'html') {
            $this->material_save_error = "Материал должен быть только HTML";    
            return false;
        }
                
        if($is_new || !$_POST['news_id']) {          
            /* инсерт в новости */
            $sql = "INSERT INTO ".$DB->T('news')." (`material_id`, `timestamp`, `short_text`, `picture_file_id`) VALUES(".$DB->F($material->getId()).", ".$DB->F($_POST['news_date']).", ".$DB->F($_POST['news_short'], true).", ".$DB->F($_POST['pic_file_id']).")";
            $DB->query($sql);
            $_POST['news_id'] = $DB->insert_id();            
            
        } else {
            $sql = "UPDATE ".$DB->T('news')." SET `timestamp`=".$DB->F($_POST['news_date']).", `short_text`=".$DB->F($_POST['news_short'], true).", `picture_file_id`=".$DB->F($_POST['pic_file_id'])." WHERE `id`=".$DB->F($_POST['news_id']);
            $DB->query($sql);
        }
                
        $DB->query("DELETE FROM ".$DB->T('news_in_groups')." WHERE `news_id`=".$DB->F($_POST['news_id']));
        foreach($_POST['news_groups'] as $group_id) {
            $DB->query("INSERT IGNORE INTO ".$DB->T('news_in_groups')." (`news_id`, `group_id`) VALUES(".$DB->F($_POST['news_id']).", ".$DB->F($group_id).")");
        }       
               
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
        return $this->material_save_error ? $this->material_save_error : false;
    }
    
    /*-----------------------------------------------------------------------------------------------------------------------------------------------*/
        
    private function show_news()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/news");
        $tpl->loadTemplatefile("admin.news.html");
        
        $tpl->setVariable("MATERIAL_GROUP_ID", intval($this->getOption('mat_group_id')));
        
        $sql = "SELECT EXTRACT(YEAR FROM n.timestamp), COUNT(*) FROM ".$DB->T('news')." AS n GROUP BY EXTRACT(YEAR FROM n.timestamp) ORDER BY n.timestamp DESC;";
        $DB->query($sql);
        while(list($year, $cnt) = $DB->fetch()) {
            if(!isset($_GET['year']) || !$_GET['year']) $_GET['year'] = $year;
            $tpl->setCurrentBlock("years");
            $tpl->setVariable("YEAR_VAL", $year);
            $tpl->setVariable("YEAR_SEL", $year == $_GET['year'] ? "selected" : "");             
            $tpl->setVariable("YEAR_CNT", $cnt);
            $tpl->parse("years");
        }
        $DB->free();
        
        $sql = "SELECT n.id, m.id, n.timestamp, m.name, m.active, (n.short_text IS NOT NULL), n.picture_file_id FROM ".$DB->T('_material')." AS m RIGHT JOIN ".$DB->T('news')." AS n ON n.material_id=m.id WHERE YEAR(n.timestamp)=".$DB->F($_GET['year'])." ORDER BY n.timestamp DESC";
        $DB->query($sql);
        while(list($news_id, $material_id, $timestamp, $material_name, $material_active, $is_short_text, $picture_id) = $DB->fetch()) {
            $tpl->setCurrentBlock("row");
            $tpl->setVariable("NEWS_ID", $news_id);
            $tpl->setVariable("NEWS_MATERIAL_ID", $material_id);
            $tpl->setVariable("NEWS_DATE", rudate("d F Y H:i", $timestamp));
            $tpl->setVariable("NEWS_TITLE", $material_name);
            $tpl->setVariable("IS_RSS", $is_short_text ? "rss":"");
            $tpl->setVariable("IS_PIC", $picture_id ? "pic":"");
            $tpl->setVariable("ONOFF", $material_active ? "on":"off");
            $groups = implode(", ", $DB->getCell("SELECT g.name FROM ".$DB->T('news_in_groups')." AS ng LEFT JOIN ".$DB->T('news_groups')." AS g ON ng.group_id=g.id WHERE ng.news_id=".$DB->F($news_id)." ORDER BY g.is_hidden, g.name"));
            $tpl->setVariable("NEWS_GROUPS", $groups ? $groups : "<em>нет</em>");
            $tpl->parse("row");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function onoff($material_id, $off = false)
    {
        global $DB;
        if(1 != $DB->getField("SELECT COUNT(*) FROM ".$DB->T('news')." WHERE `material_id`=".$DB->F($material_id))) {
            return false;
        }
        $sql = "UPDATE ".$DB->T('_material')." SET `active`=".$DB->F($off ? 0 : 1)." WHERE `id`=".$DB->F($material_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    private function groupOnOff($group_id, $off = false)
    {
        global $DB;        
        $sql = "UPDATE ".$DB->T('news_groups')." SET `is_hidden`=".$DB->F($off ? 1 : 0)." WHERE `id`=".$DB->F($group_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    private function groupRss($group_id, $rss = true)
    {
        global $DB;        
        $sql = "UPDATE ".$DB->T('news_groups')." SET `rss`=".$DB->F($rss ? 1 : 0)." WHERE `id`=".$DB->F($group_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    private function addGroup($name)
    {
        global $DB;   
        if(!$name) return false;
             
        $sql = "INSERT INTO ".$DB->T('news_groups')." (`name`, `rss`, `is_hidden`) VALUES (".$DB->F($name).", 0, 1)";
        $DB->query($sql);
        if($DB->errno()) return false;
        else {
            $group_id = $DB->insert_id();
            cmsLogObject("Добавлена группа новостей '".$name."' (id: ".$group_id.")", "plugin", $this->getUid());
            return true;    
        }
    }
    
    private function groupUpdate($group_id, $name)
    {
        global $DB;   
        if(!$name) return false;
        
        $sql = "UPDATE ".$DB->T('news_groups')." SET `name`=".$DB->F($name)." WHERE `id`=".$DB->F($group_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    private function delGroup($group_id)
    {
        global $DB;   
        if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('news_in_groups')." WHERE `group_id`=".$DB->F($group_id)) > 0) return false;
        $name = $DB->getField("SELECT `name` FROM ".$DB->T('news_groups')." WHERE `id`=".$DB->F($group_id));
        $sql = "DELETE FROM ".$DB->T('news_groups')." WHERE `id`=".$DB->F($group_id);
        $DB->query($sql);
        if($DB->errno()) return false;
        else {
            cmsLogObject("Удалена группа новостей '".$name."' (id: ".$group_id.")", "plugin", $this->getUid());
            return true;    
        }
    }
    
    private function show_groups()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/news");
        $tpl->loadTemplatefile("admin.groups.html");        
        
        $sql = "SELECT g.id, g.name, g.is_hidden, g.rss, (SELECT COUNT(*) FROM ".$DB->T('news_in_groups')." AS ng WHERE ng.group_id=g.id) AS cnt FROM ".$DB->T('news_groups')." AS g ORDER BY g.is_hidden, g.name";
        $DB->query($sql);
        while(list($group_id, $group_name, $is_hidden, $rss, $cnt) = $DB->fetch())
        {
            $tpl->setCurrentBlock("row");
            $tpl->setVariable("GROUP_ID", $group_id);
            $tpl->setVariable("GROUP_NAME", $group_name);
            $tpl->setVariable("ONOFF", $is_hidden ? "off" : "on");
            $tpl->setVariable("IS_RSS", $rss ? "rss" : "");
            $tpl->setVariable("RSS_CHK", $rss ? "checked" : "");
            $tpl->setVariable("GROUP_CNT", $cnt);
            $tpl->parse("row");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function getPictureOptions($material_id, $sel_id = 0)
    {
        global $DB;
        $files = $DB->getCell2("SELECT `id`, `orig_name` FROM ".$DB->T('_files')." WHERE `mat_id`=".$DB->F($material_id)." AND `mime_type` LIKE 'image/%' ORDER BY `orig_name`");
        $options = "";
        foreach($files as $file_id=>$file_name) {
            $sel = $file_id==$sel_id ? "selected" : "";
            $options .= "<option value='$file_id' $sel>$file_name</option>\r\n";
        }
        return $options;
    }
}

?>