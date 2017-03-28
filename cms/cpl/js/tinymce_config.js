var tinymce_config_advanced = {
    // Location of TinyMCE script 
    script_url : '../editor/tinymce/jscripts/tiny_mce/tiny_mce.js',
    // Lang
    language : "ru",
    // General options
	gecko_spellcheck : true,
    theme : "advanced",
    plugins : "safari,pagebreak,style,table,advhr,advimage,advlink,insertdatetime,media,searchreplace,contextmenu,paste,visualchars,nonbreaking,xhtmlxtras",//inlinepopups", 
    // Theme options
    theme_advanced_buttons1 : "code,|,cut,copy,paste,pastetext,pasteword,|,cleanup,removeformat,|,undo,redo,|,link,unlink,anchor,image,media,advhr,charmap,nonbreaking,|,tablecontrols,|,search,replace,|,pagebreak,visualchars,visualaid,|,help",
    theme_advanced_buttons2 : "styleprops,formatselect,fontselect,fontsizeselect,bold,italic,underline,strikethrough,|,sub,sup,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,blockquote,|,forecolor,backcolor,|,cite,abbr,acronym,del,ins,attribs",
    theme_advanced_buttons3 : "",
    theme_advanced_buttons4 : "", 
    theme_advanced_toolbar_location : "top", 
    theme_advanced_toolbar_align : "left", 
    theme_advanced_statusbar_location : "bottom", 
    theme_advanced_resizing : true,
    theme_advanced_blockformats : "p,div,h1,h2,h3,h4,h5,h6,address,pre,code,samp",               
    // URLs
    relative_urls : true, // Default value 
    convert_urls : false, // to allow absolute urls inside document_base_url 
    document_base_url : typeof(tinymce_base_url) != 'undefined'? tinymce_base_url : '',
    content_css : "cms/templates/default.css",
    // format
    apply_source_formatting : true,
    // callbacks
    file_browser_callback : "mceFileBrowserCallback",
    //
    cms_material_id : 0
};

var tinymce_config_simple = jQuery.extend({}, tinymce_config_advanced);
tinymce_config_simple.theme = "simple";

var tinymce_config_exttoolbar = jQuery.extend({}, tinymce_config_advanced);
tinymce_config_exttoolbar.theme_advanced_toolbar_location = "external";

function mceFileBrowserCallback(field_name, url, type, win) {
    //alert("Field_Name: " + field_name + "\nURL: " + url + "\nType: " + type + "\nWin: " + win);
    // http://wiki.moxiecode.com/index.php/TinyMCE:Custom_filebrowser 

    var cmsURL = '../../../../../../cpl/mce-browser.php?type='+type+'&url='+url;
    
    tinyMCE.activeEditor.windowManager.open({
        file : cmsURL,
        title : 'Triage CMS File Browser',
        width : 500,  // Your dimensions may differ - toy around with them!
        height : 500,
        resizable : "yes",
        inline : "yes",  // This parameter only has an effect if you use the inlinepopups plugin!
        close_previous : "no",
        popup_css : false
    }, {
        window : win,
        input : field_name
    });
    return false;
}