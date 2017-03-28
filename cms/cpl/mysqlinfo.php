<?php

/**
 * MySQL info 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

header("Content-type: text/html; charset=utf-8");

echo "<html><body>\r\n";
echo "<h1>MySQL variables</h1>\r\n";
echo "<table border=1>\r\n";

$DB->query("SHOW VARIABLES");
while(list($var_name, $var_value) = $DB->fetch()) {
    echo "<tr><td>$var_name</td><td>$var_value</td></tr>\r\n";
}
$DB->free();

echo "</table>\r\n";
echo "</body></html>\r\n";
?>