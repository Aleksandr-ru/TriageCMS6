<?php

/**
 * AJAX модуль выполнения админки плагинов
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010  
 */

define('TRIAGE_CMS', true);
define('AJAX', true);  

session_start();

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

require_once("$_ROOT/cms/classes/Plugin.php");
require_once("$_ROOT/cms/classes/DB.php");
require_once("$_ROOT/cms/classes/User.php");

$USER = new UserSession();
$PLUGIN = new Plugin($_GET['plugin_uid']);

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    //cpl_redirect("login.php");
    die("Log in please");
}
elseif(!$USER->checkGroup($PLUGIN->getGroupId()))
{
    //cpl_redirect("forbidden.php");
    die("Forbidden");
}

if(!$PLUGIN->getUid()) die("No such plugin");



$plugin_uid = $_GET['plugin_uid'];
$plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php";
if(!is_file($plugin_file)) {            
    Debugger::mes(230, "Plugin file ($plugin_file) is absent for '$plugin_uid'.", __FILE__, __LINE__);
    die("Error 230");
}

include_once($plugin_file);

$plugin_class = $plugin_uid."PluginAdmin";
if(!class_exists($plugin_class)) {
    Debugger::mes(231, "Plugin class for '$plugin_uid' does not exists.", __FILE__, __LINE__);
    die("Error 231");
}

$plugin = new $plugin_class($material_id);
if(!method_exists($plugin, "get")) {
    Debugger::mes(232, "Plugin class method ($plugin_class::get) for '$plugin_uid' does not exists.", __FILE__, __LINE__);            
    die("Error 232");
}

echo $plugin->get();
?>