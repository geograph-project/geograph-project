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

$cacheid = md5("$include|$exclude|$title");

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600; //1hour cache
	customExpiresHeader(3600,false,true);
}
	
//regenerate?
if (!$smarty->is_cached($template, $cacheid) && strlen($include) > 2)
{
	$where = array();
	
	$imagelist=new ImageList;

	$db = $imagelist->_getDB();
	
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
		$last = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");
	
		$where[] = 'gridimage_id > '.($last-3000);
		
		$where= implode(' AND ',$where);
	} else {
		die("umm?");
	}
	
	$sql="select gridimage_id,user_id,realname,title,comment,grid_reference ".
		"from gridimage_search ".
		"where $where ".
		($max_gridimage_id?" and gridimage_id < $max_gridimage_id ":'').
		"order by gridimage_id desc limit 50";
print "<pre>$sql</pre>";
#exit;
	$imagelist->_getImagesBySql($sql);
	
	if (count($imagelist->images)) {
		
		$smarty->assign_by_ref('images', $imagelist->images);
		$smarty->assign_by_ref('image_count', count($imagelist->images));

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
	} 

}


$smarty->display($template, $cacheid);

	
?>
