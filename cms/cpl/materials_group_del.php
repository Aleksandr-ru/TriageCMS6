<?php

/**
 * Скрипт создания новой страницы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('struct');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

//TODO:сделать проверку REFERER что не удваляли почем зря

if($group_id = $_GET['group_id'])
{
    $a = $DB->getRow("SELECT * FROM ".$DB->T('_material_groups')." WHERE `id`=".$DB->F($group_id), true, false);
    $group_name = $a['name'];
        
    $sql = "UPDATE ".$DB->T('_material')." SET `group_id`=0 WHERE `group_id=".$DB->F($group_id);
    $DB->query($sql);
    
    $sql = "DELETE FROM ".$DB->T('_material_groups')." WHERE `id`=".$DB->F($group_id);
    $DB->query($sql);
    if(!$DB->errno()){
        cmsLogObject("Удалена группа материалов '$group_name' (id: $group_id)", 'matgrp', $group_id);    
        cpl_trash('Группа материалов', $group_name, '_material_groups', $a);
    }
}
cpl_redirect("materials.php");
?>