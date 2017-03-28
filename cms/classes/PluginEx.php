<?php

/**
 * Класс редактирования плагина CMS
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../lib/db.lib.php");
require_once(dirname(__FILE__)."/Plugin.php");
require_once(dirname(__FILE__)."/PluginInstaller.php");

class PluginEx extends Plugin
{
    function __construct($uid)
    {
        parent::__construct($uid);                
    }
    
    function setTitle($value, $commit = false)
    {                
        $this->row['title'] = $value;
        
        if($commit) {
            return $this->update();
        }
        else {
            return true;
        }
    }
    
    function setDesc($value, $commit = false)
    {                
        $this->row['desc'] = $value;
        
        if($commit) {
            return $this->update();
        }
        else {
            return true;
        }
    }
    
    function setActive($value, $commit = false)
    {                
        $this->row['active'] = $value ? 1 : 0;
        
        if($commit) {
            return $this->update();
        }
        else {
            return true;
        }
    }
    
    function setGroupId($value, $commit = false)
    {                
        $this->row['access_group'] = intval($value);
        
        if($commit) {
            return $this->update();
        }
        else {
            return true;
        }
    }
    
    function update()
    {
        global $DB;
        
        if(!$this->getUid()) return false;
        
        $update = array();
        $update[] = "`title`=".$DB->F($this->row['title'], true);
        $update[] = "`desc`=".$DB->F($this->row['desc'], true);
        $update[] = "`active`=".$DB->F($this->row['active'], true);
        $update[] = "`access_group`=".$DB->F($this->row['access_group']);
                
        $sql = "UPDATE ".$DB->T('_plugins')." SET ".implode(", ", $update)." WHERE `uid`=".$DB->F($this->getUid());
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    function getGroupOptions()
    {
        global $DB;
        
        $ret = "";
        foreach($DB->getCol2("SELECT `id`, `name` FROM ".$DB->T('_groups')." ORDER BY `name`") as $group_id=>$group_name)
        {
            $sel = $group_id == $this->getGroupId() ? "selected" : "";
            $ret .= "<option value=\"$group_id\" $sel>$group_name</option>\r\n";
        }
        return $ret;
    }
    
    function setOption($name, $material_id = 0, $value = '')
    {
        global $DB;
        if(!$name) return false;
                
        $sql = "INSERT INTO ".$DB->T('_plugin_options')." (`plugin_uid`, `material_id`, `name`, `value`) VALUES(".$DB->F($this->getUid()).", ".$DB->F($material_id).", ".$DB->F($name).", ".$DB->F($value).") ON DUPLICATE KEY UPDATE `value`=".$DB->F($value);
        return $DB->query($sql);
    }
    
    function ResetOptions()
    {
        global $_ROOT, $plugin_install;
        
        include($_ROOT."/cms/plugins/".$this->getUid()."/".$this->getUid().".install.php");
        if(!isset($plugin_install['title'][$this->getUid()])) return false;
        
        if(($func = $this->getUid().'_install') && function_exists($func)) {            
            return $func(true);
        }
        return false;
    }
    
    function ResetMaterials()
    {
        global $DB;
        $sql = "DELETE FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($this->getUid())." AND `material_id` != 0";
        return $DB->query($sql);
    }
    
    /*
    function ResetMaterialOptions($material_id)
    {
        global $DB;
                
        if(!$material_id) return false;
        
        $sql = "DELETE FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($this->getUid())." AND `material_id`=".$DB->F($material_id);
        return $DB->query($sql);
    }
    */
}
?>