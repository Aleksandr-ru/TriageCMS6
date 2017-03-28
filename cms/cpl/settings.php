<?php

/**
 * Настройки системы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
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
$E = new ErrorSession('settings');

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
elseif(!$USER->checkGroup(getSetting('settings_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("settings.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Настройки");

$E->showWarning($tpl);

$sql = "SELECT `name`, `value`, `desc`, `sprav` FROM ".$DB->T('_settings')." ORDER BY `desc`, `name`";
$result = $DB->query($sql);
while(list($name, $value, $desc, $sprav) = $DB->fetch(false, true, $result))
{
    $tpl->setCurrentBlock("set");
    $tpl->setVariable("SETTING_NAME", $desc ? $desc : "<strong>$name</strong>");
    /*
    if($sprav && ($sprav_array = explode(";", $sprav)) && sizeof($sprav_array) )
    {
        $val = "<select name=\"setting[$name]\">";
        
        if(preg_match("/^((".$_config['table_prefix'].".+)\..+(;|$))+/iU", $sprav, $arr))
        {           
            $sql = "SELECT ".$sprav_array[0].", ".$sprav_array[1]." FROM ".$arr[2]." ORDER BY ".$sprav_array[1];
            $res = $DB->query($sql);
            while(list($vv, $txt) = $DB->fetch(false, true, $res))
            {
                $val .= "<option value=\"$vv\" ".($vv==$value ? "selected":"").">$txt</option>";
            }
            $DB->free($res);
        }
        else
        {
            for($i=0; $i<sizeof($sprav_array); $i+=2)
            {
                $val .= "<option value=\"".$sprav_array[$i]."\" ".($sprav_array[$i]==$value ? "selected":"").">".$sprav_array[$i+1]."</option>";
            }
        }
        
        $val .= "</select>";
        
        $tpl->setVariable("SETTING_VALUE", $val);
    }
    else
    {
        $tpl->setVariable("SETTING_VALUE", "<input type=\"text\" name=\"setting[$name]\" value=\"$value\">");
    }
    */
    $tpl->setVariable("SETTING_VALUE", cpl_getInputBySprav("setting[$name]", $value, $sprav));
    $tpl->parse("set");
}
$DB->free($result);

$tpl->show();
?>