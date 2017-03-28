<?php

/**
 * Панель управления
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

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("main.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Панель управления");

/* struct */
$plugins_count = 0;
$sql = "SELECT p.id, p.title, p.name, p.order FROM ".$DB->T('_pages')." AS p ORDER BY p.id DESC LIMIT 10";
$DB->query($sql);
while(list($page_id, $page_title, $page_name, $page_order) = $DB->fetch())
{
    $tpl->setCurrentBlock("page");
    $tpl->setVariable("PAGE_ID", $page_id);
    $tpl->setVariable("PAGE_TITLE", $page_title);
    $tpl->setVariable("PAGE_NAME", $page_name);     
    $tpl->setVariable("PAGE_CLASS", $page_order ? "" : "inactive");
    $tpl->parse("page");
}
$DB->free();

/* materials */
$plugins_count = 0;
$sql = "SELECT m.id, m.name, CASE WHEN m.text IS NOT NULL THEN 'text' WHEN m.html IS NOT NULL THEN 'html' WHEN m.css IS NOT NULL THEN 'css' WHEN m.javascript IS NOT NULL THEN 'javascript' WHEN m.plugin IS NOT NULL THEN 'plugin' END AS type, m.active, m.group_id, g.name FROM ".$DB->T('_material')." AS m LEFT JOIN ".$DB->T('_material_groups')." AS g ON m.group_id=g.id ORDER BY m.id DESC LIMIT 10";
$DB->query($sql);
while(list($material_id, $material_name, $material_type, $material_active, $material_group_id, $material_group_name) = $DB->fetch())
{
    $tpl->setCurrentBlock("material");
    $tpl->setVariable("MATERIAL_ID", $material_id);
    $tpl->setVariable("MATERIAL_NAME", $material_name);
    $tpl->setVariable("MATERIAL_GROUP", $material_group_id ? $material_group_name : "(вне групп)");
    $tpl->setVariable("MATERIAL_TYPE", $material_type);     
    $tpl->setVariable("MATERIAL_CLASS", $material_active ? "" : "inactive");
    $tpl->parse("material");
}
$DB->free();

/* plugins */
$plugins_count = 0;
$sql = "SELECT p.uid, p.title, p.desc, p.access_group FROM ".$DB->T('_plugins')." AS p WHERE p.active ORDER BY p.title";
$DB->query($sql);
while(list($plugin_uid, $plugin_title, $plugin_desc, $plugin_access_group_id) = $DB->fetch())
{
    if( $USER->checkGroup($plugin_access_group_id) && is_file("$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php") )
    {
        $plugins_count++;
        
        $tpl->setCurrentBlock("plugin");
        $tpl->setVariable("PLUGIN_UID", $plugin_uid);
        $tpl->setVariable("PLUGIN_TITLE", $plugin_title);
        $tpl->setVariable("PLUGIN_DESC", $plugin_desc);              
        $tpl->parse("plugin");
    }
}
$DB->free();

if($plugins_count < 1) {
    $tpl->touchBlock("noplugins");
}

/* syslog */
if($plugins_count > 10) {
    $log_limit = $plugins_count;
} else {
    $log_limit = 10;
}
$sql = "SELECT l.datetime, l.user_id, l.text, u.login FROM ".$DB->T('_log')." AS l LEFT JOIN ".$DB->T('_users')." AS u ON l.user_id=u.id ORDER BY l.datetime DESC LIMIT $log_limit";
$result = $DB->query($sql);
while(list($log_date, $log_user_id, $log_text, $log_login) = $DB->fetch(false, true, $result))
{
    $tpl->setCurrentBlock("syslog");
    $tpl->setVariable("LOG_DATE", $log_date);
    $tpl->setVariable("LOG_USER", $log_user_id && $log_login ? $log_login : ($log_user_id ? "неизвестно (id: $log_user_id)" : "система"));
    $tpl->setVariable("LOG_TEXT", $log_text);
    $tpl->parse("syslog");
}
$tpl->setVariable("SHOW_CNT", $DB->num_rows($result));
$DB->free($result);

$tpl->show();
?>