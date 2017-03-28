<?php

/**
 * Функции для панели управления
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013 
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

// define('CPL_ACCESS', 90); устарело

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/classes/Debugger.php");

$DEBUG = new Debugger(@$_GET['debug']);

function cpl_redirect($url, $return = false)
{
    global $_config, $_BASE, $_ROOT, $DEBUG;
    
    if(!defined('CPL_REDIRECT')) define('CPL_REDIRECT', true);
    
    if(!$DEBUG->getDebug()) {
        if(preg_match("@^[a-z]+://.+@i", $url)) $location = $url;
        elseif(substr($url, 0, 1) == '/') $location = make_base($_BASE).substr($url, 1);
        else $location = make_base("$_BASE/cms/cpl/").$url;        
        header("Location: $location");
    }
    
    require_once("$_ROOT/cms/classes/ITM.php");
    $tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
    $tpl->loadTemplatefile("redirect.html", true, true);
    
    $tpl->setVariable("HTTP_BASE", make_base("$_BASE/cms/cpl/"));
    $tpl->setVariable("URL", $url);
        
    if($return) return $tpl->get();
    else               $tpl->show();
    
}

function cpl_header($tpl)
{
    global $DEBUG, $DB, $_ROOT;
    
    if($DEBUG instanceof Debugger) $debug = $DEBUG->getDebug();
    else                           $debug = @$_SESSION['debug'];
    
    $debug_url = $_SERVER['PHP_SELF'].($debug ? "?debug=0" : "?debug=2047");
    
    $tpl->setVariable("CPLHEADER_NUM_PAGES", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." AS p"));
    $tpl->setVariable("CPLHEADER_NUM_MATERIALS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_material')." AS m WHERE m.active"));
    $tpl->setVariable("CPLHEADER_NUM_PLUGINS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_plugins')." AS pl"));
    $tpl->setVariable("CPLHEADER_NUM_PLUGINS2", cpl_numOfAvailPlugins());
    $tpl->setVariable("CPLHEADER_NUM_USERS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_users')." AS u WHERE u.active"));
    $tpl->setVariable("CPLHEADER_NUM_GROUPS", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_groups')." AS g"));
    $tpl->setVariable("CPLHEADER_NUM_TEMPLATES", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_templates')." AS t"));
    $tpl->setVariable("CPLHEADER_NUM_TRASH", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_trashcan')));
    $tpl->setVariable("CPLHEADER_NUM_LOG", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_log')." AS l"));
    $tpl->setVariable("CPLHEADER_DEBUG", $debug ? "вкл" : "выкл");
    $tpl->setVariable("CPLHEADER_DEBUG_URL", $debug_url);
    
    $sql = "SELECT `uid`, `title`, `desc` FROM ".$DB->T('_plugins')." WHERE `active` ORDER BY `title`";
    $DB->query($sql);
    while(list($plugin_uid, $plugin_name, $plugin_desc) = $DB->fetch())
    {
        if( is_file("$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.admin.php") )
        {
            $tpl->setCurrentBlock("cplheader_plugin");
            $tpl->setVariable("CPLHEADER_PLUGIN_UID", $plugin_uid);
            $tpl->setVariable("CPLHEADER_PLUGIN_NAME", $plugin_name);
            $tpl->setVariable("CPLHEADER_PLUGIN_DESC", $plugin_desc);
            $tpl->parse("cplheader_plugin");   
        }
    }
    $DB->free();
}

function cpl_footer($tpl)
{
    global $USER;
    
    $tpl->setVariable("CMS_VERSION", CMS_VERSION);
	$tpl->setVariable("PHP_VERSION", phpversion());
	$tpl->setVariable("MYSQL_VERSION", mysql_get_server_info());
    $tpl->setVariable("CPLFOOTER_USER_NAME", $USER->getLogin());
}

function cpl_getAvailPlugins()
{
    global $DB, $_ROOT;
    $new_plugins = array();
    $installed_plugins = $DB->getCol("SELECT `uid` FROM ".$DB->T('_plugins'));
    $dd = scandir("$_ROOT/cms/plugins");
    foreach($dd as $dir)
    {
        if($dir != "." && $dir != ".." && is_dir("$_ROOT/cms/plugins/$dir") && !in_array($dir, $installed_plugins) && is_file("$_ROOT/cms/plugins/$dir/$dir.install.php"))
        {
            $new_plugins[] = $dir;
        }
    }
    return $new_plugins;
}

function cpl_numOfAvailPlugins()
{
    return sizeof( cpl_getAvailPlugins() );
}

function cpl_getInputBySprav($input_name, $value, $sprav)
{
    global $_config, $DB;
    
    if($sprav && ($sprav_array = explode(";", $sprav)) && sizeof($sprav_array) )
    {
        $val = "<select name=\"$input_name\">";
        
        if(preg_match("/^((".$_config['table_prefix'].".+)\..+(;|$))+/iU", $sprav, $arr))
        {           
            $sql = "SELECT ".$sprav_array[0].", ".$sprav_array[1]." FROM ".$arr[2]." ORDER BY ".$sprav_array[1];
            $res = $DB->query($sql);
            while(list($vv, $txt) = $DB->fetch(false, true, $res)) {
                $val .= "<option value=\"$vv\" ".($vv==$value ? "selected":"").">$txt</option>";
            }
            $DB->free($res);
        }
        else
        {
            for($i=0; $i<sizeof($sprav_array); $i+=2) {
                $val .= "<option value=\"".$sprav_array[$i]."\" ".($sprav_array[$i]==$value ? "selected":"").">".$sprav_array[$i+1]."</option>";
            }
        }
        
        $val .= "</select>";
        
        return $val;
    }
    else
    {
        return "<input type=\"text\" name=\"$input_name\" value=\"$value\">";
    }
}

function uploadMaterialFile($material_id, $file_name, $file_tmp_name, $file_size = 0, $file_type = 'application/octet-stream')
{
    global $_ROOT, $DB;
    
    if(!($material_id = intval($material_id))) {
        trigger_error("No material ID", E_USER_WARNING);
        return false;
    }
    
    $clean_name = make_clean_filename($file_name, 200);
    $new_filename = $_ROOT."/files/cms/".$material_id."-".$clean_name;                
    if(!is_file($new_filename) && move_uploaded_file($file_tmp_name, $new_filename)) {
        if(!$file_size) $file_size = filesize($file_tmp_name);
        
        $sql = "INSERT INTO ".$DB->T('_files')." (`mat_id`, `orig_name`, `clean_name`, `mime_type`, `size`) VALUES(".$DB->F($material_id).", ".$DB->F($file_name).", ".$DB->F($clean_name).", ".$DB->F($file_type).", ".$DB->F($file_size).")";
        $DB->query($sql);
        if($DB->errno()) {
            @unlink($new_filename);
            trigger_error("DB error during file upload: ".$DB->error(), E_USER_WARNING);
            return false;
        } else {
            $file_id = $DB->insert_id();
            @chmod($new_filename, 0666); 
            cmsLogObject("Загружен файл '".$file_name."'", "file", $file_id);
            return $file_id;
        }
    } else {
        //echo "Ошибка перемещения файла '".$_FILES['userfile']['name'][$key]."' в '$clean_name'\r\n";
        trigger_error("Move uploaded file error or file exists!", E_USER_WARNING);
        return false;
    }
}

function cpl_trash($type, $name, $table, $data_array)
{
    global $DB, $USER;
    
    $sql = "INSERT INTO ".$DB->T('_trashcan')." (`type_name`, `name`, `table`, `data_serialized`, `date`, `user_id`) VALUES(".$DB->F($type).", ".$DB->F($name).", ".$DB->F($table).", ".$DB->F(serialize($data_array)).", NOW(), ".$DB->F($USER->getId()).")";    
    $DB->query($sql);
    return $DB->errno() ? false : $DB->insert_id();
}
?>