<?php

/**
 * Класс обработки пользователя системы
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2013
 */
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
require_once(dirname(__FILE__)."/../lib/db.lib.php");
 
class User
{
    private $row = array();
    private $groups = array();
    
    function __construct($id)
    {
        global $DB;
        
        $this->row = $DB->getRow("SELECT * FROM ".$DB->T('_users')." as u WHERE(u.id = ".$DB->F($id).")", true);
        $this->groups = $DB->getCol("SELECT ug.group_id FROM ".$DB->T('_user_groups')." AS ug WHERE(ug.user_id=".$DB->F($this->getId()).")");
    }
    
    function __destruct()
    {
        $this->row = array();
        $this->groups = array();
    }
    
    function isActive()
    {
        return @$this->row['active'] ? true : false;
    }
    
    function isSuper()
    {
        if(!$this->isActive()) return false;
        return $this->row['super'] ? true : false;
    }
    
    function getId()
    {
        return $this->row['id'];
    }
    
    function getLogin()
    {
        return $this->row['login'];
    }
    
    function getEmail()
    {
        return $this->row['email'];
    }
        
    function getGroups()
    {
        return $this->groups;
    }
    
    function checkGroup($group_id)
    {
        if(!$group_id) return true;
        elseif(!$this->getId()) return false;
        elseif($this->isSuper()) return true;
        else return in_array($group_id, $this->groups);
    }
    
    function getHash()
    {
        if(!sizeof($this->row)) return false;
        return md5($this->row['id'].$this->row['password']);
    }
    
    static function getCookie($name, $default = null)
    {
        global $_config;
        return isset($_COOKIE[$_config['cookie_prefix'].$name]) ? $_COOKIE[$_config['cookie_prefix'].$name] : $default;
    }
    
    static function setCookie($name, $value)
    {
        global $_config;        
        return setcookie($_config['cookie_prefix'].$name, $value, mktime(0,0,0,1,date("m"),date("Y")+1), self::getCookiePath());
    }
    
    static function unsetCookie($name)
    {
        global $_config;
        return setcookie($_config['cookie_prefix'].$name, '', time()-42000, self::getCookiePath());
    }
    
    static function isCookie($name)
    {
        global $_config;
        return isset($_COOKIE[$_config['cookie_prefix'].$name]);
    }
    
    static function getCookiePath()
    {
        global $_config;
        if(!$_SERVER['HTTP_HOST']) return null;
        $cookie_path = substr($_config['http_base'], strpos($_config['http_base'], $_SERVER['HTTP_HOST']) + strlen($_SERVER['HTTP_HOST']));
		if(substr($cookie_path, -1) != '/') $cookie_path .= '/';
		return $cookie_path;
    }
    
    /**
     * User::getGravatar()
     * Get either a Gravatar URL or complete image tag for a specified email address.
     * 
     * @param string $email The email address
     * @param bool $img True to return a complete IMG tag False for just the URL
     * @param array $imgattrs Optional, additional key/value attributes to include in the IMG tag
     * @return String containing either just a URL or a complete image tag
     * @source http://gravatar.com/site/implement/images/php/
     */
    static function get_gravatar($email, $img = false, $imgattrs = array())
    {
        $default = getSetting("gravatar", "404");
        $rating  = getSetting("gravatar_rating", "g");
        $size    = getSetting("gravatar_size", "80");
        
        $url = 'http://www.gravatar.com/avatar/';
        $url .= md5( strtolower( trim( $email ) ) );
        $url .= "?s=$size&d=$default&r=$rating";
        if ( $img ) {
            $url = '<img src="' . $url . '"';
            foreach ( $imgattrs as $key => $val )
            	$url .= ' ' . $key . '="' . $val . '"';
            $url .= ' />';
        }
        return $url;
    }
    
    function getGravatar($img = false, $imgatts = array())
    {
        return $this->get_gravatar($this->getEmail(), $img, $imgatts);
    }
}
 
class UserSession extends User
{
    private $error = array('code'=>0, 'text'=>'ok');
    
    function __construct()
    {
        @session_start();
        
        if(isset($_POST['cms_logoff']))
        {
            $this->logOff();
        } 
        elseif(isset($_POST['cms_login']) && isset($_POST['user_login']) && isset($_POST['user_password']) && $this->checkReferer()) 
        {
            $this->logIn($_POST['user_login'], $_POST['user_password'], isset($_POST['user_cookie']) && $_POST['user_cookie']);
        } 
        elseif(isset($_SESSION['cms_user_id'])) 
        {
            parent::__construct($_SESSION['cms_user_id']);
        } 
        elseif(parent::isCookie('user_login') && parent::isCookie('user_hash')) 
        {
            if(!$this->logInHash(parent::getCookie('user_login'), parent::getCookie('user_hash'))) {
                Debugger::mes(1, "Cookie authorization failed!", __FILE__, __LINE__, print_r($_COOKIE, 1));
            }
        }
        else
        {
            // ничего не делать
        }
    }
    
    function logIn($login, $password, $save_cookie = false)
    {
        global $DB;
        
        $user_id = $DB->getField("SELECT u.id FROM ".$DB->T('_users')." as u WHERE(UPPER(u.login) = UPPER(".$DB->F($login).") AND UPPER(u.password)=UPPER(".$DB->F($password).") AND u.active )");    
        if(!$user_id) $this->setError(1, "Неверное сочетание логин/пароль или учетная запись отключена.");
        else {
            $_SESSION['cms_user_id'] = $user_id;
            parent::__construct($user_id);
            if($save_cookie) {
                parent::setCookie('user_login', $login);
                parent::setCookie('user_hash', parent::getHash());
            }
        }            
        raise_event('user_login', $this->getId());
        
        return $user_id ? $user_id : false;
    }
    
    function logInHash($login, $hash)
    {
        global $DB;
        
        $user_id = $DB->getField("SELECT u.id FROM ".$DB->T('_users')." as u WHERE(UPPER(u.login) = UPPER(".$DB->F($login).") AND MD5(CONCAT(u.id, u.password))=".$DB->F($hash)." AND u.access_flag )");    
            
        if(!$user_id) {
            Debugger::mes(1, "Cookie authorization failed!", __FILE__, __LINE__, print_r($_COOKIE, 1));
        } else {
            $_SESSION['cms_user_id'] = $user_id;
            parent::__construct($user_id);
        }
        raise_event('user_login', $this->getId());
        return $user_id ? $user_id : false;
    }
    
    function logOff()
    {
        raise_event('user_logoff', $this->getId());
            
        parent::unsetCookie('user_login');
        parent::unsetCookie('user_hash');
    
        $_SESSION['cms_user_id'] = 0;
        unset($_SESSION['cms_user_id']);
        parent::__destruct();
        $this->setError(0);
        
        return true;       
    }
    
    function setError($code, $text = 'OK')
    {
        $this->error['code'] = $code;
        $this->error['text'] = $text;
    }
    
    function getError($escape = true)
    {
        if($this->error['code']) {
            return $escape ? htmlspecialchars($this->error['text']) : $this->error['text'];
        } else  {
            return false;
        }
    }
    
    static function checkReferer()
    {
        global $_config;
        $ref = getenv('HTTP_REFERER');
        if(strpos($ref, $_config['http_base']) === 0) {
            return true;
        } else {
            trigger_error("Referer check failed for '$ref'", E_USER_NOTICE);
            return false;
        }
    }
}
?>