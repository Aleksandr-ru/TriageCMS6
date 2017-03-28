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

if(isset($_POST['group_id']) && @$_POST['group_name'] && @$_POST['group_old_name'])
{
    $sql = "UPDATE ".$DB->T('_material_groups')." SET `name`=".$DB->F($_POST['group_name'])." WHERE `id`=".$DB->F($_POST['group_id']);
    $DB->query($sql);
    if($DB->errno()) echo "Ошибка БД: ".$DB->error();
    else cmsLogObject("Группа материалов '".$_POST['group_old_name']."' (id: ".$_POST['group_id'].") переименована в '".$_POST['group_name']."'", "matgrp", $_POST['group_id']);
}
else
{
    echo "Нет ID группы";
}
?>