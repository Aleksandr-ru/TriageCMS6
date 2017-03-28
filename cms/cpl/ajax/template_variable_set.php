<?php

/**
 * AJAX скрипт добавления переменной для шаблонов
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);
session_start();

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
elseif(!$USER->checkGroup(getSetting('templates_edit_group')))
{
    die("Нет доступа!");
}

if(isset($_POST['template_id']) && isset($_POST['variable']) && isset($_POST['material_id']))
{
    $sql = "INSERT INTO ".$DB->T('_variables')." (`name`, `page_id`, `material_id`) VALUES(".$DB->F($_POST['variable']).", 0, ".$DB->F($_POST['material_id']).") ON DUPLICATE KEY UPDATE `material_id` = ".$DB->F($_POST['material_id']);
    $DB->query($sql);
    if($DB->errno())
    {
        //$E->addWarning("Не удалось утсановить переменную ".$_POST['variable'], $DB->error());
        die("Не удалось утсановить переменную ".$_POST['variable']);
    }
    else
    {
        cmsLogObject("В глобальное значение для '".$_POST['variable']."' установлен материал '".getMaterialName($_POST['material_id'])."' (id: ".$_POST['material_id'].")", "tmpl", $_POST['template_id']);
        die("OK");
    }
}
//cpl_redirect("struct.php?page_id=".$_POST['page_id']."#variables");
die("Недостаточно данных или недопустммый запрос!");
?>