<?php

/**
 * Сохранение опций плагинов 
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
require_once("$_ROOT/cms/classes/ErrorSession.php");
require_once("$_ROOT/cms/classes/PluginEx.php");

$USER = new UserSession();
$E = new ErrorSession('plugins');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('plugin_conf_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

// [plugin_uid] => materialfiles [options] => Array ( [precision] => 2 ) [active] => 1 [plugin_group] => 2 [reset_options] => 1 [reset_materials] => 1
$plugin = new PluginEx($_POST['plugin_uid']);
if(!$plugin->getUid()) $E->addError("Нет такого плагина", "Плагин '".htmlspecialchars($_POST['plugin_uid'])."' не найден.");
else {
    foreach($_POST['options'] as $option_name=>$option_value) {
        $plugin->setOption($option_name, 0, $option_value);
    }
    
    if($plugin->isActive() != $_POST['active']) {
        $plugin->setActive($_POST['active']);
        cmsLogObject( ($_POST['active'] ? "Активирован" : "Деактивирован")." плагин '".$plugin->getTitle()."'", 'plugin', $plugin->getUid());    
    }
    
    $plugin->setGroupId($_POST['plugin_group']);
    if(!$plugin->update()) $E->addWarning("Не удалось обновить плагин");
    
    if(isset($_POST['reset_options'])) {
        if($plugin->ResetOptions()) {
            cmsLogObject("Восстановлены начальные настройки плагина '".$plugin->getTitle()."'", 'plugin', $plugin->getUid());    
        } else {
            $E->addWarning("Не удалось восстановить начальные настройки плагина", "Убедитесь, что файл '".$plugin->getUid().".install.php' существует в папке плагина.");    
        }
    }
    
    if(isset($_POST['reset_materials'])) {
        if($plugin->ResetMaterials()) {
            cmsLogObject("Сброшены настройки для всех материалов у плагина '".$plugin->getTitle()."'", 'plugin', $plugin->getUid());    
        } else {
            $E->addWarning("Не удалось сбросить настройки для всех материалов");
        }
    }
}

cpl_redirect("plugins.php?plugin_uid=".$plugin->getUid());
?>