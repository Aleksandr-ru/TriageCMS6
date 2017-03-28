<?php

/**
 * Session debugger для CMS 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
class Debugger
{    
	/**
	 * Debugger::Debugger() - Конструктор, с учетом сессии + установка assert + 
     * 
	 * @param mixed $level - уровень отладки цифра или коннстанта от error_reporting
	 * @return false если не удалось session_start, true в остальных случаях
	 */
	function Debugger($level = null)
	{
        @session_start();
        if(isset($level)) self::setDebug(intval($level));
        
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 0);
        assert_options(ASSERT_CALLBACK, array('Debugger', 'assert'));
        /* If you want your callback function be a method of a class. You can pass an array. 
        First is the class name, second is the method name */
        
        set_error_handler(array('Debugger', 'error'));
	}
    
    /**
     * Debugger::setDebug() - устанавливает уровень отладки и запоминает его в сессию
     * 
     * @param mixed $level - уровень отладки цифра или коннстанта от error_reporting
     * @return ничего
     */
    static function setDebug($level)
    {
        $_SESSION['debug'] = $level;
        error_reporting($level);
    }
    
    /**
     * Debugger::getDebug() - получить текущий уровень отдадки 
     * 
     * @return цифра 
     */
    static function getDebug()
    {
        return isset($_SESSION['debug']) ? $_SESSION['debug'] : 0;
    }
    
    /**
     * Debugger::mes() - вывродит сообщение если есть код ошибки и включена отладка
     * 
     * @param integer $errno - код ошибки
     * @param string $errmes - сообщение об ошибке
     * @param string $file - файл в котором случилось, желательно передавать __FILE__
     * @param integer $line - строка на которой случилось, желательно передавать __LINE__
     * @param string $source - источник ошибки (например sql запрос)
     * @return true если сообщение показано, false если нет
     */
    static function mes($errno = 0, $errmes = "", $file = "undefined_file", $line = 0, $source = "")
    {
        if(self::getDebug() && $errno != 0)
		{
		    $source = $source ? "Source: $source" : "";
echo <<<DEBUGMES
<pre class="cms-debug-message">
Triage CMS: Debug Message #$errno:
$errmes
in $file at line $line.
$source
</pre>
DEBUGMES;
            
			return true;
		}
		else return false;
    }
    
    /**
     * Debugger::assert() - callback для assert 
     * 
     * @param string $file
     * @param integer $line
     * @param string $message
     * @return
     */
    static function assert($file, $line, $message)
    {
        self::mes(1, "Assertion Failed!", $file, $line, $message);
        die("<pre class='cms-debug-assertion'>Triage CMS: Assertion Failed!\r\nFile: $file\r\nLine: $line</pre>");
    }

    
    /**
     * Debugger::error() - callback для set_error_handler 
     * 
     * @param mixed $errno
     * @param mixed $errstr
     * @param mixed $file
     * @param mixed $line
     * @return true чтоб не вызывать стандартный хендлер ошибок 
     */
    static function error($errno, $errstr, $file, $line)
    {
        switch ($errno) 
        {
            case E_NOTICE:
            case E_USER_NOTICE:
                $err = "Notice";
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $err = "Warning";
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $err = "Fatal Error";
                break;
            default:
                $err = "<font color=red>Error</font> ($errno)";
                break;
        }
        self::mes($errno, "<strong>$err!</strong> $errstr", $file, $line);
        return true;
    }
    
    /**
     * Debugger::dump() - показывает дамп выражения в читабельном html-виде
     * останавливает работу скрипта в случае форсированного дампа
     * отображения дампа происходит только если включен режим отладки или форсированный дамп
     * 
     * @param mixed $expression - выражения для передачи в print_r()
     * @param bool $force - форсированный дамп (происходит при любых обстоятельствах и останавливает работу скрипта)
     * @param string $file - файл в котором случилось, желательно передавать __FILE__
     * @param integer $line - строка на которой случилось, желательно передавать __LINE__
     * @param string $source - источник ошибки (например sql запрос)
     * @return teue если произошел дамп, false - если нет
     */
    static function dump($expression, $force = false, $file = "undefined_file", $line = 0, $source = "")
    {
        if(self::getDebug() || $force)
		{
            $dump = htmlspecialchars(print_r($expression, 1));
		    $source = $source ? "Source: $source" : "";
echo <<<DEBUGDUMP
<pre class="cms-debug-dump">
<font color=gray>Triage CMS: Debug dump for <i>$file line $line</i>:</font>
$dump
$source
</pre>
DEBUGDUMP;
            if($force) die("Forced dump happends!");
			return true;
		}
		else return false;
    }
}

if(isset($DEBUG) && !is_a($DEBUG, 'Debugger'))
{ 
    trigger_error("\$DEBUG variable already set as ".(get_class($DEBUG) ? "member of ".get_class($DEBUG) : gettype($DEBUG))."! Unsetting :)", E_USER_WARNING);
    unset($DEBUG);
}
$DEBUG = new Debugger(isset($_GET['debug']) ? intval($_GET['debug']) : null);
?>