<?php

/**
 * AJAX модуль выполнения плагинов
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true);
define('AJAX', true);  

session_start();

require_once(dirname(__FILE__)."/config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

require_once("$_ROOT/cms/classes/DB.php");
require_once("$_ROOT/cms/classes/User.php");

$USER = new UserSession();

$plugin_uid = $_GET['plugin'];
$plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.php";
if(!is_file($plugin_file)) {            
    Debugger::mes(231, "Plugin file ($plugin_file) is absent for '$plugin_uid'.", __FILE__, __LINE__);
    exit ;
}

include_once($plugin_file);

$plugin_class = $plugin_uid."Plugin";
if(!class_exists($plugin_class)) {
    Debugger::mes(232, "Plugin class for '$plugin_uid' does not exists.", __FILE__, __LINE__);
    exit ;
}

$material_id = isset($_POST['material_id']) ? intval($_POST['material_id']) : (isset($_GET['material_id']) ? intval($_GET['material_id']) : 0);
$plugin = new $plugin_class($material_id);
if(!method_exists($plugin, "get")) {
    Debugger::mes(233, "Plugin class method ($plugin_class::get) for '$plugin_uid' does not exists.", __FILE__, __LINE__);            
    exit ;
}

echo $plugin->get();

?>