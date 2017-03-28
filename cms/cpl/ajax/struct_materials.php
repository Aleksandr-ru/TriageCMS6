<?php

/**
 * AJAX загрузчик материалов страницы
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);
session_start();

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

require_once("$_ROOT/cms/classes/PageEx.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    die("Нет доступа!");
}

$page_id = isset($_POST['page_id']) ? $_POST['page_id'] : ( isset($_GET['page_id'])? $_GET['page_id'] : 0);
$page = new PageEx($page_id);

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("ajax.struct_materials.html", true, true);

$material_blocks = $page->getMaterialBlocks();
while(list($block_num, $block_name) = each($material_blocks))
{
    $tpl->setCurrentBlock("materials_block");
    $tpl->setVariable("MATERIALS_BLOCK_NAME", $block_name);
    
    //$sql = "SELECT m.id, m.type, m.name, pm.order, m.active FROM ".$DB->T('_page_materials')." AS pm LEFT JOIN ".$DB->T('_material')." AS m ON pm.material_id = m.id WHERE(pm.page_id=".$DB->F($page->getId())." AND pm.place_number=".$DB->F($block_num).") ORDER BY pm.order";
    $sql = "SELECT m.id, CASE WHEN m.text IS NOT NULL THEN 'text' WHEN m.html IS NOT NULL THEN 'html' WHEN m.css IS NOT NULL THEN 'css' WHEN m.javascript IS NOT NULL THEN 'javascript' WHEN m.plugin IS NOT NULL THEN 'plugin' END AS type, m.name, pm.order, m.active FROM ".$DB->T('_page_materials')." AS pm LEFT JOIN ".$DB->T('_material')." AS m ON pm.material_id = m.id WHERE(pm.page_id=".$DB->F($page->getId())." AND pm.place_number=".$DB->F($block_num).") ORDER BY pm.order";
    $result = $DB->query($sql);
    while(list($material_id, $material_type, $material_name, $material_order, $material_active) = $DB->fetch(false, true, $result))
    {
        $tpl->setCurrentBlock("material");
        $tpl->setVariable("MATERIAL_BLOCK", $block_name);
        $tpl->setVariable("MATERIAL_NUM", $material_order);
        $tpl->setVariable("MATERIAL_ID", $material_id);
        $tpl->setVariable("MATERIAL_NAME", $material_name);
        $tpl->setVariable("MATERIAL_CLASS", $material_type.($material_active ? "":" inactive"));
        $tpl->setVariable("MATERIAL_PAGE_ID", $page->getId());
        $tpl->parse("material");
    }
    $DB->free($result);
    
    $tpl->parse("materials_block");
}

$tpl->show();
?>