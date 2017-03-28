<?php

/**
 * Отправка сообщения
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
 
class sendemailPlugin extends Plugin
{
    const uid = 'sendemail';
    
    private $send_err = '';
    
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
        global $DB, $PAGE, $USER;
        
        if(defined('AJAX')){
           if( isset($_GET['event']) && $_GET['event']=='send') {
               return $this->send() ? 'OK' : $this->send_err;
           }
           return false;
        }
        
        $tpl = $this->loadTemplate("sendemail.tmpl.htm");
        
        if(isset($_POST['sendemail_send'])) {
            if($this->send()) {
                $tpl->touchBlock("sent");
            }
        }
        
        if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('sendemail_addr')." WHERE `order`>0 AND `material_id`=".$DB->F($this->material_id))) {
            $sql = "SELECT `name`, `email` FROM ".$DB->T('sendemail_addr')." WHERE `order`>0 AND `material_id`=".$DB->F($this->material_id)." ORDER BY `order`, `name`";
            $DB->query($sql);
            while(list($to_name, $to_email) = $DB->fetch()) {
                $tpl->setCurrentBlock("tooption");
                $tpl->setVariable("TO_EMAIL", $to_email);
                $tpl->setVariable("TO_NAME", $to_name);
                if(isset($_POST['to']) && $_POST['to']==$to_email) $tpl->setVariable("TO_SEL", "selected");
                $tpl->parse("tooption");
            }
            $DB->free($sql);
            
        } elseif(($to = $this->getOption('email', 0)) && ($to = explode(';', $to)) && (sizeof($to)>1)) {
            $tpl->setCurrentBlock("selectto");
            for($i=0; $i<sizeof($to); $i+=2) {
                $tpl->setCurrentBlock("tooption");
                $tpl->setVariable("TO_EMAIL", htmlspecialchars($to[$i]));
                $tpl->setVariable("TO_NAME", htmlspecialchars($to[$i+1]));
                if(isset($_POST['to']) && $_POST['to']==$to[$i]) $tpl->setVariable("TO_SEL", "selected");
                $tpl->parse("tooption");
            }
            $tpl->parse("selectto");
        }
        
        if($USER->getId()) {
            $tpl->setCurrentBlock("user_fields");
            $tpl->setVariable("USER_ID", $USER->getId());
            $tpl->setVariable("USER", $USER->getLogin());
            $tpl->setVariable("EMAIL", $USER->getEmail());
            $tpl->parse("user_fields");
        } else {
            $tpl->setCurrentBlock("guest_fields");
            $tpl->setVariable("NAME", htmlspecialchars($USER->getCookie('name')));
            $tpl->setVariable("EMAIL", htmlspecialchars($USER->getCookie('email')));
            $tpl->parse("guest_fields");
        }
        if($this->send_err) $tpl->setVariable("ERROR", $this->send_err);
        $tpl->setVariable("MATERIAL_ID", $this->material_id);
        
        $sql = "SELECT `id`, `name`, `type`, `default`, `regexp`, `required` FROM ".$DB->T('sendemail_fields')." WHERE `order` > 0 AND `material_id`=".$DB->F($this->material_id)." ORDER BY `order`, `name`";
        $DB->query($sql);
        while(list($field_id, $field_name, $field_type, $field_default, $field_regexp, $field_req) = $DB->fetch()) {
            $tpl->setCurrentBlock("add_fields");
            $tpl->setVariable("FIELD_NAME", $field_name);
                 
            switch($field_type) {                                
                case 'textarea':
                case 'text':                
                    $tpl->setCurrentBlock($field_type);        
                    $tpl->setVariable(strtoupper($field_type)."_FIELD_ID", $field_id);
                    $tpl->setVariable(strtoupper($field_type)."_REQUIRED", $field_req ? "required" : "");
                    $tpl->setVariable(strtoupper($field_type)."_ACCEPT", $field_regexp ? $field_regexp : "");
                    $tpl->setVariable(strtoupper($field_type)."_FIELD_VALUE", isset($_POST['fields'][$field_id]) ? htmlspecialchars($_POST['fields'][$field_id]) : $field_default);
                    $tpl->parse($field_type);
                    break;
                case 'checkbox':
                case 'radio':                    
                    foreach(explode(';', $field_default) as $field_val) {                                                
                        $tpl->setCurrentBlock($field_type);        
                        $tpl->setVariable(strtoupper($field_type)."_FIELD_ID", $field_id);
                        $tpl->setVariable(strtoupper($field_type)."_REQUIRED", $field_req ? "required" : "");
                        $tpl->setVariable(strtoupper($field_type)."_ACCEPT", $field_regexp ? $field_regexp : "");
                        $tpl->setVariable(strtoupper($field_type)."_FIELD_VALUE", $field_val);
                        $tpl->parse($field_type);
                    }
                    break;                
                case 'select':
                    $tpl->setCurrentBlock($field_type);        
                    $tpl->setVariable(strtoupper($field_type)."_FIELD_ID", $field_id);
                    $tpl->setVariable(strtoupper($field_type)."_REQUIRED", $field_req ? "required" : "");
                    $tpl->setVariable(strtoupper($field_type)."_ACCEPT", $field_regexp ? $field_regexp : "");
                    $tpl->setVariable(strtoupper($field_type)."_FIELD_VALUE", implode("\r\n", array_map(array($this, 'tooption'), explode(';', $field_default))));
                    $tpl->parse($field_type);                    
                    break;                
            }            
            $tpl->parse("add_fields");
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
        global $DB;
        
        list($event) = $eventinfo;
    
        switch($event)
        {
        }
    }
    
    private function send()
    {
        global $USER, $_BASE, $DB;
        
        if($_BASE != substr(getenv('HTTP_REFERER'), 0, strlen($_BASE))) {
            $this->send_err = 'Отправка сообщений с другого домена запрещена!';
            return false;
        }
        
        if((isset($_POST['sendemail_roboname']) && $_POST['sendemail_roboname']) || isset($_POST['sendemail_robochk'])) {
            // spam robot!
            $this->send_err = 'Вы похожи на спам-робота!';
            return false;
        }
        if(!isset($_POST['sendemail_text']) || !$_POST['sendemail_text']) {
            // no body
            $this->send_err = 'Не введено сообщение';
            return false;
        }
        
        if($_POST['sendemail_user_id'] && $_POST['sendemail_user_id']==$USER->getId()) {
            $name = $USER->getLogin()." (зарегистрирован)";
            $email = $_POST['sendemail_email'] ? $_POST['sendemail_email'] : $USER->getEmail(); 
        } elseif(!$_POST['sendemail_user_id'] && $_POST['sendemail_name'] && ($_POST['sendemail_email'] || $_POST['sendemail_cont'])) {
            $name = $_POST['sendemail_name'];
            $email = $_POST['sendemail_email'];
            
            $USER->setCookie("name", $name);
            $USER->setCookie("email", $email);
            $name .= " (не зарегистрирован)";
        } else {
            // no user data
            $this->send_err = "Не введено имя, email или способ связи";
            return false;
        }
        
        if(!preg_match("/^[a-z0-9\._-]+\@[a-z0-9\._-]+\.[a-z]+$/i", $email) && !$_POST['sendemail_cont']) {
            $this->send_err = "Недопустимый email или способ связи";
            return false;
        }
        
        $field_data  = '';
        $subj_data   = '';
        $sql = "SELECT `id`, `name`, `type`, `regexp`, `required`, `is_subj` FROM ".$DB->T('sendemail_fields')." WHERE `order` > 0 AND `material_id`=".$DB->F($this->material_id)." ORDER BY `order`, `name`";
        $DB->query($sql);
        while(list($field_id, $field_name, $field_type, $field_regexp, $field_req, $is_subj) = $DB->fetch()) {
            if($field_req && !$_POST['fields'][$field_id]) {
                $this->send_err = "Не заполнено обязательное поле $field_name";
                $DB->free();
                return false;
            } elseif($_POST['fields'][$field_id] && $field_regexp && !preg_match($field_regexp, $_POST['fields'][$field_id])) {
                $this->send_err = "Недопустимое значение в поле $field_name";
                $DB->free();
                return false;
            }
            
            $field_data .= "$field_name: ".(is_array($_POST['fields'][$field_id]) ? implode("; ", $_POST['fields'][$field_id]) : $_POST['fields'][$field_id])."\r\n\r\n";
            $subj_data .= $is_subj ? (is_array($_POST['fields'][$field_id]) ? implode("; ", $_POST['fields'][$field_id]) : $_POST['fields'][$field_id]).' ' : '';
        }
        $DB->free();
        
        if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('sendemail_addr')." WHERE `order`>0 AND `material_id`=".$DB->F($this->material_id))) {
            if($DB->getField("SELECT COUNT(*) FROM ".$DB->T('sendemail_addr')." WHERE `order`>0 AND `material_id`=".$DB->F($this->material_id)." AND `email` LIKE ".$DB->F($_POST['to'])) != 1) {
                $this->send_err = 'Недопустимый адрес назначения';
                return false;
            } 
            $to = $_POST['to'];                       
        } else {
            if(!($to = $this->getOption('email', 0))) {
                $this->send_err = 'Невозможно отправить сообщение';
                return false;
            }
                    
            if(isset($_POST['to']) && (false === strpos($to, $_POST['to']))) {
                $this->send_err = 'Недопустимый адрес назначения';
                return false;
            } elseif(isset($_POST['to']) && preg_match("/^[a-z0-9\._-]+\@[a-z0-9\._-]+\.[a-z]+$/i", $_POST['to'])) {
                $to = $_POST['to'];
            }    
        }
        
        if($this->getOption('use_replyto', 0) && preg_match("/^[a-z0-9\._-]+\@[a-z0-9\._-]+\.[a-z]+$/i", $email)) {
            $replyto = "Reply-To: $email\r\n";
        } else {
            $replyto = '';
        }
        
        if($this->getOption('use_from', 0) && preg_match("/^[a-z0-9\._-]+\@[a-z0-9\._-]+\.[a-z]+$/i", $email)) {
            $replyto = "From: $email\r\n";
        } else {
            $from = "From: ".$this->getUid().'@'.$_SERVER['HTTP_HOST']."\r\n";
        }
                
        $subj = "Сообщение с сайта ".getSetting('site_title')." ($_BASE)";
        if($subj_data) $subj = $subj_data."[$subj]";
        		
		$subj = "=?UTF-8?B?".base64_encode($subj)."?=";
		$text = "От: $name <$email>\r\nСвязь: ".($_POST['sendemail_cont'] ? $_POST['sendemail_cont'] : "-")."\r\n\r\n$field_data".$_POST['sendemail_text'];

		if(!mail($to, $subj, $text, $from.$replyto."MIME-Version: 1.0\r\nContent-type: text/plain; charset=UTF-8\r\n")) {
            cmsLogObject('Не удалось отправить сообщение по e-mail', 'plugin', $this->getUid());
            $this->send_err = 'Не удалось отправить сообщение';
            return false;
		}
        return true;
    }
    
    private function tooption($value)
    {        
        return "<option>$value</option>";
    }
}
?>