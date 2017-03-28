<?php

/**
 * AJAX обновление матенриала
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
require_once("$_ROOT/cms/classes/MaterialEx.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    die("Нет доступа!");
}

if(isset($_GET['material_id']) && isset($_POST['data']))
{
    $material = new MaterialEx($_GET['material_id']);
    $material->setData($_POST['data']);
    if($material->update()) {
        cmsLogObject("Отредактирован материал '{$material->getName()}'", "material", $_GET['material_id']);  
        die("OK");
    } else {
        die("Ошибка сохранения!");
    }
}
else
{
    echo "Нет ID материала";
}
?>