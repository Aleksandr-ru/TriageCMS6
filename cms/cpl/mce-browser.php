<?php

/**
 * Броузер-диалог для TinyMCE 
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
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/Dialog.php");

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!getSetting('use_tinymce'))
{
    die('TinyMCE is disabled!');
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("mce-browser.dlg.html", true, true);

$tpl->setVariable("INI_MAX_FILEZIE", ini_get('upload_max_filesize'));
$tpl->setVariable("INI_POST_MAXSIZE", ini_get('post_max_size'));
$tpl->setVariable("BASE", $_BASE);
$tpl->setVariable("OLD_VALUE", htmlspecialchars($_GET['url']));
$tpl->setVariable("TYPE", htmlspecialchars($_GET['type']));

if($_GET['type'] == 'file') {
    $struct = new Dialog("struct");
    $_GET['page'] = $_GET['url'];
    $tpl->setVariable("STRUCT", $struct->getContents());    
    $tpl->setVariable("HIDE_STRUCT", 0);
    $tpl->setVariable("TITLE", "Обзор сайта");
} else {
    $tpl->setVariable("HIDE_STRUCT", 1);
    $tpl->setVariable("TITLE", "Обзор файлов");
}

$_GET['file'] = $_GET['url'];
$files = new Dialog("files");
$tpl->setVariable("FILES", $files->getContents());

$tpl->show();
?>