<?php

/**
 * Установщик CMS 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */
 
define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/../classes/Debugger.php");
require_once(dirname(__FILE__)."/../classes/ITM.php");

$tpl = new HTML_Template_IT(dirname(__FILE__));
$tpl->loadTemplatefile("install.html", true, true);
$tpl->setVariable("TITLE", "Установка");

$err = array();
$warn = array();

$tpl->setVariable("SERVER_SOFTWARE", $_SERVER['SERVER_SOFTWARE']);
if(stripos($_SERVER['SERVER_SOFTWARE'], 'apache') === false) {
    $tpl->setVariable("SERVER_SOFTWARE_CLASS", 'err');
    $err[] = "Ваш веб-сервер не подходит, требуется сервер Apache.";
} else {
    $tpl->setVariable("SERVER_SOFTWARE_CLASS", 'ok');
}

if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
    $tpl->setVariable("MOD_REWRITE", 'Присутствует');  
    $tpl->setVariable("MOD_REWRITE_CLASS", 'ok');  
} else {
    $tpl->setVariable("MOD_REWRITE", 'Отсутствует');  
    $tpl->setVariable("MOD_REWRITE_CLASS", 'err');
    $err[] = "Модуль Apache mod_rewrite необходим для правильной работы CMS.";
}

$tpl->setVariable("PHP", phpversion());
if(version_compare(phpversion(), '5.0.0', '>=')) {
    $tpl->setVariable("PHP_CLASS", 'ok');
} else {
    $tpl->setVariable("PHP_CLASS", 'err');
    $err[] = "Слишком старая версия PHP. Требуется версия 5.x или новее.";
}

if(function_exists('mysql_get_server_info')) {
    $tpl->setVariable("MYSQL", mysql_get_server_info());  
    if(!mysql_get_server_info()) {
        $tpl->setVariable("MYSQL_CLASS", 'err');
        $warn[] = "Не удалось автоматически определить версию MySQL на этом сервере. Для корректной работы требуется версия 4.1 или новее.";
        $tpl->setVariable("MYSQL", "Неизвестно");  
    } elseif(version_compare(mysql_get_server_info(), '4.1', '>=')) {
        $tpl->setVariable("MYSQL_CLASS", 'ok');
    } else {
        $tpl->setVariable("MYSQL_CLASS", 'err');
        $err[] = "Слишком старая версия MySQL. Требуется версия 4.1 или новее.";
    }
} else {
    $tpl->setVariable("MYSQL", 'Недоступно');
    $tpl->setVariable("MYSQL_CLASS", 'err');
    $err[] = "MySQL не обнаружен!";  
}

if(function_exists('oci_connect')) {
    $tpl->setVariable("ORACLE", 'Присутствует');
    $tpl->setVariable("ORACLE_CLASS", 'ok');
    $tpl->touchBlock("ora");
} else {
    $tpl->setVariable("ORACLE", 'Отсутствует');
    $tpl->setVariable("ORACLE_CLASS", 'err');
    $warn[] = "Работа с плагинами, использующими Oracle (если таковые есть), будет не возможна.";    
}

if(is_file(dirname(__FILE__)."/../config.php") && filesize(dirname(__FILE__)."/../config.php") ) {
    $tpl->setVariable("OLD_CONFIG", 'Обнаружена, CMS уже установлена!');
    $tpl->setVariable("OLD_CONFIG_CLASS", 'err');
    $err[] = "Обнаружен не пустой файл конфигурации 'cms/config.php'. Похоже, что система уже установлена.";
} else {
    $tpl->setVariable("OLD_CONFIG", 'Не обнаружена');
    $tpl->setVariable("OLD_CONFIG_CLASS", 'ok');
} 

if(is_file(dirname(__FILE__)."/../config.php") && is_writable(dirname(__FILE__)."/../config.php") ) {
    $tpl->setVariable("CONFIG_WRITE", 'Доступно');
    $tpl->setVariable("CONFIG_WRITE_CLASS", 'ok');
} elseif(!is_file(dirname(__FILE__)."/../config.php") && is_writable(dirname(__FILE__)."/../") ) {
    $tpl->setVariable("CONFIG_WRITE", 'Доступно');
    $tpl->setVariable("CONFIG_WRITE_CLASS", 'ok');
} else {
    $tpl->setVariable("CONFIG_WRITE", 'Не доступно');
    $tpl->setVariable("CONFIG_WRITE_CLASS", 'err');
    $err[] = "Файл 'cms/config.php' (или папка 'cms') не доступен для записи. Установите права на запись.";
}

if(is_writable(dirname(__FILE__)."/../templates/") ) {
    $tpl->setVariable("TEMPLATE_WRITE", 'Доступно');
    $tpl->setVariable("TEMPLATE_WRITE_CLASS", 'ok');
} else {
    $tpl->setVariable("TEMPLATE_WRITE", 'Не доступно');
    $tpl->setVariable("TEMPLATE_WRITE_CLASS", 'err');
    $warn[] = "Папка 'cms/templates/' не доступна для записи, в будущем Вы не сможете загрузить шаблоны страниц. Установите права на запись.";
}

if(is_writable(dirname(__FILE__)."/../../files/cms/") ) {
    $tpl->setVariable("FILES_WRITE", 'Доступно');
    $tpl->setVariable("FILES_WRITE_CLASS", 'ok');
} else {
    $tpl->setVariable("FILES_WRITE", 'Не доступно');
    $tpl->setVariable("FILES_WRITE_CLASS", 'err');
    $warn[] = "Папка 'files/cms/' не доступна для записи, в будущем Вы не сможете загрузить файлы для материалов. Установите права на запись.";
}

if(sizeof($err)) {
    foreach($err as $i=>$e) $err[$i] = "<li>".htmlspecialchars($e)."</li>";
    $tpl->setCurrentBlock("error");    
    $tpl->setVariable("ERR_LIST", implode("\r\n", $err));
    $tpl->parse("error");
} else {
    $tpl->touchBlock("notice");
}
if(sizeof($warn)) {
    foreach($warn as $i=>$w) $warn[$i] = "<li>".htmlspecialchars($w)."</li>";
    $tpl->setCurrentBlock("warning");    
    $tpl->setVariable("WARN_LIST", implode("\r\n", $warn));
    $tpl->parse("warning");
}

$tpl->setVariable("DOCUMENT_ROOT", addslashes(realpath(dirname(__FILE__).'/../../')));
$tpl->setVariable("HTTP_BASE", htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].'/'));

$tpl->show();
?>