<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/imagelist.class.php');
init_session();


$smarty = new GeographPage;

customGZipHandlerStart();

dieUnderHighLoad(0.8);

$USER->mustHavePerm("basic");


$template='admin_typohunter.tpl';

$max_gridimage_id = 0;
$count = 0;
if (!empty($_GET['next'])) {
	$token=new Token;
	
	if ($token->parse($_GET['next']) && $token->hasValue("id")) {
		$max_gridimage_id = intval($token->getValue("id"));
		$count = intval($token->getValue("c"));
	} else {
		die("invalid token");
	}
}
$include = $exclude = $title = '';
if (!empty($_GET['include'])) {
	$include= $_GET['include'];
} 
if (!empty($_GET['exclude'])) {
	$exclude= $_GET['exclude'];
} 
if (!empty($_GET['title'])) {
	$title= $_GET['title'];
} 

$size = (!empty($_GET['size']))?intval($_GET['size']):3000;
$size = max(100,min(10000,$size));

$cacheid = md5("$include|$exclude|$title|$size");

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600; //1hour cache
	customExpiresHeader(3600,false,true);
}

$smarty->assign('sizes',array(1000=>1000,3000=>3000,5000=>5000,10000=>10000));
$smarty->assign('size',$size);
	
//regenerate?
if (!$smarty->is_cached($template, $cacheid) && strlen($include))
{
	
	$imagelist=new ImageList;

	$db = $imagelist->_getDB();
	
	$last_id = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
	
	if (!empty($_GET['profile']) && $_GET['profile'] == 'keywords') {
		
		$pgsize = 50;
					
		$pg = (!empty($_GET['page']))?intval(str_replace('/','',$_GET['page'])):0;
		if (empty($pg) || $pg < 1) {$pg = 1;}

		$q = '';
		if (!empty($_GET['include'])) {
			$q = trim($_GET['include']);
			$smarty->assign('include',$_GET['include']);
		}
		if (!empty($_GET['exclude'])) {
			$q .= " -(".trim($_GET['exclude']).")";
			$smarty->assign('exclude',$_GET['exclude']);
		}
		
		if ($imagelist->getImagesBySphinx($q,$pgsize,$pg = 1)) {
			$total_results = $imagelist->resultCount;
		}
		
		$smarty->assign('profile',$_GET['profile']);
	} else {
		$where = array();
		
		if (!empty($_GET['title'])) {
			if (!empty($_GET['include'])) {
				$where[] = '(title LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['include']).'%').
					' OR comment LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['include']).'%').')';
				$smarty->assign('include',$_GET['include']);
			} 
			if (!empty($_GET['exclude'])) {
				$where[] = 'title NOT LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['exclude']).'%');
				$where[] = 'comment NOT LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['exclude']).'%');
				$smarty->assign('exclude',$_GET['exclude']);
			} 		
			$smarty->assign('title',1);
		} else {
			if (!empty($_GET['include'])) {
				$where[] = 'comment LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['include']).'%');
				$smarty->assign('include',$_GET['include']);
			} 
			if (!empty($_GET['exclude'])) {
				$where[] = 'comment NOT LIKE '.$db->Quote('%'.preg_replace('/[\+~]$/','',$_GET['exclude']).'%');
				$smarty->assign('exclude',$_GET['exclude']);
			} 
		}
		if (count($where)) {
			
			$where[] = 'gridimage_id > '.($last_id-$size);

			$where= implode(' AND ',$where);
		} else {
			die("umm?");
		}


		$sql="select gridimage_id,user_id,realname,title,comment,grid_reference ".
			"from gridimage_search ".
			"where $where ".
			($max_gridimage_id?" and gridimage_id < $max_gridimage_id ":'').
			"order by gridimage_id desc limit 50";
	#print "<pre>$sql</pre>";
	#exit;
		$imagelist->_getImagesBySql($sql);
		
		if (count($imagelist->images)) {
			$total_results = count($imagelist->images);
		}
	}
	
	if ($db->readonly) {
		$db = GeographDatabaseConnection(false);
	}

	if (count($imagelist->images)) {
		
		$smarty->assign_by_ref('images', $imagelist->images);
		$smarty->assign_by_ref('image_count', $total_results);

		/*
		$last = $imagelist->images[count($imagelist->images)-1];

		$max_gridimage_id = $last->gridimage_id;
		$count++;

		if ($count < 10) {
			$token=new Token;
			$token->setValue("id", intval($max_gridimage_id));
			$token->setValue("c", intval($count));

			$smarty->assign('next', $token->getToken());
		}*/
		
		$inserts = array();
		$inserts[] = "created=NOW()";
		$inserts[] = "include = ".$db->Quote($_GET['include']);
		$inserts[] = "exclude = ".$db->Quote($_GET['exclude']);
		$inserts[] = "title = ".intval($_GET['title']);
		$inserts[] = "profile = ".$db->Quote($_GET['profile']);

		
		$inserts[] = "last_results = ".$total_results;
		$inserts[] = "last_time=NOW()";
		$inserts[] = "last_size=$size";
		$inserts[] = "last_gridimage_id=$last_id";
		
		$inserts[] = "total_results = ".$total_results;
		$inserts[] = "total_runs = 1";
		
		$inserts[] = "user_id = ".$USER->user_id;
		$inserts[] = "last_user_id = ".$USER->user_id;
		
		
		$updates = array();
		$updates[] = "last_results = ".$total_results;
		$updates[] = "last_time=NOW()";
		$updates[] = "last_size=$size";
		$updates[] = "last_gridimage_id=$last_id";
		$updates[] = "total_results = total_results + ".$total_results;
		$updates[] = "total_runs = total_runs + 1";
		$updates[] = "last_user_id = ".$USER->user_id;
		
		$db->Execute('INSERT INTO typo SET '.implode(',',$inserts).' ON DUPLICATE KEY UPDATE '.implode(',',$updates));

	} else {
		//if no results, just update, no insert. 
		
		$where = array();
		$where[] = "include = ".$db->Quote($_GET['include']);
		$where[] = "exclude = ".$db->Quote($_GET['exclude']);
		$where[] = "profile = ".$db->Quote($_GET['profile']);

		$where[] = "title = ".intval($_GET['title']);
		
		$updates = array();
		$updates[] = "last_results = 0";
		$updates[] = "last_time=NOW()";
		$updates[] = "last_size=$size";
		$updates[] = "last_gridimage_id=$last_id";
		$updates[] = "total_runs = total_runs + 1";
		$updates[] = "last_user_id = ".$USER->user_id;
		
		$db->Execute('UPDATE typo SET '.implode(',',$updates).' WHERE '.implode(' AND ',$where));
	}

}


$smarty->display($template, $cacheid);

