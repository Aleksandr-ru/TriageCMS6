<?php

/**
 * Управление файлами
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
require_once("$_ROOT/cms/classes/ErrorSession.php");

$USER = new UserSession();
$E = new ErrorSession('filemanager');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('filemanager_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

if(!@$_GET['path']) $_GET['path'] = "/";

$_GET['path'] = str_replace("..", "", $_GET['path']);
$_GET['path'] = preg_replace("@/\.+/@", "/", $_GET['path']);
$_GET['path'] = preg_replace("@/+@", "/", $_GET['path']);

if(!is_dir($_ROOT."/".$_GET['path']))
{
    $E->addWarning("Нет такой папки", "На сервере нет папки '".htmlspecialchars($_GET['path'])."'");
    $_GET['path'] = "/";
}
else
{
    //Debugger::dump($_POST);

    if(isset($_POST['create_folder']) && $_POST['newfoldername'])
    {
        $_POST['newfoldername'] = str_replace("..", "", $_POST['newfoldername']);
        if(!mkdir($_ROOT."/".$_GET['path']."/".$_POST['newfoldername'])) {
            $E->addError("Не удалось создать папку", "Убедитесь что папка с имененм '".htmlspecialchars($_POST['newfoldername'])."' не существует и родительский каталог доступен для записи.");    
        } else {
            cmsLog("Создана папка '".$_GET['path']."/".$_POST['newfoldername']."'");
        }
    }
    
    if(isset($_POST['delete']) && sizeof($_POST['del']))
    {
        foreach($_POST['del'] as $del_name)
        {
            $del_fullpath = $_ROOT."/".$_GET['path']."/".$del_name;
            if(is_file($del_fullpath)) {
                if(!unlink($del_fullpath)) $E->addWarning("Не удалось удалить файл '".htmlspecialchars($del_name)."'", "Убедитесь что такой файл существует и доступен для удаления.");
                else cmsLog("Удален файл '".$_GET['path']."/".$del_name."'");
            }
            elseif(is_dir($del_fullpath)) {
                if(!rmdir($del_fullpath)) $E->addWarning("Не удалось удалить папку '".htmlspecialchars($del_name)."'", "Убедитесь что папка пустая и доступна для удаления.");
                else cmsLog("Удалена папка '".$_GET['path']."/".$del_name."'");
            }
        }
    }
    
    if(isset($_POST['upload']) && sizeof($_FILES))
    {
        //Debugger::dump($_FILES);
        
        foreach ($_FILES["userfile"]["error"] as $key => $error) {
            if ($error == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["userfile"]["tmp_name"][$key];
                $org_name = $_FILES["userfile"]["name"][$key];
                if(!isset($_POST['overwrite']) && is_file($_ROOT."/".$_GET['path']."/".$org_name)) {
                    $E->addNotice("Файл '".htmlspecialchars($org_name)."' не загружен", "Файл с таким имененм уже существует!");
                } else {
                    if (!move_uploaded_file($tmp_name, $_ROOT."/".$_GET['path']."/".$org_name)) $E->addWarning("Файл '".htmlspecialchars($org_name)."' не загружен", "Не удалось переместить файл.");
                    else cmsLog("Загружен файл '".$_GET['path']."/".$org_name."'");
                }
            } else {
                $E->addError("Ошибка загрузки файла, код $error", "Не удалось загрузить файл '".htmlspecialchars($org_name)."', убедитесь что его размер не превышает максимально допустимый.");
            }
        }

    }
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("filemanager.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Управление файлами");
$tpl->setVariable("INI_MAX_FILEZIE", ini_get('upload_max_filesize'));
$tpl->setVariable("INI_POST_MAXSIZE", ini_get('post_max_size'));


if( $_GET['path'] == "/" ) $tpl->setVariable("ROOT_CLASS", "open");
$tpl->setVariable("FOLDER", htmlspecialchars($_GET['path']));
$uppath = explode("/", $_GET['path']);
array_pop($uppath);
$uppath = implode("/", $uppath);
$tpl->setVariable("UP_PATH", $uppath);

function folder_tree($level = 0)
{
    global $_ROOT;
    
    $ret = "<ul>";
    
    $path = explode("/", $_GET['path']);    
    if($path[sizeof($path)-1] == "") array_pop($path);
    
    $folder = "";
    for($i=0; $i<=$level; $i++) $folder .= "/".$path[$i];        
    $folders = scandir($_ROOT.$folder);
    foreach($folders as $dir)
    {
        if($dir != "." && $dir != ".." && is_dir($_ROOT.$folder."/$dir"))
        {
            $class = ""; 
            if(isset($path[$level+1]) && $dir == $path[$level+1]) {
                $class = "selected";
                if($level == sizeof($path)-2) $class .= " open";
            }
            $ret .= "<li class=\"$class\"><a href=\"filemanager.php?path=".preg_replace("@/+@", "/", "$folder/$dir")."\">$dir</a>";
            if(isset($path[$level+1]) && $dir == $path[$level+1]) $ret .= folder_tree($level+1);
            $ret .= "</li>";
        }
    }
    $ret .= "</ul>";
    return $ret;
}
$tpl->setVariable("FOLDER_TREE", folder_tree());

$ff = scandir($_ROOT."/".$_GET['path']);
foreach($ff as $f)
{
    $fullpath = preg_replace("@/+@", "/", $_ROOT."/".$_GET['path']."/$f");
    
    if(is_file($fullpath))
    {
        $tpl->setCurrentBlock("file");
        $tpl->setVariable("FILENAME", $f);
        $tpl->setVariable("FILEPATH", $_GET['path']."/".$f);
        $tpl->setVariable("FILESIZE", format_filesize(filesize($fullpath)));
        $tpl->setVariable("FILE_CLASS", pathinfo($fullpath, PATHINFO_EXTENSION));
        $tpl->parse("file");
    }
    elseif($f != ".." && $f != ".")
    {
        $tpl->setCurrentBlock("folder");
        $tpl->setVariable("FOLDER_NAME", $f);
        $tpl->setVariable("FOLDER_PATH", $_GET['path']."/".$f);
        $tpl->parse("folder");
    }
}


$E->showAll($tpl);

$tpl->show();
?>