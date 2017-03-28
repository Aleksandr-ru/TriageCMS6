<?php

/**
 * AJAX активатор юзеров 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('user_edit_group')))
{
    die("Нет доступа!");
}

if(isset($_POST['user_id']) && isset($_POST['current_act']))
{
    if($USER->getId() == $_POST['user_id']) die("Нельзя деактивировать собственную учетную запсиь!");
    $user_login = $DB->getField("SELECT `login` FROM ".$DB->T('_users')." WHERE `id`=".$DB->F($_POST['user_id']));
    
    $new_active = $_POST['current_act'] ? 0 : 1;
    $sql = "UPDATE ".$DB->T('_users')." SET `active`=".$DB->F($new_active)." WHERE `id`=".$DB->F($_POST['user_id']);
    $DB->query($sql);
    if($DB->errno()) echo "Ошибка БД: ".$DB->error();
    else cmsLogObject(($new_active ? "Активирован":"Деактивирован")." пользователь '$user_login' (id: ".$_POST['user_id'].")", "user", $_POST['user_id']);
}
else
{
    echo "Нет ID пользователя";
}
?>