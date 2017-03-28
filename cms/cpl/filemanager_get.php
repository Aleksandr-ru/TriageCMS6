<?php

/**
 * Управление файлами - скачивание файла
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
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('filemanager');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('filemanager_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$_GET['file'] = str_replace("..", "", $_GET['file']);
$_GET['file'] = preg_replace("@/\.+/@", "/", $_GET['file']);
$_GET['file'] = preg_replace("@/+@", "/", $_GET['file']);

if(!is_file($file = $_ROOT."/".$_GET['file'])) {
    $E->addError("Нет такого файла", "Файл '".htmlspecialchars($_GET['file'])."' не найден на сервере!");    
    $path = explode("/", $_GET['file']);
    array_pop($path);
    cpl_redirect("filemanager.php?path=".implode("/", $path));
    exit;
}

$file_name = array_pop($arr = explode("/", $file));

header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Content-Description: File Transfer");
header("Content-type: application/octet-stream");
header("Content-Length: ".filesize($file));
header("Content-Disposition: attachment; filename=\"$file_name\"");

readfile($file);
?>