<?php

/**
 * Управление шаблонами
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/plugins.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");
require_once("$_ROOT/cms/classes/TemplateEx.php");

$USER = new UserSession();
$E = new ErrorSession('templates');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('templates_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("templates.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Шаблоны");
//$tpl->setVariable("USE_CODEMIRROR", intval(getSetting("use_codemirror")));

if(!@$_GET['template_id']) $_GET['template_id'] = $DB->getField("SELECT `id` FROM ".$DB->T('_templates')." ORDER BY `name` LIMIT 1");

$template = new TemplateEx($_GET['template_id']);

if($template->isSpecial())
{
    $tpl->setVariable("ID_TEXT", "специальный ".$template->getId());
    $tpl->setVariable("SPECIAL_DIS", "disabled");
    $tpl->setVariable("SPECIAL_SEL".$template->getId(), "selected");
    //$tpl->setVariable("FILE_READONLY", "readonly");
}
else
{
    $tpl->setVariable("ID_TEXT", $template->getId());
    
    /* linked pages */
    
    $DB->query("SELECT `id`, `name`, `order` FROM ".$DB->T('_pages')." WHERE `template_id`=".$DB->F($template->getId())." ORDER BY `name`");
    while(list($page_id, $page_name, $page_order) = $DB->fetch())
    {
        $tpl->setCurrentBlock("page_row");
        $tpl->setVariable("PAGE_ID", $page_id);
        $tpl->setVariable("PAGE_NAME", $page_name);
        $tpl->setVariable("PAGE_CLASS", $page_order ? "" : "disabled");
        $tpl->parse("page_row");
    }
    $DB->free();
}

if(!$template->getFile())
{
    $E->addWarning("У шаблона нет файла", "Заргузите файл шаблона!");  
}
else {
    
    if(is_file($template->getFile(true, $_ROOT)) &&!is_writable($template->getFile(true, $_ROOT)))
    {
        $E->addWarning("Файл шаблона не доступен для записи", "Вы не сможете сохранить изменения, внесенные в файл шаблона. Установите права (chmod) на файл '".$template->getFile(true, $_ROOT)."'.");    
    }
    $tpl->setCurrentBlock("template_file");
    $tpl->setVariable("TF_FILENAME", $template->getFile());
    $tpl->parse("template_file");
}   

if($template->getFolder() && is_dir($template->getFolder(true, $_ROOT)))
{
    $files = scandir($template->getFolder(true, $_ROOT));
    foreach($files as $filename) 
    {
        if(is_file($template->getFolder(true, $_ROOT)."/$filename") && preg_match("/\.css$/i", $filename))
        {
            $tpl->setCurrentBlock("template_css");
            $tpl->setVariable("TC_FOLDER", $template->getFolder());
            $tpl->setVariable("TC_FILENAME", $filename);
            $tpl->parse("template_css");
        }
    }
    
    if(is_dir($template->getFolder(true, $_ROOT)."/plugins"))
    {
        $modules = scandir($template->getFolder(true, $_ROOT)."/plugins");
        foreach($modules as $module)
        {
            if($module != "." && $module != ".." && is_dir($template->getFolder(true, $_ROOT)."/plugins/$module"))
            {
                $tpl->setCurrentBlock("moduletmpl");
                $tpl->setVariable("MT_NAME", getPluginTitle($module));
                $tpl->setVariable("MT_UID", $module);
                $tpl->parse("moduletmpl");
                
                $tpl->setCurrentBlock("module_files");
                $tpl->setVariable("MF_MODULE", getPluginTitle($module));
                $tpl->setVariable("MF_UID", $module);
                $module_files = scandir($template->getFolder(true, $_ROOT)."/plugins/$module");
                foreach($module_files as $module_file)
                {
                    if(is_file($template->getFolder(true, $_ROOT)."/plugins/$module/$module_file"))
                    {
                        $ext = strtolower(pathinfo($template->getFolder(true, $_ROOT)."/plugins/$module/$module_file", PATHINFO_EXTENSION));
                        if($ext == "htm") $ext = "html";
                        
                        $tpl->setCurrentBlock("module_file");
                        $tpl->setVariable("MF_TEMPLATE", $template->getFolder());
                        $tpl->setVariable("MF_FOLDER", $module);
                        $tpl->setVariable("MF_FILENAME", $module_file);
                        $tpl->setVariable("MF_CLASS", $ext);
                        $tpl->parse("module_file");
                    }
                }
                $tpl->parse("module_files");       
            }
        }        
    }
}

$tpl->setVariable("TMPL_TYPE", $template->isSpecial() ? 1 : 0);
$tpl->setVariable("TMPL_ID", $template->getId());
$tpl->setVariable("TMPL_NAME", $template->getName());
$tpl->setVariable("TMPL_FILENAME", $template->getFile());
//$tpl->setVariable("CSS_OPTIONS", $template->getCssOptions());

/* moved to ajax
foreach($template->getVariableNames() as $variable_name)
{
    $tpl->setCurrentBlock("variable_row");
    $tpl->setVariable("VARIABLE_NAME", $variable_name);
    $sql = "SELECT m.id, m.name, m.type, m.active FROM ".$DB->T('_variables')." AS v LEFT JOIN ".$DB->T('_material')." AS m ON v.material_id=m.id WHERE(v.name=".$DB->F($variable_name)." AND v.page_id=0)";
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
*/

$DB->query("SELECT `id`, `name`, (select count(*) from ".$DB->T('_pages')." WHERE `template_id`=t.id) FROM ".$DB->T('_templates')." AS t ORDER BY `name`");
while(list($tmpl_id, $tmpl_name, $tmpl_pages_count) = $DB->fetch())
{
    $tpl->setCurrentBlock("tmpl_row");
    $tpl->setVariable("T_ID", $tmpl_id);
    $tpl->setVariable("T_NAME", $tmpl_name);
    $tpl->setVariable("T_CLASS", ($tmpl_id == $template->getId()) && !$template->isSpecial() ? "selected" : "");
    $tpl->setVariable("T_PAGES_COUNT", $tmpl_pages_count);
    $tpl->parse("tmpl_row");
}
$DB->free();

$existing_files = $DB->getCol("SELECT `file` FROM ".$DB->T('_templates'));
$new_files = scandir( $_ROOT."/cms/templates/");
sort($new_files);
foreach($new_files as $filename) {
    if(!preg_match("/^(\d+)|(.+\.inc).html?$/i", $filename) && preg_match("/^.+\.html?$/i", $filename) && !in_array($filename, $existing_files)) {
        $tpl->setCurrentBlock("file_row");
        $tpl->setVariable("FILENAME", $filename);
        $tpl->parse("file_row");
    }
}

$E->showAll($tpl);

$tpl->show();
?>