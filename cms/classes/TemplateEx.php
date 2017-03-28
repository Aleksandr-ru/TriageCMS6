<?php

/**
 * Класс редактирования шаблона
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once(dirname(__FILE__)."/ITM.php");

class TemplateEx
{
    private $row = array();
    private $special = false;
    private $variables = array();
    private $old_filename = "";
    private $new_contents = "";
        
    function __construct($tmpl_id, $special = false)
    {
        global $DB;
        
        if(preg_match("/special:([0-9]+)/i", $tmpl_id, $arr)) {
            $this->special = true;
            $this->row['id'] = $arr[1];
            $this->row['file'] = $arr[1].".html";
        } elseif($special) {
            $this->special = true;
            $this->row['id'] = $tmpl_id;
            $this->row['file'] = $tmpl_id.".html";          
        } elseif(intval($tmpl_id)) {
            $this->row = $DB->getRow("SELECT * FROM ".$DB->T('_templates')." WHERE `id`=".$DB->F($tmpl_id), true);
            $this->old_filename = $this->row['file'];
        }
        $this->loadVariables();
    }
    
    function isSpecial()
    {
        return $this->special;
    }
    
    function getId()
    {
        return $this->row['id'];
    }
    
    function getName()
    {
        if($this->isSpecial())
        {
            switch($this->getId())
            {
                case 301:
                case 302:                 
                    return $this->getId()." перенаправление";
                case 403:
                    return $this->getId()." доступ запрещен";
                case 404:
                    return $this->getId()." страница не найдена";         
            }
            return false;
        }
        else
        {
            return $this->row['name'];    
        }        
    }
    
    function getFile($fullpath = false, $fullpath_prefix = "")
    {
        if(!$this->row['file']) return false;
        return $fullpath ? preg_replace("@/+@", "/", $fullpath_prefix."/cms/templates/".$this->row['file']) : $this->row['file'];
    }
    
    function getFolder($fullpath = false, $fullpath_prefix = "")
    {        
        if($this->isSpecial() || !$this->row['file']) return false;
        
        $folder_name = $this->file_name($this->row['file']);
        return $fullpath ? preg_replace("@/+@", "/", $fullpath_prefix."/cms/templates/".$folder_name) : $folder_name;
    }
        
    /*
    function getCssFile($fullpath = false, $fullpath_prefix = "")
    {
        if(!$this->row['css_file']) return false;
        return $fullpath ? preg_replace("@/+@", "/", $fullpath_prefix."/cms/templates/".$this->row['css_file']) : $this->row['css_file'];
    }
    
    function getCssOptions()
    {
        global $_ROOT;
        
        $ret = "";
        $dir = scandir("$_ROOT/cms/templates");
        foreach($dir as $filename)
        {
            if(preg_match("/.+\.css$/i", $filename) && is_file("$_ROOT/cms/templates/$filename"))
            {
                $ret .= "<option value=\"$filename\" ".($filename == $this->row['css_file'] ? "selected" :"").">$filename</option>\r\n";
            }
        }
        return $ret;
    }
    */
    
    function getContents()
    {
        global $_ROOT;
        
        if($this->row['file'] && is_readable($this->getFile(true, $_ROOT)))
        {
            return file_get_contents($this->getFile(true, $_ROOT));
        }
        else
        {
            return false;    
        }
    }
    
    /**
     * TemplateEx::getAllVariableNames() получить массив всех переменных на странице
     * 
     * @return массив, содержащий названия всех перменных, включая системные
     */
    function getAllVariableNames()
    {
        return $this->variables;
    }
    
    /**
     * TemplateEx::getVariableNames() получить массив переменных на странице
     * переменные с префиксом CMS_ считаются системными и не выбираются
     * 
     * @return массив, содержащий названия перменных, за исключением системных
     */
    function getVariableNames()
    {
        $arr = array();
        foreach($this->variables as $var)
        {
            //if(!eregi("^CMS_", $var)) -- php 5.3 deprecated            
            if(!preg_match("/^CMS_/i", $var))
            {
                $arr[] = $var;    
            }
        }
        sort($arr);
        return $arr;
    } 
    
    private function loadVariables()
    {
        global $_ROOT;
        
        if(!$this->getFile()) return false;
        $file = $this->getFile(true, $_ROOT);
        if(is_file($file))
        {
            $tpl = new HTML_Template_IT("$_ROOT/cms/templates");
            $tpl->loadTemplatefile($this->row['file'], true, true);
            
            // переменные
            while(list($var_name) = each($tpl->blockvariables['__global__']))
            {                
                if(!preg_match("/^__.+__$/", $var_name))
                {
                    $this->variables[] = $var_name;
                }
            }
            reset($tpl->blockvariables['__global__']);
            
            $tpl->free();
            unset($tpl);  
            return true;            
        }
        else
        {
            Debugger::mes(1, "Can't open template file '$file'!", __FILE__, __LINE__, "TemplateEx::loadVariables()");
            return false;    
        }
    } 
    
    private function makeFilename($filename)
    {
        if(preg_match("/.+\.html$/i", $filename))    return $filename;
        elseif(preg_match("/.+\.htm$/i", $filename)) return $filename."l";
        else                                         return $filename.".html";
    }
    
    function checkFilename($value, $is_fullpath = false)
    {
        global $_ROOT;
        
        $value = $this->makeFilename($value);
        
        if($is_fullpath)
        {
            $short_name = array_pop(preg_split("@(\|/)+@", $value));
            $full_name = $value;     
        }
        else
        {
            $short_name = $value;
            $full_name = preg_replace("@/+@", "/", "$_ROOT/cms/templates/$value");   
        }
        
        if($value == $this->old_filename) return true;
        elseif(is_file($full_name)) return false;
        else return true;
        
    }
    
    /**
     * TemplateEx::file_name() получить имя файла из пути
     * поскольку константа PATHINFO_FILENAME была введена только в PHP 5.2.0
     * 
     * @param string $path
     * @param bool $extenstion
     * @return
     */
    private function file_name($path, $extenstion = false)
    {
        if (version_compare(PHP_VERSION, '5.2.0', '>=')) {
            return $extenstion ? pathinfo($this->row['file'], PATHINFO_BASENAME) : pathinfo($this->row['file'], PATHINFO_FILENAME);
        }
        
        $filename = explode("/", $path);
        $filename = array_pop($filename);
        if($extenstion) return $filename;
        
        $filename = explode(".", $filename);
        return array_shift($filename);
    }
    
    function update()
    {
        global $DB, $_ROOT;
        
        if(!$this->getId()) return false;
        
        if($this->old_filename && $this->old_filename != $this->row['file'] && !$this->isSpecial() && $this->checkFilename($this->row['file']))
        {                  
            $old_fullpath = $_ROOT."/cms/templates/".$this->old_filename;
            $new_fullpath = $_ROOT."/cms/templates/".$this->row['file']; 
            if( !rename($old_fullpath, $new_fullpath) )
            {
                $this->row['file'] = $this->old_filename;
                Debugger::mes(6, "Can't rename template file from '".$this->old_filename."' to '".$this->row['file']."'", __FILE__, __LINE__, "TemplateEx::update()");
            }            
        }
                
        if(!$this->isSpecial())
        {
            $old_dir = $_ROOT."/cms/templates/".$this->file_name($this->old_filename);
            $new_dir = $_ROOT."/cms/templates/".$this->file_name($this->row['file']);
            
            if(is_dir($old_dir))
            {
                if( ($old_dir != $new_dir) && !rename($old_dir, $new_dir)) {
                    Debugger::mes(60, "Can't rename template dir from '".$old_dir."' to '".$new_dir."'", __FILE__, __LINE__, "TemplateEx::update()");
                }
            }
            else
            {
                if(!mkdir($new_dir, 0777)) {
                    Debugger::mes(61, "Can't create directory '".$new_dir."'!", __FILE__, __LINE__, "TemplateEx::update()");
                }
            }
        }
        
        if($this->new_contents)
        {
            if(is_writable( $this->getFile(true, $_ROOT) ) || !is_file( $this->getFile(true, $_ROOT) ) )
            {
                if(function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');
                else Debugger::mes(80, "mbstring extension seems to be absent. File will be written as ANSI.", __FILE__, __LINE__, "TemplateEx::update()");
                
                if( !file_put_contents( $this->getFile(true, $_ROOT), $this->new_contents) )
                {
                    Debugger::mes(8, "New contents was NOT written to '".$this->getFile(true, $_ROOT)."'.", __FILE__, __LINE__, "TemplateEx::update()");
                }
            }
            else
            {
                Debugger::mes(7, "Can't save new contents to '".$this->getFile(true, $_ROOT)."', file is not wtiteable!", __FILE__, __LINE__, "TemplateEx::update()");
            }
        }
        
        $update = array();
        foreach($this->row as $key=>$value)
        {            
            if(!preg_match("/^[0-9]+$/", $key) && $key != "id")
            {
                $update[] = "`$key`=".$DB->F($value, true);
            }
        }
                        
        $sql = "UPDATE ".$DB->T('_templates')." SET ".implode(", ", $update)." WHERE `id`=".$DB->F($this->getId());
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    function setName($value, $commit = false)
    {
        if($this->isSpecial())
        {
            return false;    
        }
        
        if(!$value)
        {
            Debugger::mes(2, "Can't set empty NAME", __FILE__, __LINE__, "TemplateEx::setName($value, $commit)");
            return false;    
        }
        
        $this->row['name'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setFileName($value, $commit = false)
    {
        if($this->isSpecial())
        {
            Debugger::mes(10, "Can't change filename for special template!", __FILE__, __LINE__, "TemplateEx::setFileName($value, $commit)");
            return false;   
        }
        
        if(!$value)
        {
            Debugger::mes(3, "Can't set empty FILENAME", __FILE__, __LINE__, "TemplateEx::setFileName($value, $commit)");
            return false;    
        }
        elseif(!$this->checkFilename($value))
        {
            Debugger::mes(4, "File '".$this->makeFilename($value)."' is used by another template.", __FILE__, __LINE__, "TemplateEx::setFileName($value, $commit)");
            return false;    
        }
        
        $this->row['file'] = $this->makeFilename($value);
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setContents($value, $commit = false)
    {
        global $_ROOT;
        
        if(!$value)
        {
            Debugger::mes(5, "Can't set empty CONTENTS", __FILE__, __LINE__, "TemplateEx::setContents($value)");
            return false;    
        }
        
        if($this->row['file'] && (is_writable($this->getFile(true, $_ROOT)) || !is_file($this->getFile(true, $_ROOT)) ))
        {
            $this->new_contents = $value;
            return true;
        }
        else
        {
            Debugger::mes(11, "Can't set CONTENTS for file '".$this->getFile(true, $_ROOT)."'. Is it writeable?", __FILE__, __LINE__, "TemplateEx::setContents($value)");
            return false;    
        }
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function uploadTemplate($uploaded_filename, $commit = false)
    {
        if(!$uploaded_filename || !is_readable($uploaded_filename) || filesize($uploaded_filename) < 1)
        {
            Debugger::mes(9, "Can't read uploaded file '$uploaded_filename'.", __FILE__, __LINE__, "TemplateEx::uploadTemplate($uploaded_filename, $commit)");
            return false;  
        }
        
        $this->new_contents = file_get_contents($uploaded_filename);
                
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    /*
    function setCssFile($value, $commit = false)
    {        
        $this->row['css_file'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    */
    
    /**
     * TemplateEx::deleteTemplate()
     * удаляет шаблон и все его файлы
     * 
     * @return bool
     */
    function deleteTemplate()
    {
        global $DB, $_ROOT;
        
        if(!$this->getId() || $this->isSpecial()) return false;
        
        if($cnt = $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `template_id`=".$DB->F($this->getId()))) {
            Debugger::mes(553, "Unable to delete template. $cnt pages found!", __FILE__, __LINE__, "TemplateEx::deleteTemplate()");
            return false;
        }
        
        $file = $this->getFile(true, $_ROOT);
        $folder = $this->getFolder(true, $_ROOT);
        
        if(!$file) return false;
        
        if(is_dir($folder) && !$this->rrmdir($folder)) {
            Debugger::mes(551, "Unable to delete '$folder'", __FILE__, __LINE__, "TemplateEx::deleteTemplate()");
            return false;
        }
        
        if(!unlink($file)) {
            Debugger::mes(552, "Unable to delete '$file'", __FILE__, __LINE__, "TemplateEx::deleteTemplate()");
            return false;
        }
        
        $DB->query("DELETE FROM ".$DB->T('_templates')." WHERE `id`=".$DB->F($this->getId()) );
        if($DB->errno()) return false;
        
        $this->row = array();
        return true;
    }
    
    private function rrmdir($dir) //http://ru.php.net/manual/en/function.rmdir.php  
    { 
        if(is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach($objects as $object) { 
                if($object != "." && $object != "..") { 
                    if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
                } 
            } 
            reset($objects); 
            return rmdir($dir); 
        } else {
            return false;
        }
    }
}
?>