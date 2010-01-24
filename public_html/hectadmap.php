<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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


$smarty = new GeographPage;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$w = (isset($_GET['w']) && is_numeric($_GET['w']))?intval($_GET['w']):1;


$template='hectadmap.tpl';
$cacheid="$u.$w";

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hour cache


$maximages = 100; //percentage AND number of images in a hectad


function smarty_modifier_colerize($input) {
	global $maximages;
	if ($input) {
		if ($input == $maximages) {
			return 'ffcc11';
		}
		$hex = str_pad(dechex(255 - $input/$maximages*255), 2, '0', STR_PAD_LEFT); 
		return "ffff$hex";
	} 
	return 'ffffff';
}

$smarty->register_modifier("colerize", "smarty_modifier_colerize");



if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if ($u) {
		$columns = '0 as geosquares,0 as percentage,';
	} else {
		$columns = "geosquares,
			round(geosquares/landsquares*100,1) as percentage,";
	}
	$hectads = $db->getAll("select 
		hectad,landsquares,
		$columns
		landsquares,x,y
		from hectad_stat 
		where landsquares > 0");
	
	$lookup = $grid = array();
	$x1 = 9999999;
	$x2 = 0;
	foreach ($hectads as $i => $h) {
		$h['digits'] = substr($h['hectad'],-2);
		$x = intval($h['x']/10)+10;
		$y = intval($h['y']/10);
		$grid[$y][$x] = $h;
		if ($u)
			$lookup[$h['hectad']] = array($y,$x);
		$x1 = min($x,$x1);
		$x2 = max($x,$x2);
	}
	
	if ($u) {
		if ($u == $USER->user_id) {
			$smarty->assign_by_ref('profile', $USER);
		} else {
			$profile=new GeographUser($u);
			$smarty->assign_by_ref('profile', $profile);
		}
		$smarty->assign_by_ref('u', $u);
		
		$hectads2 = $db->CacheGetAll(3600*24,"select 
		concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) as hectad,
		count(distinct x,y) as geosquares
		from gridimage_search gs
		where user_id = $u and moderation_status = 'geograph'
		group by hectad 
		order by null");
		foreach ($hectads2 as $i => $h) {
			list($y,$x) = $lookup[$h['hectad']];
			$grid[$y][$x]['geosquares'] = $h['geosquares'];
			$grid[$y][$x]['percentage'] = round($h['geosquares']/$grid[$y][$x]['landsquares']*100,1);
		}
	}
	
	$ys = array_keys($grid);
	$y1 = min($ys);
	$y2 = max($ys);
	
	$smarty->assign('which',$w);
	switch($w) {
		case '3': $w = "landsquares"; break;
		case '2': $w = "percentage"; break;
		default: $w = "geosquares"; break;
	}
	$smarty->assign('column',$w);
	
	$smarty->assign_by_ref('grid',$grid);
	$smarty->assign('x1',$x1);
	$smarty->assign('x2',$x2);
	$smarty->assign('y1',$y1);
	$smarty->assign('y2',$y2);
	$smarty->assign('w',$x2-$x1+5);
	$smarty->assign('h',$y2-$y1);

} elseif ($u) {
	if ($u == $USER->user_id) {
		$smarty->assign_by_ref('profile', $USER);
	} else {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
	}
	$smarty->assign_by_ref('u', $u);
}


$smarty->display($template, $cacheid);

	
?>
