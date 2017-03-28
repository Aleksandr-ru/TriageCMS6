<?php

/**
 * AJAX убиралка страницы из списка раскрытых в структуре
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

//header('Content-type: text/html; charset=utf-8');

unset($_SESSION['triage_cpl']['struct_expand'][array_search($_POST['page_id'], $_SESSION['triage_cpl']['struct_expand'])]);
?>