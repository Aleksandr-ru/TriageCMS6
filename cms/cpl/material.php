<?php

/**
 * Редактор материала 
 *  
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");
require_once("$_ROOT/cms/classes/MaterialEx.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('material_editor');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("material_edit.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Редактор материала");
$tpl->setVariable("INI_MAX_FILEZIE", ini_get('upload_max_filesize'));
$tpl->setVariable("INI_POST_MAXSIZE", ini_get('post_max_size'));

$tpl->setVariable("USE_TINYMCE", intval(getSetting("use_tinymce")));
$tpl->setVariable("USE_CODEMIRROR", intval(getSetting("use_codemirror")));
$tpl->setVariable("TINYMCE_BASE_URL", make_base($_BASE));

//TODO:уйти от использования $_GET['id']
$material = new MaterialEx(isset($_GET['material_id']) ? $_GET['material_id'] : @$_GET['id'] );
$material_plugin = "";

if(isset($_GET['plugin_uid']) && $_GET['plugin_uid']) 
{
    require_once("$_ROOT/cms/classes/Plugin.php");
    
    $PLUGIN = new Plugin($_GET['plugin_uid']);
    if(!$USER->checkGroup($PLUGIN->getGroupId()))
    {
        cpl_redirect("forbidden.php");
        exit ;
    }   
    
    if(!$PLUGIN->getUid()) $E->addError("Нет такого плагина", "Плагин с таким UID не зарегистрирован в системе.");
    
    $plugin_uid = $PLUGIN->getUid();
    $plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php";
    if(!is_file($plugin_file)) {            
        $E->addWarning("Отсутвует интерфейс управления плагином", "Убедитесь, что файл '$plugin_file' существует.");
    } 
    else 
    {
        include_once($plugin_file);
    
        $plugin_class = $plugin_uid."PluginAdmin";
        if(!class_exists($plugin_class)) {
            $E->addWarning("Отсутвует класс управления плагином", "Обратитесь к разработчику плагина!");
        }
        else 
        {
            $pluginAdmin = new $plugin_class();
            if(!method_exists($pluginAdmin, "materialeditor_load")) {            
                $E->addWarning("Отсутвует метод в классе управления плагином", "Обратитесь к разработчику плагина!");
            }     
            else 
            {                                
                $tpl->setVariable("REF_PLUGIN_UID", $pluginAdmin->getUid());
                $tpl->setCurrentBlock("pluginoutput");
                $tpl->setVariable("PLUGIN_OUTPUT", $pluginAdmin->materialeditor_load($material));
                $tpl->parse("pluginoutput");
                $tpl->setVariable('DEL_DIS', 'disabled');
            }
        }   
    } 
}

if($material->getId())
{     
    $tpl->setVariable("MATERIAL_ID", $material->getId());
    $tpl->setVariable("MATERIAL_TYPE", $material->getType());
    $tpl->setVariable("MATERIAL_ID_TEXT", $material->getId());
    $tpl->setVariable("MATERIAL_NAME", $material->getName());    
    $tpl->setVariable("ACTIVE_CHK", $material->isActive() ? "checked" : "");
    /*
    switch($material->getType())
    {
        case "html":
            //$tpl->setVariable("MATERIAL_DATA_HTML", htmlescapetmpl($material->getDataRaw(), true));
            $tpl->setVariable("MATERIAL_DATA_HTML", htmlspecialchars($material->getDataRaw()));
            break;
        case "css":
            //$tpl->setVariable("MATERIAL_DATA_CSS", htmlescapetmpl($material->getDataRaw(), true));
            $tpl->setVariable("MATERIAL_DATA_CSS", htmlspecialchars($material->getDataRaw()));
            break;
        case "javascript":
            //$tpl->setVariable("MATERIAL_DATA_JS", htmlescapetmpl($material->getDataRaw(), true));
            $tpl->setVariable("MATERIAL_DATA_JS", htmlspecialchars($material->getDataRaw()));
            break;
        case "plugin":
            $material_plugin = $material->getDataRaw();
            break;
        case "text":
        default:
            $tpl->setVariable("MATERIAL_DATA_TEXT", $material->getDataRaw());        
    }
    */
    
    $tpl->setVariable("MATERIAL_DATA_TEXT", htmlspecialchars($material->getDataRaw('text')));
    $tpl->setVariable("MATERIAL_DATA_HTML", htmlspecialchars($material->getDataRaw('html')));
    $tpl->setVariable("MATERIAL_DATA_CSS", htmlspecialchars($material->getDataRaw('css')));
    $tpl->setVariable("MATERIAL_DATA_JS", htmlspecialchars($material->getDataRaw('javascript')));
    $material_plugin = $material->getDataRaw('plugin');
                 
}
else
{
    $tpl->setVariable("MATERIAL_TYPE", "text");
    $tpl->setVariable("MATERIAL_ID_TEXT", "новый");
    $tpl->setVariable('DEL_DIS', 'disabled');
}

$tpl->setVariable("REF_PAGE_ID", isset($_GET['page_id']) ? intval($_GET['page_id']) : '');
$tpl->setVariable("REF_TMPL_ID", isset($_GET['template_id']) ? intval($_GET['template_id']) : '');
$tpl->setVariable("REF_BLOCK", isset($_GET['block']) ? htmlspecialchars($_GET['block']) : '');
$tpl->setVariable("REF_VAR", isset($_GET['variable']) ? htmlspecialchars($_GET['variable']) : '');


$sql = "SELECT `uid`, `title`, `desc`, `active` FROM ".$DB->T('_plugins')." ORDER BY `title`";
$DB->query($sql);
if($DB->num_rows() < 1)
{
    $tpl->touchBlock("noplugin");
}
while(list($plugin_uid, $plugin_title, $plugin_desc, $plugin_active) = $DB->fetch())
{
    $plugin_class = "";
    if(!$plugin_active)
    {
        $plugin_class = "disabled";
        $plugin_title .= " (отключен)";
          
    } 
    $tpl->setCurrentBlock("plugin");
    $tpl->setVariable("PLUGIN_UID", $plugin_uid);
    $tpl->setVariable("PLUGIN_TITLE", $plugin_title);
    $tpl->setVariable("PLUGIN_DESC", $plugin_desc);
    if($material_plugin == $plugin_uid)
    {
        $plugin_class .= " hover";
        $tpl->setVariable("PLUGIN_CHK", "checked");
    }
    $tpl->setVariable("PLUGIN_CLASS", $plugin_class);
    $tpl->parse("plugin");
}
$DB->free();

$tpl->setVariable("MATERIAL_GROUP_OPTIONS", $material->getGroupOptions(@$_GET['group_id']));
$tpl->setVariable("ACCESS_GROUP_OPTIONS", $material->getAccessGroupOptions());

if(!$material->getId())
{
    $tpl->touchBlock("no_library");
}

$E->showError($tpl);
$E->showWarning($tpl);
$E->showNotice($tpl);

$tpl->show();
?>