<?php

/**
 * Класс плагина CMS
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../lib/db.lib.php");
require_once("$_ROOT/cms/lib/plugins.lib.php");

class Plugin
{
    protected $row = array();
    protected $material_id = 0;
    protected $options = array();
    
    function __construct($uid, $material_id = 0)
    {
        global $DB;
        $this->material_id = $material_id;
        $this->row = $DB->getRow("SELECT * FROM ".$DB->T('_plugins')." WHERE `uid`=".$DB->F($uid), true);                 
        $this->options = $DB->getCol2("SELECT `name`, `value` FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($this->getUid())." AND (`material_id`=0 OR `material_id`=".$DB->F($this->material_id).") ORDER BY `material_id`", false);   
    }
    
    function getUid()
    {
        return isset($this->row['uid']) && $this->row['uid'] ? $this->row['uid'] : false;
    }
    
    function getTitle()
    {
        return $this->row['title'];
    }
    
    function getDesc()
    {
        return $this->row['desc'];
    }
    
    function isActive()
    {
        return $this->row['active'] ? true : false;
    }
    
    function getGroupId()
    {
        return $this->row['access_group'];
    }
    
    function getOption($name, $default = null)
    {
        global $DB;
        /*
        $value = $DB->getField("SELECT `value` FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($this->getUid())." AND `name`=".$DB->F($name)." AND (`material_id`=0 OR `material_id`=".$DB->F($this->material_id).") ORDER BY `material_id` DESC LIMIT 1", false);
        
        if($value === false) {
            return $default;
        } else {
            return $value;
        }*/
        return isset($this->options[$name]) ? $this->options[$name] : $default; 
    }
    
    function loadTemplate($template_file)
    {
        global $PAGE;
        return getPluginTemplateObject($this->getUid(), $template_file, ($PAGE instanceof Page) ? $PAGE : null);                        
    }
    
    /**
     * Plugin::getPluginEmailAddr()
     * получить адрес с которого уходит почта от плагина
     * вида plugin-name@host
     * 
     * @return email
     */
    function getPluginEmailAddr()
    {
        return $this->getUid().'@'.$_SERVER['HTTP_HOST'];
    }
    
    /**
     * Plugin::getAffectedMaterials()
     * получить массив id материалов в которых используется плагин
     * 
     * @param bool $active_only возвращать только включенные материалы
     * @return array
     */
    function getAffectedMaterials($active_only = true)
    {
        global $DB;
        return $DB->getCol("SELECT m.id FROM ".$DB->T('_material')." AS m WHERE m.type='plugin' AND m.data=".$DB->F($this->getUid()).($active_only ? " AND m.active" : ''));
    }
    
    /**
     * Plugin::getAffectedPages()
     * получить массив id страниц на которых используется плагин
     * 
     * @param bool $active_only возвращать только включенные страницы со включенными материалами
     * @return array
     */
    function getAffectedPages($active_only = true)
    {
        global $DB;
        return $DB->getCol("SELECT pm.page_id FROM ".$DB->T('_material')." AS m JOIN ".$DB->T('_page_materials')." AS pm ON m.id=pm.material_id JOIN ".$DB->T('_pages')." AS p ON pm.page_id=p.id WHERE m.type='plugin' AND m.data=".$DB->F($this->getUid()).($active_only ? " AND m.active AND p.order" : ''));
    }
}
?>