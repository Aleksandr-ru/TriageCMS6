<?php

/**
 * Класс установки плагина CMS
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../lib/db.lib.php");
require_once(dirname(__FILE__)."/DB.php");
require_once("$_ROOT/cms/lib/plugins.lib.php");

class PluginInstaller
{
    private $uid = '';
    private $title = '';
    private $description = '';
    private $options = array();
    private $events = array();
    
    function __construct($uid, $title, $description)
    {
        $this->uid = $uid;
        $this->title = $title;
        $this->description = $description;
    }
    
    function getUid()
    {
        return $this->uid;
    }
    
    function getTitle($escape = true)
    {
        return $escape ? htmlspecialchars($this->title) : $this->title;
    }
    
    function getDescription($escape = true)
    {
        return $escape ? htmlspecialchars($this->description) : $this->description;
    }
    
    function addOption($option_name, $option_value, $option_desc, $option_sprav = '')
    {
        $this->options[] = array($option_name, $option_value, $option_desc, $option_sprav);
        return sizeof($this->options);
    }
    
    function addEvent($event)
    {
        $this->events[] = $event;
        return sizeof($this->events);
    }
    
    function Install($reinstall = false)
    {
        global $DB;
        $ret = true;
        
        $sql = "INSERT INTO ".$DB->T('_plugins')." (`uid`, `title`, `desc`, `active`, `access_group`) VALUES (".$DB->F($this->getUid()).", ".$DB->F($this->getTitle(false)).", ".$DB->F($this->getDescription(false), true).", 0, 0) ON DUPLICATE KEY UPDATE `title`=".$DB->F($this->getTitle(false)).", `desc`=".$DB->F($this->getDescription(false), true).", `access_group`=0";
        $DB->query($sql);
        $ret = $ret && ($DB->errno() ? false : true);
        
        if($ret) {
            cmsLogObject(($reinstall ? "Переустановлен" : "Установлен"). " плагин '".$this->getTitle(false)."'", "plugin", $this->getUid()); 
        }
        
        $sql = "DELETE FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($this->getUid());
        $DB->query($sql);
        
        foreach($this->options as $options) {
            list($option_name, $option_value, $option_desc, $option_sprav) = $options;
            $sql = "INSERT INTO ".$DB->T('_plugin_options')." (`plugin_uid`, `material_id`, `name`, `value`, `desc`, `sprav`) VALUES (".$DB->F($this->getUid()).", 0, ".$DB->F($option_name).", ".$DB->F($option_value).", ".$DB->F($option_desc, true).", ".$DB->F($option_sprav, true).")";
            $DB->query($sql);
            $ret = $ret && ($DB->errno() ? false : true);
        }
        
        $sql = "DELETE FROM ".$DB->T('_events')." WHERE `plugin_uid`=".$DB->F($this->getUid());
        $DB->query($sql);
        
        foreach($this->events as $event) {
            $sql = "INSERT INTO ".$DB->T('_events')." (`plugin_uid`, `event`) VALUES (".$DB->F($this->getUid()).", ".$DB->F($event).")";
            $DB->query($sql);
            $ret = $ret && ($DB->errno() ? false : true);
        }
        
        return $ret;
    }
    
    /*
    static function parseSQL($sql)
    {
        global $_config;
        return str_replace("%PREFIX%", $_config['table_prefix'], $sql);
    }
    */
}
?>