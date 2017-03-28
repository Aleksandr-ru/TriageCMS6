<?php

/**
 * AJAX пересортировщик материалов страницы
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
elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    die("Нет доступа!");
}

//print_r($_POST);
/*
    [current_id] => 1
    [current_order] => 1
    [other_id] => 3
    [other_order] => 2
*/
if(isset($_POST['current_id']) && isset($_POST['current_order']) && isset($_POST['other_id']) && isset($_POST['other_order']) && isset($_GET['page_id']) && isset($_GET['block']) && preg_match("/^materials([0-9]+)$/", $_GET['block'], $arr))
{
    $sql = "UPDATE ".$DB->T('_page_materials')." SET `order`=".$DB->F($_POST['other_order'])." WHERE `page_id`=".$DB->F($_GET['page_id'])." AND `material_id`=".$DB->F($_POST['current_id'])." AND `place_number`=".$DB->F($arr[1])." AND `order`=".$DB->F($_POST['current_order']);   
    $DB->query($sql);
    if($DB->errno()) echo "Ощибка БД (1): ".addslashes($DB->error())."\n";
    $sql = "UPDATE ".$DB->T('_page_materials')." SET `order`=".$DB->F($_POST['current_order'])." WHERE `page_id`=".$DB->F($_GET['page_id'])." AND `material_id`=".$DB->F($_POST['other_id'])." AND `place_number`=".$DB->F($arr[1])." AND `order`=".$DB->F($_POST['other_order']);   
    $DB->query($sql);
    if($DB->errno()) echo "Ощибка БД (2): ".addslashes($DB->error())."\n";
}
else echo "Оштбка! Недостаточно данных";
?>