<?php

/**
 * Класс редактора страницы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");

require_once("$_ROOT/cms/lib/db.lib.php");

require_once(dirname(__FILE__)."/ITM.php");


class PageEx
{
    private $row = array();
    private $variables = array();
    private $materials = array();
    
    function __construct($id)
    {
        global $DB, $_ROOT;
        
        $sql = "SELECT * FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($id);
        $this->row = $DB->getRow($sql, true);
        assert('sizeof($this->row)');
        
        $template_file = $DB->getField("SELECT t.file FROM ".$DB->T('_templates')." AS t WHERE(t.id=".$DB->F($this->getTemplateId()).");");
        if(!is_file("$_ROOT/cms/templates/$template_file"))
        {
            Debugger::mes(1, "$template_file not found in $_ROOT/cms/templates!", __FILE__, __LINE__, "PageEx($id)");
        }
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/templates");
        $tpl->loadTemplatefile($template_file, true, true);
        
        // материалы
        while(list($block_name) = each($tpl->blockvariables))
        {
            //if(ereg("^materials([0-9]+)$", $block_name, $regs)) -- php 5.3 deprecated
            if(preg_match("/^materials(\d+)$/", $block_name, $regs))
            {
                $this->materials[$regs[1]] = $block_name;
            }
        }
        reset($tpl->blockvariables);
        ksort($this->materials);
        
        // переменные
        while(list($var_name) = each($tpl->blockvariables['__global__']))
        {
            //if(!ereg("^__.+__$", $var_name)) -- php 5.3 deprecated
            if(!preg_match("/^__.+__$/", $var_name))
            {
                $this->variables[] = $var_name;
            }
        }
        reset($tpl->blockvariables['__global__']);
        
        $tpl->free();
        unset($tpl);        
    }
    
    static function createNew($parent_id = 0, $template_id = 0, $page_name = "Новая страница")
    {
        global $DB;
                
        $page_id = $DB->new_id($DB->T('_pages'));
        $sql = "INSERT INTO ".$DB->T('_pages')." (`parent_id`, `name`, `key`, `order`, `template_id`) VALUES(".$DB->F($parent_id).", ".$DB->F($page_name).", ".$DB->F($page_id).", 0, ".$DB->F($template_id).");";
        $DB->query($sql);
        if($DB->insert_id() == $page_id) {            
            return $page_id;
        } else {
            return false;
        }
    }
    
    function getId()
    {
        return $this->row['id'];
    }
    
    function numChildren()
    {
        global $DB;
        return $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($this->row['id']));
    }
    
    function getName()
    {
        return $this->row['name'];
    }
    
    function getTitle()
    {
        return $this->row['title'];
    }
    
    function getParentId()
    {
        return $this->row['parent_id'];
    }
    
    function getParentName()
    {
        global $DB;
        if($this->row['parent_id'])
        {
            return $DB->getField("SELECT `name` FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($this->row['parent_id']));
        }
        else
        {
            return "";
        }
    }
    
    /**
     * PageEx::getKey() получить ключ страницы
     * если ключ == id, то функция возвращает пустую строку ''
     * 
     * @return значение ключа
     */
    function getKey()
    {
        //return ereg("^[0-9]+$", $this->row['key']) ? "" : $this->row['key'];
        return preg_match("/^\d+$/", $this->row['key']) ? "" : $this->row['key'];
    }
    
    function isHome()
    {
        return $this->row['is_home'] ? true : false;
    }
    
    function isActive()
    {
        return $this->row['order'] ? true : false;
    }
    
    function getOrder()
    {
        return $this->row['order'];
    }
    
    function getTemplateId()
    {
        return $this->row['template_id'];
    }
    
    function getTemplateOptions()
    {
        global $DB;
        $ret = "";
        $sql = "SELECT t.id, t.name FROM ".$DB->T('_templates')." AS t ORDER BY t.name";
        $result = $DB->query($sql);
        while(list($tmpl_id, $tmpl_name) = $DB->fetch(false,true,$result))
        {
            $ret .= "<option value=\"$tmpl_id\" ".($tmpl_id==$this->row['template_id'] ? "selected":"").">$tmpl_name</option>\r\n";
        }
        $DB->free($result);
        return $ret;
    }
    
    function getRedirect()
    {
        return $this->row['redirect'];
    }
    
    function getKeywords()
    {
        return stripslashes($this->row['meta_keywords']);
    }
    
    function getDescription()
    {
        return stripslashes($this->row['meta_description']);
    }
    
    /**
     * PageEx::getAllVariableNames() получить массив всех переменных на странице
     * 
     * @return массив, содержащий названия всех перменных, включая системные
     */
    function getAllVariableNames()
    {
        return $this->variables;
    }
    
    /**
     * PageEx::getVariableNames() получить массив переменных на странице
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
    
    function getMaterialBlocks()
    {
        return $this->materials;        
    }
    
    function getAccessGroupId()
    {
        return $this->row['access_group'];
    }
    
    function getAccessGroupOptions()
    {
        global $DB;
        $ret = "<option value=\"0\">Все посетители сайта</option>\r\n";
        $sql = "SELECT g.id, g.name FROM ".$DB->T('_groups')." AS g ORDER BY g.name";
        $result = $DB->query($sql);
        while(list($group_id, $group_name) = $DB->fetch(false, true, $result))
        {
            $ret .= "<option value=\"$group_id\" ".($group_id==$this->getAccessGroupId() ? "selected":"").">$group_name</option>\r\n";
        }
        $DB->free($result);
        return $ret;
    }
    
    function update()
    {
        global $DB;
        
        if(!$this->getId()) return false;
        
        $update = array();
        foreach($this->row as $key=>$value)
        {            
            if(!preg_match("/^[0-9]+$/", $key) && $key != "id")
            {
                $update[] = "`$key`=".$DB->F($value, true);
            }
        }
        
        if($this->row['is_home'])
        {
            $sql = "UPDATE ".$DB->T('_pages')." SET `is_home`=0";
            $DB->query($sql);
        }
        
        $sql = "UPDATE ".$DB->T('_pages')." SET ".implode(", ", $update)." WHERE `id`=".$DB->F($this->getId());
        $DB->query($sql);
        return $DB->errno() ? false : true;
    }
    
    /**
     * PageEx::checkKey() проверяет допустимость использования ключа
     * 
     * @param string $key - значение ключа для проверки
     * @param int $parent - id родителя группы страниц, в которой проверяется ключ, если не указан то используется родитель текущей страницы
     * @param bool $true_if_empty - возвращать TRUE в случае пустого ключа
     * @return (в зависимости от параметра $true_if_empty) TRUE если коюч является уникальным для группы страниц, FALSE если нет
     */
    function checkKey($key, $parent = null, $true_if_empty = true)
    {
        global $DB;
        
        if($key == '') return $true_if_empty;
        if(!isset($parent)) $parent = $this->getParentId();
        $bad_key = $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_pages')." WHERE `id`<>".$DB->F($this->getId())." AND `parent_id`=".$DB->F($parent)." AND `key` LIKE ".$DB->F($key));
        return $bad_key ? false : true; 
    }
    
    /**
     * PageEx::setParent() устанавливает родителя страницы (с проверкой уникальности ключа)
     * если ключ не является уникальным в группе нового родителя, обновление не производится
     * 
     * @param int $value - id родительской страницы
     * @param bool $commit - признак немедленного обновления БД
     * @return TRUE в случае успешного обновления, FALSE если ошибка или ключ не уникальный для нового родителя
     */
    function setParent($value, $commit = false)
    {
        if(!$this->checkKey($this->getKey(), $value))
        {
            Debugger::mes(100, "Key '".$this->getKey()."' is not unique (parent_id: $value)", __FILE__, __LINE__, "PageEx::setParent($value)");  
            return false;
        }
        
        $this->row['parent_id'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    /**
     * PageEx::setKey() устанавливает новый ключ страницы с проверкой уникальности
     * если ключ не уникаьный в группе, то обновление не производится
     * 
     * @param string $value - новое значение ключа
     * @param bool $commit - признак немедленного обновления БД
     * @return TRUE в случае успешного обновления, FALSE если ошибка или ключ не уникальный
     */
    function setKey($value, $commit = false)
    {
        if(!$this->checkKey($value))
        {
            Debugger::mes(101, "Key '$value' is not unique (parent_id: ".$this->getParentId().")", __FILE__, __LINE__, "PageEx::setKey($value)");  
            return false;
        }
        
        $this->row['key'] = $value ? $value : $this->getId();
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setName($value, $commit = false)
    {
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
    
    function setTitle($value, $commit = false)
    {
        $this->row['title'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setHome($value = true, $commit = false)
    {
        $this->row['is_home'] = $value ? 1 : 0;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setOrder($value, $commit = false)
    {
        $this->row['order'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    /**
     * PageEx::setOrderAuto() присвоить странице последний порядок сортировки на своем уровне
     * выбирается максимальный order в группе
     * странице присваевается максимальный order + 1
     * 
     * @param bool $commit - признак немедленного обновления БД
     * @return TRUE при успешном обновлении, FALSE если у станицы order > 0 или ошибка
     */
    function setOrderAuto($commit = false)
    {
        global $DB;
        
        if($this->getOrder())
        {
            return false;
        }
        else
        {
            $order = 1 + $DB->getField("SELECT MAX(`order`) FROM ".$DB->T('_pages')." WHERE `parent_id`=".$DB->F($this->getParentId())); 
            return $this->setOrder($order, $commit);   
        }
    }
    
    function setTemplate($value, $commit = false)
    {
        $this->row['template_id'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setAccessGroup($value, $commit = false)
    {
        $this->row['access_group'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setRedirect($value, $commit = false)
    {
        $this->row['redirect'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    /**
     * PageEx::setKeywords() устанавливает ключевые слова страницы
     * переводы строк заменяются пробелами
     * 
     * @param string $value - новое значение ключевых слов
     * @param bool $commit - признак немедленного обновления БД
     * @return TRUE в случае успешного обновления, FALSE если ошибка
     */
    function setKeywords($value, $commit = false)
    {
        $this->row['meta_keywords'] = preg_replace("/\r?\n/m", " ", $value);
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    /**
     * PageEx::setDescription() устанавливает описание страницы
     * переводы строк заменяются пробелами
     * 
     * @param string $value - новое значение описания
     * @param bool $commit - признак немедленного обновления БД
     * @return TRUE в случае успешного обновления, FALSE если ошибка
     */
    function setDescription($value, $commit = false)
    {
        $this->row['meta_description'] = preg_replace("/\r?\n/m", " ", $value);
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
}

?>