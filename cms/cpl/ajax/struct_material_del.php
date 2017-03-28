<?php

/**
 * AJAX скрипт удаления материала со страницы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);
session_start();

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/classes/User.php");

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


if(isset($_GET['page_id']) && isset($_GET['block']) && preg_match("/^materials([0-9]+)$/", $_GET['block'], $arr) && isset($_GET['material_id']) && isset($_GET['num']))
{
    $sql = "DELETE FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($_GET['page_id'])." AND `material_id`=".$DB->F($_GET['material_id'])." AND `place_number`=".$DB->F($arr[1])." AND `order`=".$DB->F($_GET['num']);
    $DB->query($sql);
    if(!$DB->errno())
    {
        $material_name = getMaterialName($_GET['material_id']);
        $page_name = getPageName($_GET['page_id']);
        cmsLogObject("Материал '$material_name' (id: ".$_GET['material_id'].") удален из '".$_GET['block']."' со страницы '$page_name'", "page", $_GET['page_id']);
    }
    if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($_GET['page_id'])." AND `place_number`=".$DB->F($arr[1])))
    {
        $err = "";
        //TODO:изменить алгоритм автоматической сортировки для совместимости с другими типами БД
        $sql = "CREATE TEMPORARY TABLE `temp_material_order` (`order` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT , `mat_id` INT( 10 ) UNSIGNED NOT NULL , PRIMARY KEY ( `order` ) )";// TYPE = HEAP";
        $DB->query($sql);
        //if($DB->errno()) $E->addWarning("Ошибка автоматической пересортировки материалов (1)", $DB->error());
        if($DB->errno()) $err .= "Ошибка автоматической пересортировки материалов (1): ".$DB->error()."\r\n";
        
        $sql = "INSERT INTO `temp_material_order` (`mat_id`) SELECT `material_id` FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($_GET['page_id'])." AND `place_number`=".$DB->F($arr[1])." ORDER BY `order`";
        $DB->query($sql);
        //if($DB->errno()) $E->addWarning("Ошибка автоматической пересортировки материалов (2)", $DB->error());
        if($DB->errno()) $err .= "Ошибка автоматической пересортировки материалов (2): ".$DB->error()."\r\n";
        
        $sql = "UPDATE ".$DB->T('_page_materials')." AS m1 SET m1.order=( SELECT m2.order FROM `temp_material_order` AS m2 WHERE m2.mat_id=m1.material_id ) WHERE m1.page_id=".$DB->F($_GET['page_id'])." AND m1.place_number=".$DB->F($arr[1]);
        $DB->query($sql);
        //if($DB->errno()) $E->addWarning("Ошибка автоматической пересортировки материалов (3)", $DB->error());
        if($DB->errno()) $err .= "Ошибка автоматической пересортировки материалов (3): ".$DB->error()."\r\n";
        
        $sql = "DROP TABLE `temp_material_order`";
        $DB->query($sql);
    }
    else
    {
        //$E->addNotice("В блоке '".$_GET['block']."' больше нет материалов", "На странице этот блок будет пустой :)");
    }
    die($err ? $err : "OK");
}
die("Недостаточно данных или недопустммый запрос!");
?>