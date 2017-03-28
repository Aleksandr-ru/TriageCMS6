<?php

/**
 * Сохранение пользователя
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
require_once("$_ROOT/cms/classes/UserEx.php");
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

//die( "<pre>".print_r($_POST,1)."</pre>" );
if(isset($_POST['login']) && $_POST['login'])
{
    $user = new UserEx($_POST['user_id']);
    $user->setLogin($_POST['login']);    
    $user->setEmail($_POST['email']);
    
    if(!$_POST['user_id'] && !$_POST['pass1']) $E->addWarning("У каждого пользователя должен быть пароль", "Невозможно создать пользователя без пароля.");
    elseif($_POST['pass1'] != $_POST['pass2']) $E->addWarning("Пароль и подтверждение не совпадают", "Введите одинаковые значения в поля 'Новый пароль' и 'Подтверждение'.");
    elseif($_POST['pass1']) $user->setPassword($_POST['pass1']);
    
    if( $USER->getId() != $user->getId() ) $user->setActive($_POST['active']);
    if( $USER->isSuper() && $USER->getId() != $user->getId() ) $user->setSuper($_POST['super']);
    $user->setGroups($_POST['groups']);
    
    if(!$E->isWarning())
    {
        $ret = $_POST['user_id'] ? $user->update() : $user->create();
        if(!$ret) {
            $E->addError("Не удалось сохранить пользователя", "Возможные причины: логин пользователя уже занят, адрес e-mail уже занят или введен недопустимый e-mail.");    
        } else {
            cmsLogObject(($_POST['user_id'] ? "Отредактирован":"Добавлен")." пользователь '".$user->getLogin()."' (id: ".$user->getId().")", "user", $user->getId());
        }
    }
    
    //Debugger::dump($user);
}
//die();
cpl_redirect($E->isError() || $E->isWarning() ? "user_edit.php?user_id=".$user->getId() : "users.php?l=".strtoupper(substr($user->getLogin(), 0, 1)));
?>