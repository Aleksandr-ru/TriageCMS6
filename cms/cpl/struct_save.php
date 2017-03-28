<?php

/**
 * Скрипт применения изменений страницы
 * 
 * @package Triage CMS v.6
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

//echo "<pre>".print_r($_POST,1)."</pre>";
/*
    [parent_id] => 
    [page_name] => test
    [page_title] => Первая страничка
    [root_page] => 1
    [auto_key] => 1
    [key] => main
    [home] => 1
    [order] => 1
    [active] => 1
    [template_id] => 1
    [access_group] => 0
    [redirect] => 
    [keywords] => 
    [description] => 
*/

if(@$_POST['page_id'])
{
    $page = new PageEx($_POST['page_id']);
    $page_old_name = $page->getName();
        
    if($_POST['root_page']) $_POST['parent_id'] = 0;
    
    if(isset($_POST['auto_key']))
    {
        $key = make_key($_POST['page_name']);
        if(!$page->checkKey($key, $_POST['parent_id']))
        {
            $E->addWarning("Автоматическое создание ключа из названия страницы не удалось", "Страница с таким ключем уже существует на выбранном уровне. Ключ установлен в значение по-умолчанию.");
            $_POST['key'] = '';
        }
        else
        {
            $_POST['key'] = $key;
        }
    }
    elseif($_POST['key'] != '')
    {
        if(!$page->checkKey($_POST['key'], $_POST['parent_id']))
        {
            $E->addWarning("Ключ установлен в значение по-умолчанию", "Страница с таким ключем уже существует на выбранном уровне. Попробуйте ввести другой ключ.");
            $_POST['key'] = '';
        }
    }
    
    if($_POST['key']=='') $_POST['key'] = $_POST['page_id'];
    
    $page->setParent($_POST['parent_id']);
    $page->setKey(make_key($_POST['key']));
    $page->setName($_POST['page_name']);
    $page->setTitle($_POST['page_title']);
    $page->setOrder(isset($_POST['active']) ? $_POST['order'] : 0);    
    $page->setTemplate($_POST['template_id']);
    $page->setAccessGroup($_POST['access_group']);
    
    if($_POST['parent_id'] && (isset($_POST['home']) || $page->isHome())) {
        $E->addNotice("С этой страницы больше не может начинаться просмотр сайта", "Главная страница может располагаться только на верхнем уровне иерархии. Назначьте новую главную страницу.");
        unset($_POST['home']);   
    }
    $page->setHome(isset($_POST['home']));
    
    if(isset($_POST['redirect_id']) && $_POST['redirect_id'])
    {
        $_POST['redirect'] = Page::path($_POST['redirect_id']);
    }
    $page->setRedirect($_POST['redirect']);
    //TODO:сделать target
    //$page->setTarget();
    $page->setKeywords($_POST['keywords']);
    $page->setDescription($_POST['description']);
    
    if($page->update())
    {
        $page_new_name = $page->getName();
        $page_name = $page_old_name != $page_new_name ? "'$page_old_name'->'$page_new_name'" : "'$page_old_name'";
        if(!isset($_POST['delete'])) cmsLogObject("Отредактирована страница $page_name", "page", $_POST['page_id']);    
    }
    else
    {
        $E->addError("Не удалось обновить страницу", "Произошла ошибка при обновлении страницы.");
    }
    
    if(isset($_POST['delete']) && $_POST['delete']) {
        if($c = $page->numChildren()) {
            $E->addError("Нельзя удалить страницу", "Иммется $c дочерних страниц, сначала нужно переместить или удалить их, а потом удалять эту страницу.");
        } else {
            $a = $DB->getRow("SELECT * FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($page->getId()), true, false);
            $a['parent_id'] = 0;
            $a['order'] = 0;
            $a['is_home'] = 0;
            $a['key'] = $a['id'];
            
            $sql = "DELETE FROM ".$DB->T('_page_materials')." WHERE `page_id`=".$DB->F($page->getId());
            $DB->query($sql);
            
            $sql = "DELETE FROM ".$DB->T('_variables')." WHERE `page_id`=".$DB->F($page->getId());
            $DB->query($sql);
            
            $sql = "DELETE FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($page->getId());
            $DB->query($sql);
            
            cpl_trash('Страница', $a['name'], '_pages', $a);
            cmsLogObject("Удалена страница '".$page->getName()."' (id: ".$page->getId().")", 'page', $page->getId()); 
        
            cpl_redirect("struct.php");
            exit;    
        }        
    }
}
else
{
    $E->addError("Нет ID страницы", "Нельзя сохранить страницу без идентификатора!");
}

cpl_redirect("struct.php?page_id=".$_POST['page_id']);
?>