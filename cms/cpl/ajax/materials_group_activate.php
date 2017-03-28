<?php

/**
 * AJAX активатор группы материалов
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
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    die("Нет доступа!");
}

if(isset($_POST['group_id']))
{
    list($group_name, $group_hidden) = $DB->getRow("SELECT `name`, `hidden` FROM ".$DB->T('_material_groups')." WHERE `id`=".$DB->F($_POST['group_id']));
    if(!$group_name) die("Нет шруппы с таким ID");
    
    $new_hidden = $group_hidden ? 0 : 1;
    $sql = "UPDATE ".$DB->T('_material_groups')." SET `hidden`=".$DB->F($new_hidden)." WHERE `id`=".$DB->F($_POST['group_id']);
    $DB->query($sql);
    if($DB->errno()) echo "Ошибка БД: ".$DB->error();
    else cmsLogObject(($new_hidden ? "Скрыта":"Открыта")." группа материалов '$group_name' (id: ".$_POST['group_id'].")", "matgrp", $_POST['group_id']);
}
else
{
    echo "Нет ID группы";
}
?>