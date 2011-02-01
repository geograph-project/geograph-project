<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

require_once('geograph/global.inc.php');
init_session();



$db = GeographDatabaseConnection(true);

	
	if (!empty($_GET['user_id']) && preg_match('/^\d+$/',$_GET['user_id'])) {
		$where = "AND article.user_id = {$_GET['user_id']}";
	
	} elseif (!empty($_GET['q']) && preg_match('/^[\w ]+$/',$_GET['q'])) {
		$where = "AND title LIKE '%{$_GET['q']}%'";
	
	} elseif (!empty($_GET['cat_q']) && preg_match('/^\![\w ]+$/',$_GET['cat_q'])) {
		$where = "AND category_name NOT LIKE '%".str_replace('!','',$_GET['cat_q'])."%'";
	
	} elseif (!empty($_GET['cat_word']) && preg_match('/^\![\w ]+$/',$_GET['cat_word'])) {
		$where = 'AND category_name NOT REGEXP '.$db->Quote('[[:<:]]'.str_replace('!','',$_GET['cat_word']).'[[:>:]]');
	
	} elseif (!empty($_GET['cat_q']) && preg_match('/^[\w ]+$/',$_GET['cat_q'])) {
		$where = "AND category_name LIKE '%{$_GET['cat_q']}%'";
	
	} elseif (!empty($_GET['cat_word']) && preg_match('/^[\w ]+$/',$_GET['cat_word'])) {
		$where = 'AND category_name REGEXP '.$db->Quote('[[:<:]]'.$_GET['cat_word'].'[[:>:]]');
	
	} else {
		$where = "AND category_name not like '%Geograph %'";
	}
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$list = $db->getAll("
	select category_name,article.user_id,url,title,extract,realname,parent_url
	from article 
		inner join user using (user_id)
		left join article_cat on (article.article_cat_id = article_cat.article_cat_id)
	where (licence != 'none' and approved > 0) $where
	 and category_name not like 'Redirect Only'
	order by (parent_url = '') DESC,sort_order,article.article_cat_id,article_sort_order desc,create_time desc");

	
	$t = array();
	$linker = array();
	foreach ($list as $idx => $row) {
		if (!empty($row['parent_url'])) {
			$parent = preg_replace("/^.*\//",'',$row['parent_url']);
			if ($linker[$parent] && $parent != $row['url']) {
				$linker[$parent]['children'][] = $row;
				continue;
			} 
		} 
		
		$linker[$row['url']] = $row;
		$c = '<b><big>'.htmlentities($row['category_name']).'</big></b>';
		$b = '<small><a href="?user_id='.$row['user_id'].'">by <i>'.htmlentities($row['realname']).'</i></a></small>';
		if (empty($t[$c])) {
			$t[$c] = array('title'=>$c,'children'=>array());
		} 
		if (empty($t[$c]['children'][$b])) {
			$t[$c]['children'][$b] =  array('title'=>$b,'children'=>array());
		}

		$t[$c]['children'][$b]['children'][] =& $linker[$row['url']];

	}




print "<a href=\"/article/\">Articles</a>";
print "<ul>";
foreach ($t as $idx => $row) {
	dump_list($row);
}
print "</ul>";

function dump_list($in) {
	if (is_array($in)) {
		print "<li>";
		if (!empty($in['url'])) {
			print "<a href=\"".htmlentities($in['url'])."\" title=\"".htmlentities($in['extract']?$in['extract']:$in['title'])." - by - ".htmlentities($in['realname'])."\">".htmlentities($in['title'])."</a>";
		} else {
			print ($in['title']);
		}
		
		if (!empty($in['children'])) {
			if (!empty($in['url'])) {
				print "<ul style=\"font-size:0.9em;\">";
			} else {
				print "<ul>";
			}
			foreach ($in['children'] as $idx => $row) {
				dump_list($row);
			}
			print "</ul>";
		}
		print "</li>";
	}
}

