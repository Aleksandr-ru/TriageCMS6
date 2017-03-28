<?php

/**
 * Функции для CMS 
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2009
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

require_once(dirname(__FILE__)."/../config.php");

/**
 * make_base() делает любой урл пригодеым для base href
 * 
 * @param string $url
 * @return нормальзованная ссылка для base href с / на коннце
 */
function make_base($url)
{
    global $_config, $_ROOT, $_BASE;
    
    if(strpos($url, $_BASE) !== 0) $url = "$_BASE/$url";
    $url .= "/"; // обязательно кончается на /
    //return ereg_replace("[^:]//+", "/", $url);  -- php 5.3 deprecated
    return preg_replace("@([^:])//+@", "$1/", $url);    
}

/**
 * parse_html() преобразовывает HTML в пригодный вид, для вывода на любой странице в контексте CMS
 * ссылки вида href="#..." преобразуются к виду href="<УРЛ_СТРАНИЦЫ_С_ПАРАМЕТРАМИ>#..." 
 * 
 * @param string $html - исходный HTML
 * @return преобразованный HTML
 */
function parse_html($html)
{	
	global $_config, $_ROOT, $_BASE;
    
    //BUG:архаизм? (начиная с 6.0.d)
    //$html = preg_replace("@=([\"'])/(.*)([\"'])@", "=$1".make_base($_BASE)."$2$3", $html); // меняем /images на images чтоб корректно относительно $_BASE
	
    //BUG:следующая строчка райзит Error (2048)! Only variables should be passed by reference, но работает
    // приводим ссылки вида #123 к http://full/path/#123
    $html = preg_replace("@href=([\"'])(#\S*)([\"'])@i", "href=$1".array_shift(explode("#", $_SERVER['REQUEST_URI']))."$2$3", $html); 
    
	return $html;
}
 
/**
 * raise_event() вызывает обработчики события
 * принимает на вход произвольное количетсво параметров
 * но ппервый всегда тектсовый идентификатор события
 * все параметры передаются вызываемому обработчику в виде массива eventinfo
 * 
 * @param string $event - идентификатор события
 * @param mixed ...     - остальные зависят от события
 * @param ...
 * @return void
 */
function raise_event($event)
{
    global $_config, $_ROOT, $_BASE;
    global $DEBUG, $DB;
    
    $eventinfo = func_get_args();
    
    $sql = "SELECT e.plugin_uid FROM ".$DB->T('_events')." AS e WHERE(e.event = ".$DB->F($event).")";
    $result = $DB->query($sql);
    while(list($plugin_uid) = $DB->fetch(false, false, $result))
    {
        /*
        include_once("$_ROOT/cms/plugins/$plugin/$plugin.php");
        $function = $plugin."_event";
        if(function_exists($function)) $function($eventinfo);
        else $DEBUG->mes(1, "Function $function does not exists!", __FILE__, __LINE__, "raise_event($event)");
        */
        
        $plugin_file = "$_ROOT/cms/plugins/$plugin_uid/$plugin_uid.php";
        if(!is_file($plugin_file)) {            
            Debugger::mes(301, "Plugin file ($plugin_file) is absent for '$plugin_uid'.", __FILE__, __LINE__);
            continue;
        }
        
        include_once($plugin_file);
        
        $plugin_class = $plugin_uid."Plugin";
        if(!class_exists($plugin_class)) {
            Debugger::mes(302, "Plugin class for '$plugin_uid' does not exists.", __FILE__, __LINE__);
            continue;
        }
        
        $plugin = new $plugin_class();
        if(!method_exists($plugin, "event")) {
            Debugger::mes(303, "Plugin class method ($plugin_class::event) for '$plugin_uid' does not exists.", __FILE__, __LINE__);
            continue;
        }
        
        $plugin->event($eventinfo);
        unset($plugin);
    }
    $DB->free($result);
} 

/**
 * getSetting() - получить значение из таблиы настроек CMS
 * елси значение настройки имеет вид <таблица_CMS>:значение, то возвращается только значение
 * 
 * @param string $setting_name - имя значения (имя настройки)
 * @return значение настроки
 */
function getSetting($setting_name)
{
    global $_config, $_ROOT, $_BASE;
    global $DEBUG, $DB;
    
    $value = $DB->getField("SELECT st.value FROM ".$DB->T('_settings')." AS st WHERE st.name LIKE ".$DB->F($setting_name));
    if(preg_match("/^".$_config['table_prefix'].".+\..+:(.+)$/i", $value, $val)) $value = $val[1];
    return $value;
}

/**
 * cmsLog() - вносит запись в журнал работы системы
 * в зависимости от настроек уровня журналирования
 * 
 * @param string $text - запись
 * @param string $tag - необязательный параметр, определяющий объект, к которому относится запсиь, предпочтительный вид object:id
 * @return 0 в случае успеха, код ошибки в остальных случаях
 */
function cmsLog($text, $tag = '')
{
    global $DB, $USER;
    
    $log_level = getSetting('log_level'); 
    if($log_level == 0) return true;
    elseif($log_level == 1 && $tag) return true;
    
    $sql = "INSERT INTO ".$DB->T('_log')." (`datetime`, `user_id`, `text`, `tag`) VALUES( NOW(), ".$DB->F($USER->getId()).", ".$DB->F($text).", ".$DB->F($tag).")";
    $DB->query($sql);
    return $DB->errno();
}

/**
 * cmsLogObject() - вносит запись для объекта в журнал работы системы
 * в зависимости от настроек уровня журналирования
 * 
 * @param string $text - запись
 * @param string $object - тип объекта
 * @param integer/string $object_id - идентификатор, предпочтительно цифровой, но может быть и текстовый
 * @return 0 в случае успеха, код ошибки в остальных случаях
 */
function cmsLogObject($text, $object, $object_id = 0)
{
    return cmsLog($text, "$object:$object_id");
}

/**
 * make_key() переводит строку в ключ пригодный для использований в URL
 * ключ содержит только латинские символы в нижнем регистре, цифры и подчеркивания "_"
 * 
 * @param string $str - исходная строка
 * @param integer $maxlength - максимальная длина результата, если 0 - то не обрезается
 * @return строку состоящую только из латинских символов, цифр и подчеркиваний
 */
function make_key($str, $maxlength = 0)
{
    $str = preg_replace("/('|\")/", "", strtolower(translit($str)));
    $str = preg_replace("/[^a-z0-9]/i", "_", $str);
    $str = trim($str, "_");
    return $maxlength ? substr($str, 0, $maxlength) : $str;
}

/**
 * make_clean() приводит строку к виду, рекмондуемому для использования в удаленной фалйовой системе или URL
 * результат содержит только латинские символы и допустимые для файловой системы и URL (тире, точка, подчеркивание, скобки)
 * 
 * @param string $str - исходная строка
 * @param integer $maxlength - максимальная длина результата, если 0 - то не обрезается
 * @return преобразованную строку без перевода каретки
 */
function make_clean($str, $maxlength = 0)
{
    $str = preg_replace("/('|\")/", "", translit($str));
    $str = preg_replace("/[^a-z0-9\-.()\[\]]/i", "_", $str);
    $str = preg_replace("/([_])+/i", "$1", $str);
    $str = preg_replace("/([\-])+/i", "$1", $str);
    $str = preg_replace("/([.])+/i", "$1", $str);
    return $maxlength ? substr($str, 0, $maxlength) : $str;    
}

/**
 * make_clean_filename() - аналог make_clean(), но с той разницей, что максимальная длинна считается с учетом расширения файла
 * т.е. расширение файла всегда сохраняется, а имя образается до нужной длинны с учетом длинны расширения
 * 
 * @param string $str - исходная строка
 * @param integer $maxlength - максимальная длина результата, если 0 - то не обрезается и получается полный аналог make_clean()
 * @return преобразованную строку без перевода каретки
 */
function make_clean_filename($str, $maxlength = 0)
{
    if($maxlength) {
        $pathinfo = pathinfo(make_clean($str));
        $pathinfo['filename'] = substr($pathinfo['filename'], 0, $maxlength - strlen($pathinfo['extension'] - 1));
        return $pathinfo['filename'].".".$pathinfo['extension'];
    } else {
        return make_clean($str);
    }
}

/**
 * translit() - транслитирирует строку, поддерживает UTF-8
 * 
 * @param string $str - исходная строка
 * @return транслитирированная строка
 */
function translit($str)
{
    //BUG:закоменченный метод не корректно работает с UTF-8, используем альтернативу
    /*
    $str = strtr($str,"абвгдеёзийклмнопрстуфхцьыэ", "abvgdeeziyklmnoprstufhc'ie");
	$str = strtr($str,"АБВГДЕЁЗИЙКЛМНОПРСТУФХЦЬЫЭ", "ABVGDEEZIYKLMNOPRSTUFHC'IE");
	$str = strtr($str, array("ж"=>"zh", "ч"=>"ch", "ш"=>"sh", "щ"=>"sch","ъ"=>"", "ю"=>"yu", "я"=>"ya", "Ж"=>"Zh", "Ч"=>"Ch", "Ш"=>"Sh", "Щ"=>"Sch","Ъ"=>"", "Ю"=>"Yu", "Я"=>"Ya") );
    */
    $search = array("а","б","в","г","д","е","ё","з","и","й","к","л","м","н","о","п","р","с","т","у","ф","х","ц","ь","ы","э","А","Б","В","Г","Д","Е","Ё","З","И","Й","К","Л","М","Н","О","П","Р","С","Т","У","Ф","Х","Ц","Ь","Ы","Э", "ж","ч","ш","щ","ъ","ю","я","Ж","Ч","Ш","Щ","Ъ","Ю","Я");
    $replace = array("a","b","v","g","d","e","e","z","i","y","k","l","m","n","o","p","r","s","t","u","f","h","c","'","i","e","A","B","V","G","D","E","E","Z","I","Y","K","L","M","N","O","P","R","S","T","U","F","H","C","'","I","E","zh","ch","sh","sch","","yu","ya","Zh","Ch","Sh","Sch","","Yu","Ya");
    $str = str_replace($search, $replace, $str);
    return $str; 
}

/**
 * getPageName() получить имя страницы по ID
 * 
 * @param integer $id - ID страницы в БД
 * @return имя страницы или '' в случае ошибки
 */
function getPageName($id)
{
    global $DB;
    return $DB->getField("SELECT `name` FROM ".$DB->T('_pages')." WHERE `id`=".$DB->F($id));
}

/**
 * getMaterialName() получить название материала по ID
 * 
 * @param integer $id - ID материала в БД
 * @return название материала или '' в случае ошибки
 */
function getMaterialName($id)
{
    global $DB;
    return $DB->getField("SELECT `name` FROM ".$DB->T('_material')." WHERE `id`=".$DB->F($id));
}

/**
 * упраздена за ненадобностью после перехода на ITM 0.2
 * 
 * htmlescapetmpl() - экранирует текст так, чттобы он не парсился ITM
 * фактически делается htmlspecialchars и фигурные скобки переводятся
 * в коды HTML
 * 
 * @param string $str исходная строка
 * @param bool $htmlspecialchars - экранировать htmlspecialchars
 * @return string
 */
/*
function htmlescapetmpl($str, $htmlspecialchars = false)
{
    if($htmlspecialchars) $str = htmlspecialchars($str);
    $search = array('{', '}', '$');
    $replace = array('&#0123;', '&#0125;', '&#0036;');
    return str_replace($search, $replace, $str);    
    return $str;
}*/

/**
 * format_filesize() делает читабельный размер файла
 * например 100.5 Кб или 2 034.7 Мб
 * 
 * @param integer $bytes размер в байтах
 * @param integer $decimals_precision количетсво знаков после точки
 * @return string форматированный размер
 */
function format_filesize($bytes = 0, $decimals_precision = 1)
{
    if ($bytes > 1024 * 1024) { // mb
        return number_format(round($bytes / 1024 / 1024, $decimals_precision), $decimals_precision, ".", ' ')." Мб";
    } else { //kb
        return number_format(round($bytes / 1024, $decimals_precision), $decimals_precision, ".", ' ')." Кб";
    }
}

/**
 * page_split() разбивает строку $str по страницам ориентируясь на вхождения '<!-- pagebreak -->' в тексте
 * поскольку часто вместо простого разрыва встречается '<p><!-- pagebreak --></p>',
 * то чтобы не ломать верстку используется регулярное выражение, которое вычищает оборванные тэги
 * 
 * @param string $str - исходный текст
 * @return array
 */
function page_split($str)
{
    $arr = explode("<!-- pagebreak -->", $str);        
    for($i=1; $i<sizeof($arr); $i++) {
        if(preg_match("@^</.+>@iU", $arr[$i])) {
            $arr[$i-1] = preg_replace("@<[^/]+>$@iU", "", $arr[$i-1]);
            $arr[$i] = preg_replace("@^</.+>@iU", "", $arr[$i]);
        }
    }
    return $arr;
    
    //TODO:придумать регулярное выражение чтоб уйти от цикла
    /* тестовый пример
        <p><img style="padding: 10px; border: thin dotted #0e05f9;" src="files/1/Mister.Furry(ani-1-4).gif" alt="" /> <img src="files/1/Mister.Furry(1-1).gif" alt="" /> <a href="files/1/20081213_nogovo_dmitrovka_dom.kmz">20081213 ногово дмитровка дом.kmz</a> <a href="#test">test #</a></p>
        <p><!-- pagebreak --></p>
        <p>{TEST}</p>
        <p>Это было набито через <strong><cite title="Это визуальный редактор">TinyMCE</cite></strong></p>
    */
    //return preg_split("@(^|\n|<(\w).*>)?<!-- pagebreak -->(</\2>|\n|$)?@imU", $str);    
    //return preg_split("@(<([^/]+).*>)?<!-- pagebreak -->(</\\2>)?@imU", $str);    
}

/**
 * rudate() аналог date(), но с русскими названиями месяцев и дней недели
 *  
 * @param string $format - The format of the outputted date string.
 * F Полное наименование месяца, например Января или Марта от Января до Декабря 
 * M Сокращенное наименование месяца, 3 символа От Янв до Дек 
 * l (строчная 'L') Полное наименование дня недели От Воскресенье до Суббота 
 * D Сокращенное наименование дня недели, 2 символа от Вс до Сб 
 * остальные варианты форматирования см. функцию date() в мануале.
 * @param mixed $timestamp is optional and defaults to the value of time()
 * если в $timestamp не цифра, то функция пытается получить $timestamp при помощи strtotime($timestamp)
 * @param bool $nominative_month - Полное наименование месяца (F) в именительном падеже, влияет только если в $format присутствует 'F'
 * если $nominative_month истина, то: F Полное наименование месяца, например Январь или Март от Январь до Декабрь 
 * если $nominative_month ложь,   то: F Полное наименование месяца, например Января или Марта от Января до Декабря
 * @return a string formatted according to the given format string using the given integer/string timestamp or the current time if no timestamp is given.
 */
function rudate($format, $timestamp = 0, $nominative_month = false)
{
	if(!$timestamp) $timestamp = time();
	elseif(!preg_match("/^[0-9]+$/", $timestamp)) $timestamp = strtotime($timestamp);
	
    $F = $nominative_month ? array(1=>"Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь") : array(1=>"Января", "Февраля", "Марта", "Апреля", "Мая", "Июня", "Июля", "Августа", "Сентября", "Октября", "Ноября", "Декабря");
	$M = array(1=>"Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек");
	$l = array("Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота");
	$D = array("Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб");

	$format = str_replace("F", $F[date("n", $timestamp)], $format);
	$format = str_replace("M", $M[date("n", $timestamp)], $format);
	$format = str_replace("l", $l[date("w", $timestamp)], $format);
	$format = str_replace("D", $D[date("w", $timestamp)], $format);
	
	return date($format, $timestamp);
}

function getFileHref($file_id)
{
    global $DB;
    list($material_id, $clean_name) = $DB->getRow("SELECT `mat_id`, `clean_name` FROM ".$DB->T('_files')." WHERE `id`=".$DB->F($file_id));
    if(!$material_id || !$clean_name) return false;
    else return "files/$material_id/$clean_name";
}
?>