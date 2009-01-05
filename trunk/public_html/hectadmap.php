<?php
/**
 * $Project: GeoGraph $
 * $Id: hectads.php 3514 2007-07-10 21:09:55Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

		$hex = str_pad(dechex(255 - $input/$maximages*255), 2, '0', STR_PAD_LEFT); 
		return "ffff$hex";
	} 
	return 'ffffff';
}

$smarty->register_modifier("colerize", "smarty_modifier_colerize");



if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	
	$title = "Hectad Coverages";
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if ($u) {
		die("not ready yet");
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title = " for ".($profile->realname);
		
		$hectads = $db->CacheGetAll(3600,"select 
		concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) as tenk_square,
		count(distinct x,y) as geograph_count,
		round(count(distinct x,y) * 100 / sum(percent_land >0),1) as percentage,
		sum(percent_land >0) as land_count,min(x) as x,min(y) as y
		from gridsquare gs
		left join gridimage gi on (gs.gridsquare_id = gi.gridsquare_id and user_id = $u and moderation_status = 'geograph')
		group by tenk_square 
		having land_count > 0
		order by null");
	} else {
		$hectads = $db->CacheGetAll(3600,"select 
		concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) as tenk_square,
		sum(has_geographs) as geograph_count,
		round(sum(has_geographs) * 100 / sum(percent_land >0),1) as percentage,
		sum(percent_land >0) as land_count,min(x) as x,min(y) as y
		from gridsquare 
		group by tenk_square 
		having land_count > 0
		order by null");
	}
	
	$grid = array();
	$x1 = 9999999;
	$x2 = 0;
	foreach ($hectads as $i => $h) {
		$x = intval($h['x']/10)+10;
		$y = intval($h['y']/10);
		$grid[$y][$x] = $h;
	
		$x1 = min($x,$x1);
		$x2 = max($x,$x2);
	}
	$ys = array_keys($grid);
	$y1 = min($ys);
	$y2 = max($ys);
	
	$smarty->assign('which',$w);
	switch($w) {
		case '3': $w = "land_count"; break;
		case '2': $w = "percentage"; break;
		default: $w = "geograph_count"; break;
	}
	$smarty->assign('column',$w);
	
	$smarty->assign_by_ref('grid',$grid);
	$smarty->assign('x1',$x1);
	$smarty->assign('x2',$x2);
	$smarty->assign('y1',$y1);
	$smarty->assign('y2',$y2);
	$smarty->assign('w',$x2-$x1);
	$smarty->assign('h',$y2-$y1);
#print_r($smarty->_tpl_vars);	

} 

$smarty->display($template, $cacheid);

	
?>
