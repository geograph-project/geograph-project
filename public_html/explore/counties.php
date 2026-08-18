<?php
/**
 * $Project: GeoGraph $
 * $Id: counties.php 5785 2009-09-12 10:06:29Z barry $
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

	//we can carefully join on gridsquare to lookup the first!
	$cols = "g.grid_reference, gridimage_id,realname,title,imageclass,user_id,imagetaken";
	$joins = " left join gridsquare g on (g.reference_index = c.reference_index
		 AND x = (c.e DIV 1000) + IF(c.reference_index = 1, 206,10)
		 AND y = (c.n DIV 1000) + IF(c.reference_index = 1, 0,149) )";
	$joins .= " left join gridimage_search gi on (gi.gridimage_id = g.first)";

	if ($type == 'center') {
		$smarty->assign("page_title", "Ceremonial County Centre Points");
		$smarty->assign("start_info", "See <a href=\"#notes\">bottom</a> of page for clarification of Ceremonial or Geographic Counties as used on this page");
		$smarty->assign("extra_info", "<a name=\"notes\"/>* this pages uses counties from 1995, making them now known as  <a href=\"http://en.wikipedia.org/wiki/Ceremonial_counties_of_England\">Ceremonial or Geographic Counties</a> and for some unknown reason Northern Ireland is just one entity. Furthermore only counties that happen to have their calculated 'centre of bounding box' on land will be included in this list (eg Cornwall doesn't), see blue triangles on this <a href=\"http://www.deformedweb.co.uk/trigs/map.cgi?w=600&amp;b=500&amp;e=400000&amp;n=400000&amp;x=d&amp;l=0&amp;hg=1&amp;x=c\">map</a>.");

		$counties = $db->GetAll("select c.*, $cols from loc_counties c $joins where n > 0 order by reference_index,n");

	} elseif ($type == 'ireland') {
		$smarty->assign("page_title", "County Center Points - Ireland");

		$counties = $db->GetAll("SELECT c.*, $cols FROM (
			SELECT county as name,floor(avg(e)) as e, floor(avg(n)) as n, 2 as reference_index FROM ie_open_data GROUP BY country,county
		) c $joins");

	} elseif ($type == 'pre74') {
		$smarty->assign("page_title", "Historic County (Pre 1974) Centre Points");
		$smarty->assign("start_info", "These are approximate centres for counties pre 1974 re-shuffle");

		$counties = $db->GetAll("select c.*, $cols from loc_counties_pre74 c $joins where n > 0 order by reference_index,n");

	} elseif ($type == 'modern') {
		$smarty->assign("page_title", "Modern Administrative County Centre Points");
		$smarty->assign("start_info", "These are approximate centres for modern administrative counties.");
		$smarty->assign("extra_info", "<div class=\"copyright\">Great Britain locations based upon Ordnance Survey&reg 1:50 000 Scale Gazetteer with the permission of Ordnance Survey on behalf of The Controller of Her Majesty's Stationery Office, &copy; Crown copyright. Educational licence 100045616.</div>");

		$counties = $db->GetAll("select c.*, $cols from os_gaz_county c $joins where n > 0 and name not like 'XX%' order by reference_index,n");

	} elseif ($type == 'capital') {
		$smarty->assign("page_title", "Ireland County Capital Towns");
		$smarty->assign("extra_info", "* at the moment we dont actully store which county each capital is in, this information is furthermore only available for Ireland so far.");
		$counties = $db->GetAll("SELECT c.*, $cols FROM `loc_towns` c $joins WHERE `s` = '2' AND c.`reference_index` = 2 ORDER BY n");
	}

	if ($counties) {
		foreach ($counties as $i => $row) {
			if (!empty($row['gridimage_id'])) {
				$gridimage=new GridImage;
				$gridimage->fastInit($row);
				if ($gridimage->imagetaken > '1000')
					$gridimage->imagetakenString = getFormattedDate($gridimage->imagetaken);
				$gridimage->county = $row['name'];
				$results[] = $gridimage;

			} elseif (!empty($row['grid_reference'])) {
				$row['county'] = $row['name'];
				$unfilled[] = $row;
			} else {
				$nonland[] = array('county' => $row['name']);
			}
		}
	}

	$smarty->assign_by_ref("results", $results);
	$smarty->assign_by_ref("unfilled", $unfilled);
	$smarty->assign_by_ref("nonland", $nonland);
}


$smarty->display($template, $cacheid);

