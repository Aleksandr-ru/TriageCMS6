<?php

/**
 * AJAX загрузчик содержимого таблицы файлов
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

echo '<table cellpadding="4" cellspacing="1" border="0" width="100%">';

if($material_id = @$_POST['material_id'])
{
    $sql = "SELECT f.id, f.orig_name, f.clean_name, f.mime_type, f.size FROM ".$DB->T('_files')." AS f WHERE(f.mat_id = ".$DB->F($material_id).") ORDER BY f.orig_name";
    $DB->query($sql);
    while(list($file_id, $file_name, $clean_name, $file_mime, $file_size) = $DB->fetch())
    {
        $file_class = "file";
        $imgattrs = "";
        if(preg_match("/^image\/.+/i", $file_mime)) {
            $file_class .= " image";
            if(function_exists('getimagesize')) { 
                $imgsize = getimagesize($_ROOT."/files/cms/$material_id-$clean_name");
                $imgattrs = rawurlencode($imgsize[3]);
            }    
        }
        $file_href = "files/$material_id/$clean_name";
        /*if($file_size > 1024 * 1024) // mb
        {
            $file_size = number_format(round($file_size / 1024 / 1024, 1), ' ')." Мб";
        }
        else
        {
            $file_size = number_format(round($file_size / 1024, 1), ' ')." Кб";
        }*/
        $file_size = format_filesize($file_size, 0);
        
        echo '<tr>';
        echo '<td class="'.$file_class.'"><a href="'.$file_href.'" imgattrs="'.$imgattrs.'">'.$file_name.'</a></td>';
        echo '<td nowrap>'.$file_size.'</td>';
        echo '<td><a class="del" href="#'.$file_id.'"><img src="images/spacer.gif" width="16" height="16" border="0"></a></td>';
        echo '</tr>';
    }
    $DB->free();
}

echo '</table>';
?>
