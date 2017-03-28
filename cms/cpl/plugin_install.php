<?php

/**
 * Скрипт установки плагинов
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
require_once("$_ROOT/cms/classes/PluginInstaller.php");

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

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("plugin_install.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Устновка плагинов");

$plugin_install = array();
$plugins = cpl_getAvailPlugins();

if(isset($_GET['install']) && ($plugin = $_GET['install']) && in_array($plugin, $plugins)) {
    include_once($_config['document_root']."/cms/plugins/$plugin/$plugin.install.php");
    
    $foo = $plugin.'_install';
    
    if(isset($plugin_install['cms_version'][$plugin]) && version_compare(CMS_VERSION, $plugin_install['cms_version'][$plugin]) < 0){
        $E->addError("Невозможно установить плагин '".$plugin_install['title'][$plugin]."' ($plugin)", "Для его работы требуется Triage CMS версии ".$plugin_install['cms_version'][$plugin]." или новее.");
    } elseif(function_exists($foo)) {
        if( !$foo() ) {
            $E->addError("Не удалось установить плагин '".$plugin_install['title'][$plugin]."' ($plugin)", "Более подробную информацию об ошибках в процессе установки можно увидеть в режиме отладки.");
        } else {
            $E->addNotice("Плагин '".$plugin_install['title'][$plugin]."' ($plugin) успешно установлен", "Перейдите в раздел 'Плагины' и включите его, чтобы начать использовать.");
        }
    } else {
        $E->addWarning("Отсутствует функция установки плагина '".$plugin_install['title'][$plugin]."' ($plugin)", "Обратитесь к разработчику плагина за новой версией.");
    }
    
    cpl_redirect($_SERVER['PHP_SELF']);
    exit; 
}


if(sizeof($plugins)) {
    foreach($plugins as $plugin) {
        include_once($_config['document_root']."/cms/plugins/$plugin/$plugin.install.php");
        $tpl->setCurrentBlock("plugin");
        $tpl->setVariable("PLUGIN_UID", $plugin);
        $tpl->setVariable("PLUGIN_NAME", htmlspecialchars($plugin_install['title'][$plugin]));
        $tpl->setVariable("PLUGIN_DESC", htmlspecialchars($plugin_install['desc'][$plugin]));
        $tpl->parse("plugin");
    }
} else {
    $E->addNotice("Нет плагинов, доступных для установки", "Поместите новые плагины в папку 'cms/plugins' и зайдите сюда снова.");
}


// всякие прочие ошибки из сессии
$E->showError($tpl);
$E->showWarning($tpl);
$E->showNotice($tpl);

$tpl->show();
?>