<?php

/**
 * Диалог выбора материала 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

session_start(); 

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ITM.php");

if(!$DB){ global $DB; }

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("materials.dlg.html", true, true);

if(isset($_GET['material_id']))
{
    $tpl->setVariable("SEL_GRP", $DB->getField("SELECT m.group_id FROM ".$DB->T('_material')." AS m WHERE m.id=".$DB->F($_GET['material_id'])));
    $tpl->setVariable("SEL_MAT", $_GET['material_id']);
}
else
{
    $tpl->setVariable("SEL_GRP", 0);
    $tpl->setVariable("SEL_MAT", 0);
}

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

$tpl->show();
?>