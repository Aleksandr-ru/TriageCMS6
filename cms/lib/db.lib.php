<?php

/**
 * Подключение к БД
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 * @todo сделать подключение к другим типам БД
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once(dirname(__FILE__)."/../classes/DB.php");

if(isset($DB) && !is_a($DB, 'DB'))
{ 
    trigger_error("\$DB variable already set as ".(get_class($DB) ? "member of ".get_class($DB) : gettype($DB))."! Unsetting :)", E_USER_WARNING);
    unset($DB);
}

switch($_config['db_class'])
{
    case "DB_MySQL":
    default:
        $DB = new DB_MySQL();
}

?>