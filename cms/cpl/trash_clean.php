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
$E = new ErrorSession('trashcan');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
/*elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}*/

//TODO:сделать проверку REFERER что не добавляли почем зря

if(sizeof($_POST)) {
        
    $sql = "TRUNCATE TABLE ".$DB->T('_trashcan');
    $DB->query($sql);
    if(!$DB->errno()) {
        cmsLog("Произведена очистка корзины", 'trash');    
    }
}

cpl_redirect("trashcan.php");
?>