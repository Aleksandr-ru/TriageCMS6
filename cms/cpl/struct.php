<?php

/**
 * Управление структурой сайта
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/Dialog.php");
require_once("$_ROOT/cms/classes/Page.php");
require_once("$_ROOT/cms/classes/PageEx.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('struct');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("struct.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Структура сайта");

if(!isset($_GET['page_id']))
{
    $_GET['page_id'] = $DB->getField("SELECT p.id FROM ".$DB->T('_pages')." AS p ORDER BY p.is_home DESC LIMIT 1;");
}

// ошибка - нет домашней страницы
if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." AS p WHERE p.is_home;") != 1)
{
    $E->addWarning("На сайте не выбрана главная страница!", "Выберите любую страницу верхнего уровня и установите переключатель напротив 'Главная страница'.");
}
// ошибка - нет шаблонов
if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('_templates')." AS t;") < 1)
{
    $E->addError("На сайте нет ни одного шаблона!", "Перейдите в раздел '<a href=\"templates.php\">Шаблоны</a>' и добавьте шаблоны страниц.");
}
// всякие прочие ошибки из сессии
$E->showError($tpl);
$E->showWarning($tpl);
$E->showNotice($tpl);

$struct_tree = new Dialog("struct");
$tpl->setVariable("STRUCT_TREE", $struct_tree->getContents());

$page = new PageEx($_GET['page_id']);

$tpl->setVariable("PAGE_PATH", Page::path($page->getId()));
$tpl->setVariable("PAGE_FULLPATH", Page::fullpath($page->getId()));

$tpl->setVariable("PAGE_ID", $page->getId());
$tpl->setVariable("PAGE_ICON", $page->numChildren() ? "folder_page" : "page");
$tpl->setVariable("PAGE_NAME", $page->getName());
$tpl->setVariable("PAGE_TITLE", $page->getTitle());
$tpl->setVariable("PARENT_ID", $page->getParentId());
$tpl->setVariable("PARENT_NAME", $page->getParentName());
if($page->getParentId())
{
    $tpl->setVariable("ROOTPAGE0", "checked");
    $tpl->setVariable("HOME_DIS", "disabled");
}
else
{
    $tpl->setVariable("ROOTPAGE1", "checked");
}
if($page->isHome())
{
    $tpl->setVariable("HOME_CHK", "checked");
}

$tpl->setVariable("KEY", $page->getKey());
if($page->isActive())
{
    $tpl->setVariable("ACTIVE_CHK", "checked");
    $tpl->setVariable("ORDER", $page->getOrder());
}
else
{
    $tpl->setVariable("ORDER_DIS", "disabled");   
    $tpl->setVariable("ORDER", $DB->getField("SELECT MAX(`order`)+1 FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($page->getParentId())));
}
$tpl->setVariable("TEMPLATE_ID", $page->getTemplateId());
$tpl->setVariable("TEMPLATE_OPTIONS", $page->getTemplateOptions());
$tpl->setVariable("ACCESS_GROUP_OPTIONS", $page->getAccessGroupOptions());
$tpl->setVariable("REDIRECT", $page->getRedirect());
$tpl->setVariable("KEYWORDS", $page->getKeywords());
$tpl->setVariable("DESCRIPTION", $page->getDescription());

/* moved to ajax
$material_blocks = $page->getMaterialBlocks();
while(list($block_num, $block_name) = each($material_blocks))
{
    $tpl->setCurrentBlock("materials_block");
    $tpl->setVariable("MATERIALS_BLOCK_NAME", $block_name);
    
    $sql = "SELECT m.id, m.type, m.name, pm.order, m.active FROM ".$DB->T('_page_materials')." AS pm LEFT JOIN ".$DB->T('_material')." AS m ON pm.material_id = m.id WHERE(pm.page_id=".$DB->F($page->getId())." AND pm.place_number=".$DB->F($block_num).") ORDER BY pm.order";
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
*/

/* moved to ajax
foreach($page->getVariableNames() as $variable_name)
{
    $tpl->setCurrentBlock("variable_row");
    $tpl->setVariable("VARIABLE_NAME", $variable_name);
    
    $sql = "SELECT m.id, m.name, m.type, m.active, v.page_id FROM ".$DB->T('_variables')." AS v LEFT JOIN ".$DB->T('_material')." AS m ON v.material_id=m.id WHERE(v.name=".$DB->F($variable_name)." AND (v.page_id=".$DB->F($page->getId())." OR v.page_id=0)) ORDER BY v.page_id";
    $result = $DB->query($sql);
    
    $no_local_var = true;
    while(list($material_id, $material_name, $material_type, $material_active, $v_page_id) = $DB->fetch(false, true, $result))
    {
        if(!$v_page_id) // global
        {
            $tpl->setVariable("VAR_MATERIAL_ID1", $material_id);
            $tpl->setVariable("VAR_MATERIAL_NAME1", $material_name);
            $tpl->setVariable("VARIABLE_CLASS1", $material_type.($material_active ? "":" inactive"));
            
            $tpl->setCurrentBlock("var_edit1");
            $tpl->setVariable("VAR_EDIT1_ID", $material_id);
            $tpl->parse("var_edit1");
        }
        else
        {
            $no_local_var = false;
            
            $tpl->setVariable("VAR_MATERIAL_ID2", $material_id);
            $tpl->setVariable("VAR_MATERIAL_NAME2", $material_name);
            $tpl->setVariable("VARIABLE_CLASS2", $material_type.($material_active ? "":" inactive"));
            
            $tpl->setCurrentBlock("var_edit2");
            $tpl->setVariable("VAR_EDIT2_ID", $material_id);
            $tpl->parse("var_edit2");
            
            $tpl->setCurrentBlock("var_del");
            $tpl->setVariable("VAR_DEL_ID", $material_id);
            $tpl->setVariable("VAR_DEL_VAR", $variable_name);
            $tpl->setVariable("VAR_DEL_PAGE", $page->getId());
            $tpl->parse("var_del");
        }
    }
    $DB->free($result);
    
    if($no_local_var)
    {
        $tpl->setCurrentBlock("var_add");        
        $tpl->setVariable("VAR_ADD_VAR", $variable_name);
        $tpl->parse("var_add");
    }
    
    $tpl->parse("variable_row");
}
*/

$tpl->show();
?>