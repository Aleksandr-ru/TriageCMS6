<?php

/**
 * AJAX скрипт добавления материала к странице
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


if(isset($_POST['page_id']) && isset($_POST['block']) && preg_match("/^materials([0-9]+)$/", $_POST['block'], $arr) && isset($_POST['material_id']))
{
    $order = 1 + $DB->getField("SELECT MAX(`order`) FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($_POST['page_id'])." AND `place_number`=".$DB->F($arr[1]));
    $sql = "INSERT INTO ".$DB->T('_page_materials')." (`page_id`, `material_id`, `place_number`, `order`) VALUES (".$DB->F($_POST['page_id']).", ".$DB->F($_POST['material_id']).", ".$DB->F($arr[1]).", ".$DB->F($order).")";    
    $DB->query($sql);
    if($DB->errno() == 1062)
    {
        die("Этот материал уже есть в '".$_POST['block']."'! Не допускается установка одного материала несколько раз в одном блоке, выберите другой материал или блок.");
    }
    elseif(!$DB->errno())
    {
        $material_name = getMaterialName($_POST['material_id']);
        $page_name = getPageName($_POST['page_id']);
        cmsLogObject("Материал '$material_name' (id: ".$_POST['material_id'].") добавлен в '".$_POST['block']."' на странице '$page_name'", "page", $_POST['page_id']);
        echo "OK"; // all OK
    }
}
else
{
    die("Недостаточно данных или недопустммый запрос!");
}
?>