<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

if (!empty($_GET['hectad']) && !empty($_GET['top'])) {
	$db = GeographDatabaseConnection(true);
	$q = $_GET['hectad'];
	
	$q .= " @imageclass (\"{$_GET['top']}\"";
	
	$list = $db->getCol("SELECT imageclass FROM category_top WHERE top = ".$db->Quote($_GET['top'])." GROUP BY imageclass LIMIT 10");
	foreach ($list as $c) {
		if (strpos($c,' ') !== FALSE) {
			$q .= "| \"$c\"";
		} else {
			$q .= "| $c";
		}
	}
	$q .= ")";
	
	$q= urlencode($q);
	header("Location: /search.php?q=$q");
	exit;
}

init_session();

$USER->mustHavePerm("basic");


$smarty = new GeographPage;

$template='stuff_top.tpl';
$cacheid='';

if (!empty($_GET['import'])) {
	$template='stuff_top_import.tpl';

	if (!empty($_POST['text'])) {
		$db = GeographDatabaseConnection(false);
		
		$text = strip_tags(str_replace("\r",'',$_POST['text']));
		$skipped = 0;$rows = 0;
		foreach (explode("\n",$text) as $line) {
			if (empty($line)) {
				$skipped++;
				continue;
			}
			
			list($category,$top) = preg_split('/[;\t]+/',$line,2);
			$top = trim($top);
			if (empty($top) || $top == '#N/A') {
				$skipped++;
				continue;
			}
			
			if ($top == 'Unallocated') {
				$top = '-bad-';
			}
			
			$updates = array();
			$updates['imageclass'] = trim($category);
			$updates['top'] = $top;
			$updates['user_id'] = $USER->user_id;
						
			$db->Execute('REPLACE INTO category_top_log SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			$affected= mysql_affected_rows();
			
			$rows++;
		}
		$smarty->assign("rows",$rows);
		$smarty->assign("affected",$affected);
		$smarty->assign("skipped",$skipped);
	}
	
} elseif (!empty($_GET['stats'])) {
	$template='stuff_top_stats.tpl';

	if (!$smarty->is_cached($template, $cacheid)) {
		$db = GeographDatabaseConnection(false);
		
		$db->Execute("truncate category_top");
		$db->Execute("insert into category_top select null as category_map_id,imageclass,top,users from (select imageclass,top,count(distinct user_id) as users,count(distinct top) as tops from category_top_log where imageclass != '' group by imageclass order by null) t2 where users > 1 and tops = 1");

		$data = $db->getRow("SELECT COUNT(*) AS normal FROM category_stat");
		$smarty->assign($data);
		$data = $db->getRow("SELECT COUNT(*) AS suggestions,COUNT(DISTINCT imageclass) AS cats,COUNT(DISTINCT top) AS tops,COUNT(DISTINCT user_id) AS users FROM category_top_log WHERE imageclass != ''");
		$smarty->assign($data);
		$data = $db->getRow("SELECT COUNT(DISTINCT imageclass) AS final,COUNT(DISTINCT top) AS tops_final FROM category_top WHERE top != '-bad-'");
		$smarty->assign($data);

		$users = $db->getAll("select user_id,realname,count(*) tops ,round(ln(count(*))) as sortter from category_top_log inner join user using (user_id) group by user_id order by sortter desc,user_id");
		$smarty->assign_by_ref('userlist',$users);

	}
	
} elseif (!empty($_GET['preview'])) {
	$template='stuff_top_tree.tpl';
	$cacheid='preview'.preg_replace('/[^\w]+/','',$_GET['alpha']);
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$db = GeographDatabaseConnection(true);
		
		$smarty->assign('intro',"<b>NOTE</b>: This is only the result of the first pass over the data. It will be slightly messy as it combines results from multiple users, <u>without any processing</u>.");
		
		$list = $db->getAll("SELECT imageclass,top FROM category_top_log WHERE imageclass != '' GROUP BY imageclass ORDER BY LOWER(top)");
		$smarty->assign_by_ref('list',$list);
	}

} elseif (!empty($_GET['preview'])) {
	$template='stuff_top_tree.tpl';
	$cacheid='preview'.preg_replace('/[^\w]+/','',$_GET['alpha']);
	if (!$smarty->is_cached($template, $cacheid)) {
		
		$db = GeographDatabaseConnection(true);
		
		$letters = $db->getAll("SELECT top,COUNT(DISTINCT imageclass) AS classes FROM category_top_log WHERE top != '-bad-' AND imageclass != '' GROUP BY LOWER(SUBSTRING(top,1,1))");
		
		$a = 'A';
		$str = "";
		foreach ($letters as $row) {
			$al = strtoupper(substr($row['top'],0,1));
			$size = max(1,log1p($row['classes'])*0.4);
			if ($al == $_GET['alpha']) {
				$str .= "<b style=\"font-size:{$size}em\">$al</b> ";
				$a = $al;
			} else {
				$str .= "<a href=\"?preview=1&amp;alpha=$al\" style=\"font-size:{$size}em\">$al</a> ";
			}
		}
		
		$smarty->assign('intro',"<b>NOTE</b>: This is only the result of the first pass over the data. It will be slightly messy as it combines results from multiple users, <u>without any processing</u>.<p>First letter: $str</p>");
		
		$list = $db->getAll("SELECT imageclass,top FROM category_top_log WHERE top LIKE '$a%' AND imageclass != '' GROUP BY imageclass ORDER BY LOWER(top)");
		$smarty->assign_by_ref('list',$list);
	}

} elseif (!empty($_GET['final'])) {
	$template='stuff_top_tree.tpl';
	$cacheid='final';
	if (!$smarty->is_cached($template, $cacheid)) {
		$smarty->assign('intro',"This is preliminary results of the mapping - showing top categories confirmed by at least 3 people in stage 1. Also takes into account confirmed renames as per stage 2.");
	
		$db = GeographDatabaseConnection(true);
		
		$list = $db->getAll("SELECT imageclass,top FROM category_top WHERE top != '-bad-' GROUP BY imageclass ORDER BY LOWER(top),LOWER(imageclass) LIMIT 1000");
		$smarty->assign_by_ref('list',$list);
	}

} elseif (!empty($_GET['top'])) {
	$template='stuff_top_top.tpl';
	$cacheid='preview';
	if (!$smarty->is_cached($template, $cacheid)) {
		$smarty->assign('intro',"This is the current list of top categories. Categories suggested by few people are shown in gray.");
	
		$db = GeographDatabaseConnection(true);
		
		$list = $db->getAll("SELECT top,COUNT(DISTINCT imageclass)-1 AS cats,COUNT(DISTINCT cm.user_id) AS users FROM category_top_log cm WHERE top != '-bad-' GROUP BY LOWER(top)");
		$smarty->assign_by_ref('list',$list);
	}
	
} elseif (!empty($_GET['sample'])) {
	if (!empty($_GET['tree'])) {
		$template='stuff_top_tree.tpl';
		$order = "top,imageclass";
	} else {
		$template='stuff_top_list.tpl';
		$order = "imageclass";
	}
	$cacheid='sample';
	
	if (!$smarty->is_cached($template, $cacheid)) {
		$smarty->assign('intro',"This is a small sample of mappings for demonstration purposes.");
	
		$db = GeographDatabaseConnection(true);
		
		$list = $db->getAll("SELECT imageclass,top FROM category_top_log WHERE user_id = 3 ORDER BY $order LIMIT 100");
		$smarty->assign_by_ref('list',$list);
	}
	
} elseif (!empty($_GET['review'])) {
	$template='stuff_top_review.tpl';
	
	$db = GeographDatabaseConnection(true);
	
	$list = $db->getAll("SELECT imageclass,top FROM category_top_log WHERE user_id = {$USER->user_id} ORDER BY category_map_id DESC LIMIT 100");
	$smarty->assign_by_ref('list',$list);

	
} elseif (!empty($_GET['bulk'])) {

	if (!empty($_POST['top']) && !empty($_POST['imageclass'])) {
		print "<pre>";
		print_r($_POST);
	
		$db = GeographDatabaseConnection(false);
	
		$updates = array();
		$updates['top'] = $_POST['top'];
		$updates['user_id'] = $USER->user_id;
		
		foreach ($_POST['imageclass'] as $category) {
			$updates['imageclass'] = trim($category);

			$db->Execute('REPLACE INTO category_top_log SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
			$affected += mysql_affected_rows();
		}
		
		print "Thank you, {$affected} records recorded. <a href=\"?\">Continue...</a>";
		exit;
	} 

	$template='stuff_top_bulk.tpl';
	
	$sphinx = new sphinxwrapper($_GET['bulk']);
				
	$sphinx->pageSize = $pgsize = 500; 

	$pg = 1;

	$offset = (($pg -1)* $sphinx->pageSize)+1;

	if ($offset < (1000-$pgsize) ) { 
		$sphinx->processQuery();

		$sphinx->q = "\"^{$sphinx->q}$\" | ($sphinx->q)";

		$ids = $sphinx->returnIds($pg,'category');

		if (!empty($ids) && count($ids)) {
			
			$db = GeographDatabaseConnection(true);
			
			$where = "category_id IN(".join(",",$ids).")";
			$rows = $db->GetAll("
				SELECT cs.* 
				FROM category_stat cs 
				LEFT JOIN category_top_log cm 
					ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
				LEFT JOIN category_top cc
					ON (cs.imageclass=cc.imageclass)
				WHERE cm.category_map_id IS NULL
					AND cc.imageclass IS NULL
					AND $where
				ORDER BY cs.imageclass
				LIMIT 250");
			$smarty->assign_by_ref('rows',$rows);
		}
	}
	
	if (empty($db)) {
		die("no matching categories found. <a href=\"?\">Continue...</a>");
	} else {
		$list = $db->getAll("SELECT top,count(*) AS count FROM category_top_log WHERE top != '-bad-' GROUP BY top");
		$smarty->assign_by_ref('list',$list);
	}
} elseif (!empty($_GET['mode'])) {
	
	if (!empty($_POST) && $_POST['submit'] && !empty($_POST['imageclass']) && !empty($_POST['top'])) {
		$db = GeographDatabaseConnection(false);
	
		switch ($_POST['top']) {
		
			case 'asis':
				$top = $_POST['imageclass'];
				break;
			case 'other': 
				$top = $_POST['other'];
				break;
			case 'prev': 
				$top = $_POST['prev'];
				break;
			case 'new': 
				$top = $_POST['new'];
				break;
			case 'bad': 
				$top = '-bad-';
				break;
		}
		if (!empty($top)) {
			$updates = array();
			$updates['imageclass'] = $_POST['imageclass'];
			$updates['top'] = $top;
			$updates['user_id'] = $USER->user_id;
			
			$db->Execute('REPLACE INTO category_top_log SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
		} else {
			//try again!
			$imageclass = $_POST['imageclass'];
		}
	} else {
		$db = GeographDatabaseConnection(true);
		
		if (!empty($_GET['category'])) {
			$imageclass = $_GET['category'];
		}
	}
	
	if (!empty($imageclass)) {
		$row = array('imageclass'=>$imageclass);
	} else {
		switch ($_GET['mode']) {

			case 'alpha':
				$row = $db->GetRow("
					SELECT cs.* 
					FROM category_stat cs 
					LEFT JOIN category_top_log cm 
						ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
					LEFT JOIN category_top cc
						ON (cs.imageclass=cc.imageclass)
					WHERE cm.category_map_id IS NULL
                                                AND cs.imageclass != ''
						AND cc.imageclass IS NULL
					ORDER BY cs.imageclass
					LIMIT 1");

				break;
			case 'random':
				$orders = array('category_id','cs.imageclass desc','category_id desc','reverse(category_id)','c desc');
				$order = $orders[date('G')%(count($orders)-1)];
				$row = $db->GetRow("
					SELECT cs.* 
					FROM category_stat cs 
					LEFT JOIN category_top_log cm 
						ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
					LEFT JOIN category_top cc
						ON (cs.imageclass=cc.imageclass)
					WHERE cm.category_map_id IS NULL
                                                AND cs.imageclass != ''
						AND cc.imageclass IS NULL
					ORDER BY $order
					LIMIT 1");

				break;
			case 'unmapped':
				if (date('G')%2 == 0) {
					//in category_top_log, but not shown to this user, but not in final
					$row = $db->GetRow("
						SELECT cs.* 
						FROM category_stat cs 
						INNER JOIN category_top_log cm 
							ON (cs.imageclass=cm.imageclass)
						LEFT JOIN category_top_log cm2 
							ON (cs.imageclass=cm2.imageclass AND cm2.user_id = {$USER->user_id})
						LEFT JOIN category_top cc
							ON (cs.imageclass=cc.imageclass)
						WHERE cm2.category_map_id IS NULL
                                                        AND cs.imageclass != ''
							AND cc.imageclass IS NULL
						ORDER BY cs.category_id
						LIMIT 1");
				} else {
					//not in category_top_log
					//TODO - this should use the final 'approved' list.
					$row = $db->GetRow("
						SELECT cs.* 
						FROM category_stat cs 
						LEFT JOIN category_top_log cm 
							ON (cs.imageclass=cm.imageclass)
						WHERE cm.category_map_id IS NULL
                                                        AND cs.imageclass != ''
						ORDER BY cs.category_id
						LIMIT 1");
				}
				break;
			default:
				$q=trim($_GET['mode']);
				
				$sphinx = new sphinxwrapper($q);
			
				$sphinx->pageSize = $pgsize = 100; 
			
				$pg = 1;
					
				$offset = (($pg -1)* $sphinx->pageSize)+1;
	
				if ($offset < (1000-$pgsize) ) { 
					$sphinx->processQuery();
			
					$sphinx->q = "\"^{$sphinx->q}$\" | ($sphinx->q)";
			
					$ids = $sphinx->returnIds($pg,'category');
					
					if (!empty($ids) && count($ids)) {
			
						$where = "category_id IN(".join(",",$ids).")";
						$row = $db->GetRow("
							SELECT cs.* 
							FROM category_stat cs 
							LEFT JOIN category_top_log cm 
								ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
							LEFT JOIN category_top cc
								ON (cs.imageclass=cc.imageclass)
							WHERE cm.category_map_id IS NULL
								AND cs.imageclass != ''
								AND cc.imageclass IS NULL
								AND $where
							ORDER BY cs.imageclass
							LIMIT 1");
					}
				}
				
				break;
		}
	}
	
	if ($row) {
		if (empty($row['imageclass']) && !empty($row[1])) {
			//work around adodb bug. if label consists solely of hyphens its ignored
			$row['imageclass'] = $row[1];
		}
		
		
		$smarty->assign($row);
		$smarty->assign('mode',$_GET['mode']);
		
		$prev = $db->getAll("SELECT top FROM category_top_log WHERE imageclass = ".$db->Quote($row['imageclass'])." GROUP BY top");
		$smarty->assign_by_ref('prev',$prev);
		
		//todo - use this from the confirmed one?
		$list = $db->getAll("SELECT top,count(*) AS count FROM category_top_log WHERE top != '-bad-' GROUP BY top");
		$smarty->assign_by_ref('list',$list);
		
	} else {
	
		$smarty->assign('done',1);
	}
}


$smarty->display($template, $cacheid);
