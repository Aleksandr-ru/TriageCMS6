<?php

/**
 * Диалог выбора страницы 
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

session_start();

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");
require_once("$_ROOT/cms/classes/Page.php");

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}

if(isset($_GET['page'])) {
    $_GET['page_id'] = Page::path2id($_GET['page']);
}

if(isset($_GET['page_id']) && $_GET['page_id']) // добавим всех родителей выбранной страницы в раскрытые старницы
{
    $_SESSION['triage_cpl']['struct_expand'] = array_unique(array_merge(is_array($_SESSION['triage_cpl']['struct_expand']) ? $_SESSION['triage_cpl']['struct_expand'] : array(), array_slice(Page::path_ids($_GET['page_id']), 0, -1)));  
}

function struct_tree($parent = 0)
{
    global $DB;
    
    $sql = "SELECT p.id, p.name, p.key, p.is_home, (SELECT COUNT(*) FROM ".$DB->T('_pages')." AS pp WHERE(pp.parent_id=p.id)), (p.order = 0) AS ord FROM ".$DB->T('_pages')." AS p WHERE(p.parent_id = ".$DB->F($parent).") ORDER BY ord, p.order";
    
    $ret = "<ul>\r\n";    
    $result = $DB->query($sql);
    while(list($page_id, $page_name, $page_key, $page_home, $page_children, $ord) = $DB->fetch(false, true, $result))
    {
        if($page_id == @$_GET['skip'])
        {
            
        }
        else
        {
            $class = $page_children ? "folder" : "page";
            if($ord) $class .= "_gray";
            if($page_home) $class .= " home";
            if($page_id == @$_GET['page_id']) $class .= " selected";
            
            $aa = $page_children ? "<b title='Свернуть/развернуть'></b>" : "";
            $sup = $page_children ? "<sup title='Количество дочерних страниц'>$page_children</sup>" : "";
            
            $ret .= "<li id=\"page-$page_id\" class=\"$class\" page_id=\"$page_id\" page_key=\"$page_key\"><a href=\"struct.php?page_id=$page_id\" page_id=\"$page_id\" page_key=\"$page_key\" title='Выбрать страницу'>$page_name</a>$sup".( ($parent == 0 && $page_children <=10 ) || @in_array($page_id, $_SESSION['triage_cpl']['struct_expand']) ? "<div class=\"subfolder visible\">".struct_tree($page_id)."</div>" : "<div class=\"subfolder\"></div>")."$aa</li>\r\n";
        }
    }
    $DB->free($result);
    $ret .= "</ul>\r\n";
    
    return $ret;
}

?>

<style type="text/css">
	/* #struct_tree { margin: 5px; padding: 5px; background-color: #eee; border: solid 1px #ccc; } */
    #struct_tree ul { margin: 0; padding: 2px; }	
        
	#struct_tree li { list-style-type: none; padding: 6px 0 0 24px; background-position: 4px 4px; background-repeat: no-repeat; position: relative; }
	#struct_tree li.folder { background-image: url('images/icons/16/folder_page.png'); }	
	#struct_tree li.folder_gray { background-image: url('images/icons/16/folder_page_gray.png'); }		
    #struct_tree li.page { background-image: url('images/icons/16/page.png'); }
	#struct_tree li.page_gray { background-image: url('images/icons/16/page_gray.png');  }
    #struct_tree li.loading { background-image: url('images/icons/16/ajax-loader.gif') !important; }
	
	#struct_tree .subfolder { display: none; }
	#struct_tree .visible { display: block; }
	    
    #struct_tree a { color: #000; text-decoration: none; }
    #struct_tree li.home > a { padding-left: 20px; background: url('images/icons/16/house.png') left center no-repeat;  }
    #struct_tree li.selected > a { font-weight: bold; border: dotted 1px black; }
    #struct_tree li b {
        position: absolute;
        top: 3px;
        left: 0;
        width: 22px;
        height: 18px;
        background: transparent url('images/icons/16/bullet_toggle_plus.png') left 7px no-repeat;  
        border: none;
        padding: 0;
        margin: 0;
        cursor: pointer;
    }
    #struct_tree li .subfolder.visible + b {
        background-image: url('images/icons/16/bullet_toggle_minus.png');
    }	
    #struct_tree li sup {
        vertical-align: top;
        margin-left: 5px;        
        padding: 0 3px;
        padding-bottom: 1px;
        background: #bbb;
        color: white;
        border-radius: 3px;
        font-size: 10px;
        cursor: default;
    }
</style>

<script type="text/javascript">
<!--
	$(document).ready(function(){
		$("#struct_tree li.folder b, #struct_tree li.folder_gray b").live("click", function(e){
			
            /*e = e || window.event;
            var offset = $(this).offset();            
            if( e.clientX-offset.left>24 ||  e.clientY-offset.top>18 ) return false;*/
            
            //var li = $(this);
            var li = $(this).parent("li");
			var sub =  li.find(".subfolder:first");
			
			if(sub.is(":visible")) 
			{
				$.post("ajax/struct_collapse.php", { page_id: li.attr("page_id") });
                sub.slideUp("slow", function(){ $(this).removeClass("visible") });
				return false;
			}

			if(sub.html()) sub.slideDown("slow", function(){ $(this).addClass("visible") });
			else
			{
				$("body").css("cursor", "wait");
                li.addClass("loading");
                sub.load("ajax/struct_folder.php", { parent_id: li.attr("page_id") }, function(){
                    li.removeClass("loading");
                    sub.slideDown("slow", function(){ $(this).addClass("visible") });
                    $("body").css("cursor", "");
                });				
			}
			return false;
		});
        
        $("#struct_tree a").live("click", function(){ 
            $("#struct_tree li").removeClass("selected"); 
            $(this).parent("li").addClass("selected"); 
            
            if(typeof(callback_params) != 'undefined')
            {
                callback_params = {id: $(this).attr("page_id"), key: $(this).attr("page_key"), name: $(this).text()};                
            } 
            
            return false; 
        });
	});
//-->
</script>

<div id="struct_tree">

<?php echo struct_tree(); ?>

</div>