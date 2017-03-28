<?php

/**
 * Установщик CMS - шаг 2, подключение к БД
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */
 
define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../classes/Debugger.php");

//print_r($_POST);
// [db_class] => DB_MySQL [db_host] => localhost [db_port] => 3306 [db_login] => root [db_password] => [db_name] => [table_prefix] => cms_

$err = array();

if($_POST['db_port']) $_POST['db_host'] .= ':'.$_POST['db_port'];
if($test = mysql_connect( $_POST['db_host'], $_POST['db_login'], $_POST['db_password'] )) {
    mysql_close($test);
} else {
    $err[] = 'Ошибка подключения к БД, сервер сообщает: '.mysql_error();
}

if(!trim($_POST['db_name'])) {
    $err[] = "Название базы не может быть пустым, введите корректное название";
}

if(isset($_POST['is_oracle'])) {
    //BUG:под FreeBSD (возможно и другими UNIX) требуется установить NLS_LANG для работы в UTF8        
    @putenv("NLS_LANG=American_America.UTF8");
    if($test = oci_connect( $_POST['ora_login'], $_POST['ora_password'], $_POST['ora_db'], 'UTF8')) {
        oci_close($test);
    } else {
        $e = oci_error();
        //BUG:oci_error выдает в ANSI
        $err[] = 'Ошибка подключения к Oracle, сервер сообщает: '.iconv('windows-1251', 'utf-8', $e['message']);
    }
}

if(sizeof($err)) {
    foreach($err as $i=>$e) $err[$i] = "<li>".htmlspecialchars($e)."</li>";
    echo '<div class="error"><b>Невозможно продолжть установку</b><ul>'.implode("\r\n", $err).'</ul></div>';
} else {
    echo "OK";
}
?>