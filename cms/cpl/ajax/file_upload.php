<?php

/**
 * AJAX загрузчик файлов в библиотеку
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
require_once("$_ROOT/cms/lib/cpl.lib.php");

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
if(isset($_POST['material_id']))
{
    //echo "<pre>".print_r($_FILES, 1)."</pre>";
    foreach($_FILES['userfile']['error'] as $key => $error)
    {
        switch($error)
        {
            case UPLOAD_ERR_OK:                
                /*
                $clean_name = make_clean_filename($_FILES['userfile']['name'][$key], 200);
                $new_filename = $_ROOT."/files/cms/".$_POST['material_id']."-".$clean_name;                
                if(!is_file($new_filename) && move_uploaded_file($_FILES['userfile']['tmp_name'][$key], $new_filename))
                {
                    $sql = "INSERT INTO ".$DB->T('_files')." (`mat_id`, `orig_name`, `clean_name`, `mime_type`, `size`) VALUES(".$DB->F($_POST['material_id']).", ".$DB->F($_FILES['userfile']['name'][$key]).", ".$DB->F($clean_name).", ".$DB->F($_FILES['userfile']['type'][$key]).", ".$DB->F($_FILES['userfile']['size'][$key]).")";
                    $DB->query($sql);
                    if($DB->errno())
                    {
                        @unlink($new_filename);
                        echo "Файл '".$_FILES['userfile']['name'][$key]."' - Ошибка БД: ".$DB->error()."\r\n";
                    }
                    else
                    {
                        $file_id = $DB->insert_id();
                        @chmod($new_filename, 0666); 
                        cmsLogObject("Загружен файл '".$_FILES['userfile']['name'][$key]."'", "file", $file_id);
                    }
                }
                else 
                {
                    echo "Ошибка перемещения файла '".$_FILES['userfile']['name'][$key]."' в '$clean_name'\r\n";
                }*/
                if(!uploadMaterialFile($_POST['material_id'], $_FILES['userfile']['name'][$key], $_FILES['userfile']['tmp_name'][$key], $_FILES['userfile']['size'][$key], $_FILES['userfile']['type'][$key])) {
                    echo "Ошибка загрузки файла '".$_FILES['userfile']['name'][$key]."'\r\n";
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                echo "Размер файла '".$_FILES['userfile']['name'][$key]."' превышает максимально допустимый\r\n";
                break;
            case UPLOAD_ERR_PARTIAL:
                echo "Файл '".$_FILES['userfile']['name'][$key]."' был получен только частично\r\n";
                break;
            case UPLOAD_ERR_NO_FILE:
            default:
        }
    }
}
?>