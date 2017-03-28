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
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('trashcan');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
/*elseif(!$USER->checkGroup(getSetting('struct_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}*/

//TODO:сделать проверку REFERER что не добавляли почем зря

if($trash = $DB->getRow("SELECT * FROM ".$DB->T('_trashcan')." WHERE `id`=".$DB->F($_REQUEST['trash_id']), true, false)) {
    
    $trash_data = unserialize($trash['data_serialized']);
    
    $fields = array();
    $values = array();
    foreach($trash_data as $key=>$value) {
        $fields[] = "`$key`";
        $values[] = $DB->F($value);
    }
    
    $sql = "INSERT INTO ".$DB->T($trash['table'])." (".implode(', ', $fields).") VALUES(".implode(', ', $values).")";
    $DB->query($sql);
    if($DB->errno()) {
        $E->addError("Не удалось восстановить объект '".$trash['type_name']." ".$trash['name']."'", $DB->error());
    } else {
        $E->addNotice("Объект восстановлен", "Успешно восстановлен объект '".$trash['type_name']." ".$trash['name']."'");
        $sql = "DELETE FROM ".$DB->T('_trashcan')." WHERE `id`=".$DB->F($trash['id']);
        $DB->query($sql);
        cmsLog("Восстановлен объект '".$trash['type_name']." ".$trash['name']."' из корзины", 'trash');    
    }
}

cpl_redirect("trashcan.php");
?>