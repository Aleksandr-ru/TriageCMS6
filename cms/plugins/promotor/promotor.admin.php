<?php

/**
 * Управление модулем
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2011
 */

 
if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../../config.php");
require_once("$_ROOT/cms/classes/Plugin.php");
require_once("$_ROOT/cms/classes/ITM.php");
require_once("$_ROOT/cms/classes/ErrorSession.php");

require_once("$_ROOT/cms/classes/Page.php");
 
class promotorPluginAdmin extends Plugin
{
    const uid = 'promotor';
           
    /**
     * <plugin_name>PluginAdmin::__construct() стандартный конструктор
     * 
     * @return экземпляр класса
     */
    function __construct()
    {
        parent::__construct(self::uid);
    }
    
    /**
     * <plugin_name>PluginAdmin::get() основная функция модуля управления
     * вызывается при входе в управление плагином
     * 
     * @return string text/html какоторый будет выведен в броузер клиента
     */
    function get()
    {                    
        if(defined("AJAX")) {
            switch($_GET['event'])
            {
                case "delete":
                    return $this->delete($_POST['banner_id']) ? "OK" : "Ошибка удаления";                    
                case "toggle":
                    return $this->toggle($_POST['banner_id']) ? "OK" : "Ошибка!";
            }
            return "No event";
        }
        
        switch($_GET['event'])
        {
            case 'edit':
                return $this->editor($_GET['banner_id']);
            case 'save':
                return $this->save_banner();
            default:
                return $this->showlist();
        }
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_load()
     * вызывается при загрузке редактора материалов со ссылкой на плагин
     * 
     * @param MaterialEx $material
     * @see classes/MaterialEx.php
     * @return string html форма, которая выводится в редактор материала
     */
    function materialeditor_load($material)
    {        
        global $_ROOT, $DB;
        
        return false;
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_save()
     * вызывается после сохранения материала, если в редакторе была ссылка на плагин
     * 
     * @param MaterialEx $material $material
     * @see classes/MaterialEx.php
     * @param bool $is_new признак, что это только что созданный материал
     * @return bool результат обработки (сохранения своих данных плагином)
     * если возвращается false, то <plugin_name>PluginAdmin::materialeditor_error() должна возвращать описание ошибки  
     */
    function materialeditor_save($material, $is_new = false)
    {
        global $DB, $USER;
        
        return false;
    }
    
    /**
     * <plugin_name>PluginAdmin::materialeditor_error() 
     * вызывается после сохранения материала, если <plugin_name>PluginAdmin::materialeditor_save() вернула false     
     * 
     * @return string описание ошибки произошедшей в <plugin_name>PluginAdmin::materialeditor_save() или false если ошибок не было 
     */
    function materialeditor_error()
    {
        return false;
    }
    
    /*************************************************************************************/
    
    private function showlist()
    {
        global $_BASE, $_ROOT, $DB;
                
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid());
        $tpl->loadTemplatefile("admin.list.html");
        $tpl->setVariable("PLUGIN_UID", $this->getUid());
        
        $tpl->setVariable("NUM_TOTAL", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('promotor')));
        $tpl->setVariable("NUM_ACTIVE", $DB->getField("SELECT COUNT(*) FROM ".$DB->T('promotor')." WHERE `active`"));
        
        $sql = "SELECT * FROM ".$DB->T('promotor')." AS p ORDER BY p.active DESC, p.name";
        $DB->query($sql);
        while($banner = $DB->fetch(true)) {
            $href = $banner['href'];
            if(preg_match("/^#(\d)+#/", $href, $arr)) {
                $href = Page::fullpath($arr[1]);
            }
            
            $tpl->setCurrentBlock("row");
            $tpl->setVariable("ID", $banner['id']);
            $tpl->setVariable("NAME", $banner['name']);
            $tpl->setVariable("HREF", $href);
            $tpl->setVariable("SHOWS", $banner['shows']);
            $tpl->setVariable("CLICKS", $banner['clicks']);
            $tpl->setVariable("CTR", round($banner['clicks']/$banner['shows']*100, 1));
            $tpl->setVariable("CLASS", $banner['active'] ? 'active' : 'inactive');
            
            $file = "files/promotor/{$banner['id']}.{$banner['file_ext']}";
            list(,,, $dimensions) = getimagesize($_ROOT.'/'.$file);
            $tpl->setVariable('IMG_SRC', $_BASE.$file);
            $tpl->setVariable('IMG_DIMENSIONS', $dimensions);            
            
            $tpl->parse("row");
        }
        $DB->free();
        
        return $tpl->get();
    }
    
    private function editor($id)
    {
        global $_ROOT, $_BASE, $DB, $E;
                        
        $tpl = new HTML_Template_IT("$_ROOT/cms/plugins/".$this->getUid());
        $tpl->loadTemplatefile("admin.editor.html");
        $tpl->setVariable("PLUGIN_UID", $this->getUid());   
        
        if($id) {
            $banner = $DB->getRow("SELECT * FROM ".$DB->T('promotor')." AS p WHERE `id`=".$DB->F($id), true);
            
            if(!$banner) {
                $E->addWarning("Нет такого баннера", "Выбранный для редактирования баннер не найден");
                cpl_redirect("plugin.php?plugin_uid={$this->getUid()}");  
                exit();
            }
            
            $tpl->setVariable("ID", $banner['id']);
            $tpl->setVariable("ID_TEXT", $banner['id']);
            $tpl->setVariable("NAME", $banner['name']);
            $tpl->setVariable("HREF", $banner['href']);
                        
            $file = "files/promotor/{$banner['id']}.{$banner['file_ext']}";
            list(,,, $dimensions) = getimagesize($_ROOT.'/'.$file);
            $tpl->setVariable('IMG_SRC', $_BASE.$file);
            $tpl->setVariable('IMG_DIMENSIONS', $dimensions); 
            
        } else {
            $E->addNotice("Загрузите изображение размером не более ".ini_get('upload_max_filesize'));
            $tpl->setVariable('ID_TEXT', 'Новый');
        }
        
        return $tpl->get(); 
    }
    
    private function save_banner()
    {
        global $DB, $_ROOT, $E;
        //print_r($_POST); Array ( [promotor_id] => [promotor_name] => [promotor_href] => )
        
        if(!$_POST['promotor_name']) $E->addWarning("Введите название");
        if(!$_POST['promotor_href']) $E->addWarning("Укажите ссылку");
        
        if(!$_POST['promotor_id']) {
            if($_FILES['userfile']['error']) $E->addWarning("Ошибка загрузки файла", "Код {$_FILES['userfile']['error']}");
            elseif(strpos($_FILES['userfile']['type'], 'image') !== 0) $E->addWarning("Недопустимый тип файла", "Допускается загружать только изображения.");
        }
        if($E->isError() || $E->isWarning()) {
            cpl_redirect("plugin.php?plugin_uid={$this->getUid()}&event=edit&promotor_id={$_POST['promotor_id']}");  
            exit();
        }
        
        if($_POST['promotor_id']) 
        {
            $sql = "UPDATE ".$DB->T('promotor')." SET `name`=".$DB->F($_POST['promotor_name']).", `href`=".$DB->F($_POST['promotor_href'])." WHERE `id`=".$DB->F($_POST['promotor_id']);    
        } else {
            $sql = "INSERT INTO ".$DB->T('promotor')." (`name`, `href`) VALUES(".$DB->F($_POST['promotor_name']).", ".$DB->F($_POST['promotor_href']).")";
        }
        $DB->query($sql);
        if($DB->errno()) {
            $E->addError("Ошибка MySQL", $DB->error());
            cpl_redirect("plugin.php?plugin_uid={$this->getUid()}&event=edit&promotor_id={$_POST['promotor_id']}");  
            exit();
        } elseif(!$_POST['promotor_id']) {
            $_POST['promotor_id'] = $DB->insert_id();    
        }
        
        if(!$_FILES['userfile']['error']) {
            if($old_ext = $DB->getField("SELECT `file_ext` FROM ".$DB->T('promotor')." WHERE `id`=".$DB->F($_POST['promotor_id']))) {
                @unlink("files/promotor/{$_POST['promotor_id']}.$old_ext");
            }
            $ext = array_pop(explode('.', $_FILES['userfile']['name'])); 
            $filename = "/files/promotor/{$_POST['promotor_id']}.$ext"; 
            
            if(!move_uploaded_file($_FILES['userfile']['tmp_name'], $_ROOT.$filename)) {
                $E->addError("Ошибка перемещения файла");
            } else {
                $sql = "UPDATE ".$DB->T('promotor')." SET `file_ext`=".$DB->F($ext)." WHERE `id`=".$DB->F($_POST['promotor_id']);
                $DB->query($sql);    
            }
        }
        
        if($E->isError() || $E->isWarning()) {
            cpl_redirect("plugin.php?plugin_uid={$this->getUid()}&event=edit&promotor_id={$_POST['promotor_id']}");  
            exit();
        } else {
            cpl_redirect("plugin.php?plugin_uid={$this->getUid()}");  
            exit();
        }
        
    }
    
    private function delete($id)
    {
        global $DB, $_ROOT;
        
        if($old_ext = $DB->getField("SELECT `file_ext` FROM ".$DB->T('promotor')." WHERE `id`=".$DB->F($id))) {
            @unlink("files/promotor/{$_POST['promotor_id']}.$old_ext");
        }
        
        $DB->query("DELETE FROM ".$DB->T('promotor')." WHERE `id`=".$DB->F($id));
        return $DB->errno() ? false : true;
    }
    
    private function toggle($id)
    {
        global $DB;
        $DB->query("UPDATE ".$DB->T('promotor')." SET `active` = IF(`active`, 0, 1) WHERE `id`=".$DB->F($id));
        return $DB->errno() ? false : true;
    }
}

?>