<?php

/**
 * Редактор файлов - сохранение
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

//Debugger::dump($_POST, 1);
if($_POST['file']) {
    if(function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
    else Debugger::mes(80, "mbstring extension seems to be absent. File will be written as ANSI.", __FILE__, __LINE__);
    
    if(file_put_contents($_ROOT."/".$_POST['file'], $_POST['file_data'])) {
        cmsLog("Отредактирован файл '".$_POST['file']."'");    
    }    
}
cpl_redirect($_POST['back']);
?>