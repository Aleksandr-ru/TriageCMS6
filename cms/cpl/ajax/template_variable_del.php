<?php

/**
 * AJAX скрипт удаления переменной
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

if(isset($_GET['template_id']) && isset($_GET['variable']) && isset($_GET['material_id']))
{
    $sql = "DELETE FROM ".$DB->T('_variables')." WHERE(`name` LIKE ".$DB->F($_GET['variable'])." AND `page_id`=0)";
    $DB->query($sql);
    if($DB->errno())
    {
        //$E->addWarning("Не удалось удалить локальное значение для переменной ".$_GET['variable'], $DB->error());
        die("Не удалось удалить глобальное значение для переменной ".$_GET['variable']);
    }
    else
    {
        cmsLogObject("Удалено глобальное значение для '".$_GET['variable']."'", "tmpl", $_GET['template_id']);
        //$E->addNotice("Удалено локальное значение для переменной ".$_GET['variable'], "Теперь на этой странице будет использоваться глобальное значение переменной.");
        die("OK");
    }
}
//cpl_redirect("struct.php?page_id=".$_GET['page_id']."#variables");
die("Недостаточно данных или недопустммый запрос!");
?>