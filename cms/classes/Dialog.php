<?php

/**
 * Класс вывода диалога CPL
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 * @todo доделать добавление замену всех кнопок через массив 
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
require_once("$_ROOT/cms/classes/ITM.php");
 
class Dialog
{
    private $template = 'dialog.html';    
    private $buttons = array('DLG_OK'=>"OK", 'DLG_CANCEL'=>"Отмена");
    private $dialog_title = "Диалог";
    private $dialog_script;
    private $dialog_callback;
    
    /**
     * Dialog::Dialog()
     * 
     * @param string $script - скрипт для тела диалога из cpl/dialogs, .dlg.php добавляется автоматически
     * @param string $js_callback - callback-функция (javascript) родительского окна
     * @param string $title - загловок окна диалога
     * @return всегда true
     */
    function Dialog($script, $js_callback = '', $title = '')
    {
        if($title) $this->dialog_title = $title;
        $this->dialog_script = "$script.dlg.php";
        $this->dialog_callback = $js_callback;
        
        return true;
    }
    
    /**
     * Dialog::appendButton() - добавляет/заменяет кнопки диалога
     * 
     * @param string $button_id - ИД кнопки (DLG_OK и тп)
     * @param string $button_text - текст на кнопке
     * @return кол-во кнопок в диалоге
     */
    function appendButton($button_id, $button_text)
    {
        $this->buttons[$button_id] = $button_text;
        return sizeof($this->buttons);
    }
    
    /**
     * Dialog::removeButton() - удалет кнопку или все кнопки диалога
     * 
     * @param string $button_id, ИД кнопки (DLG_OK и тп), если не указан - удаляет все кнопки
     * @return кол-во кнопок в диалоге
     */
    function removeButton($button_id = null)
    {
        if($button_id) unset($this->buttons[$button_id]);
        else $this->buttons = array();
        return sizeof($this->buttons);
    }
    
    /**
     * Dialog::get() - получить HTML код всего диалога
     * 
     * @return HTML всего диалога
     */
    function get()
    {
        global $_config, $_ROOT, $_BASE;
        
        assert('is_file("$_ROOT/cms/cpl/templates/dialog.html")');
        
        $tpl = new HTML_Template_IT("$_ROOT/cms/cpl/templates");
        $tpl->loadTemplatefile("dialog.html", true, true);
        
        $tpl->setVariable("TITLE", htmlspecialchars($this->dialog_title));
        $tpl->setVariable("CALLBACK_FUNCTION", addslashes($this->dialog_callback));
        $tpl->setVariable("DIALOG_BODY", $this->getContents());
        
        while(list($button_id, $button_text) = each($this->buttons))
        {
            $tpl->setCurrentBlock("button");
            $tpl->setVariable("BUTTON_ID", $button_id);
            $tpl->setVariable("BUTTON_TEXT", $button_text);
            $tpl->parse("button");
        }
        
        return $tpl->get();
    }
    
    /**
     * Dialog::show() - выводит диалог в броузер
     * 
     * @return ничего
     */
    function show()
    {
        echo $this->get();
    }
    
    /**
     * Dialog::getContents() - получить только результат работы скрипта диалога, без шапки и кнопок
     * удобно при выводе диалога как части страницы
     * @see cpl/struct.php
     * 
     * @return HTML содержательной части диалога
     */
    function getContents()
    {
        global $_config, $_ROOT, $_BASE;
                
        assert('is_file("$_ROOT/cms/cpl/dialogs/".$this->dialog_script)');
        
        ob_start();
        
        include_once("$_ROOT/cms/cpl/dialogs/".$this->dialog_script);
        
        $ret = ob_get_contents();
        ob_end_clean();
        
        return $ret;
    }
}

class PluginDialog extends Dialog
{
    function PluginDialog()
    {
        
    }
}
?>