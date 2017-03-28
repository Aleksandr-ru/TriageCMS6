<?php

/**
 * Модуль отображения блога 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/lib/pages.lib.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class lastnewsPlugin extends Plugin
{
    const uid = 'lastnews';
        
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
        global $DB, $PAGE;
                
        $PAGE->setTemplateVariable("CMS_RSS_LINK", $this->getRSSLink());
        $PAGE->setTemplateVariable("CMS_RSS_TITLE", $this->getTitle());
        
        $limit = $this->getOption('limit', 3);        
        $date_format = $this->getOption('date_format', "d F Y");
        $news_page_id = $this->getOption('news_page_id', 0);
        $all_groups = $this->getOption('all_groups', 1);    
        $group_id = $this->getOption('group_id', 0);
        
        $tpl = $this->loadTemplate("lastnews.tmpl.htm");
        $tpl->setVariable("RSS_LINK", $this->getRSSLink());
        
        if(!$group_id) $all_groups = 1;
        if(!intval($limit)) $limit = 3;
        
        $sql = "SELECT n.id, n.timestamp, mat.name, n.short_text, n.picture_file_id FROM ".$DB->T('news')." AS n LEFT JOIN ".$DB->T('news_in_groups')." AS nig ON nig.news_id=n.id JOIN ".$DB->T('_material')." AS mat ON n.material_id=mat.id WHERE mat.active ".($all_groups ? '' : "AND nig.group_id=".$DB->F($group_id))." GROUP BY n.id ORDER BY n.timestamp DESC, n.id DESC LIMIT ".intval($limit);
        $DB->query($sql);
        while(list($news_id, $timestamp, $material_name, $short_text, $file_id) = $DB->fetch()) {
            $tpl->setCurrentBlock("lastnews");
            $tpl->setVariable("NEWS_ID", $news_id);
            $tpl->setVariable("NEWS_DATE", rudate($date_format, $timestamp));
            $tpl->setVariable("NEWS_TITLE", $material_name);
            $tpl->setVariable("NEWS_SHORT", $short_text);
            $tpl->setVariable("NEWS_HREF", $PAGE->fullpath($news_page_id, "-shownews/$news_id"));
            if($file_id && ($src = getFileHref($file_id))) {
                $tpl->setVariable("NEWS_IMG_SRC", $src);
                $tpl->setVariable("NEWS_IMG", '<img src="'.$src.'" class="lastnews-image lastnews-img-'.$news_id.'">');
            }            
            $tpl->parse("lastnews");
        }
        $DB->free();
                
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
    
    /**************************************************************************************************/
            
    function getRSSLink()
    {
        global $PAGE;
        $page_id = $this->getOption('news_page_id', 0);
        return $page_id ? $PAGE->fullpath($page_id, '-rss.xml') : false;
    }
}

?>