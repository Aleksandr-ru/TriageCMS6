var tinymce_config_viseditor = jQuery.extend({}, tinymce_config_exttoolbar);
tinymce_config_viseditor.script_url = tinymce_base_url + 'cms/editor/tinymce/jscripts/tiny_mce/tiny_mce.js';
tinymce_config_viseditor.plugins += ",save";
tinymce_config_viseditor.theme_advanced_buttons2 = "save,cancel,|," + tinymce_config_viseditor.theme_advanced_buttons2;

tinymce_config_viseditor.save_onsavecallback = function(ed){
    
    var material_id = ed.getParam('cms_material_id');
    
    var div = $("#"+ed.id).parents(".cms_viseditor_material");
    
    var toolbar_div = div.find(".cms_viseditor_material_toolbar");
    var data_div = div.find(".cms_viseditor_material_data");
    var editor_div = div.find(".cms_viseditor_material_editor");
    
    $("body").css("cursor", "wait");
    $.post("cms/cpl/ajax/material_set_data.php?material_id="+material_id, {data: ed.getContent()}, function(data){
        if(data != "OK") {
            alert(data);  
            $("body").css("cursor", "auto");                  
        } else {
            data_div.load("cms/cpl/ajax/material_get_data.php?material_id="+material_id, function(){
                data_div.show();
                editor_div.html("<textarea></textarea>").hide();
                $("body").css("cursor", "auto");
            });
        }
        //$("body").css("cursor", "auto");
    });
    
    return tinymce_config_viseditor.save_oncancelcallback(ed);
}

tinymce_config_viseditor.save_oncancelcallback = function(ed){
    
    var div = $("#"+ed.id).parents(".cms_viseditor_material");
        
    var toolbar_div = div.find(".cms_viseditor_material_toolbar");
    var data_div = div.find(".cms_viseditor_material_html");
    var editor_div = div.find(".cms_viseditor_material_editor");  
    
    tinyMCE.execCommand('mceRemoveControl', false, ed.id); 
    
    toolbar_div.show();
    data_div.show();
    editor_div.html("<textarea></textarea>").hide();  
    return false;
}

jQuery(document).ready(function($){
    $(".cms_viseditor_material .cms_viseditor_material_data").click(function(){
        var old_status = window.status; 
        window.status = 'В визуальном редакторе это не кликается!';
        setTimeout(function(){ window.status=old_status; }, 1500);
        return false;
    });
    
    $(".cms_viseditor_material .cms_viseditor_material_toolbar .cms_viseditor_btnedit").click(function(){
        var material_id = $(this).parents(".cms_viseditor_material").attr("material_id");
        var material_type = $(this).parents(".cms_viseditor_material").attr("material_type");
        var toolbar_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_toolbar");
        var data_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_data");
        var editor_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_editor");
        
        var w = data_div.outerWidth();
        var h = data_div.outerHeight();
        if(h > $(window).height()-100) h = $(window).height()-100;
        
        if(material_type == 'html') {
            if(use_tinymce) {
                toolbar_div.hide();
                data_div.hide();
                editor_div.show();
                
                tinymce_config_viseditor.cms_material_id = material_id;
                editor_div.find("textarea").css({width: w, height: h}).val(data_div.html()).tinymce(tinymce_config_viseditor);                
            } else if(confirm('Редактор TinyMCE отключен.\nОткрыть стандартный редактор материала?')) {
                window.open('cms/cpl/material.php?material_id='+material_id);
            }
                
        } else if(material_type == 'text') {
            if( editor_div.find("textarea").is(":visible") ) return false;
            data_div.hide();
            editor_div.show();
            toolbar_div.find(".cms_viseditor_btnsave").show();
            
            editor_div.find("textarea").css({width: w, height: h}).val(data_div.html().trim());
            editor_div.find("textarea").tabby();       
        } else {
            if(confirm('Изменить этот материал в визуальном редакторе нельзя.\nОткрыть стандартный редактор материала?')) {
                window.open('cms/cpl/material.php?material_id='+material_id);
            }
        }       
                
        return false;
    }); 
    
    $(".cms_viseditor_material .cms_viseditor_material_toolbar .cms_viseditor_btndel").click(function(){ 
        var div = $(this).parents(".cms_viseditor_material");
        var material_id = $(this).parents(".cms_viseditor_material").attr("material_id");
        var var_name = $(this).parents(".cms_viseditor_material").attr("variable_name");
        var block_name = $(this).parents(".cms_viseditor_material").attr("block_name");
        var num = $(this).parents(".cms_viseditor_material").attr("place_number");
        var arr = window.location.href.match(/page_id=([0-9]+)/);
        var page_id = arr[1];
                
        var url;                
        if(num) url = "cms/cpl/ajax/struct_material_del.php?page_id="+page_id+"&block="+block_name+"&material_id="+material_id+"&num="+num;
        //else url = "cms/cpl/ajax/struct_variable_del.php?page_id="+page_id+"&variable="+var_name+"&material_id="+material_id;
        
        if(url && confirm('Убрать материал со страницы?')) {
            $("body").css("cursor", "wait");
            $.post(url, {}, function(data){
                if(data != "OK") {
                    alert(data);                    
                } else {
                    div.remove();
                }
                $("body").css("cursor", "auto");
            });
        }
        
        return false;
    }); 
    
    $(".cms_viseditor_material .cms_viseditor_material_toolbar .cms_viseditor_btnsave").click(function(){ 
        var material_id = $(this).parents(".cms_viseditor_material").attr("material_id");
        var toolbar_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_toolbar");
        var data_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_data");
        var editor_div = $(this).parents(".cms_viseditor_material").find(".cms_viseditor_material_editor");
        
        $("body").css("cursor", "wait");
        $.post("cms/cpl/ajax/material_set_data.php?material_id="+material_id, {data: editor_div.find("textarea").val()}, function(data){
            if(data != "OK") {
                alert(data);  
                $("body").css("cursor", "auto");                  
            } else {
                data_div.load("cms/cpl/ajax/material_get_data.php?material_id="+material_id, function(){
                    data_div.show();
                    editor_div.hide();
                    toolbar_div.find(".cms_viseditor_btnsave").hide();
                    $("body").css("cursor", "auto");
                });
            }
            //$("body").css("cursor", "auto");
        });
        
        return false;
    }); 
});