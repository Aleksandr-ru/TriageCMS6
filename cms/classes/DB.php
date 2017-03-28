<?php

/**
 * Класс работы с БД
 * 
 * @package Triage CMS v.6
 * @version 6.2
 * @author Rebel
 * @copyright 2010
 * @todo сделать работу с другими типам БД
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');
 
require_once(dirname(__FILE__)."/../config.php");
require_once("$_ROOT/cms/classes/Debugger.php");
 
/**
 * DB - базовый абстрактный класс для работы с БД
 * 
 * @access public
 */
abstract class DB
{
    protected $insert_id = 0;
    protected $queries_counter = 0;
    
    /**
     * DB::table() добавляет префикс к имени таблицы
     * 
     * @param string $table
     * @param bool $revApostr признак необходимости обернуть название в обатные апострофы (`) применимо только для DB_MySQL
     * @return имя таблицы с префиксом, готовое для подстановки в запрос
     */
    function table($table, $revApostr = true)
    {
        global $_config;
        
        $table = trim($table, "`'\"");
                
        switch(get_class($this)) {
            case 'DB_MySQL':
                //return $revApostr ? "`".addslashes($_config['table_prefix'].$table)."`" : addslashes($_config['table_prefix'].$table);  
                return $revApostr ? "`".mysql_real_escape_string($_config['table_prefix'].$table)."`" : mysql_real_escape_string($_config['table_prefix'].$table);
            case 'DB_Oracle':
                return ($_config['ora_schema'] && !strpos($table, '.')) ? $_config['ora_schema'].".$table" : $table;
            default:
                trigger_error("DB::table() called from unsupported class '".get_class($this)."'!", E_USER_WARNING);
                return $table;
        }                
    }
    
    /**
     * DB::tableField() формирует название поле с названием таблицы
     * например table1.id
     * 
     * @param string $table
     * @param string $field
     * @return string
     */
    function tableField($table, $field)
    {
        //return self::table($table, false).'.'.addslashes($field);
        return self::table($table, false).'.'.mysql_real_escape_string($field);
    }
    
    /**
     * DB::T() - алиас к DB::table()
     * 
     * @return имя таблицы с префиксом, готовое для подстановки в запрос
     */
    function T($table)
    {
        return self::table($table);
    }
    
    /**
     * DB::TF() алиас к DB::tableField()
     * 
     * @param string $table
     * @param string $field
     * @return string
     */
    function TF($table, $field = 'id')
    {
        return self::tableField($table, $field);
    }
    
    /**
     * DB::field() - экранирует значение и оборачивает в одинарные кавычки (')
     * 
     * @param mixed $value - исходное значение
     * @param integer $maxlength - максимальная длинна, если 0 - то не обрезается
     * @return значение, готовое для подстановки в запрос
     */
    static function field($value, $maxlength = 0)
    {
        //$value = $maxlength ? addslashes(substr($value, 0, $maxlength)) : addslashes($value);
        $value = mysql_real_escape_string($maxlength ? substr($value, 0, $maxlength) : $value);
        return "'$value'";
    }
    
    /**
     * DB::nullfield() - экранирует значение и оборачивает в одинарные кавычки (')
     * в случае пустого значения возвращает NULL
     * 
     * @param mixed $value - исходное значение
     * @param integer $maxlength - максимальная длинна, если 0 - то не обрезается
     * @return значение, готовое для подстановки в запрос
     */
    static function nullField($value, $maxlength = 0)
    {
        //BUG:sql-mode=STRICT_TRANS_TABLES при null не ставит дефолтное значение а ругается в MySQL 5.x
        if($value || $value === 0 || $value == '0') {
            return self::field($value, $maxlength);    
        } else {
            return "NULL";    
        }        
    }
    
    /**
     * DB::F() - экранирует значение и оборачивает в одинарные кавычки (') 
     * опционально может выозвращать NULL в случае пустого значения 
     * 
     * @param mixed $value - исходное значение
     * @param bool $allow_null - dthyenm NULL в случае пустого значения 
     * @return значение, готовое для подстановки в запрос
     */
    static function F($value, $allow_null = false)
    {
        return $allow_null ? self::nullfield($value) : self::field($value);
    }
    
    function getQueriesCount()
    {
        return $this->queries_counter;
    }
    
    /**
     * DB::getField() - получить одно первое значение из SQL запроса, вида
     * select VALUE from TABLE where ID = 1 
     * предполагается, что запрос возвращает только один ряд с одним полем
     * 
     * @param string $sql - строка запроса
     * @param bool $escape - экранировать результат
     * @return первое поле из результата или false если записи не найдено
     */
    function getField($sql, $escape = true)
    {
        $result = $this->query($sql);
        if($this->num_rows($result) != 1){
            Debugger::mes(1, ($this->num_rows($result)==''?'No':$this->num_rows($result))." rows found!", __FILE__, __LINE__, "DB::getField( $sql )");
            //BUG:тк оркл не отдает кол-во рядов, каментим
            /*$this->free($result);
            return false;
            */
        }
    	list($ret) = $this->fetch(false, $escape, $result);
    	$this->free($result);
    	return $ret;
    }
    
    /**
     * DB::getRow() - получить первый ряд из SQL запроса, вида
     * select NAME, FIO, ... from TABLE where ID = 1
     * предполагается, что запрос возвращает только один ряд
     * 
     * @param string $sql - строка запроса
     * @param bool $assoc - вернуть ассоциативный массив
     * @param bool $escape - экранировать результат
     * @return массив (или ассоциативный массив) состоящий из полей результата
     */
    function getRow($sql, $assoc = false, $escape = true)
    {
        $result = $this->query($sql);
        if($this->num_rows($result) != 1) {
            Debugger::mes(2, ($this->num_rows($result)==''?'No':$this->num_rows($result))." rows found!", __FILE__, __LINE__, "DB::getRow( $sql )");    
        }    	        
    	$ret = $this->fetch($assoc, $escape, $result);
    	$this->free($result);
    	return $ret;
    }
    
    /**
     * DB::getCol() - получить первый столбец из SQL запроса, вида
     * select NAME from TABLE where ID > 10 and ID < 100
     * предполагается, что запрос возвращает только одну колонку
     * 
     * @param string $sql - строка запроса
     * @param bool $escape - экранировать результат
     * @return массив, элементами которого являются значения первого столбца результата
     */
    function getCol($sql, $escape = true)
    {
        $ret = array();
        $result = $this->query($sql);
        while(list($coldata) = $this->fetch(false, $escape, $result)) {
    	   $ret[] = $coldata;
    	}
    	$this->free($result);
    	return $ret;
    }
    
    /**
     * DB::getCell() алиас к DB::getCol() для обратной совместимости
     * 
     * @return DB::getCol()
     */
    function getCell($sql, $escape = true)
    {
        return $this->getCol($sql, $escape);
    }
    
    /**
     * DB::getCol2()- получить первый и второй столбец из SQL запроса, вида
     * select PARAM_NAME, PARAM_VALUE from TABLE where ID > 10 and ID < 100
     * предполагается, что запрос возвращает 2 колонки, которые помещаются в массив в виде (PARAM_NAME => PARAM_VALUE)
     * предполагается, что значения первого столбца (PARAM_NAME) всегдя будут уникальными, иначе элемент массива перезаписывается
     * 
     * @param string $sql - строка запроса
     * @param bool $escape - экранировать результат
     * @return массив, элементами которого являются значения первого столбца результата в качестве ключей, а второго - в качестве значений соответственно
     */
    function getCol2($sql, $escape = true)
    {
        $ret = array();
        $result = $this->query($sql);
        while(list($key, $value) = $this->fetch(false, $escape, $result)) {
    	   $ret[$key] = $value;
    	}
    	$this->free($result);
    	return $ret;
    }
    
    /**
     * DB::getCell2() алиас к DB::getCol2() для обратной совместимости
     * 
     * @return DB::getCol2()
     */
    function getCell2($sql, $escape = true)
    {
        return $this->getCol2($sql, $escape);
    }
}

class DB_MySQL extends DB
{
    protected $handle = null;
    protected $result = array();
    
    function DB_MySQL($use_config = true, $host = 'localhost', $login = '', $pass = '', $dbname = '')
    {
        global $_config;
                
        if($use_config)
        {
            $host   = $_config['db_host'];
            $login  = $_config['db_login'];
            $pass   = $_config['db_password'];
            $dbname = $_config['db_name'];
        }
        
        if($this->handle = mysql_connect($host, $login, $pass))
        {                  
            //TODO:избавиться от set names
            mysql_query("SET NAMES utf8");
            
            mysql_select_db($dbname, $this->handle);
            Debugger::mes(mysql_errno(), mysql_error(), __FILE__, __LINE__, "mysql_select_db($dbname)");
        }
        else
        {
            Debugger::mes(mysql_errno(), mysql_error(), __FILE__, __LINE__, "mysql_connect($host, ...)");
            return false;
        }
    }
    
    function handle()
    {
        return $this->handle;
    }
    
    private function last_result()
    {
        if(sizeof($this->result))
        {
            $keys = array_keys($this->result);
            return $this->result[array_pop($keys)];
        }
        else
        {
            return false;
        }
    }
    
    function query($sql)
    {          
        $this->queries_counter++;
        
        $this->result[] = mysql_query($sql, $this->handle);
        Debugger::mes(mysql_errno(), mysql_error(), __FILE__, __LINE__, $sql);
        if(!mysql_errno() && preg_match("/^INSERT.+/i", $sql))
        {
            $this->insert_id = mysql_insert_id($this->handle);
        }
        return $this->last_result();
    }
    
    function fetch($assoc = false, $escape = true, $result = null)
    {
        if($assoc) $ret = mysql_fetch_assoc($result ? $result : $this->last_result());
        else $ret =  mysql_fetch_row($result ? $result : $this->last_result());
        
        if(!$ret) return false; 
        
        //if($escape) $ret = array_map('htmlspecialchars', array_map('stripslashes', $ret));
        if($escape) $ret = array_map('htmlspecialchars', $ret);
                
        return $ret;
    }
    
    function num_rows($result = null)
    {
        return mysql_num_rows($result ? $result : $this->last_result());
    }
    
    function new_id($table)
    {
        //if(ereg("^`(.+)`$", $table, $regs)) -- php 5.3 deprecated
        if(preg_match("/^`(.+)`$/", $table, $regs))
        {
            $table = $regs[1];
        }
        
        $sql = "SHOW TABLE STATUS LIKE ".$this->F($table);
        $row = $this->getRow($sql, true);
        return $row['Auto_increment'];
    }
    
    function insert_id()
    {
        return $this->insert_id;
    }
    
    function free($result = null)
    {
        if($result) $key = array_search($result, $this->result, true);
        @mysql_free_result($result ? $result : array_pop($this->result));
        if(isset($key)) unset($this->result[$key]);
    }
    
    function error()
    {
        return mysql_error($this->handle);
    }
    
    function errno()
    {
        return mysql_errno($this->handle);
    }
    
    function destruct()
    {
        foreach($this->result as $r) mysql_free_result($r);
        return mysql_close($this->handle);
    }
}

class DB_Oracle extends DB
{
    const NLS_DATE_FORMAT = 'yyyy-mm-dd';
    
    protected $conn = null;
    protected $stmt = array();
    protected $curs = array();
    private $e;
    
    function __construct($use_config = true, $user = '', $pass = '', $db = null, $schema = null)
    {
        global $_config;
                
        if($use_config) {
            $db = $_config['ora_db'];
            $schema = $_config['ora_schema'];
            $user = $_config['ora_user'];
            $pass = $_config['ora_password'];            
        }
        
        //BUG:под FreeBSD (возможно и другими UNIX) требуется установить NLS_LANG для работы в UTF8        
        @putenv("NLS_LANG=American_America.UTF8");
        
        if($this->conn = oci_connect($user, $pass, $db, 'UTF8')) {
            if($schema && preg_match("/^[a-z0-9_]+$/i", $schema)) { // set default schema
                $stmt = oci_parse($this->conn, "alter session set CURRENT_SCHEMA = ".addslashes($schema));
                oci_execute($stmt);
                $this->e = $e = oci_error();
                Debugger::mes($e['code'], $e['message'], __FILE__, __LINE__, "DB_Oracle::__construct()");
                oci_free_statement($stmt);
            }
            if(self::NLS_DATE_FORMAT) {
                $stmt = oci_parse($this->conn, "alter session set NLS_DATE_FORMAT = ".$this->F(self::NLS_DATE_FORMAT));
                oci_execute($stmt);
                $this->e = oci_error();                
                oci_free_statement($stmt);
            }
        } else {
            $this->e = $e = oci_error();
            Debugger::mes($e['code'], $e['message'], __FILE__, __LINE__, "DB_Oracle::__construct()");
            return false;
        }
    }
    
    function Commit()
    {
        return oci_commit($this->conn);
    }
    
    private function last_stmt()
    {
        if(sizeof($this->stmt)) {
            $keys = array_keys($this->stmt);
            return $this->stmt[array_pop($keys)];
        } else {
            return false;
        }
    }
    
    private function last_curs()
    {
        if(sizeof($this->curs)) {
            $keys = array_keys($this->curs);
            return $this->curs[array_pop($keys)];
        } else {
            return false;
        }
    }
    
    function newCursor()
    {
        $this->curs[] = oci_new_cursor($this->conn);
        return $this->last_curs();
    }
    
    function parse($sql)
    {
        $this->queries_counter++;
        
        $this->stmt[] = oci_parse($this->conn, $sql);
        $this->e = $e = oci_error($this->conn);
        Debugger::mes($e['code'], $e['message'], __FILE__, __LINE__, $sql);
        return $this->last_stmt();
    }
    
    function bindByName($name, &$variable, $maxlength = -1, $stmt = null)
    {
        $stmt = $stmt ? $stmt : $this->last_stmt();
        return oci_bind_by_name($stmt, $name, $variable, $maxlength);
    }
    
    function bindCursor($name, $curs = null, $stmt = null)
    {
        $stmt = $stmt ? $stmt : $this->last_stmt();
        $curs = $curs ? $curs : $this->last_curs();
        return oci_bind_by_name($stmt, $name, $curs, -1, OCI_B_CURSOR);
    }
    
    function bindBlob($name, &$lob, $stmt = null)
    {
        $stmt = $stmt ? $stmt : $this->last_stmt();        
        $lob = oci_new_descriptor($this->conn, OCI_D_LOB);
        return oci_bind_by_name($stmt, $name, $lob, -1, OCI_B_BLOB);
    }
    
    function bindClob($name, &$lob, $stmt = null)
    {
        $stmt = $stmt ? $stmt : $this->last_stmt();  
        $lob = oci_new_descriptor($this->conn, OCI_D_LOB);      
        return oci_bind_by_name($stmt, $name, $lob, -1, OCI_B_CLOB);
    }
    
    function execute($stmt = null)
    {
        $stmt = $stmt ? $stmt : $this->last_stmt();
        if(oci_execute($stmt)){
            return true;
        } else {
            $this->e = $e = oci_error($stmt);
            Debugger::mes($e['code'], $e['message'], __FILE__, __LINE__, $sql);
            return false;
        }
    }
    
    function executeAndFree($stmt = null)
    {
        return $this->execute($stmt) && $this->free($stmt);
    }
    
    function executeCursor($curs = null)
    {
        $curs = $curs ? $curs : $this->last_curs();
        if(oci_execute($curs)){
            return true;
        } else {
            $this->e = $e = oci_error($curs);
            Debugger::mes($e['code'], $e['message'], __FILE__, __LINE__, $sql);
            return false;
        }
    }
    
    /**
     * DB_Oracle::query()
     * выполняет parse + execute. такдже если есть $binds, то перед execute делается bind_by_name
     * 
     * @param string $sql
     * @param array $binds - ассоциативный массив для bind_by_name
     * @param integer $maxlength - для всех binds
     * @return resource stmt
     */
    function query($sql, $binds = null, $maxlength = -1)
    {
        $stmt = $this->parse($sql);
        if(is_array($binds) && sizeof($binds)) {
            foreach($binds as $name=>$var) {
                $this->bindByName($name, $var, $maxlength, $stmt);
            }
        }
        $this->execute($stmt);
        return $stmt;
    }
    
    function free($stmt = null)
    {
        if($stmt) $key = array_search($stmt, $this->stmt, true);
        @ocifreestatement($stmt ? $stmt : array_pop($this->stmt));
        if(isset($key)) unset($this->stmt[$key]);
    }
    
    function freeCursor($curs = null)
    {
        if($curs) $key = array_search($curs, $this->curs, true);
        @ocifreecursor($curs ? $curs : array_pop($this->curs));
        if(isset($key)) unset($this->curs[$key]);
    }
    
    function fetch($assoc = false, $escape = true, $stmt = null)
    {
        if($assoc) $ret = oci_fetch_assoc($stmt ? $stmt : $this->last_stmt());
        else $ret =  oci_fetch_row($stmt ? $stmt : $this->last_stmt());
        
        if(!$ret) return false; 
        
        if($escape) $ret = array_map('htmlspecialchars', $ret);
                
        return $ret;
    }
    
    function fetchCursor($assoc = false, $escape = true, $curs = null)
    {
        return $this->fetch($assoc, $escape, $curs ? $curs : $this->last_curs());
    }
    
    function num_rows($stmt = null)
    {
        return oci_num_rows($stmt ? $stmt : $this->last_stmt());
    }
    
    function error($stmt = null)
    {        
        //$e = oci_error($stmt ? $stmt : $this->last_stmt());
        return $this->e['message'];
    }
    
    function errno($stmt = null)
    {
        //$e = oci_error($stmt ? $stmt : $this->last_stmt());
        return $this->e['code'];
    }
    
    function destruct()
    {
        foreach($this->curs as $r) oci_free_cursor($r);
        foreach($this->stmt as $r) oci_free_statement($r);        
        return oci_close($this->conn);
    }
}
?>