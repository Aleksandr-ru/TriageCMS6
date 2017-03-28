<?php

/**
 * AJAX загрузчик подстраниц
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
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

$_SESSION['triage_cpl']['struct_expand'][] = $_POST['parent_id'];
$_SESSION['triage_cpl']['struct_expand'] = array_unique($_SESSION['triage_cpl']['struct_expand']);

$sql = "SELECT p.id, p.name, p.key, p.is_home, (SELECT COUNT(*) FROM ".$DB->T('_pages')." AS pp WHERE(pp.parent_id=p.id)), (p.order = 0) AS ord FROM ".$DB->T('_pages')." AS p WHERE(p.parent_id = ".$DB->F($_POST['parent_id']).") ORDER BY ord, p.order";

echo "<ul>";
$result = $DB->query($sql);
while(list($page_id, $page_name, $page_key, $page_home, $page_children, $ord) = $DB->fetch(false, true, $result))
{
    $class = $page_children ? "folder" : "page";
    if($ord) $class .= "_gray";
    if($page_home) $class .= " home";
    
    echo "<li id=\"page-$page_id\" class=\"$class\" page_id=\"$page_id\" page_key=\"$page_key\"><a href=\"struct.php?page_id=$page_id\" page_id=\"$page_id\" page_key=\"$page_key\">$page_name</a><div class=\"subfolder\"></div></li>";
}
$DB->free($result);
echo "</ul>";
?>