<?php

/**
 * Скрипт создания новой страницы
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

require_once("$_ROOT/cms/classes/User.php");
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

//TODO:сделать проверку REFERER что не добавляли почем зря

/* OLD!!!
$new_id = $DB->new_id($DB->T('_pages'));
$sql = "INSERT INTO ".$DB->T('_pages')." (`parent_id`, `name`, `key`, `order`, `template_id`) VALUES(".$DB->F($_GET['parent_id']).", 'Новая страница', ".$DB->F($new_id).", 0, ".$DB->F($_GET['template_id']).");";

$DB->query($sql);
if($DB->errno())
{
    $E->addError("Не удалось добавить страницу!", $DB->error());
}
else
{
    $page_id = $DB->insert_id();
    cmsLogObject("Добавлена страница (parent_id: ".$_GET['parent_id'].")", 'page', $page_id);
}
*/

if($page_id = PageEx::createNew($_GET['parent_id'], $_GET['template_id'])) {
    cmsLogObject("Добавлена страница (parent_id: ".$_GET['parent_id'].")", 'page', $page_id);
    $_SESSION[$_config['cookie_prefix'].'cpl_just_added_page_id'] = $page_id;
}
else {
    $E->addError("Не удалось добавить страницу!", $DB->error());
}
cpl_redirect("struct.php?page_id=".@$page_id);
?>