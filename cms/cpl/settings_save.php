<?php

/**
 * Скрипт сохранения настроек 
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
$E = new ErrorSession('settings');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('settings_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$old_settings = array();
$sql = "SELECT `name`, `value`, `desc` FROM ".$DB->T('_settings');
$result = $DB->query($sql);
while(list($name, $value) = $DB->fetch(false, false, $result))
{
    $old_settings[$name] = $value;
}
$DB->free($result);

while(list($name, $value) = each($_POST['setting']))
{
    if($old_settings[$name] != $value)
    {
        $sql = "UPDATE ".$DB->T('_settings')." SET `value`=".$DB->F($value)." WHERE `name`=".$DB->F($name);
        $DB->query($sql);
        if($DB->errno()) $E->addWarning("Не удалось сохранить значение для '$name'", $DB->error());
        else cmsLog("Изменены настройки системы: '$name' = $value");
    }
}
cpl_redirect("settings.php");
?>