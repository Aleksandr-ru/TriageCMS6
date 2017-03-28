<?php

/**
 * AJAX активатор юзеров
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

if(@$_POST['group_name'])
{
    $group_id = $DB->new_id($DB->T('_material_groups'));
    $sql = "INSERT INTO ".$DB->T('_material_groups')." (`id`, `name`) VALUES(".$DB->F($group_id).", ".$DB->F($_POST['group_name']).")";
    $DB->query($sql);
    if($DB->errno()) echo "Ошибка БД: ".$DB->error();
    else cmsLogObject("Добавлена группа материалов '".$_POST['group_name']."' (id: ".$group_id.")", "matgrp", $group_id);
}
else
{
    echo "Нет названия группы";
}
?>