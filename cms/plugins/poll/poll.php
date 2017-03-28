<?php

/**
 * Опрос 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class pollPlugin extends Plugin
{
    const uid = 'poll';
    
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
        global $DB, $USER, $PAGE;
        
        $poll_id = $this->getOption("poll_id", 0);
        $block_ip = $this->getOption("block_ip", 0);
        $block_cookie = $this->getOption("block_cookie", 1);
        
        if($poll_id) {
            if(1 <> $DB->getField("SELECT `active` FROM ".$DB->T('polls')." WHERE `id`=".$DB->F($poll_id))) {
                return $this->show_results($poll_id);
            }    
        } else {
            $poll_id = $DB->getField("SELECT `id` FROM ".$DB->T('polls')." WHERE `active` LIMIT 1");
        }
        if(!$poll_id) {
            $poll_id = $DB->getField("SELECT `id` FROM ".$DB->T('polls')." LIMIT 1");
            if($poll_id) {
                return $this->show_results($poll_id);
            } else {
                trigger_error("No poll!", E_USER_NOTICE);
                return false;    
            }                
        }
        
        if(isset($_GET['event']) && $_GET['event']=='pollresults'){
            return $this->show_results($poll_id);
        }     
        if($block_ip && $DB->getField("SELECT COUNT(*) FROM ".$DB->T('poll_ips')." WHERE `poll_id`=".$DB->F($poll_id)." AND `ip` LIKE ".$DB->F($_SERVER['REMOTE_ADDR']))) {
            return $this->show_results($poll_id);
        }
        if($block_cookie && ($cookie = $USER->getCookie("voted_polls")) && ($cookie = preg_split("/[^0-9]+/", $cookie, -1, PREG_SPLIT_NO_EMPTY)) && in_array($poll_id, $cookie) ) {
            return $this->show_results($poll_id);
        }
        if(isset($_POST['pollvote']) || $_GET['event']=='pollvote'){
            $this->add_vote($poll_id, $_POST['vote_id'], $block_ip, $block_cookie);
            return $this->show_results($poll_id);
        } 
        
        return defined('AJAX') ?  $this->show_results($poll_id) : $this->show_poll($poll_id);
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
    
    private function show_poll($poll_id)
    {
        global $DB, $PAGE;
        
        $tpl = $this->loadTemplate('poll.tmpl.htm');
        $tpl->setVariable("RESULTS_HREF", $PAGE->getPath("-pollresults"));
                
        $poll = $DB->getRow("SELECT * FROM ".$DB->T('polls')." WHERE `id`=".$DB->F($poll_id), true); 
        $tpl->setVariable("POLL_ID", $poll['id']);
        $tpl->setVariable("POLL_NAME", $poll['name']);
        $tpl->setVariable("POLL_DESC", $poll['desc']);
        
        $votes = $DB->getCell2("SELECT `id`, `name` FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." ORDER BY `order`");
        foreach($votes as $vote_id=>$vote_name) {
            $tpl->setCurrentBlock("vote");
            $tpl->setVariable("VOTE_ID", $vote_id);
            $tpl->setVariable("VOTE_NAME", $vote_name);
            $tpl->parse("vote");
        }
        return $tpl->get();
    }
    
    private function show_results($poll_id)
    {
        global $DB;
        
        $tpl = $this->loadTemplate('results.tmpl.htm');
                        
        $poll = $DB->getRow("SELECT * FROM ".$DB->T('polls')." WHERE `id`=".$DB->F($poll_id), true); 
        $tpl->setVariable("POLL_ID", $poll['id']);
        $tpl->setVariable("POLL_NAME", $poll['name']);
        $tpl->setVariable("POLL_DESC", $poll['desc']);
        
        $total = $total_real = $DB->getField("SELECT SUM(`votes`) FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id));
        if($poll['fake_percent'] && $poll['fake_threshold'] && $total > $poll['fake_threshold']) {
            list($fake_id, $fake_votes) = $DB->getRow("SELECT `id`, `votes` FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." AND `is_fake`");
            //TODO:нужно-ли изменять только когда хоть кто-то проголосовал?
            if($fake_id /*&& $fake_votes*/) {
                $sum2 = $DB->getField("SELECT SUM(`votes`) FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." AND `id`!=".$DB->F($fake_id));
                
                $percent2 = 100 - $poll['fake_percent'];
				$x = round($sum2 * 100 / $percent2, 0);
				$xx = $x-$sum2;
				$total += ($xx - $fake_votes);
            } /*else {
                $xx = 0;
            }*/
        }
        
        $sql = "SELECT `id`, `name`, `votes` FROM ".$DB->T('poll_votes')." WHERE `poll_id`=".$DB->F($poll_id)." ORDER BY `order`";
        $DB->query($sql);
        while(list($vote_id, $vote_name, $vote_cnt)=$DB->fetch()) {
            $tpl->setCurrentBlock("vote");
            $tpl->setVariable("VOTE_ID", $vote_id);
            $tpl->setVariable("VOTE_NAME", $vote_name);
            
            if(isset($fake_id) && ($vote_id==$fake_id)) $vote_cnt = $xx;
            $tpl->setVariable("VOTE_CNT", $vote_cnt);
            $tpl->setVariable("VOTE_PERCENT", round($vote_cnt/$total*100, 0));
            $tpl->parse("vote");
        }
        $tpl->setVariable("TOTAL", $total);
        return $tpl->get();
    }
    
    private function add_vote($poll_id, $vote_id, $block_ip = false, $block_cookie = true)
    {
        global $DB, $USER;
        
        $DB->query("UPDATE ".$DB->T('poll_votes')." SET `votes`=`votes`+1 WHERE `id`=".$DB->F($vote_id)." AND `poll_id`=".$DB->F($poll_id));
        if($poll_id) {
            $DB->query("INSERT IGNORE INTO ".$DB->T('poll_ips')." (`poll_id`, `ip`) VALUES(".$DB->F($poll_id).", ".$DB->F($_SERVER['REMOTE_ADDR']).")");
        
            $cookie = $USER->getCookie("voted_polls");
            $cookie = preg_split("/[^0-9]+/", $cookie, -1, PREG_SPLIT_NO_EMPTY);
            $cookie[] = $poll_id;
            $cookie = array_unique($cookie);
            $USER->setCookie("voted_polls", implode(',',$cookie));
        }
        return ($vote_id && $poll_id) ? true : false;
    }
}
?>