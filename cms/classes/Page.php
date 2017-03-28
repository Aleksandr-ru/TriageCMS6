<?php

/**
 * Класс вывода страницы
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once(dirname(__FILE__)."/ITM.php");
require_once(dirname(__FILE__)."/Material.php");

class Page
{
	protected $row = array();
	protected $path_keys = array();
    protected $path_ids = array();
    protected $status = 0;
    
    private $template_vars = array();

	function __construct($id)
	{
        global $DB, $USER;
        
        if(!$id) {
            $this->setStatus(404); 
            return ;     
        }
        		
        $sql = "SELECT p.*, t.file FROM ".$DB->T('_pages')." AS p LEFT JOIN ".$DB->T('_templates')." AS t ON p.template_id=t.id WHERE(p.id=".$DB->F($id).")";
		$result = $DB->query($sql);
                        
        if($DB->errno() || $DB->num_rows($result) != 1) {
            $this->setStatus(404);
        } else {
			$this->row = $DB->fetch(true, true, $result);
            
            if($this->row['redirect']) $this->setStatus(301); // Moved Permanently
        }                   
        
        $DB->free($result);
        
        if(!$USER->checkGroup($this->row['access_group'])) {
            $this->setStatus(403);
        }
	}
    
    /**
     * Page::setStatus()
     * 
     * @param integer $code - код HTTP статуса (404, 403)
     * @return void
     */
    function setStatus($code)
    {
        if($code) {
            $this->status = $code;
            $this->row['name'] = $code;
            $this->row['file'] = "$code.html";   
        }
    }
    
    /**
     * Page::setRedirect()
     * 
     * @param string $url
     * @param integer $code
     * @return void
     */
    function setRedirect($url, $code = 301)
    {
        $this->row['redirect'] = $url;
        $this->setStatus($code);
    }
            
    /**
     * Page::get()
     * 
     * @return html код сгенерированной страницы
     */    
    function get()
    {
        global $_config, $_ROOT, $_BASE;
        global $DB, $USER, $DEBUG;
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/templates");
        
        assert('is_file("$_ROOT/cms/templates/".$this->row["file"])');        
        $tpl->loadTemplatefile($this->row['file'], true, true);
        
        // поскольку плагины могут поменять статуст страницы, запоминаем текущий и далее если он поменяется сменим шаблон
        $old_status = $this->status;
        
        raise_event('page_init', $this->id(), $tpl);
        
        /**
         * 1) установить материалы первыми тк там могут быт плагины которые сменят статус и шаблон
         */
        //$sql = "SELECT pm.place_number, pm.material_id, m.type, m.data, m.access_group FROM ".$DB->T('_page_materials')." AS pm LEFT JOIN ".$DB->T('_material')." AS m ON pm.material_id=m.id WHERE(pm.page_id=".$DB->F($this->id())." AND m.active) ORDER BY pm.place_number, pm.order";  
        $sql = "SELECT pm.place_number, pm.material_id, m.access_group FROM ".$DB->T('_page_materials')." AS pm LEFT JOIN ".$DB->T('_material')." AS m ON pm.material_id=m.id WHERE(pm.page_id=".$DB->F($this->id())." AND m.active) ORDER BY pm.place_number, pm.order";
        $result = $DB->query($sql);
        
        //while(list($place_number, $material_id, $material_type, $material_data, $material_access_group) = $DB->fetch(false, false, $result)) {
        while(list($place_number, $material_id, $material_access_group) = $DB->fetch(false, false, $result)) {
            $block_name = "materials".$place_number;
            $variable_name = "MATERIAL".$place_number;
             
            if($USER->checkGroup($material_access_group)) {
                //$material = new Material($material_id, $material_type, $material_data);
                $material = new Material($material_id);
                raise_event('material_init', $material, $material_access_group);
                $tpl->setCurrentBlock($block_name);            
                $tpl->setVariable($variable_name, $material->parse());
                $tpl->parse($block_name);
                unset($material);    
            } else {
                raise_event('material_noaccess', $material_id, $material_access_group, $tpl, $block_name, $variable_name);
            }                                   
        }
        $DB->free($result);
        
        // 1.5 проверяем, не изменился-ли статус (и шаблон) после работы плагинов
        // и если изменился перезанружаем шаблон
        if($this->status && $old_status != $this->status) {
            $DEBUG->mes(50, "Template was changed during processing of Materials! Status {$this->status}", __FILE__, __LINE__);
            assert('is_file("$_ROOT/cms/templates/".$this->row["file"])');        
            $tpl->loadTemplatefile($this->row['file'], true, true);    
            $old_status = $this->status;                        
        }
        
        /**
         * 2) установить переменные
         * @abstract для шаблонов используются пеерменные с page_id = 0
         * таким образом уходит выставление переменный конкретно на шаблон,
         * а вместо этого просто общие переменные для всех шаблонов,
         * которые могут быть перекрыты на каждой конкретной странице
         */
        $page_variables = array_keys($tpl->blockvariables['__global__']);        
        
        //$sql = "SELECT * FROM (SELECT var.name, var.material_id, mat.type, mat.data, mat.access_group FROM ".$DB->T('_variables')." AS var JOIN ".$DB->T('_material')." AS mat ON mat.id = var.material_id WHERE(var.page_id=".$DB->F($this->id())." OR var.page_id=0) ORDER BY var.page_id DESC) AS v GROUP BY v.name";
        $sql = "SELECT * FROM (SELECT var.name, var.material_id, mat.access_group FROM ".$DB->T('_variables')." AS var JOIN ".$DB->T('_material')." AS mat ON mat.id = var.material_id WHERE(var.page_id=".$DB->F($this->id())." OR var.page_id=0) ORDER BY var.page_id DESC) AS v GROUP BY v.name";
        $result = $DB->query($sql);

        //while(list($variable_name, $material_id, $material_type, $material_data, $material_access_group) = $DB->fetch(false, false, $result)) {
        while(list($variable_name, $material_id, $material_access_group) = $DB->fetch(false, false, $result)) {
            if(in_array($variable_name, $page_variables)) { // проверим, есть-ли переменная на странице чтоб сократить нагрузку
                if($USER->checkGroup($material_access_group)) {
                    //$material = new Material($material_id, $material_type, $material_data);
                    $material = new Material($material_id);
                    raise_event('material_init', $material, $material_access_group);
                    $tpl->setVariable($variable_name, $material->parse());                                
                    unset($material);                
                } else {
                    raise_event('material_noaccess', $material_id, $material_access_group, $tpl, "", $variable_name);
                }          
            }          
        }
        $DB->free($result);
        
        // 2.5 проверяем, не изменился-ли статус (и шаблон) после работы плагинов
        // и если изменился перезанружаем шаблон
        if($this->status && $old_status != $this->status) {
            $DEBUG->mes(60, "Template was changed during processing of Variables! Status {$this->status}", __FILE__, __LINE__);
            assert('is_file("$_ROOT/cms/templates/".$this->row["file"])');        
            $tpl->loadTemplatefile($this->row['file'], true, true);    
            $old_status = $this->status;            
        }
                              
        /**
         * 3) системные переменные
         */        
        $tpl->setVariable("CMS_SITE_TITLE",    getSetting('site_title'));
        $tpl->setVariable("CMS_REDIRECT",      $this->row['redirect']);
        $tpl->setVariable("CMS_KEYWORDS",      $this->getKeywords());
    	$tpl->setVariable("CMS_DESCRIPTION",   $this->getDescription());
    	$tpl->setVariable("CMS_PAGE_NAME",     $this->getName());
    	$tpl->setVariable("CMS_PAGE_TITLE",    $this->getTitle());
    	$tpl->setVariable("CMS_PAGE_FULLPATH", $this->getFullPath());
    	$tpl->setVariable("CMS_HTTP_BASE",     make_base($_BASE));
        $tpl->setVariable("CMS_CSS_FILE",      $this->getCssHref());
        $tpl->setVariable("CMS_TEMPLATE_PATH", $this->getTemplatePath());
        $tpl->setVariable("CMS_GENERATOR",     "Triage CMS ".substr(CMS_VERSION, 0, strpos(CMS_VERSION, ".", 2)));
        
        /**
         * 4) кустом переменные
         */
        foreach($this->template_vars as $var_name=>$var_value) {
            $tpl->setVariable($var_name, $var_value);
        }
        
        raise_event('page_complete', $this->id(), $tpl);
        return $tpl->get();
    }
    
    /**
     * Page::show()
     * генерирует и выводит страницы в броузер
     * также при необходимости передается заголовок статуса
     * 
     * @return void
     * @see Page::setStatus()
     */
    function show()
    {
        global $_BASE;
        
        switch($this->status) {
            case 301:
            case 302: //TODO:нужен-ли 302
                if (substr(php_sapi_name(), 0, 3) == 'cgi') header('Status: 301 Moved Permanently', TRUE);
                else header($_SERVER['SERVER_PROTOCOL'].' 301 Moved Permanently');
                //TODO:нужно-ли header или meta+script в шаблоне достаточно
                $location = 
                    stripos($this->row['redirect'], '/')===0 || 
                    /*stripos($this->row['redirect'], 'http://')===0*/
                    preg_match("@^\w+://.+@i", $this->row['redirect']) 
                    ? $this->row['redirect'] : make_base($_BASE).$this->row['redirect'];
                header("Location: $location");
                break;
            case 403:
                if (substr(php_sapi_name(), 0, 3) == 'cgi') header('Status: 403 Forbidden', TRUE);
                else header($_SERVER['SERVER_PROTOCOL'].' 403 Forbidden');
                break;
            case 404:
                if (substr(php_sapi_name(), 0, 3) == 'cgi') header('Status: 404 Not Found', TRUE);
                else header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
                break;
            default:
                // ничего не делать
        }
        
        echo $this->get();
    }
    
    function getName()
    {
        return $this->row['name'];
    }
    
    function getTitle()
    {
        return $this->row['title'] ? $this->row['title'] : $this->getName(); 
    }
    
    /**
     * Page::setTitle()
     * меняет заголовок страницы
     * ко всем параметрам применяется htmlspecialchars
     * 
     * @param string $str
     * @param bool $append, если true то $str дописывается в конец
     * @param string $glue, если текущий title не пустой и $append, то получается 'title . $glue . $str' 
     * @return новый title
     */
    function setTitle($str, $append = false, $glue = ' ')
    {
        if($append && !$this->row['title']) $this->row['title'] = $this->getName();
        return $append ? $this->row['title'] .= htmlspecialchars($glue.$str) : $this->row['title'] = htmlspecialchars($str); 
    }
    
    function getKeywords()
    {
        return $this->row['meta_keywords'];
    }
    
    /**
     * Page::setKeywords()
     * меняет ключенвые слова
     * ко всем параметрам применяется htmlspecialchars
     * 
     * @param string $str
     * @param bool $append, если true то $str дописывается в конец
     * @param string $glue, если текущиe keywords не пустые и $append, то получается 'keywords . $glue . $str' 
     * @return новые keywords
     */
    function setKeywords($str, $append = false, $glue = ', ')
    {
        if($append && !$this->row['meta_keywords']) $glue = '';
        return $append ? $this->row['meta_keywords'] .= htmlspecialchars($glue.$str) : $this->row['meta_keywords'] = htmlspecialchars($str); 
    }
    
    function getDescription()
    {
        return $this->row['meta_description'];
    }
    
    /**
     * Page::setDescription()
     * меняет описание
     * ко всем параметрам применяется htmlspecialchars
     * 
     * @param string $str
     * @param bool $append, если true то $str дописывается в конец
     * @param string $glue, если текущий description не пустой и $append, то получается 'description . $glue . $str' 
     * @return новый description
     */
    function setDescription($str, $append = false, $glue = ' ')
    {
        if($append && !$this->row['meta_description']) $glue = '';
        return $append ? $this->row['meta_description'] .= htmlspecialchars($glue.$str) : $this->row['meta_description'] = htmlspecialchars($str); 
    }
    
    function getCssHref()
    {
        //global $_config, $_ROOT, $_BASE;
        //return $_BASE."/cms/templates/".$this->row['css_file'];
        //return preg_replace("@([^:])//+@", "$1/", $_BASE."/cms/templates/default.css");     
        return make_base("/cms/templates")."default.css";       
    }
    
    function getTemplateFile($extension = true)
    {
        if($extension){
            return $this->row['file'];    
        } 
        else {
            $file = explode(".", $this->row['file']);
            array_pop($file);
            return implode(".", $file);
        }
    }
    
    function getTemplatePath()
    {
        //global $_config, $_ROOT, $_BASE;    
        
        if($this->status) return false;
        
        $file = explode(".", $this->row['file']);
        array_pop($file);    
        //return preg_replace("@([^:])//+@", "$1/", $_BASE."/cms/templates/".implode(".", $file)."/");
        return make_base( "/cms/templates/".implode(".", $file) );
    }

	function id()
	{
		return isset($this->row['id']) ? $this->row['id'] : 0;
	}
    
    function getId() // совместимость с теипичными классами
    {
        return $this->id();
    }
    
    /**
     * Page::getTemplateVariable()
     * получить значение (добавленной)переменной шаблона
     * 
     * @param string $name
     * @return mixed value or false if not exists
     */
    function getTemplateVariable($name) 
    {
        return isset($this->template_vars[$name]) ? $this->template_vars[$name] : false;
    }
    
    /**
     * Page::setTemplateVariable()
     * добавить значенеи переменной к шаблону
     * значениями этих переменных могут быть заменены системные переменные
     * 
     * @param string $name
     * @param mixed $value
     * @param bool $overwrite - перезаписать если существует
     * @return bool
     */
    function setTemplateVariable($name, $value, $overwrite = true) 
    {
        if($overwrite || !isset($this->template_vars[$name])) {
            $this->template_vars[$name] = $value;
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Page::path2id()
     * получить ID старницы по ее пути
     * подходит для обработки $_GET['rewrite_path']
     * если путь пустой или '/' возвращается ID домашней страницы
     * если путь некорректный (содержит несуществующие страницы) возвращает false
     * 
     * @param string $path
     * @return int page_id или false в случае ошибки
     * @see .htaccess
     */
    static function path2id($path)
    {
        global $DB;
      
        //$path = eregi_replace("\.html$", "", $path); -- php 5.3 deprecated
        $path = preg_replace("/\.html$/i", "", $path); // отсекаем .html если есть
        $path = trim($path, "/");
        $path = preg_split("/\//", $path, -1, PREG_SPLIT_NO_EMPTY);
        
        if(sizeof($path) < 1){
            return $DB->getField("SELECT p.id FROM ".$DB->T('_pages')." AS p WHERE(p.is_home)");    
        } 
         
        $id = 0;
        foreach($path as $key) {
            //if(ereg("^[0-9]+$", $key)) -- php 5.3 deprecated
            if(preg_match("/^\d+$/", $key)) {
                $id = $key;
            } else {
                $id = $DB->getField("SELECT p.id FROM ".$DB->T('_pages')." AS p WHERE p.parent_id=".$DB->F($id)." AND p.key LIKE ".$DB->F($key));
            }
            
            if(!$id) return false;
        }
        return $id;
    }
    
    /**
     * Page::path_ids()
     * возвращает массив из ID страниц, содержащихся в пути до страницы (включительно)
     * 
     * @param int $page_id
     * @return array
     */
    static function path_ids($page_id)
    {
        global $DB;
    
        $id = $page_id;
        $path = array();
        while($id != 0) {
            $sql = "SELECT `id`, `parent_id` FROM ".$DB->T('_pages')." WHERE(`id`=".$DB->F($id).")";
            $result = $DB->query($sql);
            list( , $parent_id) = $DB->fetch(false, true, $result);
            $DB->free($result);
            
            $path[] = $id;
            $id = $parent_id;
        }
        return array_reverse($path);
    }
    
    /**
     * Page::getPathIds()
     * возвращает массив из ID страниц, содержащихся в пути до страницы (включительно)
     * 
     * @return array
     * @see Page::path_ids()
     */
    function getPathIds()
    {
        return $this->path_ids($this->id());
    }
    
    /**
     * Page::path_keys()
     * возвращает массив из ключей страниц, содержащихся в пути до страницы (включительно)
     * 
     * @param int $page_id
     * @return array
     */
    static function path_keys($page_id)
    {
        global $DB;
    
        $id = $page_id;
        $path = array();
        while($id != 0) {
            $sql = "SELECT `id`, `key`, `parent_id` FROM ".$DB->T('_pages')." WHERE(`id`=".$DB->F($id).")";
            $result = $DB->query($sql);
            list( , $page_key, $parent_id) = $DB->fetch(false, true, $result);
            $DB->free($result);
            
            $path[] = $page_key ? $page_key : $id;
            $id = $parent_id;
        }
        return array_reverse($path);
    }
    
    /**
     * Page::getPathKeys()
     * возвращает массив из ключей страниц, содержащихся в пути до страницы (включительно)
     * 
     * @return array
     * @see Page::path_keys()
     */
    function getPathKeys()
    {
        return $this->path_keys($this->id());
    }
    
    /**
     * Page::path()
     * возвращает путь страницы
     * концовка пути .html если нет дочерних страниц или '/' если они есть
     * через $suffix удобно передавать event или номер старницы     
     * 
     * @param int $page_id
     * @param string $suffix задает концовку пути, есть есть - добавляется через '/' в конце
     * @return string path
     */
    static function path($page_id, $suffix = "")
    {
        global $DB;
        //концовка пути .html если нет дочерних страниц или '/' если они есть
        $num_children = $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($page_id));
        $home_id = $DB->getField("SELECT p.id FROM ".$DB->T('_pages')." AS p WHERE(p.is_home)");
        
        if($home_id == $page_id) {
            return preg_replace("@^/+@", "", $suffix);
        } else {
            $suffix = preg_replace("@^/+@", "", $suffix);
            return implode("/", self::path_keys($page_id)).($suffix ? "/".$suffix : "").(preg_match("@.+(\.[a-z]+|/)$@i", $suffix) ? "" : ($num_children ? "/" : ".html"));    
        }
    }
    
    /**
     * Page::getPath()
     * возвращает путь страницы
     * 
     * @param string $suffix задает концовку пути
     * @return string path
     * @see Page::path()
     */
    function getPath($suffix = "")
    {
        return $this->path($this->id(), $suffix);
    }
    
    /**
     * Page::fullpath()
     * возвращает полный путь страницы начиная с HTTP_BASE
     * 
     * @param int $page_id
     * @param string $suffix задает концовку пути, которая передается в Page::path()
     * @return string path
     * @see Page::path()
     */
    static function fullpath($page_id, $suffix = "")
    {
        global $_BASE;
        return make_base($_BASE).self::path($page_id, $suffix);
    }
    
    /**
     * Page::getFullPath()
     * возвращает полный путь страницы начиная с HTTP_BASE
     * 
     * @param string $suffix задает концовку пути
     * @return string path
     * @see Page::fullpath()
     */
    function getFullPath($suffix = "")
    {
        return $this->fullpath($this->id(), $suffix);
    }
    
    function getNumChildren($active_only = true)
    {
        global $DB;
        return $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($this->getId().($active_only ? " AND `order`>0" : "")));
    }
    
    function getParentId()
    {
        return $this->row['parent_id'];
    }
}
?>