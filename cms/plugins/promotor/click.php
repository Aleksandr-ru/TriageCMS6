<?php

/**
 * @package Triage CMS v6 Promotor Plugin
 * @author Rebel
 * @copyright 2011
 */

define('TRIAGE_CMS', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/classes/Page.php");

$href = $DB->getField("SELECT `href` FROM ".$DB->T('promotor')." WHERE `id`=".$DB->F($_GET['id']));
if(!$href) die('Ссылка не найдена');

if(preg_match("/^#(\d)+#/", $href, $arr)) {
    $href = Page::fullpath($arr[1]);
}

$DB->query("UPDATE ".$DB->T('promotor')." SET `clicks`=`clicks`+1 WHERE `id`=".$DB->F($_GET['id']));
if(strpos($href, '://') === false) $href = make_base($_BASE).$href;
header("Location: $href");
?>