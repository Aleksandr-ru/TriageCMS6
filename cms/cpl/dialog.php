<?php

/**
 * Универсальный вывод диалога
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009 
 * @todo сделать работу с кнопками диалога
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/Dialog.php");

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

$dialog = new Dialog($_GET['script'], $_GET['callback'], $_GET['title']);
if(isset($_GET['buttons']) && is_array($_GET['buttons'])) {
    foreach($_GET['buttons'] as $button_id => $button_name) {
        if($button_id && $button_name) $dialog->appendButton($button_id, $button_name);
        elseif($button_id && !$button_name) $dialog->removeButton($button_id);
    }
}
$dialog->show();

?>