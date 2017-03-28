<?php

/**
 * Управление модулем
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010 
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
require_once("$_ROOT/cms/classes/ITM.php");
 
class sendemailPluginAdmin extends Plugin
{
    const uid = 'sendemail';
            
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
                case "delpost":
                    return $this->del_post($_POST['post_id']) ? "" : "Ошибка удаления поста";
            }
            return "No event";
        }
        
        switch($_GET['event']) {
            case "save_emails":
                return $this->save_emails();
            case "save_fields":
                return $this->save_fields();    
        }        
        return $this->show($_GET['material_id']);
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
    
    /* --- */
    
    private function show($material_id)
    {
        global $_ROOT, $DB, $E;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/sendemail");
        $tpl->loadTemplatefile("admin.tmpl.html");
        
        $material_options = "";
        $sql = "SELECT `id`, `name` FROM ".$DB->T('_material')." WHERE `type`='plugin' AND `data`='sendemail' ORDER BY `name`";
        $DB->query($sql);
        while(list($mat_id, $material_name) = $DB->fetch()) {
            if(!$material_id) $material_id = $mat_id;
            $sel = $mat_id == $material_id ? "selected" : "";
            $material_options .= "<option value='$mat_id' $sel>$material_name</option>\r\n";
        }
        $DB->free();
        $tpl->setVariable("MATERIAL_OPTIONS", $material_options);
        if(!$material_options) {
            $E->addWarning("Нет материалов с плагином", "Создайте материал с плагином '{$this->getTitle()}' в редакторе материалов, после этого будет доступен интерфейс управления. <a href='material.php'>Создать материал</a>.");
            $tpl->setVariable("NOMATERIAL_DIS", "disabled");
        }
        $tpl->setVariable("MATERIAL_ID", $material_id);
        
        $sql = "SELECT `id`, `name`, `email`, `order`, (`order`=0) AS noord FROM ".$DB->T('sendemail_addr')." WHERE `material_id`=".$DB->F($material_id)." ORDER BY noord, `order`, `name`";
        $DB->query($sql);
        while(list($email_id, $email_name, $email_email, $email_order) = $DB->fetch()) {
            $tpl->setCurrentBlock("emailrow");
            $tpl->setVariable("EMAIL_ID", $email_id);
            $tpl->setVariable("EMAIL_NAME", $email_name);
            $tpl->setVariable("EMAIL_EMAIL", $email_email);
            $tpl->setVariable("EMAIL_SORT", $email_order);
            $tpl->parse("emailrow");
        }
        $DB->free();
        
        $sql = "SELECT `id`, `name`, `type`, `default`, `regexp`, `required`, `is_subj`, `order`, (`order`=0) AS noord FROM ".$DB->T('sendemail_fields')." WHERE `material_id`=".$DB->F($material_id)." ORDER BY noord, `order`, `name`";
        $DB->query($sql);
        while(list($field_id, $field_name, $field_type, $field_default, $field_regexp, $field_req, $field_subj, $field_order) = $DB->fetch()) {
            $tpl->setCurrentBlock("fieldrow");
            $tpl->setVariable("FIELD_ID", $field_id);
            $tpl->setVariable("FIELD_NAME", $field_name);
            $tpl->setVariable("SEL_".strtoupper($field_type), "selected");
            $tpl->setVariable("FIELD_DEFAULT", $field_default);
            $tpl->setVariable("FIELD_ACCEPT", $field_regexp);
            $tpl->setVariable("FIELD_REQ_CHK", $field_req ? "checked" : "");
            $tpl->setVariable("FIELD_SUBJ_CHK", $field_subj ? "checked" : "");
            $tpl->setVariable("FIELD_SORT", $field_order);
            $tpl->parse("fieldrow");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function save_emails()
    {
        global $DB;
        
        //return print_r($_POST, 1);
        
        if($_POST['material_id']) foreach($_POST['email_id'] as $email_id) {
            if(isset($_POST['email_del']) && in_array($email_id, $_POST['email_del'])) {
                $DB->query("DELETE FROM ".$DB->T('sendemail_addr')." WHERE `material_id`=".$DB->F($_POST['material_id'])." AND `id`=".$DB->F($email_id));
            } elseif($_POST['email_name'][$email_id] && $_POST['email_email'][$email_id]) {
                $sql = "UPDATE ".$DB->T('sendemail_addr')." SET `name`=".$DB->F($_POST['email_name'][$email_id]).", email=".$DB->F($_POST['email_email'][$email_id]).", `order`=".$DB->F($_POST['email_sort'][$email_id])." WHERE `material_id`=".$DB->F($_POST['material_id'])." AND `id`=".$DB->F($email_id);
                $DB->query($sql);
            }
        }
        
        if($_POST['material_id'] && $_POST['new_name'] && $_POST['new_email']) {
            $sql = "INSERT INTO ".$DB->T('sendemail_addr')." (`material_id`, `name`, `email`, `order`) VALUES(".$DB->F($_POST['material_id']).", ".$DB->F($_POST['new_name']).", ".$DB->F($_POST['new_email']).", ".$DB->F($_POST['new_sort']).")";
            $DB->query($sql);            
        }
        
        if(isset($_POST['clear'])) {
            $DB->query("DELETE FROM ".$DB->T('sendemail_addr')." WHERE `material_id`=".$DB->F($_POST['material_id']));
        }
        
        return cpl_redirect($_SERVER['PHP_SELF']."?plugin_uid=".$this->getUid()."&material_id=".$_POST['material_id'], true);
    }
    
    private function save_fields()
    {
        global $DB;
        
        //return print_r($_POST, 1);
        
        if($_POST['material_id']) foreach($_POST['field_id'] as $field_id) {
            if(isset($_POST['field_del']) && in_array($field_id, $_POST['field_del'])) {
                $DB->query("DELETE FROM ".$DB->T('sendemail_fields')." WHERE `material_id`=".$DB->F($_POST['material_id'])." AND `id`=".$DB->F($field_id));
            } elseif($_POST['field_name'][$field_id] && $_POST['field_type'][$field_id]) {
                $sql = "UPDATE ".$DB->T('sendemail_fields')." SET `name`=".$DB->F($_POST['field_name'][$field_id]).", `type`=".$DB->F($_POST['field_type'][$field_id]).", `default`=".$DB->F($_POST['field_default'][$field_id], true).", `regexp`=".$DB->F($_POST['field_accept'][$field_id], true).", `required`=".$DB->F(in_array($field_id, $_POST['field_req']) ? 1 : 0).", `is_subj`=".$DB->F(in_array($field_id, $_POST['field_subj']) ? 1 : 0).", `order`=".$DB->F($_POST['field_sort'][$field_id])." WHERE `material_id`=".$DB->F($_POST['material_id'])." AND `id`=".$DB->F($field_id);
                $DB->query($sql);
            }
        }
        
        if($_POST['material_id'] && $_POST['new_name'] && $_POST['new_type']) {
            $sql = "INSERT INTO ".$DB->T('sendemail_fields')." (`material_id`, `name`, `type`, `default`, `regexp`, `required`, `is_subj`, `order`) VALUES(".$DB->F($_POST['material_id']).", ".$DB->F($_POST['new_name']).", ".$DB->F($_POST['new_type']).", ".$DB->F($_POST['new_default']).", ".$DB->F($_POST['new_accept']).", ".$DB->F(isset($_POST['new_req']) ? 1 : 0).", ".$DB->F(isset($_POST['new_subj']) ? 1 : 0).", ".$DB->F($_POST['new_sort']).")";
            $DB->query($sql);            
        }
        
        if(isset($_POST['clear'])) {
            $DB->query("DELETE FROM ".$DB->T('sendemail_fields')." WHERE `material_id`=".$DB->F($_POST['material_id']));
        }
        
        return cpl_redirect($_SERVER['PHP_SELF']."?plugin_uid=".$this->getUid()."&material_id=".$_POST['material_id'], true);
    }
}

?>