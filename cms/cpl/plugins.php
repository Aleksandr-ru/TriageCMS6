<?php

/**
 * Управление плагинами
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009 
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
require_once("$_ROOT/cms/classes/PluginEx.php");

$USER = new UserSession();
$E = new ErrorSession('plugins');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("plugins.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Плагины");

$sql = "SELECT p.uid, p.title, p.desc, p.active, p.access_group, g.name FROM ".$DB->T('_plugins')." AS p LEFT JOIN ".$DB->T('_groups')." g ON p.access_group=g.id ORDER BY p.title";
$DB->query($sql);
if($DB->num_rows() < 1)
{
    $tpl->touchBlock("noplugin");
    $tpl->setVariable("SETTINGS_DIS", "disabled");
    
    if(!$USER->checkGroup(getSetting('plugin_conf_group')))
    {
        $tpl->touchBlock("cant_install");
    }
}
if( $USER->checkGroup(getSetting('plugin_conf_group')) && cpl_numOfAvailPlugins() )
{
    $tpl->setCurrentBlock("can_install");
    $tpl->setVariable("AVAIL_PLUGINS_CNT", cpl_numOfAvailPlugins());
    $tpl->parse("can_install");
}
while(list($plugin_uid, $plugin_title, $plugin_desc, $plugin_active, $access_group_id, $access_group) = $DB->fetch())
{
    if(!@$_GET['plugin_uid']) $_GET['plugin_uid'] = $plugin_uid; 
    $plugin_class = "";
    if(!$plugin_active)
    {
        $plugin_class = "disabled";
        $plugin_title .= " (отключен)";
    } 
    if($_GET['plugin_uid'] == $plugin_uid) {
        $plugin_class .= " selected";
    }
    $tpl->setCurrentBlock("plugin");
    $tpl->setVariable("PLUGIN_UID", $plugin_uid);
    $tpl->setVariable("PLUGIN_TITLE", $plugin_title);
    $tpl->setVariable("PLUGIN_DESC", $plugin_desc);
    $tpl->setVariable("PLUGIN_GROUP", $access_group_id ? $access_group : "<em>не назначено</em>");
    $tpl->setVariable("PLUGIN_CLASS", $plugin_class);
    $tpl->setVariable("CONTROL_CLASS", $USER->checkGroup($access_group_id) && is_file("$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php") ? "":"disabled");
    $tpl->setVariable("SETTING_CLASS", $USER->checkGroup(getSetting('plugin_conf_group')) ? "":"disabled");        
    $tpl->parse("plugin");
}
$DB->free();

if(!$USER->checkGroup(getSetting('plugin_conf_group'))) { 
    $E->addWarning("Вы не можете менять настройки плагинов", "У Вашей учетной запси недостаточно прав для изменения настроек плагинов.");
    $tpl->setVariable("SETTINGS_DIS", "disabled");
} else {
    
    $plugin = new PluginEx($_GET['plugin_uid']);
    
    $tpl->setCurrentBlock("settings");
    $tpl->setVariable("PLUGIN_UID2", $plugin->getUid());
    $tpl->setVariable("PLUGIN_TITLE2", $plugin->getTitle());
    $tpl->setVariable("ACTIVE_CHK", $plugin->isActive() ? "checked" : "");
    $tpl->setVariable("PLUGIN_GROUP_OPTIONS", $plugin->getGroupOptions());
    
    $sql = "SELECT `name`, `value`, `desc`, `sprav` FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($plugin->getUid())." AND `material_id`=0 ORDER BY `desc`, `name`";
    $DB->query($sql);
    while(list($option_name, $option_value, $option_desc, $option_sprav) = $DB->fetch())
    {
        $tpl->setCurrentBlock("options");
        $tpl->setVariable("OPTION_NAME", $option_desc ? $option_desc : $option_name);
        $tpl->setVariable("OPTION_VALUE", cpl_getInputBySprav("options[$option_name]", $option_value, $option_sprav));
        $tpl->parse("options");
    }
    $DB->free();
    $tpl->parse("settings");    

}

// всякие прочие ошибки из сессии
$E->showError($tpl);
$E->showWarning($tpl);
$E->showNotice($tpl);

$tpl->show();
?>