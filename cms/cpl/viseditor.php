<?php

/**
 * Визуальный редактор страницы
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
require_once("$_ROOT/cms/classes/Dialog.php");
require_once("$_ROOT/cms/classes/VisEditor.php");
//require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
//$E = new ErrorSession('struct');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$PAGE = new VisEditor($_GET['page_id']);
$PAGE->showEditor();
?>