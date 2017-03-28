<?php

/**
 * Добавление группы доступа
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

if(isset($_POST['group_name']) && $_POST['group_name'])
{
    $DB->query("INSERT INTO ".$DB->T('_groups')." (`name`) VALUES(".$DB->F($_POST['group_name']).")");
    if($DB->errno())
    {
        $E->addError("Ошибка добавления группы: ".$DB->errno(), $DB->error());
    } else {
        $group_id = $DB->insert_id();
        cmsLogObject("Добавлена группа доступа '".$_POST['group_name']."' (id: ".$group_id.")", 'usergroup', $group_id);
    }
}
cpl_redirect("groups.php");
?>