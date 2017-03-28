<?php

/**
 * Login screen для CMS 
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
require_once("$_ROOT/cms/classes/ITM.php");

$USER = new UserSession();

if($USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("index.php");
    exit ;
}
elseif($USER->getId() && !$USER->getError())
{
    $USER->setError(2, "Не достаточно прав доступа!");
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("login.html", true, true);

$tpl->setVariable("LOGIN", $USER->getLogin() ? $USER->getLogin() : htmlspecialchars(@$_POST['user_login']));
$tpl->setVariable("ERROR", $USER->getError() ? "<strong>Ошибка!</strong> ".$USER->getError() : "");

$tpl->show();
?>