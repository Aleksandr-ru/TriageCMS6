<?php

/**
 * AJAX загрузчик содержимого папки
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}

$path = trim($_POST['path'], '/');
if(!$path) exit;

echo "<ul>";

$scan = scandir($_ROOT.'/'.$path);
$dirs = array();
$files = array();
foreach($scan as $s)
{
    if($s != "." && $s != ".." && is_dir($_ROOT."/$path/$s")) $dirs[] = $s;
    elseif( is_file($_ROOT."/$path/$s") ) $files[] = $s;
}
foreach($dirs as $s) {
    $class = '';
    $fullpath = trim("$path/$s", '/');
    echo "<li class='folder $class' fullpath='$fullpath'><a href='#'>$s</a><div class='subfolder'></div></li>\r\n";    
}
foreach($files as $s) {
    $class = pathinfo($_ROOT."/$path/$s", PATHINFO_EXTENSION);
    $fullpath = trim("$path/$s", '/');
    echo "<li class='file $class' fullpath='$fullpath'><a href='#' class='filename'>$s</a></li>\r\n";    
}

echo "</ul>";
?>