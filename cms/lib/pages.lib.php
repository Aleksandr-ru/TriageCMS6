<?php

/**
 * Функции создания списка страниц
 * 
 * @package Triage CMS v.6
 * @author Rebel
 * @copyright 2010
 */

if(!defined('TRIAGE_CMS')) die('Hacking attempt!');

/**
 * pages() создает UL со списком страниц
 * удобно для вывода текцщего куска данных, полученных SQL запросом  ...LIMIT $start,$limit
 * выводит UL с классом 'pages', в котором:
 * LI со ссылками на страницы
 * LI со span для '...' и прочих не нажимаемых
 * LI с классом 'selected' и span c номером текущей страницы
 * LI с классом 'prev' и 'next' со ссылками на предыдущюю и следующую страницы
 * пример вывода:
 * <ul class="pages">
 *  <li class="prev"><a ...></li>
 *  <li class="selected"><span>1</span></li>
 *  <li><a ...></li>
 *  <li><span>...</span></li>
 *  <li class="next"><a ...></li>
 * </ul>
 * 
 * @param int $start - отступ от начала
 * @param int $limit - кол-во элементов на страницу
 * @param int $total - всего элементов
 * @param string $href формат ссылки на страницу, %s заменяется на отступ от начала, %n - на номер страницы
 * @return HTML список страниц
 */
function pages($start, $limit, $total, $href)
{
	$num_pages = ceil($total/$limit);
	$cur_page = $start / $limit;

	$arr = array();
	$arr1 = array();
    $search = array('%s', '%n');

	if($num_pages > 10) {
		$p_start = $cur_page - 5;
		$p_end   = $cur_page + 5;
		if($p_start < 1) {
			$p_start = 0;
			$p_end   = $p_start + 10;
		} else {
            $phref = str_replace($search, array(0, 1), $href);
			$arr[] = "<li><a href=\"$phref\" rel=\"first\">1</a></li>";
			if($p_start == 2) {
                $p_start = $p_start - 1;
			} elseif($p_start > 2 && $num_pages !=11) {
                $arr[] = "<li><span>&hellip;</span></li>";			 
			}
		}

		if($p_end >= $num_pages) {
			$p_start = $num_pages - 10;
			$p_end   = $num_pages;
		} else {
			$phref = str_replace($search, array(($num_pages-1)*$limit, $num_pages), $href);
			$arr1[] = "<li><a href=\"$phref\" rel=\"last\">$num_pages</a></li>";
			if($num_pages - $p_end == 2) {
                $p_end = $p_end + 1; 
			} elseif($num_pages - $p_end > 2) {
                array_unshift($arr1, "<li><span>&hellip;</span></li>"); 
			}
		}
	} else {
		$p_start = 0;
		$p_end   = $num_pages;
	}
	
	for($i=$p_start; $i<$p_end; $i++) {
		$i_start = $i*$limit;
		$i_page  = $i+1;
        $phref = str_replace($search, array($i_start, $i_page), $href);
		$arr[] = $i_start==$start ? "<li class=\"selected\"><span>$i_page</span></li>" : "<li><a href=\"$phref\">$i_page</a></li>";
	}

	if($cur_page > 0) {
		$p_start = ($cur_page-1)*$limit;
		$prev_page_num = round($cur_page,0);
        $phref = str_replace($search, array($p_start, $prev_page_num), $href);
		array_unshift($arr, "<li class=\"prev\"><a href=\"$phref\" rel=\"prev\">&larr;</a></li>");
	}		

	if($cur_page < $num_pages - 1) {
		$p_start = ($cur_page+1)*$limit;
		$next_page_num = round($cur_page+2,0);
        $phref = str_replace($search, array($p_start, $next_page_num), $href);
		$arr1[] = "<li class=\"next\"><a href=\"$phref\" rel=\"next\">&rarr;</a></li>";
	}
	
	return "<ul class=\"pages\">\r\n".join("\r\n", array_merge($arr, $arr1))."</ul>\r\n";
}

?>