<?php

/**
 * AJAX поиск материалов
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

header('Content-type: text/xml; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}

$groups = array();
$materials = array();

if($q = @$_POST['q'])
{
    $sql = "SELECT m.id, m.group_id FROM ".$DB->T('_material')." AS m LEFT JOIN ".$DB->T('_material_groups')." AS g ON m.group_id=g.id WHERE(m.name LIKE '%".addslashes($q)."%') ORDER BY g.hidden, g.name";
    $DB->query($sql);
    while(list($material_id, $group_id) = $DB->fetch()) {
        //echo "$material_id;$group_id\r\n";
        $groups[] = $group_id;
        $materials[] = $material_id;
    }
}
echo "<?xml version='1.0'?>";
?>
<response>
    <groups><?php echo implode(',', array_unique($groups)); ?></groups>
    <materials><?php echo implode(',', array_unique($materials)); ?></materials>
</response>
