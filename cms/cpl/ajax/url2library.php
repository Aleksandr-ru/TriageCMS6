<?php

/**
 * AJAX загрузчик файлов c URL в библиотеку
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
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
if(isset($_POST['material_id']) && $_POST['material_id'] && isset($_POST['url']) && $_POST['url'])
{
    $url = trim($_POST['url']);
    $orig_name = basename($url);
    $filename = make_clean_filename(basename($url), 200);
    $local_path = $_ROOT."/files/cms/".$_POST['material_id']."-".$filename;
    
    if($orig_name && $filename && !is_file($local_path) && copy($url, $local_path)) {
        if(function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
            $mime = finfo_file($finfo, $local_path);
            finfo_close($finfo);
        } elseif(function_exists('mime_content_type')) {            
            $mime = mime_content_type($local_path);                
        }
        if(!$mime) $mime = preg_match("/^.+\.(bmp|png|jpg|jpeg|gif)$/i", $orig_name, $mime_arr) ? "image/{$mime_arr[1]}" : 'application/octet-stream';
                
        $sql = "INSERT INTO ".$DB->T('_files')." (`mat_id`, `orig_name`, `clean_name`, `mime_type`, `size`) VALUES(".$DB->F($_POST['material_id']).", ".$DB->F($orig_name).", ".$DB->F($filename).", ".$DB->F($mime).", ".$DB->F(filesize($local_path)).")";
        $DB->query($sql);
        
        if($DB->errno()) {
            @unlink($local_path);
            echo "Файл '".$orig_name."' - Ошибка БД: ".$DB->error();
            exit ;
        } else {
            $file_id = $DB->insert_id();
            @chmod($local_path, 0777); 
            cmsLogObject("Загружен файл c URL '".$url."'", "file", $file_id);
            echo "OK";
            exit ;
        }
    } else {
        echo "Ошибка коприрования файла с URL'".addslashes($url)."' в '$filename'";
        exit ;
    }
} else {
    echo "Нет ID материала или отсутвует URL";
    exit ;
}
?>