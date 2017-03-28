<?php

/**
 * Пример плагина который считает время вывода страницы 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class exectimePlugin extends Plugin
{
    const uid = 'exectime';
    
    /**
     * <plugin_name>Plugin::__construct() стандартный конструктор
     * 
     * @param integer $material_id - ИД материала для которого создается плагин
     * @return экземпляр класса
     */
    function __construct($material_id = 0)
    {
        parent::__construct(self::uid, $material_id);
    }
    
    /**
     * <plugin_name>Plugin::get() основная функция плгина
     * вызывается при выводе плагина
     * 
     * @internal рекомендуется предусмотреть поддржку работы через AJAX
     * @return string text/html какоторый будет выведен в броузер клиента
     */
    function get()
    {
        $href = $_SERVER['PHP_SELF']."?debug=2047";
        return "<strong> Модуль &quot;".$this->getTitle()."&quot;</strong><br />Включите <a href='$href'>режим отладки</a> и посмотрите сообщения внизу страницы.";
    }
    
    /**
     * <plugin_name>Plugin::event() хендл событий
     * вызывается если произошло событие закрепленное за плагином
     * 
     * @param array $eventinfo - массив где первый элемент тдентификатор события, а остальные зависят от события
     * @return void
     */
    function event($eventinfo)
    {
        global $DB;
        
        list($event) = $eventinfo;
    
        switch($event)
        {
            case "core_init":
                $_SESSION['exectime'] = microtime(1);
                break;
                
            case "core_finished":
                $time = round(microtime(1) - $_SESSION['exectime'], 2);
                Debugger::mes(1, "Core Execution time: $time sec<br>DB queries: {$DB->getQueriesCount()}", __FILE__, __LINE__, "ExecTime plugin" );
                break;
        }
    }
}
?>