<?php

/**
 * Класс который занимается обработкой материалов CMS
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2013
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../lib/db.lib.php");
require_once(dirname(__FILE__)."/../lib/plugins.lib.php");
require_once(dirname(__FILE__)."/Plugin.php");

class Material
{
    private $row = array();
    private $types = array(CMS_MATERIAL_TYPE_TEXT, CMS_MATERIAL_TYPE_HTML, CMS_MATERIAL_TYPE_CSS, CMS_MATERIAL_TYPE_JS, CMS_MATERIAL_TYPE_PLUGIN);
    /*
    function Material($id, $type = null, $data = null)
    {
        global $DB;
        
        if(!$id) return false;
        
        if($type) 
        {
            $this->row = array('id'=>$id, 'type'=>$type, 'data'=>$data);
            return true;
        }
        else
        {
            $sql = "SELECT m.id, m.type, m.data FROM ".$DB->T('_material')." AS m WHERE(m.id=".$DB->F($id).")";
            $result = $DB->query($sql);
            if($DB->num_rows($result) < 1) return false;
            $this->row = $DB->fetch(true, false, $result);
            $DB->free($result);
            return true;
        }
    }
    */
    function __construct($id)
    {
        global $DB;
        
        $sql = "SELECT m.id, m.text, m.html, m.css, m.javascript, m.plugin FROM ".$DB->T('_material')." AS m WHERE(m.id=".$DB->F($id).")";
        $result = $DB->query($sql);
        if($DB->num_rows($result) < 1) return false;
        $this->row = $DB->fetch(true, false, $result);
        $DB->free($result);
        return true;
    }
    
    function get()
    {
        return $this->row;
    }
    
    function getId()
    {
        return $this->row['id'];
    }
    
    /**
     * Material::getType()
     * начиная с 6.2 функция возвращает тип по первому заполненному полю
     * 
     * @return type || false
     */
    function getType()
    {
        //return $this->row['type'];
        foreach($this->types as $type) if(isset($this->row[$type]) && $this->row[$type]) return $type;
        return false;
    }
    
    /**
     * Material::getData()
     * 
     * @param string $type обязательно указывать чтоб не получить первый заполненный
     * @return data || false
     */
    function getData($type = null)
    {
        //return $this->row['data'];
        if(!$type) $type = $this->getType();
        return isset($this->row[$type]) && $this->row[$type] ? $this->row[$type] : false;
    }
    
    /**
     * Material::replaceData()
     * 
     * @param string $data
     * @param string $type
     * @return bool
     */
    function replaceData($data, $type = CMS_MATERIAL_TYPE_HTML)
    {
        //$this->row['data'] = $data;
        $this->row[$type] = $data;
        return true;
    }
    
    /**
     * Material::appendData()
     * 
     * @param string $data
     * @param string $type
     * @return void
     */
    function appendData($data, $type = CMS_MATERIAL_TYPE_HTML)
    {
        //$this->row['data'] .= $data;
        $this->row[$type] .= $data;
        return true;
    }
    
    function prependData($data, $type = CMS_MATERIAL_TYPE_HTML)
    {
        //$this->row['data'] = $data.$this->row['data'];
        $this->row[$type] = $data.$this->row[$type];
        return true;
    }
    
    function parse()
    {      
        if(!isset($this->row['id'])) return false;
        /*
        switch($this->row['type'])
		{
			case "text":
				//TODO: нужно-ли <pre> и strip_tags при выводе текста ?
                //return "\r\n<pre>".htmlspecialchars($this->row['data'])."</pre>\r\n";
                return "\r\n".$this->row['data']."\r\n";
				
			case "css":
				return "\r\n<style type=\"text/css\">\r\n".$this->row['data']."\r\n</style>\r\n";
				
			case "javascript":
				return "\r\n<script type=\"text/javascript\">\r\n".$this->row['data']."\r\n</script>\r\n";
				
			case "plugin":
                return parse_html($this->getPlugin(trim($this->row['data']), $this->getId()));
  
			case "html":
			default:
                return parse_html($this->row['data']);
		}
        */
        return 
            (isset($this->row[CMS_MATERIAL_TYPE_TEXT]) && $this->row[CMS_MATERIAL_TYPE_TEXT] 
                ? "\r\n".$this->row[CMS_MATERIAL_TYPE_TEXT]."\r\n" : '').
                
            (isset($this->row[CMS_MATERIAL_TYPE_HTML]) && $this->row[CMS_MATERIAL_TYPE_HTML] 
                ? parse_html($this->row[CMS_MATERIAL_TYPE_HTML]) : '').
                
            (isset($this->row[CMS_MATERIAL_TYPE_CSS]) && $this->row[CMS_MATERIAL_TYPE_CSS] 
                ? "\r\n<style type=\"text/css\">\r\n".$this->row[CMS_MATERIAL_TYPE_CSS]."\r\n</style>\r\n" : '').
                
            (isset($this->row[CMS_MATERIAL_TYPE_JS]) && $this->row[CMS_MATERIAL_TYPE_JS] 
                ? "\r\n<script type=\"text/javascript\">\r\n".$this->row[CMS_MATERIAL_TYPE_JS]."\r\n</script>\r\n" : '').
                
            (isset($this->row[CMS_MATERIAL_TYPE_PLUGIN]) && $this->row[CMS_MATERIAL_TYPE_PLUGIN] 
                ? parse_html($this->getPlugin($this->row[CMS_MATERIAL_TYPE_PLUGIN], $this->getId())) : ''); 
    }
        
    static function getPlugin($plugin_uid, $material_id = 0)
    {
        global $_config, $_ROOT, $_BASE;
        
        $plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.php";
        if(!is_file($plugin_file)) {            
            Debugger::mes(501, "Plugin file ($plugin_file) is absent for '$plugin_uid'.", __FILE__, __LINE__);
            return false;
        }
        
        include_once($plugin_file);
        
        $plugin_class = $plugin_uid."Plugin";
        if(!class_exists($plugin_class)) {
            Debugger::mes(502, "Plugin class for '$plugin_uid' does not exists.", __FILE__, __LINE__);
            return false;
        }
        
        $plugin = new $plugin_class($material_id);
        if(!method_exists($plugin, "get")) {
            Debugger::mes(503, "Plugin class method ($plugin_class::get) for '$plugin_uid' does not exists.", __FILE__, __LINE__);            
            return false;
        }
        
        return $plugin->isActive() ? $plugin->get() : false;
    }
}

?>