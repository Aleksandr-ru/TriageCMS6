<?php

/**
 * Установщик CMS - шаг 3, параметры конфигурации
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */
 
define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../classes/Debugger.php");

//print_r($_POST);
// [document_root] => D:/Webroot/cmstest/6.x.utf8 [http_base] => http://cms6.webhost/ [cookie_prefix] => cms_ [user_login] => [user_pass1] => [user_pass2] => [user_email] =>

$err = array();

if(!$_POST['user_login']) $err[] = "Не заполнено поле 'логин'";
if(!$_POST['user_pass1']) $err[] = "Пароль не может быть пустым";
elseif($_POST['user_pass1'] != $_POST['user_pass2']) $err[] = "Пароль и подтверждение не совпадают";
if(!preg_match("/^.+\@.+\..+$/", $_POST['user_email'])) $err[] = "Недопустимый адрес e-mail";

if(sizeof($err)) {
    foreach($err as $i=>$e) $err[$i] = "<li>".htmlspecialchars($e)."</li>";
    echo '<div class="error"><b>Невозможно продолжть установку</b><ul>'.implode("\r\n", $err).'</ul></div>';
} else {
    echo "OK";
}
?>