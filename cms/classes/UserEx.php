<?php

/**
 * Класс редактирования пользователя системы
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once(dirname(__FILE__)."/../lib/db.lib.php");
require_once(dirname(__FILE__)."/User.php");
 
class UserEx extends User
{
    private $row = array();
    private $groups = array();
    
    function UserEx($user_id = 0)
    {
        if($user_id) {
            parent::__construct($user_id);
            $this->groups = parent::getGroups();  
        }     
    }
    
    private function check_login($login)
    {
        global $DB;
        
        if(!$login) return false;
        if( $DB->getField("SELECT COUNT(*) FROM ".$DB->T('_users')." WHERE `login` LIKE ".$DB->F($login)." AND `id` != ".$DB->F(parent::getId())) ) {
            Debugger::mes(1, "User login check failed.", __FILE__, __LINE__, "UserEx::check_login($login)");
            return false;      
        } else {
            return true;
        }        
    }
    
    function create()
    {
        global $DB;
        
        if(parent::getId() || (isset($this->row['id']) && $this->row['id']) ) {
            Debugger::mes(1, "User ID already exists.", __FILE__, __LINE__, "UserEx::create()");
            return false;    
        }
        if(!isset($this->row['login']) || !$this->row['login']) {
            Debugger::mes(2, "No user LOGIN presents.", __FILE__, __LINE__, "UserEx::create()");
            return false;   
        }
        if(!isset($this->row['email']) || !preg_match("/^.+\@.+\..+$/", $this->row['email'])) {
            Debugger::mes(3, "Bad EMAIL.", __FILE__, __LINE__, "UserEx::create()");
            return false;  
        }
        if(!$this->check_login($this->row['login'])) return false;
        
        $DB->query("INSERT INTO ".$DB->T('_users')." (`login`, `email`) VALUES(".$DB->F($this->row['login']).", ".$DB->F($this->row['email']).")");
        if($DB->errno()) return false;
        
        parent::__construct($DB->insert_id());
        return $this->update();
    }
    
    function update()
    {
        global $DB;
        
        if(!parent::getId()) return false;
        
        $update = array();
        foreach($this->row as $key=>$value) {            
            if(!preg_match("/^[0-9]+$/", $key) && $key != "id") {
                $update[] = "`$key`=".$DB->F($value);
            }
        }
                
        $sql = "UPDATE ".$DB->T('_users')." SET ".implode(", ", $update)." WHERE `id`=".$DB->F($this->getId());
        $DB->query($sql);
        return $DB->errno() ? false : true && $this->updateGroups();
    }
    
    function setLogin($value, $commit = false)
    {
        if(!$this->check_login($value)) return false;
        
        $this->row['login'] = $value;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function setPassword($value, $commit = false)
    {        
        $this->row['password'] = $value;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function setEmail($value, $commit = false)
    {        
        $this->row['email'] = $value;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function setActive($value, $commit = false)
    {       
        //TODO:нужно-ли проверять на свою учетную запись?
        
        $this->row['active'] = $value ? 1 : 0;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function setSuper($value, $commit = false)
    {       
        //TODO:нужно-ли проверять на свою учетную запись и права?
        
        $this->row['super'] = $value ? 1 : 0;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function setGroups($value, $commit = false)
    {       
        if(!is_array($value)) return false;
        
        $this->groups = $value;
                
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function addGroup($group_id, $commit = false)
    {
        if(!$group_id) return false;
        $this->groups[] = $group_id;
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    function removeGroup($group_id, $commit = false)
    {
        if(!$group_id) return false;
        foreach($this->groups as $key => $value) {
            if($value == $group_id) unset($this->groups[$key]);   
        }
        
        if($commit && parent::getId()) {
            return $this->update();
        } else {
            return true;
        }
    }
    
    private function updateGroups()
    {
        global $DB;
        
        if(parent::getId()) {
            $DB->query("DELETE FROM ".$DB->T('_user_groups')." WHERE `user_id`=".$DB->F(parent::getId()));
            if($DB->errno()) return false;
            
            $this->groups = array_unique($this->groups);
                        
            foreach($this->groups as $group_id) {
                $DB->query("INSERT INTO ".$DB->T('_user_groups')." (`user_id`, `group_id`) VALUES(".$DB->F(parent::getId()).", ".$DB->F($group_id).")");
                if($DB->errno()) return false;
            }   
            return true;  
        } else {
            return false;
        }
    }    
}   
?>