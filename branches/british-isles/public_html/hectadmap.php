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

	$tables = '';

	if ($w == 10) {
		$columns = 'if(hectad_assignment_id is null,0,100) as percentage,';
		$tables = " left join hectad_assignment on (hectad_stat.hectad = hectad_assignment.hectad and status = 'accepted')"; 		
	} elseif ($u) {
		$columns = '0 as geosquares,0 as percentage,';
	} else {
		$columns = "geosquares,
			round(geosquares/landsquares*100,1) as percentage,";
		if ($w == 4) {
			 $columns .= "landsquares-geosquares as greensquares, ";
		} elseif ($w == 5) {
			 $columns .= "100-(landsquares-geosquares) as greensquares, ";
		}
	}
	$hectads = $db->getAll("select 
		hectad_stat.hectad,landsquares,
		$columns
		landsquares,x,y,reference_index
		from hectad_stat 
		$tables
		where landsquares > 0");
	
	$lookup = $grid = array();
	$x1 = 9999999;
	$x2 = 0;
	
	$o = array();
	foreach ($CONF['origins'] as $ri => $row) {
		$o[$ri] = array();
		$o[$ri][0] = $CONF['origins'][$ri][0]%10;
		$o[$ri][1] = $CONF['origins'][$ri][1]%10;
	}

	foreach ($hectads as $i => $h) {
		$h['digits'] = substr($h['hectad'],-2);
		$ri = $h['reference_index'];
		$x = intval(($h['x'] - $o[$ri][0])/10)+10;
		$y = intval(($h['y'] - $o[$ri][1])/10);
		$grid[$y][$x] = $h;
		if ($u)
			$lookup[$h['hectad']] = array($y,$x);
		$x1 = min($x,$x1);
		$x2 = max($x,$x2);
	}
	
if (!empty($_GET['dd'])) {
	print_r($grid);
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
			if ($w == 4) {
				$grid[$y][$x]['greensquares'] = $grid[$y][$x]['landsquares'] - $h['geosquares'];
			} elseif ($w == 5) {
				$grid[$y][$x]['greensquares'] = 100 - ($grid[$y][$x]['landsquares'] - $h['geosquares']);
			}
		}
	}
	
	$ys = array_keys($grid);
	$y1 = min($ys);
	$y2 = max($ys);


if (!empty($_GET['dd'])) {
	print "$x1 $x2 $y1 $y2.";
}

	$smarty->assign('which',$w);
	switch($w) {
		case '5': 
		case '4': $w = "greensquares"; break;
		case '3': $w = "landsquares"; break;
		case '10': 
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


