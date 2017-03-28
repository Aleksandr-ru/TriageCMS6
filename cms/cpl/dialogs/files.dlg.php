<?php

/**
 * Диалог выбора файла 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

session_start();

require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");
require_once("$_ROOT/cms/lib/cms.lib.php");
require_once("$_ROOT/cms/lib/cpl.lib.php");

require_once("$_ROOT/cms/classes/User.php");

$USER = new UserSession();

if(!$USER->checkGroup(getSetting('cpl_access_group')))
{
    cpl_redirect("login.php");
    exit ;
}


function files_tree($path = '')
{
    global $_ROOT;
    
    if( ($file = $_GET['file']) && is_file($_ROOT."/$file") ) {
        $fpath = explode('/', $file);
        $ffile = array_pop($fpath);
        $fpath = implode('/', $fpath);
    } else {
        $ffile = '';
    }
            
    $ret .= "<ul class='fileicon'>";
    $scan = scandir($_ROOT.'/'.$path);
    $dirs = array();
    $files = array();
    foreach($scan as $s)
    {
        if($s != "." && $s != ".." && is_dir($_ROOT."/$path/$s")) $dirs[] = $s;
        elseif( is_file($_ROOT."/$path/$s") ) $files[] = $s;
    }
    foreach($dirs as $s) {
        $fullpath = trim("$path/$s", '/');
        $class = ''; 
        $class2 = '';   
        
        if($ffile && strpos($fpath, $fullpath)===0) {
            $class = 'open';
            $class2 = 'visible';
        }
        
        $ret .= "<li class='folder $class' fullpath='$fullpath'><a href='#'>$s</a><div class='subfolder $class2'>";
        if($class == 'open') $ret .= files_tree($fullpath);
        $ret .= "</div></li>\r\n";    
    }
    foreach($files as $s) {
        $class = pathinfo($_ROOT."/$path/$s", PATHINFO_EXTENSION);
        $fullpath = trim("$path/$s", '/');
        if($fullpath == trim($fpath.'/'.$ffile, '/')) {
            $class .= ' selected';
        }
        $ret .= "<li class='file $class' fullpath='$fullpath'><a href='#' class='filename'>$s</a></li>\r\n";    
    }
        
    
    $ret .= "</ul>";
    return $ret;
}

?>

<style type="text/css">
	/* #struct_tree { margin: 5px; padding: 5px; background-color: #eee; border: solid 1px #ccc; } */
    #files_tree ul { margin: 0; padding: 2px 0; }	
        
	#files_tree li { list-style-type: none; background-position: left top; background-repeat: no-repeat; padding: 2px 0 3px 20px; }    
    #files_tree li.folder { background-image: url('images/icons/16/folder.png'); }	
    #files_tree li.open { background-image: url('images/icons/16/folder_magnify.png'); }
    #files_tree li.loading { background-image: url('images/icons/16/ajax-loader.gif') !important; }
	
	#files_tree .subfolder { display: none; }
	#files_tree .visible { display: block; }
	    
    #files_tree a { color: #000; text-decoration: none; }
    #files_tree li.selected a { font-weight: bold; border: dotted 1px black; }	
</style>

<link rel="stylesheet" type="text/css" href="templates/filetypes.css">

<script type="text/javascript">
<!--
	$(document).ready(function(){
		$("#files_tree li.folder").live("click", function(){
			var li = $(this);
			var sub =  li.find(".subfolder:first");
			
			if(sub.is(":visible")) 
			{
                li.removeClass("open");
                sub.slideUp("slow");
				return false;
			}

			if(sub.html())
            {
                li.addClass("open");
                sub.slideDown("slow"); 
            }
			else
			{
				$("body").css("cursor", "wait");
                li.addClass("loading");
                sub.load("ajax/files_folder.php", { path: $(this).attr("fullpath") }, function(){
                    li.removeClass("loading");
                    li.addClass("open");
                    sub.slideDown("slow");
                    $("body").css("cursor", "");
                });	
			}
			return false;
		});
        
        $("#files_tree a").live("click", function(){ 
            if(!$(this).hasClass('filename')) return ;
            
            $("#files_tree li").removeClass("selected"); 
            $(this).parent("li").addClass("selected"); 
            
            if(typeof(callback_params) != 'undefined')
            {
                var path = $(this).parent("li").attr('fullpath');
                callback_params = {name: $(this).text(), path: path};                
            } 
            
            return false; 
        });
	});
//-->
</script>

<div id="files_tree">

<?php echo files_tree(); ?>

</div>