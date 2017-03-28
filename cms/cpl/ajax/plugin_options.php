<?php

/**
 * AJAX опции плагина
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

define('TRIAGE_CMS', true); 
define('AJAX', true);

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");

$USER = new UserSession();

header('Content-type: text/html; charset=utf-8');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    die("Нет доступа!");
}
elseif(!$USER->checkGroup(getSetting('plugin_conf_group')))
{
    //die("Нет доступа!");
    die(); // чтоб не мазолило глаза а имитировало что нет нестроек
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("ajax.plugin_options.html", true, true);

$material_id = isset($_POST['material_id']) ? $_POST['material_id'] : 0;

if($plugin_uid = $_POST['plugin_uid'])
{
    $options_sprav = $DB->getCol2("SELECT `name`, `sprav` FROM ".$DB->T('_plugin_options')." WHERE `material_id`=0 AND `plugin_uid` LIKE ".$DB->F($plugin_uid));
    $options_default = $DB->getCol2("SELECT `name`, `value` FROM ".$DB->T('_plugin_options')." WHERE `material_id`=0 AND `plugin_uid` LIKE ".$DB->F($plugin_uid));
    $options_desc = $DB->getCol2("SELECT `name`, `desc` FROM ".$DB->T('_plugin_options')." WHERE `material_id`=0 AND `plugin_uid` LIKE ".$DB->F($plugin_uid));
    
    $sql = "SELECT opt.name, opt.value, (opt.material_id=0) AS def FROM  (SELECT * FROM ".$DB->T('_plugin_options')." WHERE(`plugin_uid` LIKE ".$DB->F($plugin_uid)." AND(`material_id`=".$DB->F($material_id)." OR `material_id`=0)) ORDER BY `material_id` DESC) AS opt GROUP BY opt.name";
    $DB->query($sql);
    while(list($option_name, $option_value, $option_default) = $DB->fetch())
    {
        $tpl->setCurrentBlock("option");
        $tpl->setVariable("OPTION_NAME", $option_name);
        $tpl->setVariable("OPTION_TITLE", $options_desc[$option_name] ? $options_desc[$option_name] : "<strong>".$option_name."</strong>" );
        $sprav = $options_sprav[$option_name];
        
        if($material_id && $option_default)
        {
            $tpl->setVariable("DEFAULT_CHK", "checked");
            $dis = "disabled";   
        }
        elseif(!$material_id && !$USER->checkGroup(getSetting('plugin_conf_group')))
        {
            $dis = "disabled"; 
        }
        else
        {
            $dis = "";
        }
        /*if($material_id)
        {*/
            $input_name = "plugin_option_value[$option_name]";
        /*}
        else
        {
            $input_name = "plugin_option_value[$plugin_uid][$option_name]";
        }*/
        
        if($sprav && ($sprav_array = explode(";", $sprav)) && sizeof($sprav_array) )
        {
            $val = "<select name=\"$input_name\" $dis>";
            
            if(preg_match("/^((".$_config['table_prefix'].".+)\..+(;|$))+/iU", $sprav, $arr))
            {           
                $sql = "SELECT ".$sprav_array[0].", ".$sprav_array[1]." FROM ".$arr[2]." ORDER BY ".$sprav_array[1];
                $res = $DB->query($sql);
                while(list($vv, $txt) = $DB->fetch(false, true, $res))
                {
                    $val .= "<option value=\"$vv\" ".($vv==$option_value ? "selected":"").">$txt</option>";
                }
                $DB->free($res);
            }
            else
            {
                for($i=0; $i<sizeof($sprav_array); $i+=2)
                {
                    $val .= "<option value=\"".$sprav_array[$i]."\" ".($sprav_array[$i]==$option_value ? "selected":"").">".$sprav_array[$i+1]."</option>";
                }
            }
            
            $val .= "</select>";
            
            $tpl->setVariable("OPTION_VAL", $val);
        }
        else
        {
            $tpl->setVariable("OPTION_VAL", "<input type=\"text\" name=\"$input_name\" value=\"$option_value\"  $dis>");
        }
        $tpl->parse("option");       
    }
    $DB->free();
}

$tpl->show();
?>
