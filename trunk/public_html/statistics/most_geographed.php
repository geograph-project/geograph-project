<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;



$template='statistics_most_geographed.tpl';
$cacheid='statistics|most_geographed';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	#$db->debug = true;
	
	$both = array();
	
	foreach (array(1,2) as $ri) {
		$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			
		$origin = $db->CacheGetRow(100*24*3600,"select origin_x,origin_y from gridprefix where reference_index=$ri order by origin_x,origin_y limit 1");
		
			
		$most = $db->GetAll("select grid_reference,x,y,concat(substring(grid_reference,1,".($letterlength+1)."),substring(grid_reference,".($letterlength+3).",1)) as tenk_square,count(*) as geograph_count from gridsquare where reference_index = $ri and imagecount>0 group by tenk_square having geograph_count > 1 order by geograph_count desc limit 50");
		
		foreach($most as $id=>$entry) 
		{
			$most[$id]['x'] = ( intval(($most[$id]['x'] - $origin['origin_x'])/10)*10 ) +  $origin['origin_x'];
			$most[$id]['y'] = ( intval(($most[$id]['y'] - $origin['origin_y'])/10)*10 ) +  $origin['origin_y'];
		}
		
		$both = array_merge($both,$most);
	}
	
	function cmp_perc($a, $b) {
			global $both;
			if ($both[$a]['geograph_count'] == $both[$b]['geograph_count']) {
					 return 0;
			}
			return ($both[$a]['geograph_count'] > $both[$b]['geograph_count']) ? -1 : 1;
	}

	uksort($both, "cmp_perc"); 

$mosaic=new GeographMapMosaic;
$mosaic->setScale(40);
$mosaic->setMosaicFactor(2);

		


	$i = 1;
	foreach($both as $id=>$entry)
	{
		if ($lastgeographs == $both[$id]['geograph_count'])
			$both[$id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {

			$units=$i%10;
			switch($units)
			{
				case 1:$end=($i==11)?'th':'st';break;
				case 2:$end=($i==12)?'th':'nd';break;
				case 3:$end=($i==13)?'th':'rd';break;
				default: $end="th";	
			}

			$both[$id]['ordinal']=$i.$end;
			$lastgeographs = $both[$id]['geograph_count'];
		}
		$i++;
		
		
		//get a token to show a suroudding geograph map
		$mosaic->setOrigin($both[$id]['x'],$both[$id]['y']);
		
		$both[$id]['map_token'] = $mosaic->getToken();
	
		
	}

	$smarty->assign_by_ref("both", $both);	
	
	$onekm = $db->GetAll("select grid_reference,imagecount from gridsquare where imagecount>1 order by imagecount desc limit 75");
		
	$i = 1;
	foreach($onekm as $id=>$entry)
	{
		if ($lastgeographs == $onekm[$id]['imagecount'])
			$onekm[$id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {

			$units=$i%10;
			switch($units)
			{
				case 1:$end=($i==11)?'th':'st';break;
				case 2:$end=($i==12)?'th':'nd';break;
				case 3:$end=($i==13)?'th':'rd';break;
				default: $end="th";	
			}

			$onekm[$id]['ordinal']=$i.$end;
			$lastgeographs = $onekm[$id]['imagecount'];
		}
		$i++;
		
	}

	$smarty->assign_by_ref("onekm", $onekm);		
	
	
	$smarty->assign('generation_time', time());
	
}


$smarty->display($template, $cacheid);

	
?>
