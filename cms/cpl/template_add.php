<?php

/**
 * Скрипт создания нового шаблона
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/plugins.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('templates');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('templates_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

//TODO:сделать проверку REFERER что не добавляли почем зря

$new_id = $DB->new_id($DB->T('_templates'));
if(isset($_GET['file']) && $_GET['file'] && is_file("$_ROOT/cms/templates/".$_GET['file'])) {
    $sql = "INSERT INTO ".$DB->T('_templates')." (`name`, `file`) VALUES('Новый шаблон из файла', ".$DB->F($_GET['file']).");";
} else {
    $sql = "INSERT INTO ".$DB->T('_templates')." (`name`) VALUES('Новый шаблон');";    
}

$DB->query($sql);
if($DB->errno())
{
    $E->addError("Не удалось добавить шаблон!", $DB->error());
}
else
{
    $tmpl_id = $DB->insert_id();
    cmsLogObject("Добавлен шаблон", 'tmpl', $tmpl_id);
}

if(!is_writable("$_ROOT/cms/templates/"))
{
    $E->addWarning("Папка шаблонов не доступна для записи", "Вы не сможете загрузить/сохранить файл шаблона и файл CSS. Установите права (chmod) на папку '$_ROOT/cms/templates/'.");   
}

cpl_redirect("templates.php?template_id=".@$tmpl_id);
?>