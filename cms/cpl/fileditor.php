<?php

/**
 * Редактор файлов
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
$E = new ErrorSession('fileditor');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif((strpos($_GET['file'], "cms/templates/", 0)===0) && !$USER->checkGroup(getSetting('templates_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('filemanager_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("fileditor.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Редактор файла");
$tpl->setVariable("USE_CODEMIRROR", intval(getSetting("use_codemirror")));

$_GET['file'] = preg_replace("@/\.+/@", "/", $_GET['file']);
$_GET['file'] = preg_replace("@/+@", "/", $_GET['file']);

if(!is_file($_ROOT."/".$_GET['file'])) {
    $E->addError("Нет такого файла", "Файл '".htmlspecialchars($_GET['file'])."' не найден на сервере!");    
}
if(!is_writable($_ROOT."/".$_GET['file'])) {
    $E->addWarning("Файл не доступен для записи", "Файл '".htmlspecialchars($_GET['file'])."' невозможно будет сохранить!");    
}

$tpl->setVariable("FILENAME", htmlspecialchars($_GET['file']));
$file_type = strtolower( pathinfo($_GET['file'], PATHINFO_EXTENSION) );
if($file_type == 'htm') $file_type = 'html';
$tpl->setVariable("FILE_TYPE", $file_type);
//$tpl->setVariable("FILE_DATA", htmlescapetmpl(file_get_contents($_ROOT."/".$_GET['file']), true));
$tpl->setVariable("FILE_DATA", htmlspecialchars(file_get_contents($_ROOT."/".$_GET['file'])));
$tpl->setVariable("BACK", getenv("HTTP_REFERER"));

if($file_type == "php") {
    $E->addNotice("Вы редактируете файл PHP", "Будте внимательны! Изменения в этом файле могут повлиять на работу всей системы! <font color=red><b>Не рекомндуется редактировать такие файлы.</b></font>");
}

$E->showAll($tpl);

$tpl->show();
?>