<?php

/**
 * Класс редактирования материалов CMS
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../lib/db.lib.php");

class MaterialEx
{
    private $row = array();
    private $types = array(CMS_MATERIAL_TYPE_TEXT, CMS_MATERIAL_TYPE_HTML, CMS_MATERIAL_TYPE_CSS, CMS_MATERIAL_TYPE_JS, CMS_MATERIAL_TYPE_PLUGIN);
    
    function MaterialEx($id)
    {
        global $DB;
        
        $sql = "SELECT * FROM ".$DB->T('_material')." AS m WHERE(m.id=".$DB->F($id).")";
        $result = $DB->query($sql);
        if($DB->num_rows($result) < 1) return false;
        $this->row = $DB->fetch(true, false, $result);
        $DB->free($result);
        return true;        
    }
    
    function getId()
    {
        return $this->row['id'];
    }
    
    function getName($escape = true)
    {
        return $escape ? htmlspecialchars($this->row['name']) : $this->row['name'];
    }
    
    /**
     * MaterialEx::getType()
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
     * MaterialEx::getDataRaw()
     * 
     * @param string $type обязательно указывать чтоб не получить первый заполненный
     * @return data || false
     */
    function getDataRaw($type = null)
    {
        //return $this->row['data'];
        if(!$type) $type = $this->getType();
        return isset($this->row[$type]) && $this->row[$type] ? $this->row[$type] : false;
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
    
    function isActive()
    {
        return $this->row['active'] ? true : false;
    }
    
    function getGroupId()
    {
        return $this->row['group_id'];
    }
    
    function getGroupOptions($sel_id = 0)
    {
        global $DB;
        $ret = "<option value=\"0\" style=\"font-style: italic;\">Вне групп</option>\r\n";
        $sql = "SELECT g.id, g.name, g.hidden FROM ".$DB->T('_material_groups')." AS g ORDER BY g.hidden, g.name";
        $result = $DB->query($sql);
        while(list($group_id, $group_name, $group_hidden) = $DB->fetch(false, true, $result))
        {
            $style = $group_hidden ? " style=\"background-color: gray;\" " : "";
            $ret .= "<option value=\"$group_id\" $style ".($sel_id ? ($group_id==$sel_id ? "selected":"") : ($group_id==$this->getGroupId() ? "selected":"")).">$group_name</option>\r\n";
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
                
        $sql = "UPDATE ".$DB->T('_material')." SET ".implode(", ", $update)." WHERE `id`=".$DB->F($this->getId());
        $DB->query($sql);
        return $DB->errno() ? false : true;
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
    
    function setGroup($value, $commit = false)
    {
        $this->row['group_id'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
    }
    
    function setActive($value, $commit = false)
    {
        $this->row['active'] = $value ? 1 : 0;
        
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
    
    /**
     * MaterialEx::setType()
     * начиная с 6.2 функция всегда возвращает TRUE + deprecation warning
     * 
     * @param string $value
     * @param bool $commit
     * @return bool
     */
    function setType($value, $commit = false)
    {
        /*
        $this->row['type'] = $value;
        
        if($commit)
        {
            return $this->update();
        }
        else
        {
            return true;
        }
        */
        trigger_error('MaterialEx::setType() is deprecated!', E_USER_WARNING);
        return true;
        
    }
    
    /**
     * MaterialEx::setData()
     * 
     * @param string $data
     * @param string $type обязательно указывать чтоб не получить deprecation warning и первый заполненный тип
     * @param bool $commit
     * @return
     */
    function setData($data, $type = null, $commit = false)
    {
        /*
        $ret1 = true;
        
        if(isset($type))
        {
            $ret1 = $this->setType($type, $commit);
        }
        
        $this->row['data'] = $data;
        
        if($commit)
        {
            return $ret1 && $this->update();
        }
        else
        {
            return $ret1 && true;
        }*/
        if(!isset($type)) $type = $this->getType();
        $this->row[$type] = trim($data);
        return $commit ? $this->update() : true;
    }
    
    function setDataAll($text, $html, $css, $javascript, $plugin, $commit = false)
    {
        $this->row[CMS_MATERIAL_TYPE_TEXT] = trim($text);
        $this->row[CMS_MATERIAL_TYPE_HTML] = trim($html);
        $this->row[CMS_MATERIAL_TYPE_CSS] = trim($css);
        $this->row[CMS_MATERIAL_TYPE_JS] = trim($javascript);
        $this->row[CMS_MATERIAL_TYPE_PLUGIN] = trim($plugin);        
        return $commit ? $this->update() : true;
    }
}

?>