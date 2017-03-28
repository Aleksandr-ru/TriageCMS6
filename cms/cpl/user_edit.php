<?php

/**
 * Редктирование пользователя
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
$tpl->loadTemplatefile("user_edit.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Редактирование пользователя");

if(isset($_GET['user_id']) && $_GET['user_id'])
{
    $user = new User($_GET['user_id']); 
    
    $tpl->setVariable("USER_ID_TEXT", $user->getId());
    $tpl->setVariable("USER_ID", $user->getId());
    
    if(!$USER->isSuper() && $user->isSuper())
    {
        $E->addWarning("Вы не можете редактировать супер-пользователя", "Необходимо обладать правами супер-пользователя для того, чтобы редактировать этого пользователя."); 
    }
    if($USER->getId() == $user->getId())
    {
        $E->addNotice("Ограничения при редактировании собственной учетной записи", "Вы не можете отключить собственную учетную запись и изменить флажек 'супер-пользователь' на вкладке 'Группы доступа'.");
        $tpl->setVariable("USER_ACTIVE_DIS", "disabled");
        $tpl->setVariable("SUPER_DIS", "disabled");
    }
    
    $tpl->setVariable("USER_LOGIN", $user->getLogin());
    $tpl->setVariable("USER_EMAIL", $user->getEmail());
    if($user->isActive()) $tpl->setVariable("USER_ACTIVE_CHK", "checked");
    if($user->isSuper()) $tpl->setVariable("SUPER_CHK", "checked");
    $user_groups = $user->getGroups();
    
    if(getSetting('gravatar') != '404') {
        $tpl->setVariable("GRAVATAR", $user->getGravatar(true));    
    } else {
        $tpl->setVariable("GRAVATAR", "Отключено");    
    }    
}
else
{
    $tpl->setVariable("USER_ID_TEXT", "новый");
    $tpl->setVariable("USER_ID", "0");
    $tpl->setVariable("GRAVATAR", "Недоступно");
    if(!$USER->isSuper()) $tpl->setVariable("SUPER_DIS", "disabled");
    $user_groups = array();
}

$E->showAll($tpl);

/* show groups */

$all_groups = $DB->getCol2("SELECT * FROM ".$DB->T('_groups')." ORDER BY `name`");
foreach($all_groups as $group_id => $group_name)
{
    $tpl->setCurrentBlock("group");
    $tpl->setVariable("GROUP_ID", $group_id);
    $tpl->setVariable("GROUP_NAME", $group_name);
    $tpl->setVariable("GROUP_CHK", in_array($group_id, $user_groups) ? "checked" : "");
    $tpl->parse("group");
}

$tpl->show();
?>