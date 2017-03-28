<?php

/**
 * Управление пользователями
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
require_once("$_ROOT/cms/classes/Dialog.php");
require_once("$_ROOT/cms/classes/PageEx.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('users');

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
$tpl->loadTemplatefile("users.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Пользователи");

$E->showWarning($tpl);
$E->showError($tpl);

$sql = "SELECT DISTINCT(UPPER(LEFT(`login`,1))) FROM ".$DB->T('_users')." ORDER BY `login`";
$result = $DB->query($sql);
while(list($letter) = $DB->fetch(false, true, $result))
{
    if(!isset($_GET['l'])) $_GET['l'] = $letter;
    $tpl->setCurrentBlock("let");
    $tpl->setVariable("LETTER", $letter);
    $tpl->setVariable("LETTER_URL", urlencode($letter));
    if($_GET['l']==$letter) $tpl->setVariable("SELECTED", "selected");
    $tpl->parse("let");
}
$DB->free($result);

$sql = "SELECT `id`, `login`, `email`, `active`, `super` FROM".$DB->T('_users')." WHERE(`login` LIKE '".addslashes($_GET['l'])."%') ORDER BY `login`";
$result = $DB->query($sql);
while(list($user_id, $user_login, $user_email, $user_active, $user_super) = $DB->fetch(false, true, $result))
{
    $user_class = $user_active ? "active" : "inactive";
    if($user_super) $user_class .= " super";
    $tpl->setCurrentBlock("row");
    $tpl->setVariable("USER_CLASS", $user_class);
    $tpl->setVariable("USER_ID", $user_id);
    $tpl->setVariable("USER_LOGIN", $user_login);
    $tpl->setVariable("USER_EMAIL", $user_email);
    $tpl->parse("row");
}
$DB->free($result);

$tpl->show();
?>