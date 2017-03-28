<?php

/**
 * AJAX удаление группы доступа
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
require_once("$_ROOT/cms/lib/cpl.lib.php");

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

if(isset($_GET['group_id']) && $_GET['group_id'])
{
    $group_id = $_GET['group_id'];
    $cnt =  $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_user_groups')." WHERE `group_id`=".$DB->F($group_id)) + 
            $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `access_group`=".$DB->F($group_id)) +
            $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_material')." WHERE `access_group`=".$DB->F($group_id)) +
            $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_plugins')." WHERE `access_group`=".$DB->F($group_id)) +
            $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_settings')." WHERE `value`=".$DB->F($group_id)." AND `sprav` LIKE '".str_replace("`","",$DB->T('_groups')).".id%'");
    if($cnt)
    {
        $code = 4;
        $message = "Эта группа используется, нельзя удалить!";
    }
    else
    {
        $a = $DB->getRow("SELECT * FROM ".$DB->T('_groups')." WHERE `id`=".$DB->F($_GET['group_id']), true, false);
        
        $DB->query("DELETE FROM ".$DB->T('_groups')." WHERE `id`=".$DB->F($_GET['group_id']));
        $code = $DB->errno();
        $message = $DB->errno() ? $DB->error() : $_POST['new_name'];   
        
        if(!$DB->errno()) {
            cpl_trash('Группа доступа', $a['name'], '_groups', $a);
            cmsLogObject("Удалена группа доступа '".$a['name']."' (id: ".$a['id'].")", 'usergroup', $a['id']);
        }
    }
}
else
{
    $code = 3;
    $message = "Недостаточно данных ('".$_GET['group_id']."', '".$_POST['new_name']."')";
}

echo "<reply><code>$code</code><data>$message</data></reply>";
?>