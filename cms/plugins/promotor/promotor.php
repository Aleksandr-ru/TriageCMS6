<?php

/**
 * Модуль отображения плагина 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/lib/pages.lib.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class promotorPlugin extends Plugin
{
    const uid = 'promotor';
        
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
        global $DB, $_ROOT, $PAGE;
        
        $count_clicks = $this->getOption('count_clicks', 0);
        $limit = intval($this->getOption('max_banners', 3));
        if($limit) $limit = " LIMIT $limit ";
        
        $tpl = $this->loadTemplate("promotor.tmpl.htm");
        
        $showed = array();
                
        $sql = "SELECT p.id, p.href, p.file_ext FROM ".$DB->T('promotor'). " AS p WHERE p.active ORDER BY RAND() $limit";
        $DB->query($sql);
        while(list($promotor_id, $href, $ext) = $DB->fetch()) {
            $showed[] = intval($promotor_id);
            
            $file = "files/promotor/$promotor_id.$ext";
            list(,,, $dimensions) = getimagesize($_ROOT.'/'.$file);
            
            if($count_clicks) {
                $href = make_base('cms/plugins/promotor/')."click.php?id=$promotor_id";
            } elseif(preg_match("/^#(\d)+#/", $href, $arr)) {
                $href = $PAGE->fullpath($arr[1]);
            }
            
            $tpl->setCurrentBlock("banner");
            $tpl->setVariable('HREF', $href);
            $tpl->setVariable('IMG_SRC', $file);
            $tpl->setVariable('IMG_DIMENSIONS', $dimensions);
            $tpl->parse("banner");
        }
        $DB->free();
        
        if(sizeof($showed)) {
            $sql = "UPDATE ".$DB->T('promotor')." SET `shows`=`shows`+1 WHERE `id` IN(".implode(',', $showed).")";
            $DB->query($sql);    
        }
                
        return $tpl->get();
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
        global $_config, $_ROOT, $_BASE;
        global $DB, $USER;
    
        switch($event)
        {
        }
    }
}

?>