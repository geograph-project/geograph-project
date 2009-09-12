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
require_once('geograph/gridsquare.class.php');
init_session();

$smarty = new GeographPage;

$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'center';

$template='explore_counties.tpl';
$cacheid='explore|counties'.$type;

$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);

	require_once('geograph/conversions.class.php');
	$conv = new Conversions;

	if ($type == 'center') {
		$smarty->assign("page_title", "Ceremonial County Centre Points");
		$smarty->assign("start_info", "See <a href=\"#notes\">bottom</a> of page for clarification of Ceremonial or Geographic Counties as used on this page");
		$smarty->assign("extra_info", "<a name=\"notes\"/>* this pages uses counties from 1995, making them now known as  <a href=\"http://en.wikipedia.org/wiki/Ceremonial_counties_of_England\">Ceremonial or Geographic Counties</a> and for some unknown reason Northern Ireland is just one entity. Furthermore only counties that happen to have their calculated 'centre of bounding box' on land will be included in this list (eg Cornwall doesn't), see blue triangles on this <a href=\"http://www.deformedweb.co.uk/trigs/map.cgi?w=600&amp;b=500&amp;e=400000&amp;n=400000&amp;x=d&amp;l=0&amp;hg=1&amp;x=c\">map</a>.");
		
		$counties = $db->GetAll("select * from loc_counties where n > 0 order by reference_index,n");
		
	} elseif ($type == 'pre74') {
		$smarty->assign("page_title", "Historic County (Pre 1974) Centre Points");
		$smarty->assign("start_info", "These are approximate centres for counties pre 1974 re-shuffle");
				
		$counties = $db->GetAll("select * from loc_counties_pre74 where n > 0 order by reference_index,n");
	
	} elseif ($type == 'modern') {
		$smarty->assign("page_title", "Modern Administrative County Centre Points");
		$smarty->assign("start_info", "These are approximate centres for modern administrative counties.");
		$smarty->assign("extra_info", "<div class=\"copyright\">Great Britain locations based upon Ordnance Survey&reg 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright. Educational licence 100045616.</div>");
				
		$counties = $db->GetAll("select * from os_gaz_county where n > 0 and name not like 'XX%' order by reference_index,n");
		 
	} elseif ($type == 'capital') {
		$smarty->assign("page_title", "Ireland County Capital Towns");
		$smarty->assign("extra_info", "* at the moment we dont actully store which county each capital is in, this information is furthermore only available for Ireland so far.");
		$counties = $db->GetAll("SELECT * FROM `loc_towns` WHERE `s` = '2' AND `reference_index` = 2 ORDER BY n");
	
	}
	
	if ($counties) {
		foreach ($counties as $i => $row) {
			list($x,$y) = $conv->national_to_internal($row['e'],$row['n'],$row['reference_index']);
			$sql="select * from gridimage_search where x=$x and y=$y order by moderation_status+0 desc,seq_no limit 1";

			$rec=$db->GetRow($sql);
			if (count($rec))
			{
				$gridimage=new GridImage;
				$gridimage->fastInit($rec);
				
				$gridimage->county = $row['name'];
				
				$results[] = $gridimage;
			}
			else 
			{
				$sql="select grid_reference from gridsquare where x=$x and y=$y limit 1";
				
				$rec=$db->GetRow($sql);
				if (count($rec)) 
				{
					$rec['county'] = $row['name'];
					$unfilled[] = $rec;
				} else {
					$nonland[] = array('county' => $row['name']);
				}
			}
		}
	}

	$smarty->assign_by_ref("results", $results);	
	$smarty->assign_by_ref("unfilled", $unfilled);
	$smarty->assign_by_ref("nonland", $nonland);
}


$smarty->display($template, $cacheid);

	
?>
