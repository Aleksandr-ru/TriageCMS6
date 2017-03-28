<?php

/**
 * Редирект в панель управления 
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2009
 */
 
define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/config.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

cpl_redirect('');

?>