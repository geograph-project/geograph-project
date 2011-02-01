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


if (!empty($_GET['build'])) {
	
	$db = GeographDatabaseConnection(false);

	function rebuild_article_tree($parent = 0, $left = 1) { 
		global $db;
		// the right value of this node is the left value + 1 
		$right = $left+1; 

		// get all children of this node 
		$results = $db->getCol("SELECT article_cat_id FROM article_cat WHERE parent_id=$parent order by sort_order, article_cat_id"); 
		foreach($results as $result) { 
			// recursive execution of this function for each 
			// child of this node 
			// $right is the current right value, which is 
			// incremented by the rebuild_tree function 
			$right = rebuild_article_tree($result, $right); 
		} 

		// we've got the left value, and now that we've processed 
		// the children of this node we also know the right value 
		$db->query("UPDATE article_cat SET lft$w=$left, rgt$w=$right WHERE article_cat_id=$parent;");

		// return the right value of this node + 1 
		return $right+1; 
	}

	rebuild_article_tree();
	print "done";
	
	exit;
}

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
	
	$t = $link = array();
	
	if (true) {//setup the category tree?
	
		
		function build_article_tree($parent = 0,$name) { 
			global $db,$t,$link;
			
			$t2 = array();
			
			$c = '<b><big>'.htmlentities($name).'</big></b>';
			$r =  array('title'=>$c,'children'=>array());
				
			$results = $db->getAssoc("SELECT article_cat_id,category_name FROM article_cat WHERE parent_id=$parent order by sort_order, article_cat_id"); 
			if ($results)
				foreach($results as $id => $name) { 

					$c = '<b><big>'.htmlentities($name).'</big></b>';
					$r =  array('title'=>$c,'children'=>array());

					$r['children'] = build_article_tree($id,$name); 

					$t2[$c] = $r;
					$link[$c] =& $t2[$c];
				} 
				
			return $t2;
		}
		
		$results = $db->getAssoc("SELECT article_cat_id,category_name FROM article_cat WHERE parent_id=0 order by sort_order, article_cat_id"); 
		if ($results)
			foreach($results as $id => $name) { 
				
				$c = '<b><big>'.htmlentities($name).'</big></b>';
				$r =  array('title'=>$c,'children'=>array());
			
				$r['children'] = build_article_tree($id,$name); 
				
				$t[$c] = $r;
				$link[$c] =& $t[$c];
			} 
		

	}

#print "<pre>";
#print_r($t);
#print_r($link);
#exit;
	
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
	#	if (empty($link[$c])) {
	#		$t[$c] = array('title'=>$c,'children'=>array());
	#		$link[$c] =& $t[$c];
	#	} 
		if (empty($link[$c]['children'][$b])) {
			$link[$c]['children'][$b] =  array('title'=>$b,'children'=>array());
		}

		$link[$c]['children'][$b]['children'][] =& $linker[$row['url']];

	}




print "<a href=\"/article/\">Back to Article List</a>";
print "<ul>";
foreach ($t as $idx => $row) {
	dump_list($row);
}
print "</ul>";

function dump_list($in) {
	if (is_array($in) && (!empty($in['children']) || !empty($in['url']))) {
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
