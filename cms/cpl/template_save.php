<?php

/**
 * Скрипт сохранения шаблона
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
require_once("$_ROOT/cms/classes/TemplateEx.php");

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

//Debugger::dump($_POST, true, __FILE__, __LINE__);

$template = new TemplateEx($_POST['template_id'], $_POST['template_type']);
if(!$template->getId()) Debugger::dump($_POST, false, __FILE__, __LINE__, "No ID in TemplateEx, \$_POST dumped.");

if(isset($_POST['del']) && !$template->isSpecial() ) {
    // delete template
    if($template->deleteTemplate()) {
        cpl_redirect("templates.php?");
        exit ;
    } else {
        $E->addWarning("Не удалось удалить шаблон.", "Убедитесь, что он не используется ни на одной из страниц и его файлы доступны для записи.");
    }
}

if(!$template->isSpecial()) $template->setName($_POST['name']);

if($_FILES['userfile']['error'] == UPLOAD_ERR_OK && $_FILES['userfile']['type'] == "text/html")
{
    //if(!$template->isSpecial()) $template->setFileName($_FILES['userfile']['name']);
    if($template->uploadTemplate($_FILES['userfile']['tmp_name']))
    {
        if(!$template->getFile()) {
            if(!$template->setFileName($_FILES['userfile']['name'])) $E->addWarning("Файл с таким именем уже существует", "Имя файла шаблона не изменено");
        }
    }
    else
    {
        $E->addError("Не удалось обновить содержимое файла.", "Убедитесь, что вы загружаете не пустой файл, и папка с шаблонами и файлы доступны для записи.");
    }
}
else
{
    if(isset($_FILES['userfile']) && $_FILES['userfile']['error'] != UPLOAD_ERR_NO_FILE)
    {
        $E->addWarning("Не удалось загрузить файл шаблона на сервер, код '".$_FILES['userfile']['error']."'.", "Убедитесь, что размер загружаемого файла не превышает ".ini_get("upload_max_filesize").", файл является документом HTML и папка с шаблонами доступна для записи.");
    }
    
    //if(!$template->isSpecial()) $template->setFileName($_POST['filename']);
    /*
    if(!$template->setContents($_POST['template_file_data']))
    {
        $E->addError("Не удалось обновить содержимое файла.", "Убедитесь, что вы ввели содержимое файла в редакторе и файл шаблона доступен для записи.");
    } 
    */   
}

/*
if($_FILES['userfile2']['error'] == UPLOAD_ERR_OK && $_FILES['userfile2']['type'] == "text/css" && move_uploaded_file($_FILES['userfile2']['tmp_name'], $_ROOT."/cms/templates/".$_FILES['userfile2']['name']))
{
    $template->setCssFile($_FILES['userfile2']['name']);
}
else
{
    if(isset($_FILES['userfile2']) && $_FILES['userfile2']['error'] != UPLOAD_ERR_NO_FILE)
    {
        $E->addWarning("Не удалось загрузить файл CSS на сервер, код '".$_FILES['userfile']['error']."'.", "Убедитесь, что размер загружаемого файла не превышает ".ini_get("upload_max_filesize").", файл является стилевым листом CSS и папка с шаблонами доступна для записи.");
    }
    
    $template->setCssFile($_POST['css_file']);
}
*/

if(!$template->update())
{
    $E->addError("Не удалось обновить шаблон.", "Более подробную информацию о произошедших ошибках можно увидеть включив режим отладки.");
}

cpl_redirect("templates.php?template_id=".($template->isSpecial() ? "special:":"").$template->getId());
?>