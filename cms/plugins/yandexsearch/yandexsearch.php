<?php

/**
 * Поиск яндекс по сайту 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/lib/pages.lib.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class yandexsearchPlugin extends Plugin
{
    const uid = 'yandexsearch';
    
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
        global $PAGE;
        
        $url = $this->getOption('xmlqueryaddr');
        $results_per_page = 10;
        $maxpassages = $this->getOption('maxpassages', 2);
        $host = ($host = $this->getOption('host')) ? $host : $_SERVER['HTTP_HOST'];
		       
        $tpl = $this->loadTemplate("yandexsearch.tmpl.htm");
        $tpl->setVariable("FORM_ACTION", $PAGE->getFullPath());
        
        if(!$_GET['yandexsearchquery']) {
            return $tpl->get();
        }
                
        $esc = htmlspecialchars($_GET['yandexsearchquery']);
        $tpl->setVariable("SEARCH_QUERY", $esc);
 
        $search_tail = htmlspecialchars(" host:$host");
        $page = isset($_GET['page_num']) ? intval($_GET['page_num']) - 1 : 0;
 
        // XML запрос
        $doc = <<<DOC
<?xml version='1.0' encoding='utf-8'?>
<request>
    <query>$esc $search_tail</query>
    <maxpassages>$maxpassages</maxpassages>
    <page>$page</page>
</request>
DOC;
        $context = stream_context_create(array(
            'http' => array(
                'method'=>"POST",
                'header'=>"Content-type: application/xml\r\n" .
                          "Content-length: " . strlen($doc),
                'content'=>$doc
    
            )
        ));
        $response = file_get_contents($url, true, $context);
        if(!$response) {
            trigger_error("Yandex XML response is empty", E_USER_WARNING);
            $tpl->setCurrentBlock("error");
            $tpl->setVariable("ERR", "Внутренняя ошибка сервера");
            $tpl->parse("error");
            
            return $tpl->get();
        }
        
        $xmldoc = new SimpleXMLElement($response);
 
        $error = $xmldoc->response->error;
        $found_all = intval($xmldoc->response->found);
        $found_human = $xmldoc->response->{'found-human'};
        $found = $xmldoc->xpath("response/results/grouping/group/doc");
        
        $tpl->setVariable("NUM_RESULTS", $found_all);
        $tpl->setVariable("FOUND_HUMAN", $found_human);
        if($error) {
            trigger_error("Yandex XML error: ".$error[0], E_USER_WARNING);
            $tpl->setCurrentBlock("error");
            $tpl->setVariable("ERR", $error[0]);
            $tpl->parse("error");
        } else {
            
            $start = $page * 10 + 1;
            $tpl->setVariable("START_NUM", $start);
            $i = 0;
            foreach ($found as $item) {                
                $tpl->setCurrentBlock("result");
                $tpl->setVariable("NUM", $start + $i);
                $tpl->setVariable("HREF", $item->url);
                $tpl->setVariable("TITLE", $this->highlight_words($item->title));                
                if($item->passages) {
                    //$tpl->setVariable("DESC", $this->highlight_words($item->passages->passage[0]));
                    $tpl->setVariable("DESC", $this->highlight_words($item->passages));                                       
                }                
                $tpl->setVariable("MIME_TYPE", $item->{'mime-type'});
                $tpl->parse("result");
                $i++;
            }
            
            if($found_all > $results_per_page) {
                $max_page = ceil($found_all/$results_per_page);//$page + $pages_inc;
                $tpl->setVariable("PAGES", pages($page*$results_per_page, $results_per_page, $max_page*$results_per_page, $PAGE->getFullPath("/page-%n/")."?yandexsearchquery=$esc"));    
            }            
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
        global $DB;
        
        list($event) = $eventinfo;
    
        switch($event)
        {
        }
    }
    
    private function highlight_words($node)
    {
        $stripped = preg_replace('/<\/?(title|passage)[^>]*>/', '', $node->asXML());
        return str_replace('</hlword>', '</span>', preg_replace('/<hlword[^>]*>/', '<span class="highlight">', $stripped));        
    }
}
?>