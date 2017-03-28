<?php

/**
 * Управление группами доступа
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
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

$USER = new UserSession();
$E = new ErrorSession('groups');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('user_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("groups.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Группы доступа");

$E->showAll($tpl);

$groups = $DB->getCol2("SELECT `id`, `name` FROM ".$DB->T('_groups')." ORDER BY`name`");
while(list($group_id, $group_name) = each($groups))
{
    $tpl->setCurrentBlock("row");
    $tpl->setVariable("GROUP_ID", $group_id);
    $tpl->setVariable("GROUP_NAME", $group_name);
    $tpl->setVariable("CNT_USERS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_user_groups')." WHERE `group_id`=".$DB->F($group_id)));
    $tpl->setVariable("CNT_PAGES", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `access_group`=".$DB->F($group_id)));
    $tpl->setVariable("CNT_MAT", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_material')." WHERE `access_group`=".$DB->F($group_id)));
    $tpl->setVariable("CNT_PLUGINS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_plugins')." WHERE `access_group`=".$DB->F($group_id)));
    $tpl->setVariable("CNT_SETTIGS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_settings')." WHERE `value`=".$DB->F($group_id)." AND `sprav` LIKE '".str_replace("`","",$DB->T('_groups')).".id%'"));
    $tpl->parse("row");
}

$tpl->show();
?>