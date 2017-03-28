<?php

/**
 * AJAX получение данных из матенриала
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
require_once("$_ROOT/cms/classes/Material.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
/*
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    die("Нет доступа!");
}
*/

if(isset($_GET['material_id']))
{
    $material = new Material($_GET['material_id']);
    if(isset($_GET['raw']) && $_GET['raw']) {
        echo $material->getData();    
    } else {
        echo $material->parse();
    }
    
}
else
{
    echo "Нет ID материала";
}
?>