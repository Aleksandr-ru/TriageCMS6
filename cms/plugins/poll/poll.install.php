<?php

/**
 * Plugin Information and Install script
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

$plugin_uid = basename(__FILE__, '.install.php');

$plugin_install['title'][$plugin_uid] = "Опрос";
$plugin_install['desc'][$plugin_uid]  = "создает и у правляет опросами на сайте с возможностью искажения результатов для посетителя";

function poll_install()
{
    global $DB, $E, $plugin_install;
    
    $plugin_uid = basename(__FILE__, '.install.php');
    
    /* custom install procedures */
    
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('poll_ips')." (
          `poll_id` int(3) NOT NULL DEFAULT '0',
          `ip` varchar(15) NOT NULL DEFAULT '0.0.0.0',
          PRIMARY KEY (`poll_id`,`ip`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='ip-адреса голосовавших';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('poll_votes')." (
          `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
          `poll_id` int(3) unsigned NOT NULL DEFAULT '0',
          `name` varchar(255) NOT NULL DEFAULT '',
          `votes` int(5) unsigned NOT NULL DEFAULT '0',
          `order` int(3) unsigned NOT NULL DEFAULT '1',
          `is_fake` tinyint(2) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Варианты ответов на опрсы';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    $sql = "CREATE TABLE IF NOT EXISTS ".$DB->T('polls')." (
          `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL DEFAULT '',
          `desc` varchar(255) DEFAULT NULL,
          `active` tinyint(1) NOT NULL DEFAULT '0',
          `fake_percent` tinyint(2) NOT NULL DEFAULT '0',
          `fake_threshold` int(10) unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Опрсы';";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } 
    
    $sql = "INSERT INTO ".$DB->T('polls')." (`name`, `desc`, `active`, `fake_percent`, `fake_threshold`) VALUES ('Демо опрос', 'это опрос для демонстрации возможностей', '1', '0', '0');";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    } else {
        $poll_id = $DB->insert_id();
    }
    $sql = "INSERT INTO `cms_poll_votes` (`poll_id`, `name`, `votes`, `order`, `is_fake`) VALUES (".$DB->F($poll_id).", 'Ответ 1', 0, 1, 0), (".$DB->F($poll_id).", 'Ответ 2', 0, 2, 0), (".$DB->F($poll_id).", 'Ответ 3', 0, 3, 0);";
    $DB->query($sql);
    if($DB->errno()){
        $E->addError("Не удалось выполнить запрос", $sql);
        return false;    
    }
    
    /* common install procedures */
    
    $installer = new PluginInstaller($plugin_uid, $plugin_install['title'][$plugin_uid], $plugin_install['desc'][$plugin_uid]);
        
    $installer->addOption('poll_id',      $poll_id, 'Выбранный опрос', $DB->TF('polls', 'id').';'.$DB->TF('polls', 'name'));
    $installer->addOption('block_ip',     '0', 'Не давать голосовать с одного IP-адреса', '1;Да;0;Нет');
    $installer->addOption('block_cookie', '1', 'Не давать голосовать при наличии опроса в Cookie', '1;Да;0;Нет');
    
    return $installer->Install();
}
?>