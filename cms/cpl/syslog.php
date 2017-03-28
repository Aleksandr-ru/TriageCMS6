<?php

/**
 * Журнал системы
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
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

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}
/*elseif(!$USER->checkGroup(getSetting('settings_edit_group')))
{
    cpl_redirect("forbidden.php");
    exit ;
}*/

$tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
$tpl->loadTemplatefile("syslog.html", true, true);
cpl_header($tpl); cpl_footer($tpl);

$tpl->setVariable("TITLE", "Журнал");

/* filter begin */

$dates = $DB->getCol("SELECT DISTINCT( DATE_FORMAT(l.datetime, '%Y-%m-%d') ) AS d FROM ".$DB->T('_log')." AS l ORDER BY d DESC");
if( !isset($_SESSION['triage_cpl']['syslog_filter']['date1']) )
{
    $_SESSION['triage_cpl']['syslog_filter']['date1'] = $dates[0];
}
if( !isset($_SESSION['triage_cpl']['syslog_filter']['date2']) )
{
    $_SESSION['triage_cpl']['syslog_filter']['date2'] = $dates[0];
}

if(isset($_POST['filter_date1']))
{
    $_SESSION['triage_cpl']['syslog_filter']['date1'] = $_POST['filter_date1'];
}
if(isset($_POST['filter_date2']))
{
    $_SESSION['triage_cpl']['syslog_filter']['date2'] = $_POST['filter_date2'];
}
if(isset($_POST['filter_allusers']))
{
    unset($_SESSION['triage_cpl']['syslog_filter']['users']);
}
elseif(isset($_POST['filter_user']) && is_array($_POST['filter_user']) && sizeof($_POST['filter_user']))
{
    $_SESSION['triage_cpl']['syslog_filter']['users'] = $_POST['filter_user'];
}
if(isset($_POST['filter_allobj']))
{
    unset($_SESSION['triage_cpl']['syslog_filter']['objs']);
}
elseif(isset($_POST['filter_obj']) && is_array($_POST['filter_obj']) && sizeof($_POST['filter_obj']) )
{
    $_SESSION['triage_cpl']['syslog_filter']['objs'] = $_POST['filter_obj'];
    $_SESSION['triage_cpl']['syslog_filter']['obj_val'] = $_POST['filter_objval'];
}

$date_options1 = "";
foreach($dates as $d)
{
    $sel = $_SESSION['triage_cpl']['syslog_filter']['date1'] == $d ? "selected" : "";
    $date_options1 .= "<option $sel>$d</option>";
}
$tpl->setVariable("DATE_OPTIONS1", $date_options1);

$date_options2 = "";
foreach($dates as $d)
{
    $sel = $_SESSION['triage_cpl']['syslog_filter']['date2'] == $d ? "selected" : "";
    $date_options2 .= "<option $sel>$d</option>";
}
$tpl->setVariable("DATE_OPTIONS2", $date_options2);

$sql = "SELECT l.user_id, u.login, COUNT(*) FROM ".$DB->T('_log')." AS l LEFT JOIN ".$DB->T('_users')." AS u ON l.user_id=u.id GROUP BY l.user_id ORDER BY u.login";
$DB->query($sql);
while(list($f_user_id, $f_user_login, $f_user_cnt) = $DB->fetch(false))
{
    $tpl->setCurrentBlock("filter_user");
    $tpl->setVariable("F_USER_ID", $f_user_id);
    $tpl->setVariable("F_USER_NAME", $f_user_login ? $f_user_login : "&lt;неизвестно&gt; (id: $f_user_id)");
    if(is_array($_SESSION['triage_cpl']['syslog_filter']['users']) && in_array($f_user_id, $_SESSION['triage_cpl']['syslog_filter']['users']) )
    {
        $tpl->setVariable("F_USER_CHK", "checked");
    }
    $tpl->parse("filter_user");    
}
$DB->free();
if(!is_array($_SESSION['triage_cpl']['syslog_filter']['users']) || sizeof($_SESSION['triage_cpl']['syslog_filter']['users']) < 1)
{
    $tpl->setVariable("F_ALL_USERS_CHK", "checked");
}
else
{
    $tpl->setVariable("FILTER1_ON", "on");
}

$objs = $DB->getCol("SELECT DISTINCT( LEFT(l.tag, INSTR(l.tag, ':') ) ) AS ob FROM ".$DB->T('_log')." AS l WHERE INSTR(l.tag, ':') ORDER BY ob");
foreach($objs as $o)
{
    $tpl->setCurrentBlock("filter_obj");
    $tpl->setVariable("F_OBJ", $o);
    if(is_array($_SESSION['triage_cpl']['syslog_filter']['objs']) && in_array($o, $_SESSION['triage_cpl']['syslog_filter']['objs']) )
    {
        $tpl->setVariable("F_OBJ_CHK", "checked");
        //BUG:не дает фильтровать плагины
        //if(preg_match("/^\d+$/i", $_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o]))
        if($_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o]) {
            $tpl->setVariable("F_OBJ_VAL", htmlspecialchars($_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o]));
        }
    }
    $tpl->parse("filter_obj");    
}
if(!is_array($_SESSION['triage_cpl']['syslog_filter']['objs']) || sizeof($_SESSION['triage_cpl']['syslog_filter']['objs']) < 1)
{
    $tpl->setVariable("F_ALL_OBJ_CHK", "checked");
}
else
{
    $tpl->setVariable("FILTER2_ON", "on");
}

/* filter to sql */

$filter = array();

if($_SESSION['triage_cpl']['syslog_filter']['date1'])
{
    $filter[] = $DB->F($_SESSION['triage_cpl']['syslog_filter']['date1'])." >= DATE_FORMAT(l.datetime, '%Y-%m-%d')";
}
if($_SESSION['triage_cpl']['syslog_filter']['date2'])
{
    $filter[] = $DB->F($_SESSION['triage_cpl']['syslog_filter']['date2'])." <= DATE_FORMAT(l.datetime, '%Y-%m-%d')";
}
if(is_array($_SESSION['triage_cpl']['syslog_filter']['users']) && sizeof($_SESSION['triage_cpl']['syslog_filter']['users']))
{
    $filter[] = "l.user_id IN (".implode(",", $_SESSION['triage_cpl']['syslog_filter']['users']).")";
}
if(is_array($_SESSION['triage_cpl']['syslog_filter']['objs']) && sizeof($_SESSION['triage_cpl']['syslog_filter']['objs']))
{
    $f = array();
    foreach($_SESSION['triage_cpl']['syslog_filter']['objs'] as $o)
    {
        //BUG:не дает фильтровать плагины
        //if(preg_match("/^\d+$/i", $_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o]))
        if($_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o])
        {
            $f[] = "l.tag LIKE '".addslashes($o.$_SESSION['triage_cpl']['syslog_filter']['obj_val'][$o])."'";
        }
        else
        {
            $f[] = "l.tag LIKE '".addslashes($o)."%'";
        }
    }
    $filter[] = "(".implode(" OR ", $f).")";
}
$filter = implode(" AND ", $filter);
if(trim($filter))
{
    $filter = " WHERE ($filter) ";
}
else
{
    $filter = "";
}

/* filter end */


$sql = "SELECT l.datetime, l.user_id, l.text, l.tag, u.login FROM ".$DB->T('_log')." AS l LEFT JOIN ".$DB->T('_users')." AS u ON l.user_id=u.id $filter ORDER BY l.datetime DESC";
$result = $DB->query($sql);
while(list($date, $user_id, $text, $tag, $login) = $DB->fetch(false, true, $result))
{
    $tpl->setCurrentBlock("row");
    $tpl->setVariable("DATE", $date);
    $tpl->setVariable("USER", $user_id && $login ? $login : ($user_id ? "<em>неизвестно (id: $user_id)</em>" : "<strong>система</strong>"));
    $tpl->setVariable("EVENT", $text);
    $tpl->setVariable("OBJECT", $tag);
    $tpl->parse("row");
}
$tpl->setVariable("SHOW_CNT", $DB->num_rows($result));
$DB->free($result);

$tpl->setVariable("ALL_CNT", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_log')));

$tpl->show();
?>