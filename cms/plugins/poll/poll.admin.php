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

class pollPluginAdmin extends Plugin
{
    const uid = 'poll';
            
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
                case "onoff":
                    return $this->onoff($_GET['poll_id'], $_POST['off']) ? 'OK' : 'Ошибка переключения';                                    
                case "delvote":
                    return $this->delvote($_GET['vote_id']) ? 'OK' : 'Ошибка удаления';
            }
            return "No event";
        }
        
        switch($_GET['event'])
        {
            case "edit":
                return $this->edit_poll();
            case "save":
                return $this->save_poll();             
            case "polls":
            default:
                return $this->show_polls();
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
        return false;
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
        return false;
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
        
    private function show_polls()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/poll");
        $tpl->loadTemplatefile("admin.polls.html");
        
        $sql = "SELECT * FROM ".$DB->T('polls')." ORDER BY `active` DESC, `name`";
        $DB->query($sql);
        while(list($poll_id, $poll_name, $poll_desc, $active, $fake_percent, $fake_threshold) = $DB->fetch()) {
            $tpl->setCurrentBlock("row");
            $tpl->setVariable("POLL_ID", $poll_id);
            $tpl->setVariable("POLL_NAME", $poll_name);
            $tpl->setVariable("POLL_DESC", $poll_desc);
            if(!$active) $tpl->setVariable("INACTIVE", "inactive");
            $tpl->setVariable("FAKE_TEXT", ($fake_percent && $fake_threshold) ? "<b>$fake_percent%</b> после <b>$fake_threshold</b> голосов в сумме" : "нет");
            
            $tpl->setVariable("POLL_SUM", $sum = $DB->getField("SELECT SUM(`votes`) FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)));
            $sql = "SELECT `id`, `name`, `votes`, `is_fake` FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." ORDER BY `order`";
            $DB->query($sql);
            while(list($vote_id, $vote_name, $vote_cnt, $is_fake) = $DB->fetch()) {
                $tpl->setCurrentBlock("vote");
                $tpl->setVariable("VOTE_ID", $vote_id);
                $tpl->setVariable("VOTE_NAME", $vote_name);
                $tpl->setVariable("VOTE_CNT", $vote_cnt);
                $tpl->setVariable("VOTE_PERCENT", round($vote_cnt/$sum*100, 0));
                if($is_fake && $fake_percent && $fake_threshold) $tpl->setVariable("FAKE", "fake");
                $tpl->parse("vote");
            }
            $DB->free();
            $tpl->parse("row");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function onoff($poll_id, $off = false)
    {
        global $DB;
        $sql = "UPDATE ".$DB->T('polls')." SET `active`=".$DB->F($off ? 0 : 1)." WHERE `id`=".$DB->F($poll_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    private function delvote($vote_id)
    {
        global $DB;
        $sql = "DELETE FROM ".$DB->T('poll_votes')." WHERE `id`=".$DB->F($vote_id);
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
        
    private function edit_poll()
    {
        global $_ROOT, $DB;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/poll");
        $tpl->loadTemplatefile("admin.editor.html");        
        
        if($poll_id = isset($_GET['poll_id']) && $_GET['poll_id'] ? intval($_GET['poll_id']) : 0) {
            list($poll_id, $poll_name, $poll_desc, $active, $fake_percent, $fake_threshold) = $DB->getRow("SELECT * FROM ".$DB->T('polls')." WHERE `id`=".$DB->F($poll_id));
            $tpl->setVariable("POLL_ID", $poll_id);
            $tpl->setVariable("POLL_NAME", $poll_name);
            $tpl->setVariable("POLL_DESC", $poll_desc);
            $tpl->setVariable("FAKE_PERCENT", $fake_percent);
            $tpl->setVariable("fake_threshold", $fake_threshold);
            
            $sql = "SELECT `id`, `name`, `is_fake`, `order` FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." ORDER BY `order`";
            $DB->query($sql);
            while(list($vote_id, $vote_name, $is_fake, $vote_order) = $DB->fetch()) {
                $tpl->setCurrentBlock("vote");
                $tpl->setVariable("VOTE_ID", $vote_id);
                $tpl->setVariable("VOTE_NAME", $vote_name);
                $tpl->setVariable("VOTE_ORDER", $vote_order);
                if($is_fake) $tpl->setVariable("FAKE_CHK", "checked");
                $tpl->parse("vote");
            }
            $DB->free();
        }
        
        
        $tpl->setVariable("ID_TEXT", $poll_id ? $poll_id : 'новый');
        
        return $tpl->get();
    }
    
    private function save_poll()
    {
        global $_ROOT, $DB, $E;
        //Debugger::dump($_POST,1);
        /*
        [poll_id] => 1
        [vote_fake_idx] => 2
        [poll_name] => Демо опрос
        [fake_percent] => 60
        [fake_threshold] => 20
        [vote_id] => Array
            (
                [0] => 1
                [1] => 2
                [2] => 
            )
    
        [vote_order] => Array
            (
                [0] => 1
                [1] => 2
                [2] => 
            )
    
        [vote_name] => Array
            (
                [0] => Ответ 1
                [1] => Ответ 2
                [2] => Ответ 4
            )*/        
        if(!$_POST['poll_name']) $E->addError("Нет названия опроса", "У каждого опроса должно быть название, введите название опроса, а только потом сохраняйте его.");
        if($E->isError()) {
            cpl_redirect(getenv('HTTP_REFERER'));
            exit;    
        }
                
        if($poll_id = isset($_POST['poll_id']) && $_POST['poll_id'] ? intval($_POST['poll_id']) : 0) {
            $sql = "UPDATE ".$DB->T('polls')." SET `name`=".$DB->F($_POST['poll_name']).", `desc`=".$DB->F($_POST['poll_desc'], true).", `fake_percent`=".$DB->F(intval($_POST['fake_percent'])).", `fake_threshold`=".$DB->F(intval($_POST['fake_threshold']))." WHERE `id`=".$DB->F($poll_id);
        } else {
            $sql = "INSERT INTO ".$DB->T('polls')." (`name`, `desc`, `active`, `fake_percent`, `fake_threshold`) VALUES(".$DB->F($_POST['poll_name']).", ".$DB->F($_POST['poll_desc'], true).", 0, ".$DB->F(intval($_POST['fake_percent'])).", ".$DB->F(intval($_POST['fake_threshold'])).")";
        }
        $DB->query($sql);
        if($DB->errno()) $E->addError("Ошибка сохранения опроса", $DB->error());
        elseif(!$poll_id) $poll_id = $DB->insert_id();
        
        if($poll_id) {
            $sql = "UPDATE ".$DB->T('poll_votes')." SET `is_fake`=0 WHERE `poll_id`=".$DB->F($poll_id);
            $DB->query($sql);
            
            foreach($_POST['vote_id'] as $i=>$vote_id) {
                $is_fake = $i==$_POST['vote_fake_idx'] ? 1 : 0;
                $order = $_POST['vote_order'][$i] ? $_POST['vote_order'][$i] : $DB->getField("SELECT COUNT(*)+1 FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id));
                if($vote_id && $_POST['vote_name'][$i]) {
                    $sql = "UPDATE ".$DB->T('poll_votes')." SET `name`=".$DB->F($_POST['vote_name'][$i]).", `order`='$order', `is_fake`='$is_fake' WHERE `id`=".$DB->F($vote_id);
                    $DB->query($sql);
                } elseif(!$vote_id && $_POST['vote_name'][$i]) {
                    $sql = "INSERT INTO ".$DB->T('poll_votes')." (`poll_id`, `name`, `order`, `is_fake`) VALUES(".$DB->F($poll_id).", ".$DB->F($_POST['vote_name'][$i]).", '$order', '$is_fake')";
                    $DB->query($sql);
                }
                if($DB->errno()) $E->addWarning("Не удалось сохранить вариант ответа ($i)", $DB->error());
            }
        }    
        cpl_redirect($poll_id && !$E->isWarning() ? "plugin.php?plugin_uid=poll" : getenv('HTTP_REFERER'));
        exit;  
    }
}

?>