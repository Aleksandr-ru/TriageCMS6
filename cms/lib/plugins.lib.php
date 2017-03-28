<?php

/**
 * Функции для плагинов CMS 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/classes/Page.php");
require_once("$_ROOT/cms/classes/ITM.php");

/**
 * getPluginTitle() получить имя плагина по UID
 * 
 * @param string $uid - UID плагина в БД
 * @return имя плагина или '' в случае ошибки
 */
function getPluginTitle($uid)
{
    global $DB;
    return $DB->getField("SELECT `title` FROM ".$DB->T('_plugins')." WHERE `uid`=".$DB->F($uid));
}

/**
 * getPluginTemplateObject() создает экзепляр класса ITM, с шаблоном в зависимости шаблона страницы
 * 
 * @param string $plugin_uid идентификатор плагина
 * @param string $template_file название файла шаблона
 * @param object $page_object опциональный хендл на объект страницы
 * если null, то будет попытка использовать глобальный $PAGE
 * @return object экземпляр класса ITM
 */
function getPluginTemplateObject($plugin_uid, $template_file, $page_object = null)
{
    global $_ROOT, $PAGE;
    $template = "cms/plugins/".$plugin_uid."/".$template_file;
    if(is_null($page_object)) $page_object = $PAGE;
    if(($page_object instanceof Page) && is_file($_ROOT."/cms/templates/".$page_object->getTemplateFile(false)."/plugins/".$plugin_uid."/".$template_file)) {
        $template = "cms/templates/".$page_object->getTemplateFile(false)."/plugins/".$plugin_uid."/".$template_file;
    } 
    $template = explode("/", $template);
    $file = array_pop($template);
    $path = implode("/", $template);
    
    $template_object = new HTML_Template_IT($_ROOT."/".$path);
    $template_object->loadTemplatefile($file);
    return $template_object;
}

/**
 * plugin_exists()
 * Проверяет существование плагина
 * 
 * @param string $plugin_uid - UID плагина
 * @param bool $falseIfDisabled - если true, то проверятся включенность плагина, и если он отключен возващает false
 * @return bool
 */
function plugin_exists($plugin_uid, $falseIfDisabled = false)
{
    global $_ROOT, $DB;
    
    $plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.php";
    
    if(!is_file($plugin_file)) {            
        Debugger::mes(301, "Plugin file ($plugin_file) is absent for '$plugin_uid'.", __FILE__, __LINE__);
        return false;
    }
    
    list($uid, $active) = $DB->getRow("SELECT `uid`, `active` FROM ".$DB->T('_plugins')." WHERE `uid`=".$DB->F($plugin_uid));
    
    if($uid != $plugin_uid) {
        Debugger::mes(302, "Plugin '$plugin_uid' is not installed.", __FILE__, __LINE__);
        return false;
    } 
    
    if($falseIfDisabled && !$active) {
        Debugger::mes(303, "Plugin '$plugin_uid' is disabled.", __FILE__, __LINE__);
        return false;
    }
    
    return true;
}
?>