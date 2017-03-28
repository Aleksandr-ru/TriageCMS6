<?php

/**
 * Модуль отображения блога 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/lib/pages.lib.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class newsPlugin extends Plugin
{
    const uid = 'news';
        
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
        global $REWRITE_VARS;
        
        /**
         * вывод RSS ленты
         */
        if(isset($_GET['event']) && ($_GET['event']=='rss' || $_GET['event']=='rss.xml') ) {
            if($rss = $this->getRSS()) {
                header("Content-type: text/xml; charset=utf-8");
                echo $rss;
                exit;
            } else {
                $PAGE->setStatus(404);
                return false;
            }
            
        } 
        //print_r($REWRITE_VARS); exit;
        
        $PAGE->setTemplateVariable("CMS_RSS_LINK", $this->getRSSLink());
        $PAGE->setTemplateVariable("CMS_RSS_TITLE", $this->getTitle());
        
        switch($_GET['event']) {
            case "shownews":
                return $this->show_news($REWRITE_VARS[0]);
            case "newsarchive":
                return $this->show_archive($REWRITE_VARS['year'], $REWRITE_VARS['group']);
            default:
                $default_archive = $this->getOption('default_archive', 0);
                if($default_archive) {
                    return $this->show_archive($REWRITE_VARS['year'], $REWRITE_VARS['group']);
                } else {
                    return $this->show_news($REWRITE_VARS[0]);
                }
        }
        
        return false;
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
        
    private function show_archive($current_year = 0, $current_group = 0)
    {
        global $DB, $PAGE;
        global $REWRITE_VARS;             
        
        if(!$current_year) $current_year = 0;
        if(!$current_group) $current_group = 0;        
        
        $selected_year_class = $this->getOption('selected_year_class', 'selected');
        $selected_group_class = $this->getOption('selected_group_class', 'selected');
        $date_format = $this->getOption('date_format', "d F Y");
        $all_groups_name = $this->getOption('all_groups_name', "(Все)");    
        
        $tpl = $this->loadTemplate("archive.tmpl.htm");
        $tpl->setVariable("RSS_LINK", $this->getRSSLink());
        
        $sql = "SELECT EXTRACT(YEAR FROM n.timestamp), COUNT(*) FROM ".$DB->T('news')." AS n JOIN ".$DB->T('_material')." AS m ON n.material_id=m.id WHERE m.active GROUP BY EXTRACT(YEAR FROM n.timestamp) ORDER BY n.timestamp DESC;";
        $DB->query($sql);
        while(list($year, $cnt) = $DB->fetch()) {
            if(!$current_year) $current_year = $year;
            $tpl->setCurrentBlock("years");
            $tpl->setVariable("YEAR_VAL", $year);
            $tpl->setVariable("YEAR_SEL", $year == $current_year ? $selected_year_class : '');
            $tpl->setVariable("YEAR_HREF", $current_group ? $PAGE->getFullPath("-newsarchive/group/$current_group/year/$year") : $PAGE->getFullPath("-newsarchive/year/$year"));             
            $tpl->setVariable("YEAR_CNT", $cnt);
            $tpl->parse("years");
        }
        $DB->free();
        
        $tpl->setVariable("YEAR", $current_year);
        $tpl->setVariable("GROUP", $current_group ? $DB->getField("SELECT `name` FROM ".$DB->T('news_groups')." WHERE `id`=".$DB->F($current_group)) : $all_groups_name);
                
        $sql = "SELECT g.id, g.name, COUNT(*) FROM ".$DB->T('news_in_groups')." AS nig JOIN ".$DB->T('news_groups')." AS g ON nig.group_id=g.id JOIN ".$DB->T('news')." AS n ON nig.news_id=n.id JOIN ".$DB->T('_material')." AS m ON n.material_id=m.id WHERE NOT g.is_hidden AND m.active GROUP BY g.id ORDER BY g.name";
        $DB->query($sql);
        
        $tpl->setCurrentBlock("groups");
        $tpl->setVariable("GROUP_ID", 0);
        $tpl->setVariable("GROUP_NAME", $all_groups_name);
        $tpl->setVariable("GROUP_SEL", 0 == $current_group ? $selected_group_class : '');
        $tpl->setVariable("GROUP_HREF", $PAGE->getFullPath("-newsarchive/year/$current_year"));             
        $tpl->setVariable("GROUP_CNT", '*');
        $tpl->parse("groups");
        
        while(list($group_id, $group_name, $cnt) = $DB->fetch()) {            
            $tpl->setCurrentBlock("groups");
            $tpl->setVariable("GROUP_ID", $group_id);
            $tpl->setVariable("GROUP_NAME", $group_name);
            $tpl->setVariable("GROUP_SEL", $group_id == $current_group ? $selected_group_class : '');
            $tpl->setVariable("GROUP_HREF", $PAGE->getFullPath("-newsarchive/group/$group_id/year/$current_year"));             
            $tpl->setVariable("GROUP_CNT", $cnt);
            $tpl->parse("groups");
        }
        $DB->free();
        
        $sql = "SELECT n.id, n.timestamp, mat.name, n.short_text, n.picture_file_id FROM ".$DB->T('news')." AS n LEFT JOIN ".$DB->T('news_in_groups')." AS nig ON nig.news_id=n.id JOIN ".$DB->T('_material')." AS mat ON n.material_id=mat.id WHERE mat.active AND EXTRACT(YEAR FROM n.timestamp)=".$DB->F($current_year)." ".($current_group ? "AND nig.group_id=".$DB->F($current_group):'')." GROUP BY n.id ORDER BY n.timestamp DESC, n.id DESC";
        $DB->query($sql);
        while(list($news_id, $timestamp, $material_name, $short_text, $file_id) = $DB->fetch()) {
            $tpl->setCurrentBlock("news");
            $tpl->setVariable("NEWS_ID", $news_id);
            $tpl->setVariable("NEWS_DATE", rudate($date_format, $timestamp));
            $tpl->setVariable("NEWS_TITLE", $material_name);
            $tpl->setVariable("NEWS_SHORT", $short_text);
            $tpl->setVariable("NEWS_HREF", $PAGE->getFullPath("-shownews/$news_id"));
            if($file_id && ($src = getFileHref($file_id))) {
                $tpl->setVariable("NEWS_IMG_SRC", $src);
                $tpl->setVariable("NEWS_IMG", '<img src="'.$src.'" class="news-image news-img-'.$news_id.'">');
            }            
            $tpl->parse("news");
        }
        $DB->free();
                
        return $tpl->get();
    }
    
    private function show_news($news_id = 0)
    {
        global $DB, $USER, $PAGE;
                
        $date_format = $this->getOption('date_format', "d F Y");
        
        $tpl = $this->loadTemplate("news.tmpl.htm");
        $tpl->setVariable("RSS_LINK", $this->getRSSLink());
                        
        $sql = "SELECT n.id, n.timestamp, mat.id, mat.name, mat.type, mat.data, mat.access_group, EXTRACT(YEAR FROM n.timestamp) FROM ".$DB->T('news')." AS n JOIN ".$DB->T('_material')." AS mat ON n.material_id=mat.id WHERE mat.active ".($news_id ? "AND n.id=".$DB->F($news_id):'')." ORDER BY n.timestamp DESC, n.id DESC LIMIT 1";
        $DB->query($sql);
        while(list($news_id, $timestamp, $material_id, $material_name, $material_type, $material_data, $material_access_group_id, $year) = $DB->fetch(false, false)) {
            $material = new Material($material_id, $material_type, $material_data);
            //TODO:надо-ли событие инициировать?
            raise_event('material_init', $material, $material_access_group_id);
            $tpl->setVariable("NEWS_ID", $news_id);
            $tpl->setVariable("NEWS_DATE", rudate($date_format, $timestamp));
            $tpl->setVariable("NEWS_TITLE", htmlspecialchars($material_name));
            $tpl->setVariable("NEWS_TEXT", $material->parse());                    
            unset($material);
            
            $sql = "SELECT g.id, g.name FROM ".$DB->T('news_in_groups')." AS nig JOIN ".$DB->T('news_groups')." AS g ON nig.group_id=g.id WHERE NOT g.is_hidden AND nig.news_id=".$DB->F($news_id);
            $DB->query($sql);        
            while(list($group_id, $group_name) = $DB->fetch()) {            
                $tpl->setCurrentBlock("groups");
                $tpl->setVariable("GROUP_ID", $group_id);
                $tpl->setVariable("GROUP_NAME", $group_name);            
                $tpl->setVariable("GROUP_HREF", $PAGE->getFullPath("-newsarchive/group/$group_id/year/$year"));                         
                $tpl->parse("groups");
            }
            $DB->free();
            
            $tpl->setVariable("ARCHIVE_LINK", $PAGE->getFullPath("-newsarchive/year/$year"));
            if($this->getOption('set_title', 0)) {
                $PAGE->setTemplateVariable("CMS_PAGE_TITLE", htmlspecialchars($material_name));
            }
        }
        $DB->free();  
        
        return $tpl->get();
    }
    
    
    private function getRSS()
    {
        global $DB, $PAGE, $_config;  
            
        $last_pub_date = "";
                
        $tpl = $this->loadTemplate("rss.tmpl.htm");
        
        $sql = "SELECT n.id, n.timestamp, mat.name, n.short_text, n.picture_file_id FROM ".$DB->T('news_in_groups')." AS nig JOIN ".$DB->T('news_groups')." AS ng ON nig.group_id=ng.id JOIN ".$DB->T('news')." AS n ON nig.news_id=n.id JOIN ".$DB->T('_material')." AS mat ON n.material_id=mat.id WHERE ng.rss AND mat.active AND n.short_text IS NOT NULL AND n.short_text != '' GROUP BY n.id ORDER BY n.timestamp DESC, n.id DESC LIMIT 10";
                
        $DB->query($sql);
        while(list($news_id, $timestamp, $material_name, $short_text, $file_id) = $DB->fetch(false, false))
        {       
            if(!$last_pub_date) $last_pub_date = $timestamp;
                                    
            /*
            $html = $material->parse();
            $html = preg_replace("@(src|href)=('|\")(?!\w+://)@i", "\\1=\\2".make_base($_config['http_base']), $html);
            */
            $html = $file_id && ($fhref=getFileHref($file_id)) ? '<p><img src="'.make_base($_config['http_base']).$fhref.'"></p>' : '';
            $html .= "<p>$short_text</p>";
                        
            $tpl->setCurrentBlock("item");
            $tpl->setVariable("POST_ID", $post_id);
            $tpl->setVariable("ITEM_TITLE", htmlspecialchars($material_name));
            $tpl->setVariable("ITEM_DESCRIPTION", $short_text);
            $tpl->setVariable("ITEM_HTML", $html);
            
            $tpl->setVariable("ITEM_PUBDATE", rudate('r', $timestamp));
            $tpl->setVariable("ITEM_LINK", $PAGE->getFullPath("/-shownews/$news_id"));            
            $groups = $DB->getCell("SELECT g.name FROM ".$DB->T('news_in_groups')." AS ng JOIN ".$DB->T('news_groups')." AS g ON ng.group_id=g.id WHERE NOT g.is_hidden AND ng.news_id=".$DB->F($news_id)." ORDER BY g.name");
            foreach($groups as $group_name) {
                $tpl->setCurrentBlock("category");
                $tpl->setVariable("CATEGORY_NAME", $group_name);
                $tpl->parse("category");
            }
            $tpl->parse("item");            
        }
        $DB->free();
        
        $tpl->setVariable("CHANNEL_TITLE", getSetting('site_title')." (новости)");
        $tpl->setVariable("RSS_LINK", $this->getRSSLink());
        $tpl->setVariable("PAGE_LINK", $PAGE->getFullPath());
        $tpl->setVariable("CHANNEL_DESCRIPTION", "Новости на сайте ".getSetting('site_title'));
        $tpl->setVariable("LASTBUILD_DATE", rudate('r', $last_pub_date));
        
        return $tpl->get();
    }
        
    function getRSSLink()
    {
        global $PAGE;
        return $PAGE->getFullPath('-rss.xml');
    }
}

?>