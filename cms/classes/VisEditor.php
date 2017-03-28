<?php

/**
 * Класс визуального редактора страницы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/Page.php");

require_once("$_ROOT/cms/lib/db.lib.php");

require_once(dirname(__FILE__)."/ITM.php");


class VisEditor extends Page
{    
    function __construct($id)
    {
        parent::__construct($id);          
    }
    
    /**
     * VisEditor::getEditor()
     * 
     * @return
     */
    function getEditor()
    {
        global $_ROOT, $_BASE, $DB;
        
        if($status = $this->status) {
            Debugger::mes(501, "Page status is $status, unable to edit!", __FILE__, __LINE__, "VisEditor::getEditor()");
            return "<h1>{$status}</h1>";
        }
        
        assert('is_file("$_ROOT/cms/templates/".$this->row["file"])');
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/templates");
        $tpl->loadTemplatefile($this->row['file'], true, true);
        
        //raise_event('viseditor_init', $this->id(), $tpl);
         
        /**
         * 1) установить переменные
         * @see Page::get()
         */
        /*$var_all = array();
        $var_used = array();
        
        while(list($var_name) = each($tpl->blockvariables['__global__'])) {
            if(!preg_match("/^__.+__$/", $var_name) && !preg_match("/^CMS_.+$/", $var_name)) {
                $var_all[] = $var_name;
            }
        }
        reset($tpl->blockvariables['__global__']);
        */
        $page_variables = array_keys($tpl->blockvariables['__global__']);
        //$sql = "SELECT var.name, var.material_id FROM (SELECT * FROM (SELECT * FROM ".$DB->T('_variables')." WHERE (`page_id`=".$DB->F($this->id())." OR `page_id`=0) ORDER BY `page_id` DESC) AS v GROUP BY v.name) AS var";
        $sql = "SELECT var.name, var.material_id FROM (SELECT * FROM ".$DB->T('_variables')." WHERE (`page_id`=".$DB->F($this->id())." OR `page_id`=0) ORDER BY `page_id` DESC) AS var GROUP BY var.name";
        $result = $DB->query($sql);
                 
        while(list($variable_name, $material_id) = $DB->fetch(false, false, $result)) {  
            if(in_array($variable_name, $page_variables)) { // проверим, есть-ли переменная на странице чтоб сократить нагрузку
                $tpl->setVariable($variable_name, $this->getMaterial($material_id, '', $variable_name, 0));
                //$var_used[] = $variable_name;
            } 
        }
        $DB->free($result);
        
        //$var_free = array_diff($var_all, $var_used);        
        
        /**
         * 2) установить материалы
         * @see Page::get()
         */ 
        $sql = "SELECT pm.place_number, pm.material_id, pm.order FROM ".$DB->T('_page_materials')." AS pm WHERE(pm.page_id=".$DB->F($this->id()).") ORDER BY pm.place_number, pm.order";
        $result = $DB->query($sql);
        
        while(list($place_number, $material_id, $material_order) = $DB->fetch(false, false, $result)) {
            $block_name = "materials".$place_number;
            $variable_name = "MATERIAL".$place_number;             
            
            $tpl->setCurrentBlock($block_name);            
            $tpl->setVariable($variable_name, $this->getMaterial($material_id, $block_name, $variable_name, $material_order));
            $tpl->parse($block_name);                        
        }
        $DB->free($result);
              
        /**
         * 3) системные переменные
         * @see Page::get()
         */        
        $tpl->setVariable("CMS_SITE_TITLE",    "Визуальный редактор");
        //$tpl->setVariable("CMS_REDIRECT",      $this->row['redirect']);
        $tpl->setVariable("CMS_KEYWORDS",      $this->getKeywords());
    	$tpl->setVariable("CMS_DESCRIPTION",   $this->getDescription());
    	$tpl->setVariable("CMS_PAGE_NAME",     $this->getName());
    	$tpl->setVariable("CMS_PAGE_TITLE",    $this->getTitle());
    	$tpl->setVariable("CMS_HTTP_BASE",     make_base($_BASE));
        $tpl->setVariable("CMS_CSS_FILE",      $this->getCssHref());
        $tpl->setVariable("CMS_TEMPLATE_PATH", $this->getTemplatePath());
        $tpl->setVariable("CMS_GENERATOR",     "Triage CMS ".substr(CMS_VERSION, 0, strpos(CMS_VERSION, ".", 2)));
        
        //raise_event('viseditor_complete', $this->id(), $tpl);
        
        $append = array();
        $append[] = "<link rel='stylesheet' type='text/css' href='".make_base($_BASE)."cms/cpl/templates/viseditor.css'>";
        $append[] = "<script type='text/javascript' src='".make_base($_BASE)."cms/scripts/jquery.js'></script>";
        $append[] = "<script type='text/javascript' src='".make_base($_BASE)."cms/scripts/jquery.tabby.js'></script>";
        $append[] = "<script type='text/javascript' src='".make_base($_BASE)."cms/editor/tinymce/jscripts/tiny_mce/jquery.tinymce.js'></script>";
        $append[] = "<script type='text/javascript'>var tinymce_base_url = '".make_base($_BASE)."';</script>";
        $append[] = "<script type='text/javascript'>var use_tinymce = ".intval(getSetting("use_tinymce")).";</script>";
        $append[] = "<script type='text/javascript' src='".make_base($_BASE)."cms/cpl/js/tinymce_config.js'></script>";
        $append[] = "<script type='text/javascript' src='".make_base($_BASE)."cms/cpl/js/viseditor.js'></script>";
        $append = implode("\r\n", $append);
        
        $html = $tpl->get();
        $c = 1;
        if( stripos($html, '<head>') !== false ) {
            $html = str_ireplace('<head>', "<head>\r\n".$append, $html, $c);
        } 
        /*elseif( stripos($html, '<body>') !== false ) {
            $html = str_ireplace('<body>', $append."\r\n<body>", $html, $c);
        } */
        else {
            $html = $append.$html;
        }
        return $html;
    }
    
    function showEditor()
    {
        echo $this->getEditor();
    }
    
    static function getMaterial($material_id, $block_name, $variable_name, $num = 0)
    {
        global $_ROOT;
        
        $material = new Material($material_id);
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
        $tpl->loadTemplatefile('viseditor-material.html', true, true);
        
        $tpl->setVariable("BLOCK_NAME", $block_name);
        $tpl->setVariable("VARIABLE_NAME", $variable_name);
        $tpl->setVariable("NUM", $num);
        $tpl->setVariable("MATERIAL_ID", $material->getId());
        $tpl->setVariable("MATERIAL_TYPE", $material->getType());
        $tpl->setVariable("MATERIAL_DATA", $material->parse());
                        
        if($num == 0) {
            $tpl->setVariable("VAR_DIS", 'style="display: none;"');
        }
        
        return $tpl->get();
    }
}

?>