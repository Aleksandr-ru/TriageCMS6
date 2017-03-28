<?php

/**
 * Оболочка для управления плагином
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");
require_once("$_ROOT/cms/classes/Plugin.php");

$USER = new UserSession();
$E = new ErrorSession('plugins_admin');
$PLUGIN = new Plugin($_GET['plugin_uid']);

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup($PLUGIN->getGroupId()))
{
    cpl_redirect("forbidden.php");
    exit ;
}

if(!$PLUGIN->getUid()) $E->addError("Нет такого плагина", "Плагин с таким UID не зарегистрирован в системе.");

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("plugin_admin.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Управление плагином ".$PLUGIN->getTitle());

$tpl->setVariable("USE_TINYMCE", intval(getSetting("use_tinymce")));
$tpl->setVariable("USE_CODEMIRROR", intval(getSetting("use_codemirror")));
$tpl->setVariable("TINYMCE_BASE_URL", make_base($_BASE));


$plugin_uid = $PLUGIN->getUid();
$plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php";
if(!is_file($plugin_file)) {            
    $E->addWarning("Отсутвует интерфейс управления плагином", "Убедитесь, что файл '$plugin_file' существует.");
} 
else 
{
    include_once($plugin_file);

    $plugin_class = $plugin_uid."PluginAdmin";
    if(!class_exists($plugin_class)) {
        $E->addWarning("Отсутвует класс управления плагином", "Обратитесь к разработчику плагина!");
    }
    else 
    {
        $pluginAdmin = new $plugin_class();
        if(!method_exists($pluginAdmin, "get")) {            
            $E->addWarning("Отсутвует метод в классе управления плагином", "Обратитесь к разработчику плагина!");
        }     
        else 
        {
            $tpl->setVariable("PLUGIN_OUTPUT", $pluginAdmin->get());
        }
    }   
}

if(!defined('CPL_REDIRECT')) {
    $E->showError($tpl);
    $E->showWarning($tpl);
    $E->showNotice($tpl);
}

$tpl->show();
?>