
<?php

/**
 * AJAX удвление файла
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('material_edit_group')))
{
    die("Нет доступа!");
}

if( ($file_id = @$_POST['file_id']) && ($file = $DB->getRow("SELECT `id`, `mat_id`, `orig_name`, `clean_name` FROM ".$DB->T('_files')." WHERE `id`=".$DB->F($file_id), true)) )
{
    $filename = $_ROOT."/files/cms/".$file['mat_id']."-".$file['clean_name'];
    if(!is_file($filename) || @unlink($filename))
    {
        $sql = "DELETE FROM ".$DB->T('_files')." WHERE `id`=".$DB->F($file_id);
        $DB->query($sql);
        if($DB->errno())
        {
            echo "Ошибка удаления инормации о файле из БД: ".$DB->error();
        }
        else
        {
            cmsLogObject("Удален файл '".$file['orig_name']."'", "file", $file['id']);
        }
    }
    else
    {
        echo "Не удалось удалить $filename";
    }
}
else
{
    echo "Ошибка! Нет ID файла или файл не описан в БД.";
}
?>