<?php

/**
 * Ядро CMS
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

define('TRIAGE_CMS', true); 

session_start();

require_once(dirname(__FILE__)."/config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

// простая защита от вирусов - проверяем размер index.php
if(filesize("$_ROOT/index.php") != $_config['indexphp_size']) {
    die('Triage CMS: illegitimate changes detected! System halted.');
    exit;
}

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/plugins.lib.php");

require_once("$_ROOT/cms/classes/DB.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/Page.php");
require_once("$_ROOT/cms/classes/Material.php");

raise_event('core_init');

$USER = new UserSession();

/**
 * если присутствует $_GET['rewrite_variables'] то переводи его в массив $REWRITE_VARS
 * по алгоритму: разбиваем по '/'; если в полученном массиве ЧЕТНОЕ количество элементов, то 
 * переносим его в $REWRITE_VARS как 'ключ' = 'значение'; если нет, то просто пеерводим
*/

if(isset($_GET['rewrite_variables'])) {
    $_GET['rewrite_variables'] = trim($_GET['rewrite_variables'], "/");
    $_GET['rewrite_variables'] = explode("/", $_GET['rewrite_variables']);
    if(sizeof($_GET['rewrite_variables']) % 2 == 0) {
        for($i=0; $i<sizeof($_GET['rewrite_variables']); $i+=2) {
            $REWRITE_VARS[ $_GET['rewrite_variables'][$i] ] = $_GET['rewrite_variables'][$i+1];
        }
    } else {
        $REWRITE_VARS = $_GET['rewrite_variables'];
    }
}

/**
 * конструируем страницу
*/

if(isset($_GET['page_id'])) $page_id = $_GET['page_id'];
elseif(isset($_GET['rewrite_path'])) $page_id = Page::path2id($_GET['rewrite_path']);
else $page_id = $DB->getfield("SELECT p.id FROM ".$DB->T('_pages')." AS p WHERE(p.is_home)");

if(!$page_id) { //trigger_error("No page_id", E_USER_NOTICE);
    $DEBUG->mes(101, "No page ID", __FILE__, __LINE__, print_r($_GET, 1));
}

$PAGE = new Page($page_id);
$PAGE->show();

raise_event('core_finished');
?>