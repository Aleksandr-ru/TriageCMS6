<?php

/**
 * Справка 
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

require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("help.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Справочное руководство по системе Triage CMS");

$tpl->show();
?>