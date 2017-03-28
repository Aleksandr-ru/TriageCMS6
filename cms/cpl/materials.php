<?php

/**
 * Управление материалам
 * 
 * @package Triage CMS v.6
 * @version 6.2
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
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("materials.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Управление материалами");

$sql = "SELECT COUNT(*) FROM ".$DB->T('_material')." AS m WHERE(m.group_id NOT IN (SELECT g.id FROM ".$DB->T('_material_groups')." AS g)) ORDER BY m.name";
$tpl->setVariable("NOGROUP_COUNT", $DB->getField($sql));

$sql = "SELECT g.id, g.name, g.hidden, (SELECT COUNT(*) FROM ".$DB->T('_material')." AS m WHERE(m.group_id=g.id)) FROM ".$DB->T('_material_groups')." AS g ORDER BY g.hidden, g.name";
$result = $DB->query($sql);
while(list($group_id, $group_name, $group_hidden, $group_count) = $DB->fetch(false, true, $result))
{
    $tpl->setCurrentBlock("group");
    $tpl->setVariable("GROUP_ID", $group_id);
    $tpl->setVariable("GROUP_CLASS", $group_hidden ? "hidden_group" : "");
    $tpl->setVariable("GROUP_NAME", $group_name);
    $tpl->setVariable("GROUP_COUNT", $group_count);
    $tpl->parse("group");
}
$DB->free($result);

if(isset($_GET['group_id'])) $tpl->setVariable("SELECTED_GROUP_ID", intval($_GET['group_id']));

$tpl->show();
?>