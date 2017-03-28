<?php

/**
 * AJAX скрипт удаления переменной для страницы
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
elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    die("Нет доступа!");
}

if(isset($_GET['page_id']) && isset($_GET['variable']) && isset($_GET['material_id']))
{
    $sql = "DELETE FROM ".$DB->T('_variables')." WHERE(`name` LIKE ".$DB->F($_GET['variable'])." AND `page_id`=".$DB->F($_GET['page_id']).")";
    $DB->query($sql);
    if($DB->errno())
    {
        //$E->addWarning("Не удалось удалить локальное значение для переменной ".$_GET['variable'], $DB->error());
        die("Не удалось удалить локальное значение для переменной ".$_GET['variable']);
    }
    else
    {
        cmsLogObject("Удалено локальное значение для '".$_GET['variable']."' на странице '".getPageName($_GET['page_id'])."' (id: ".$_GET['page_id'].")", "page", $_GET['page_id']);
        //$E->addNotice("Удалено локальное значение для переменной ".$_GET['variable'], "Теперь на этой странице будет использоваться глобальное значение переменной.");
        die("OK");
    }
}
//cpl_redirect("struct.php?page_id=".$_GET['page_id']."#variables");
die("Недостаточно данных или недопустммый запрос!");
?>