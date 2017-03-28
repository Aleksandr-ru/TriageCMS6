<?php

/**
 * AJAX обновление группы доступа
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/xml; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("<reply><code>1</code><data>Нет доступа!</data></reply>");
}
elseif(!$USER->checkGroup(getSetting('user_edit_group')))
{
   die("<reply><code>2</code><data>Нет доступа!</data></reply>");
}

if(isset($_GET['group_id']) && $_GET['group_id'] && isset($_POST['new_name']) && $_POST['new_name'])
{
    $DB->query("UPDATE ".$DB->T('_groups')." SET `name`=".$DB->F($_POST['new_name'])." WHERE `id`=".$DB->F($_GET['group_id']));
    $code = $DB->errno();
    $message = $DB->errno() ? $DB->error() : $_POST['new_name'];
}
else
{
    $code = 3;
    $message = "Недостаточно данных ('".$_GET['group_id']."', '".$_POST['new_name']."')";
}

echo "<reply><code>$code</code><data>$message</data></reply>";
?>