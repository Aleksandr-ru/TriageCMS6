<?php

/**
 * Скрипт групповых операций со страницами
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

require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/Dialog.php");
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

list(list(, $operation_parent_id) , list(, $operation_recursive)) = array_map("explode", array(":", ":"), explode(";", $_GET['operation_scope']));

if(!isset($operation_parent_id) || $operation_parent_id==='')
{
    $E->addWarning("Нет области действия операции", "Невозможно провести операции без области дейсвия.");
    cpl_redirect("struct.php?page_id=".$_GET['operation_src_page_id']."#operations");
    exit ;
}

if(isset($_GET['operation_auto_key'])) // сгенерировать ключи из названий страниц где возможно
{
    function operation_auto_key($parent_id)
    {
        global $DB, $E, $operation_recursive;
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $page = new PageEx($page_id);
            if(!$page->getKey())
            {
                $key = make_key($page->getName());
                if($page->checkKey($key, $parent_id, false))
                {
                    $page->setKey($key, true);
                }
                else
                {
                    $E->addWarning("Операция 'Установить авто-ключ'", "Не удалось установить авто-ключ для страницы '".$page->getName()."' (id: $page_id). Страница с таким ключем уже существует на данном уровне иерархии. <a target=\"_blank\" href=\"struct.php?page_id=$page_id\">Редактирвать страницу</a>.");
                }
            }
            unset($page);
               
            if($operation_recursive) operation_auto_key($page_id);       
        }
    }
    operation_auto_key($operation_parent_id);
    cmsLog("Операция 'Установить авто-ключ' на области [".$_GET['operation_scope']."]");
}

if(isset($_GET['operation_activate'])) // активировать отключенные страницы
{
    function operation_activate($parent_id)
    {
        global $DB, $E, $operation_recursive;
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $page = new PageEx($page_id);
            $page->setOrderAuto(true);
            unset($page);
               
            if($operation_recursive) operation_activate($page_id);       
        }
    }
    operation_activate($operation_parent_id);
    cmsLog("Операция 'Включить страницы' на области [".$_GET['operation_scope']."]");
}

if(isset($_GET['operation_enum'])) // перенумеровать включенные страницы
{
    function operation_enum($parent_id)
    {
        global $DB, $E, $operation_recursive;          
        
        $sql = "TRUNCATE TABLE `temp_page_order`";
        $DB->query($sql);
        if($DB->errno())
        {
            $E->addWarning("Операция 'Упорядочить страницы' ошибка (1)", $DB->error());
            return false;
        }
        
        $sql = "INSERT INTO `temp_page_order` (`page_id`) SELECT `id` FROM ".$DB->T('_pages')." WHERE `order` AND `parent_id`=".$DB->F($parent_id)." ORDER BY `order`";
        $DB->query($sql);
        
        if($DB->errno())
        {
            $E->addWarning("Операция 'Упорядочить страницы' ошибка (2)", $DB->error());
            return false;
        }
        
        $sql = "UPDATE ".$DB->T('_pages')." AS p1 SET p1.order=( SELECT p2.order FROM `temp_page_order` AS p2 WHERE p2.page_id=p1.id ) WHERE p1.order AND p1.parent_id=".$DB->F($parent_id);
        $DB->query($sql);
        
        if($DB->errno())
        {
            $E->addWarning("Операция 'Упорядочить страницы' ошибка (3)", $DB->error());
            return false;
        }
        
        if($operation_recursive)
        {
            $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
            foreach($page_ids as $page_id) operation_enum($page_id);                   
        }
    }
    
    //TODO:изменить алгоритм автоматической сортировки для совместимости с другими типами БД
    $sql = "CREATE TEMPORARY TABLE `temp_page_order` (`order` INT( 5 ) UNSIGNED NOT NULL AUTO_INCREMENT , `page_id` INT( 5 ) UNSIGNED NOT NULL , PRIMARY KEY ( `order` ) ) TYPE = HEAP;";
    $DB->query($sql);
    if($DB->errno()) $E->addWarning("Операция 'Упорядочить страницы' не выполнена", $DB->error());
    else
    {
        operation_enum($operation_parent_id);
        cmsLog("Операция 'Упорядочить страницы' на области [".$_GET['operation_scope']."]");
    }
}

if(isset($_GET['operation_template']) && $_GET['operation_template'] > 0) // Установить шаблон
{
    function operation_template($parent_id)
    {
        global $DB, $E, $operation_recursive;
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $page = new PageEx($page_id);
            $page->setTemplate($_GET['operation_template'], true);
            unset($page);
               
            if($operation_recursive) operation_template($page_id);       
        }
    }
    operation_template($operation_parent_id);
    cmsLog("Операция 'Установить шаблон' (template_id: ".$_GET['operation_template'].") на области [".$_GET['operation_scope']."]");
}

if(isset($_GET['operation_access']) && $_GET['operation_access'] > -1) // Установить группу доступа
{
    function operation_access($parent_id)
    {
        global $DB, $E, $operation_recursive;
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $page = new PageEx($page_id);
            $page->setAccessGroup($_GET['operation_access'], true);
            unset($page);
               
            if($operation_recursive) operation_access($page_id);       
        }
    }
    operation_access($operation_parent_id);
    cmsLog("Операция 'Установить группу доступа' (access_group_id: ".$_GET['operation_access'].") на области [".$_GET['operation_scope']."]");
}

if(isset($_GET['operation_clean_materials'])) // убрать все материалы из всех блоков
{
    function operation_clean_materials($parent_id)
    {
        global $DB, $E, $operation_recursive;               
        
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $sql = "DELETE FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($page_id);
            $DB->query($sql);
               
            if($operation_recursive) operation_clean_materials($page_id);       
        }
    }
    operation_clean_materials($operation_parent_id);
    cmsLog("Операция 'Очистить материалы' на области [".$_GET['operation_scope']."]");
}

if(isset($_GET['operation_clean_variables'])) // убрать все локальные значения перемнных
{
    function operation_clean_variables($parent_id)
    {
        global $DB, $E, $operation_recursive;               
        
        $page_ids = $DB->getCol("SELECT `id` FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($parent_id));
        foreach($page_ids as $page_id)
        {
            $sql = "DELETE FROM ".$DB->T('_variables')." WHERE `page_id`=".$DB->F($page_id);
            $DB->query($sql);
               
            if($operation_recursive) operation_clean_variables($page_id);       
        }
    }
    operation_clean_variables($operation_parent_id);
    cmsLog("Операция 'Очистить переменные' на области [".$_GET['operation_scope']."]");
}

cpl_redirect("struct.php?page_id=".$_GET['operation_src_page_id']."#operations");
?>