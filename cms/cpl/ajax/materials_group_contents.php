<?php

/**
 * AJAX загрузчик содержимого группы материалов
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2013
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

if($group_id = @$_POST['group_id']) {
    //$sql = "SELECT m.id, m.type, m.name, m.active, (SELECT COUNT(*) FROM ".$DB->T('_files')." AS f WHERE(f.mat_id=m.id)) FROM ".$DB->T('_material')." AS m WHERE(m.group_id = ".$DB->F($group_id).") ORDER BY m.active DESC, m.name";
    $sql = "SELECT m.id, CASE WHEN m.text IS NOT NULL THEN 'text' WHEN m.html IS NOT NULL THEN 'html' WHEN m.css IS NOT NULL THEN 'css' WHEN m.javascript IS NOT NULL THEN 'javascript' WHEN m.plugin IS NOT NULL THEN 'plugin' END AS type, m.name, m.active, (SELECT COUNT(*) FROM ".$DB->T('_files')." AS f WHERE(f.mat_id=m.id)) FROM ".$DB->T('_material')." AS m WHERE(m.group_id = ".$DB->F($group_id).") ORDER BY m.active DESC, m.name";
} else {
    //$sql = "SELECT m.id, m.type, m.name, m.active, (SELECT COUNT(*) FROM ".$DB->T('_files')." AS f WHERE(f.mat_id=m.id)) FROM ".$DB->T('_material')." AS m WHERE(m.group_id NOT IN (SELECT g.id FROM ".$DB->T('_material_groups')." AS g)) ORDER BY m.active DESC, m.name";
    $sql = "SELECT m.id, CASE WHEN m.text IS NOT NULL THEN 'text' WHEN m.html IS NOT NULL THEN 'html' WHEN m.css IS NOT NULL THEN 'css' WHEN m.javascript IS NOT NULL THEN 'javascript' WHEN m.plugin IS NOT NULL THEN 'plugin' END AS type, m.name, m.active, (SELECT COUNT(*) FROM ".$DB->T('_files')." AS f WHERE(f.mat_id=m.id)) FROM ".$DB->T('_material')." AS m WHERE(m.group_id NOT IN (SELECT g.id FROM ".$DB->T('_material_groups')." AS g)) ORDER BY m.active DESC, m.name";
}

echo "<ul>";
$result = $DB->query($sql);
if($DB->num_rows($result))
{
    while(list($material_id, $material_type, $material_name, $material_active, $files) = $DB->fetch(false, true, $result))
    {
        $act = $material_active ? "Вкл." : "Выкл.";
        $class = "mat $material_type";
        if(!$material_active) $class .= " inactive";
        if($files) $class .= " files";
        echo "<li id=\"mat-$material_id\" class=\"$class\"><a title=\"$act, Файлов: $files\" href=\"material.php?material_id=$material_id\" material_id=\"$material_id\" material_type=\"$material_type\" material_active=\"$material_active\" material_files=\"$files\">$material_name</a></li>";
    }
}
else
{
    echo "<li class=\"mat\"><em>В этой группе нет материалов</em></li>";
}
$DB->free($result);
echo "</ul>";
?>