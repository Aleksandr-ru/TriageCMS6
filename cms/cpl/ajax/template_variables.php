<?php

/**
 * AJAX загрузчик переменных шаблона
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

require_once("$_ROOT/cms/classes/TemplateEx.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('templates_edit_group')))
{
    die("Нет доступа!");
}

$template_id = isset($_POST['template_id']) ? $_POST['template_id'] : ( isset($_GET['template_id'])? $_GET['template_id'] : 0);
$template = new TemplateEx($template_id);

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("ajax.template_variables.html", true, true);

foreach($template->getVariableNames() as $variable_name)
{
    $tpl->setCurrentBlock("variable_row");
    $tpl->setVariable("VARIABLE_NAME", $variable_name);
    //$sql = "SELECT m.id, m.name, m.type, m.active FROM ".$DB->T('_variables')." AS v LEFT JOIN ".$DB->T('_material')." AS m ON v.material_id=m.id WHERE(v.name=".$DB->F($variable_name)." AND v.page_id=0)";
    $sql = "SELECT m.id, m.name, CASE WHEN m.text IS NOT NULL THEN 'text' WHEN m.html IS NOT NULL THEN 'html' WHEN m.css IS NOT NULL THEN 'css' WHEN m.javascript IS NOT NULL THEN 'javascript' WHEN m.plugin IS NOT NULL THEN 'plugin' END AS type, m.active FROM ".$DB->T('_variables')." AS v LEFT JOIN ".$DB->T('_material')." AS m ON v.material_id=m.id WHERE(v.name=".$DB->F($variable_name)." AND v.page_id=0)";
    $result = $DB->query($sql);
    
    list($material_id, $material_name, $material_type, $material_active) = $DB->fetch(false, true, $result);
    $DB->free($result);
    
    $tpl->setVariable("VAR_MATERIAL_NAME2", $material_name);
    $tpl->setVariable("VARIABLE_CLASS2", $material_type.($material_active ? "":" inactive"));
    
    if($material_id)
    {
        $tpl->setCurrentBlock("var_edit2");
        $tpl->setVariable("VAR_EDIT2_ID", $material_id);
        $tpl->parse("var_edit2");
        
        $tpl->setCurrentBlock("var_del");
        $tpl->setVariable("VAR_DEL_ID", $material_id);
        $tpl->setVariable("VAR_DEL_VAR", $variable_name);
        $tpl->setVariable("VAR_DEL_TMPL_ID", $template->getId());
        $tpl->parse("var_del");    
    }
    else
    {
        $tpl->setCurrentBlock("var_add");        
        $tpl->setVariable("VAR_ADD_VAR", $variable_name);
        $tpl->parse("var_add");
    }
    $tpl->parse("variable_row");
}


$tpl->show();
?>