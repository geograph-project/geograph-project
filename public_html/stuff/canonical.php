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
init_session();

$USER->mustHavePerm("basic");


$smarty = new GeographPage;

$template='stuff_canonical.tpl';
$cacheid='';

if (!empty($_GET['preview'])) {
	$template='stuff_canonical_tree.tpl';
	$cacheid='preview';
	if (!$smarty->is_cached($template, $cacheid)) {
		$db = GeographDatabaseConnection(true);
		
		$list = $db->getAll("SELECT imageclass,canonical FROM category_map GROUP BY imageclass ORDER BY LOWER(canonical) LIMIT 1000");
		$smarty->assign('list',$list);
	}
	
} elseif (!empty($_GET['sample'])) {
	if (!empty($_GET['tree'])) {
		$template='stuff_canonical_tree.tpl';
		$order = "canonical";
	} else {
		$template='stuff_canonical_list.tpl';
		$order = "imageclass";
	}
	$cacheid='sample';
	
	if (!$smarty->is_cached($template, $cacheid)) {
		$db = GeographDatabaseConnection(true);
		
		$list = $db->getAll("SELECT imageclass,canonical FROM category_map WHERE user_id = 3 ORDER BY $order LIMIT 100");
		$smarty->assign('list',$list);
	}
	
} elseif (!empty($_GET['rename'])) {
	$template='stuff_canonical_rename.tpl';
	
	if (!empty($_POST) && $_POST['submit'] && !empty($_POST['new'])) {
		$db = GeographDatabaseConnection(false);
	
		foreach ($_POST['new'] as $old => $new) {
			if ($old != $new) {
				$sql = "UPDATE category_map SET canonical = ".$db->Quote($new)." WHERE user_id = {$USER->user_id} AND canonical = ".$db->Quote($old);
				$db->Execute($sql);
			}
		}
		header("Location: /stuff/canonical.php");
		exit;
	}
	
	$db = GeographDatabaseConnection(true);
	
	$list = $db->getAll("SELECT canonical FROM category_map WHERE user_id = {$USER->user_id} GROUP BY canonical ORDER BY category_map_id DESC LIMIT 100");
	$smarty->assign('list',$list);
	
	
} elseif (!empty($_GET['review'])) {
	$template='stuff_canonical_review.tpl';
	
	$db = GeographDatabaseConnection(true);
	
	$list = $db->getAll("SELECT imageclass,canonical FROM category_map WHERE user_id = {$USER->user_id} ORDER BY category_map_id DESC LIMIT 100");
	$smarty->assign('list',$list);
	
	
} elseif (!empty($_GET['mode'])) {
	
	if (!empty($_POST) && $_POST['submit'] && !empty($_POST['imageclass']) && !empty($_POST['canonical'])) {
		$db = GeographDatabaseConnection(false);
	
		switch ($_POST['canonical']) {
		
			case 'asis':
				$canonical = $_POST['imageclass'];
				break;
			case 'other': 
				$canonical = $_POST['other'];
				break;
			case 'new': 
				$canonical = $_POST['new'];
				break;
			case 'bad': 
				$canonical = '-bad-';
				break;
		}
		if (!empty($canonical)) {
			$updates = array();
			$updates['imageclass'] = $_POST['imageclass'];
			$updates['canonical'] = $canonical;
			$updates['user_id'] = $USER->user_id;
			
			$db->Execute('REPLACE INTO category_map SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	
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
					SELECT * 
					FROM category_stat cs 
					LEFT JOIN category_map cm 
						ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
					WHERE cm.category_map_id IS NULL
					ORDER BY cs.imageclass
					LIMIT 1");

				break;
			case 'random':
				//TODO add some more randomness?
				
				$orders = array('category_id','cs.imageclass desc','category_id desc','reverse(category_id)','c desc');
				$order = $orders[date('G')%(count($orders)-1)];
				$row = $db->GetRow("
					SELECT * 
					FROM category_stat cs 
					LEFT JOIN category_map cm 
						ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
					WHERE cm.category_map_id IS NULL
					ORDER BY $order
					LIMIT 1");

				break;
			case 'unmapped':
				//TODO - this should use the final 'approved' list.
				$row = $db->GetRow("
					SELECT * 
					FROM category_stat cs 
					LEFT JOIN category_map cm 
						ON (cs.imageclass=cm.imageclass)
					WHERE cm.category_map_id IS NULL
					ORDER BY cs.category_id
					LIMIT 1");

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
							SELECT * 
							FROM category_stat cs 
							LEFT JOIN category_map cm 
								ON (cs.imageclass=cm.imageclass AND user_id = {$USER->user_id})
							WHERE cm.category_map_id IS NULL
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
		
		//todo - use this from the confirmed one?
		$list = $db->getCol("SELECT canonical FROM category_map WHERE canonical != '-bad-' GROUP BY canonical");
		$smarty->assign('list',$list);
		
	} else {
	
		$smarty->assign('done',1);
	}
}


$smarty->display($template, $cacheid);
