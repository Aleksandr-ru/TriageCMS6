<?php

/**
 * Вход в панель управления
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

$USER = new UserSession();
$E = new ErrorSession('trashcan');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
/*elseif(!$USER->checkGroup(getSetting('settings_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}*/

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("trashcan.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Корзина");

$E->showError($tpl);
$E->showWarning($tpl);
$E->showNotice($tpl);

$sql = "SELECT l.id, l.type_name, l.name, l.date, l.user_id, u.login FROM ".$DB->T('_trashcan')." AS l LEFT JOIN ".$DB->T('_users')." AS u ON l.user_id=u.id $filter ORDER BY l.date DESC";
$result = $DB->query($sql);
while(list($trash_id, $typename, $name, $date, $user_id, $login) = $DB->fetch(false, true, $result))
{
    $tpl->setCurrentBlock("row");
    $tpl->setVariable("TRASH_ID", $trash_id);
    $tpl->setVariable("TYPE", $typename);
    $tpl->setVariable("NAME", $name);
    $tpl->setVariable("DATE", $date);
    $tpl->setVariable("USER", $user_id && $login ? $login : ($user_id ? "<em>неизвестно (id: $user_id)</em>" : "<strong>система</strong>"));
    $tpl->parse("row");
}
$tpl->setVariable("SHOW_CNT", $DB->num_rows($result));
$DB->free($result);

$tpl->setVariable("ALL_CNT", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_trashcan')));

$tpl->show();
?>