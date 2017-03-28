<?php

/**
 * Запускатель CMS 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

if(isset($_GET['debug']) && $_GET['debug']) {
    echo "<pre>REQUEST_URI:\t".$_SERVER['REQUEST_URI']."\r\nQUERY_STRING:\t".$_SERVER['QUERY_STRING']."</pre>\r\n";
    echo "<pre>\$_GET: ".print_r($_GET, 1)."</pre>\r\n";
}
 
require_once(dirname(__FILE__)."/cms/core.php");
 
?>