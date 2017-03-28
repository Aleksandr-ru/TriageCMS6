<?php

/**
 * Скрипт удаления пользователя
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
$E = new ErrorSession('users');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('user_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

//TODO:сделать проверку REFERER что не добавляли почем зря

if($user_id = @$_GET['user_id'])
{
    list($letter, $login, $super) = $DB->getRow("SELECT UPPER(LEFT(`login`,1)), `login`, `super` FROM ".$DB->T('_users')." WHERE `id`=".$DB->F($user_id));
    if($user_id == $USER->getId())
    {
        $E->addWarning("Нельзя удалить собственную учетную запись!", "Вы не можете удалить сами себя.");
    }
    elseif(!$USER->isSuper() && $super)
    {
        $E->addWarning("Вы не можете удалить супер-пользователя!", "Удалять супер-пользователей могут только супер-пользователи.");
    }
    elseif(!$letter)
    {
        $E->addError("Нет такого пользователя!", "Пользователь с id = $user_id не обнаружен в системе.");
        cpl_redirect("users.php");
        exit ;
    }
    else
    {
        $a = $DB->getRow("SELECT * FROM ".$DB->T('_users')." WHERE `id`=".$DB->F($user_id), true, false);
        $a['active'] = 0;
        
        $sql = "DELETE FROM ".$DB->T('_user_groups')." WHERE `user_id`=".$DB->F($user_id);
        $DB->query($sql);
        if($DB->errno()) $E->addWarning("Не удалось удалить пользователя из групп!", $DB->error());
        
        $sql = "DELETE FROM ".$DB->T('_users')." WHERE `id`=".$DB->F($user_id);
        $DB->query($sql);
        if($DB->errno()) $E->addWarning("Не удалось удалить пользователя!", $DB->error());
        else {
            cpl_trash('Пользователь', $a['login'], '_users', $a);
            cmsLogObject("Удален ".($super ? "супер-пользователь":"пользователь")." '$login' (id: $user_id)", 'user', $user_id);    
        }
    }
    
    cpl_redirect("users.php?l=$letter");
}
else
    cpl_redirect("users.php");
?>