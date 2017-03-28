<?php

/**
 * Сохранение материала
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
require_once("$_ROOT/cms/lib/cpl.lib.php");
require_once("$_ROOT/cms/classes/MaterialEx.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/PluginEx.php");
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

if(isset($_POST['ref_plugin_uid']) && $_POST['ref_plugin_uid']) 
{
    require_once("$_ROOT/cms/classes/Plugin.php");
    
    $PLUGIN = new Plugin($_POST['ref_plugin_uid']);
    if(!$USER->checkGroup($PLUGIN->getGroupId()))
    {
        cpl_redirect("forbidden.php");
        exit ;
    }
    
    if(!$PLUGIN->getUid()) $E->addError("Нет такого плагина", "Плагин с таким UID не зарегистрирован в системе.");
}

//die( "<pre>".print_r($_POST,1)."</pre>" );

$new = false;

if(sizeof($_POST) && !@$_POST['material_id'])
{
    $sql = "INSERT INTO ".$DB->T('_material')." (`name`) VALUES('Новый материал')";
    $DB->query($sql);
    $_POST['material_id'] = $DB->insert_id();

    $new = true;
}

if(@$_POST['material_id'])
{
    if($new)
    {
        cmsLogObject("Добавлен материал", "material", $_POST['material_id']);  
    }
    
    $material = new MaterialEx($_POST['material_id']);
    $old_name = $material->getName();
    if($_POST['material_name']) $material->setName($_POST['material_name']);
    if(!$_POST['material_name'] && !$old_name) $E->addWarning("Нельзя сохранить материал без названия", "У каждого материала должно быть название.");
    $material->setGroup($_POST['material_group']);
    $material->setActive($_POST['active']);
    $material->setAccessGroup($_POST['access_group']);
    /*
    switch($_POST['material_type'])
    {
        case 'html':
            $material->setData($_POST['material_html'], $_POST['material_type']);
            break;
        case 'css':
            $material->setData($_POST['material_css'], $_POST['material_type']);
            break;
        case 'javascript':
            $material->setData($_POST['material_javascript'], $_POST['material_type']);
            break;
        case 'plugin':
            $material->setData($_POST['material_plugin'], $_POST['material_type']);
            
            $plugin = new PluginEx($_POST['material_plugin']);
            foreach($_POST['plugin_option_default'] as $option_name=>$is_default) {
                $sql = "DELETE FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($_POST['material_plugin'])." AND `name`=".$DB->F($option_name)." AND `material_id`=".$DB->F($material->getId());
                $DB->query($sql);
            }
            foreach($_POST['plugin_option_value'] as $option_name=>$option_value) {
                $plugin->setOption($option_name, $material->getId(), $option_value);
            }
            
            break;
        case 'text':
        default:
            $material->setData($_POST['material_text'], $_POST['material_type']);
            break;
    }
    */
    $material->setDataAll($_POST['material_text'], $_POST['material_html'], $_POST['material_css'], $_POST['material_javascript'], $_POST['material_plugin']);
    if($_POST['material_plugin']) {
        $plugin = new PluginEx($_POST['material_plugin']);
        foreach($_POST['plugin_option_default'] as $option_name=>$is_default) {
            $sql = "DELETE FROM ".$DB->T('_plugin_options')." WHERE `plugin_uid`=".$DB->F($_POST['material_plugin'])." AND `name`=".$DB->F($option_name)." AND `material_id`=".$DB->F($material->getId());
            $DB->query($sql);
        }
        foreach($_POST['plugin_option_value'] as $option_name=>$option_value) {
            $plugin->setOption($option_name, $material->getId(), $option_value);
        }
    }
    
    if($material->update())
    {
        $new_name = $material->getName();
        $material_name = $old_name != $new_name ? "'$old_name'->'$new_name'" : "'$old_name'";
        if(!isset($_POST['delete'])) cmsLogObject("Отредактирован материал $material_name", "material", $_POST['material_id']);  
    }    
}

if(isset($_POST['ref_plugin_uid']) && $_POST['ref_plugin_uid']) 
{
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
                if(!$pluginAdmin->materialeditor_save($material, $new)) {
                    $E->addWarning("Ошибка плагина", method_exists($pluginAdmin, "materialeditor_error") ? $pluginAdmin->materialeditor_error() : "(без описания)");
                }
            }
        }   
    }
    
    //if($E->isError() || $E->isWarning() || $E->isNotice() || $new) {
    if($E->isError() || $E->isWarning() || $E->isNotice() || isset($_POST['apply'])) {
        cpl_redirect("material.php?material_id=".$material->getId()."&plugin_uid=".$pluginAdmin->getUid()); 
    } else {
        cpl_redirect("plugin.php?plugin_uid=".$pluginAdmin->getUid()); 
    }
}
else
{
    if($new && isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['block']) && preg_match("/^materials(\d+)$/i", $_POST['block'], $arr)) {
        // метриал в блок на странице
        //TODO:повторение кода из ajax/struct_material_set.php
        if(!$USER->checkGroup(getSetting('struct_edit_group'))){
            $E->addWarning("Не удалось установить материал на страницу", "У Вашей учетной записи не достаточно прав доступа для редактирования структуры сайта.");
        } else {
            $order = 1 + $DB->getField("SELECT MAX(`order`) FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($_POST['page_id'])." AND `place_number`=".$DB->F($arr[1]));
            $sql = "INSERT INTO ".$DB->T('_page_materials')." (`page_id`, `material_id`, `place_number`, `order`) VALUES (".$DB->F($_POST['page_id']).", ".$DB->F($material->getId()).", ".$DB->F($arr[1]).", ".$DB->F($order).")";    
            $DB->query($sql);
            if($DB->errno() == 1062) {
                $E->addWarning("Не удалось разместить материал", "Этот материал уже есть в '".$_POST['block']."'! Не допускается установка одного материала несколько раз в одном блоке, выберите другой материал или блок.");
            } elseif(!$DB->errno()) {
                $material_name = $material->getName();
                $page_name = getPageName($_POST['page_id']);
                cmsLogObject("Материал '$material_name' (id: ".$material->getId().") добавлен в '".$_POST['block']."' на странице '$page_name'", "page", $_POST['page_id']);
            }
        }
    } elseif($new && isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['variable']) && $_POST['variable']) {
        //материал в переменную на странице
        //TODO:повторение кода из ajax/struct_variable_set.php
        if(!$USER->checkGroup(getSetting('struct_edit_group'))){
            $E->addWarning("Не удалось установить материал в переменную на странице", "У Вашей учетной записи не достаточно прав доступа для редактирования структуры сайта.");
        } else {
            $sql = "INSERT INTO ".$DB->T('_variables')." (`name`, `page_id`, `material_id`) VALUES(".$DB->F($_POST['variable']).", ".$DB->F($_POST['page_id']).", ".$DB->F($material->getId()).")";
            $DB->query($sql);
            if($DB->errno()) {
                $E->addWarning("Не удалось утсановить переменную ".$_POST['variable'], $DB->error());                
            } else {
                cmsLogObject("В локальное значение для '".$_POST['variable']."' установлен материал '".$material->getName()."' (id: ".$material->getId().") на странице '".getPageName($_POST['page_id'])."' (id: ".$_POST['page_id'].")", "page", $_POST['page_id']);                
            }
        } 
    } elseif($new && isset($_POST['template_id']) && $_POST['template_id'] && isset($_POST['variable']) && $_POST['variable']) {
        //материал в переменную на шаблон 
        //TODO:повторение кода из ajax/template_variable_set.php
        if(!$USER->checkGroup(getSetting('templates_edit_group'))){
            $E->addWarning("Не удалось установить материал в шаблон", "У Вашей учетной записи не достаточно прав доступа для редактирования шаблонов.");
        } else {
            $sql = "INSERT INTO ".$DB->T('_variables')." (`name`, `page_id`, `material_id`) VALUES(".$DB->F($_POST['variable']).", 0, ".$DB->F($material->getId()).") ON DUPLICATE KEY UPDATE `material_id` = ".$DB->F($_POST['material_id']);
            $DB->query($sql);
            if($DB->errno()) {
                $E->addWarning("Не удалось утсановить переменную ".$_POST['variable'], $DB->error());                
            } else {
                cmsLogObject("В глобальное значение для '".$_POST['variable']."' установлен материал '".$material->getName()."' (id: ".$material->getId().")", "tmpl", $_POST['template_id']);                
            }
        }
    }
    
    if(!$new && $material->getId() && isset($_POST['delete']) && $_POST['delete']) {
        
        $a = $DB->getRow("SELECT * FROM ".$DB->T('_material')." WHERE `id`=".$DB->F($material->getId()), true, false);
        $a['group_id'] = 0;
        $a['active'] = 0;
        
        $sql = "DELETE FROM ".$DB->T('_page_materials')." WHERE `material_id`=".$DB->F($material->getId());
        $DB->query($sql);
        
        $sql = "DELETE FROM ".$DB->T('_variables')." WHERE `material_id`=".$DB->F($material->getId());
        $DB->query($sql);
        
        $sql = "DELETE FROM ".$DB->T('_material')." WHERE `id`=".$DB->F($material->getId());
        $DB->query($sql);
        
        cmsLogObject("Удален материал '".$material->getName(false)."' (id: ".$material->getId().")", 'material', $material->getId());    
        cpl_trash('Материал', $a['name'], '_material', $a);
        
        cpl_redirect("materials.php?group_id=".$material->getGroupId());
        exit;
    }
    
    //if($E->isError() || $E->isWarning() || $E->isNotice() || $new) {
    if($E->isError() || $E->isWarning() || $E->isNotice() || isset($_POST['apply'])) {
        if(isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['block']) && $_POST['block']) {
            cpl_redirect("material.php?material_id=".$material->getId()."&page_id=".$_POST['page_id']."&block=".$_POST['block']);
        } elseif(isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['variable']) && $_POST['variable']) {
            cpl_redirect("material.php?material_id=".$material->getId()."&page_id=".$_POST['page_id']."&variable=".$_POST['variable']);
        } elseif(isset($_POST['template_id']) && $_POST['template_id'] && isset($_POST['variable']) && $_POST['variable']) {
            cpl_redirect("material.php?material_id=".$material->getId()."&template_id=".$_POST['template_id']."&variable=".$_POST['variable']);
        } else {
            cpl_redirect("material.php?material_id=".$material->getId());
        } 
    } else {
        if(isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['block']) && $_POST['block']) {
            cpl_redirect("struct.php?page_id=".$_POST['page_id']."&r=".rand()."#materials");
        } elseif(isset($_POST['page_id']) && $_POST['page_id'] && isset($_POST['variable']) && $_POST['variable']) {
            cpl_redirect("struct.php?page_id=".$_POST['page_id']."&r=".rand()."#variables");
        } elseif(isset($_POST['template_id']) && $_POST['template_id'] && isset($_POST['variable']) && $_POST['variable']) {
            cpl_redirect("templates.php?template_id=".$_POST['template_id']."&r=".rand()."#variables");
        } else {
            cpl_redirect("materials.php?group_id=".$material->getGroupId());    
        }             
    }
}
?>