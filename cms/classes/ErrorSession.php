<?php

/**
 * Класс протаскивания ошибок/варнингов/нотисов через сессию
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
*/
 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");
 
class ErrorSession
{
    private $mutex = 'cms_error';
    private $handle;
    
    function ErrorSession($handle = '_default_')
    {
        @session_start();
        $this->handle = $handle;
    }
    
    private function add($class, $title, $desc = '')
    {
        $_SESSION[$this->mutex][$this->handle][$class][] = array($title, $desc);
        return sizeof($_SESSION[$this->mutex][$this->handle][$class]);
    }
    
    function addError($title, $desc = '')
    {
        return $this->add('error', $title, $desc);
    }
    
    function addWarning($title, $desc = '')
    {
        return $this->add('warning', $title, $desc);
    }
    
    function addNotice($title, $desc = '')
    {
        return $this->add('notice', $title, $desc);
    }
    
    private function getLast($class, $unset = true)
    {
        if($unset)
        {
            return array_pop($_SESSION[$this->mutex][$this->handle][$class]);
        }
        else
        {
            return $_SESSION[$this->mutex][$this->handle][$class][ sizeof($_SESSION[$this->mutex][$this->handle][$class])-1 ];
        }
    }
    
    /*
    function getLastError($unset = true)
    {
        return $this->getLast('error', $unset);
    }
    
    function getLastWarning($unset = true)
    {
        return $this->getLast('warning', $unset);
    }
    
    function getLastNotice($unset = true)
    {
        return $this->getLast('notice', $unset);
    }
    */
    
    private function get($class, $unset = true)
    {
        $ret = $_SESSION[$this->mutex][$this->handle][$class];
        if($unset) unset($_SESSION[$this->mutex][$this->handle][$class]);
        return $ret;
    }
    
    function getError($unset = true)
    {
        return $this->get('error', $unset);
    }
    
    function getWarning($unset = true)
    {
        return $this->get('warning', $unset);
    }
    
    function getNotice($unset = true)
    {
        return $this->get('notice', $unset);
    }
    
    private function fetch($class)
    {
        if(sizeof($_SESSION[$this->mutex][$this->handle][$class]))
        {
            return $this->getLast($class, true);
        }
        else
        {
            return false;
        }
    }
    
    function fetchError()
    {
        return $this->fetch('error');
    }
    
    function fetchWarning()
    {
        return $this->fetch('warning');
    }
    
    function fetchNotice()
    {
        return $this->fetch('notice');
    }
    
    private function is($class)
    {
        return sizeof($_SESSION[$this->mutex][$this->handle][$class]) ? true : false;
    }
    
    function isError()
    {
        return $this->is('error');
    }
    
    function isWarning()
    {
        return $this->is('warning');
    }
    
    function isNotice()
    {
        return $this->is('notice');
    }
    
    private function clean($class)
    {
        unset($_SESSION[$this->mutex][$this->handle][$class]);
    }
    
    function cleanError()
    {
        $this->clean('error');
    }
    
    function cleanWarning()
    {
        $this->clean('warning');
    }
    
    function cleanNotice()
    {
        $this->clean('notice');
    }
    
    private function show($tpl, $class)
    {
        $ret = false;
        while(list($title, $text) = $this->fetch($class))
        {
            if(is_array($text))
            {
                $text = self::array2list($text);
            }
            $ret = $tpl->setCurrentBlock($class);
            $tpl->setVariable(strtoupper($class."_TITLE"), $title);
            $tpl->setVariable(strtoupper($class."_TEXT"), $text);
            $tpl->parse($class);
        }
        return $ret;
    }
    
    static function array2list($array, $escape = false)
    {
        $ret = "<ul>\r\n";
        foreach($array as $line)
        {
            $ret .= "<li>".($escape ? htmlspecialchars($line) : $line)."</li>\r\n";
        }
        $ret .= "</ul>\r\n";
        return $ret;
    }
    
    function showError($tpl)
    {
        return $this->show($tpl, 'error');
    }
    
    function showWarning($tpl)
    {
        return $this->show($tpl, 'warning');
    }
    
    function showNotice($tpl)
    {
        return $this->show($tpl, 'notice');
    }
    
    function showAll($tpl)
    {
        return $this->showError($tpl) + $this->showWarning($tpl) + $this->showNotice($tpl);
    }
}
?>