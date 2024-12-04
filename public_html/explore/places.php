<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 8612 2017-11-04 11:46:45Z barry $
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
init_session();


pageMustBeHTTPS();


###############################################

$_GET['preview'] = 1;

//grid specified
if (!empty($_GET['ri'])) {
	//county specified
	if (!empty($_GET['adm1'])) {
		if (!empty($_GET['pid'])) {
			$url = "/search.php?placename=".intval($_GET['pid'])."&do=1";
			header("Location: $url");
			print "<a href=\"".htmlentities($url)."\">Continue to Search</a>";
			exit;
		}
		if (is_numeric($_GET['adm1'])) {
			//adm1 is actully just an example placename in the OLD table!

			$db = GeographDatabaseConnection(true);

			$sql = "SELECT co_code,full_county as adm1_name FROM os_gaz_old WHERE seq = {$_GET['adm1']}";
			$placename = $db->GetRow($sql);

			//and as its the old table - lets redirect...
			$ri = intval($_GET['ri']);
			$adm1 = preg_replace('/[^A-Za-z]/','_',$placename['adm1_name']);

			header("HTTP/1.0 301 Moved Permanently");
			header("Status: 301 Moved Permanently");
			header("Location: /explore/places/{$ri}/{$adm1}/");
			exit;
		}
		if (!empty($_GET['preview'])) {
			$template='explore_places_adm1_preview.tpl';
		} else {
			$template='explore_places_adm1.tpl';
		}
		$cacheid='places|'.$_GET['ri'].'.'.$_GET['adm1'];
	//county selector
	} else {
		$template='explore_places_ri.tpl';
		$cacheid='places|'.$_GET['ri'];
	}
//grid/country selector
} else {
	$template='explore_places.tpl';
	$cacheid='places';
}

###############################################

$smarty = new GeographPage;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache


if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, 'places|');

//regenerate?
if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');

	$db = GeographDatabaseConnection(true);

	###############################################
	//grid/country selected
	if (!empty($_GET['ri'])) {
		$smarty->assign('ri', $_GET['ri']);

		###############################################
		//county selected

		if (!empty($_GET['adm1']) && preg_match('/^[\w \&,-]+$/',$_GET['adm1'])) {
			$smarty->assign('adm1', $_GET['adm1']);


			###############################################
			// list places - works for BOTH grids!

			if (!empty($_GET['preview'])) {
				$ri = intval($_GET['ri']);

				//although needs some magic to lookup the county name!
				if ($_GET['ri'] == 2) {
					list($country,$adm1) = explode('-',$_GET['adm1']);
					if ($adm1) {
						$county = $db->GetOne("SELECT name FROM loc_adm1 WHERE country = ".$db->Quote($country)." AND adm1 = ".$db->Quote($adm1));
						$smarty->assign_by_ref('adm1_name', $county);
						$smarty->assign('parttitle', "in County");
						$filter = "county LIKE ".$db->Quote($county);
					} else {
						$smarty->assign('adm1_name', "Northern Ireland");
						$filter = "country = 'Northern Ireland'";
					}
				} else {
					$sql = "SELECT co_code,name as adm1_name FROM os_gaz_county WHERE name LIKE ".$db->Quote($_GET['adm1'])." LIMIT 1";
					$placename = $db->GetRow($sql);

					$smarty->assign_by_ref('adm1_name', $placename['adm1_name']);
					if ($placename['adm1_name'] != "Isle of Man")
						$smarty->assign('parttitle', "in County");

					$filter = "county LIKE ".$db->Quote($_GET['adm1']); //underscores already work as wildcards
				}

				$sql = "SELECT placename_id,Place as full_name,images as c
				FROM sphinx_placenames where reference_index = $ri AND images > 0 AND $filter ORDER BY Place";

			###############################################
			// list places in Ireland

			} elseif ($_GET['ri'] == 2) {
				list($country,$adm1) = explode('-',$_GET['adm1']);
				if ($adm1) {
					$sql = "SELECT name FROM loc_adm1 WHERE country = ".$db->Quote($country)." AND adm1 = ".$db->Quote($adm1);
					$smarty->assign_by_ref('adm1_name', $db->GetOne($sql));
					$smarty->assign('parttitle', "in County");
				} else {
					$smarty->assign('adm1_name', "Northern Ireland");
				}
				$sql = "SELECT placename_id,full_name,c,gridimage_id
				FROM gridimage_loc_placenames
				WHERE reference_index = ".$db->Quote($_GET['ri'])." AND country = ".$db->Quote($country)." AND adm1 = ".$db->Quote($adm1);

			###############################################
			// list places in Great Britain

			} else {
				if (preg_match('/^\w+/',$_GET['adm1'])) {
					$sql = "SELECT co_code,name as adm1_name FROM os_gaz_county WHERE name LIKE ".$db->Quote($_GET['adm1'])." LIMIT 1";
					$placename = $db->GetRow($sql);
				} else {
					die("adm1 error");
				}

				$smarty->assign_by_ref('adm1_name', $placename['adm1_name']);
				if ($placename['adm1_name'] != "Isle of Man")
					$smarty->assign('parttitle', "in County");

				$sql = "SELECT placename_id,full_name,c,gridimage_id
				FROM gridimage_os_gaz
				WHERE co_code = '{$placename['co_code']}'";
			}

			$counts = $db->GetAssoc($sql);
			$smarty->assign_by_ref('counts', $counts);
			$total = 0;
			if (!empty($counts)) foreach ($counts as $row) $total += $row['c'];
			$smarty->assign('total', number_format($total,0));

		###############################################
		// list counties in Great Britain

		} elseif ($_GET['ri'] == 1) {
			$sql = "SELECT REPLACE(REPLACE(REPLACE(REPLACE(os_gaz_county.name,'&','_'),',','_'),'-','_'),' ','_') as adm1,
			os_gaz_county.name as name,placename_id,full_name,country,
			sum(gridimage_os_gaz.c) as images,count(distinct (placename_id)) as places,gridimage_id
			FROM gridimage_os_gaz INNER JOIN os_gaz_county USING (co_code)
			GROUP BY co_code
			ORDER BY country,name";
			$counts = $db->GetAssoc($sql);
			unset($counts['XXXXXXXX']); //not a real county

			foreach ($counts as $idx => $row) {
				switch($row['country']) {
					case 'E': $counts[$idx]['country'] = 'England'; break;
					case 'M': $counts[$idx]['country'] = 'Isle of Man'; break;
					case 'S': $counts[$idx]['country'] = 'Scotland'; break;
					case 'W': $counts[$idx]['country'] = 'Wales'; break;
				}
			}

			$smarty->assign_by_ref('counts', $counts);

		###############################################
		//list counties in Ireland

		} else {
			$sql = "SELECT concat(gridimage_loc_placenames.country,'-',gridimage_loc_placenames.adm1) as adm1,coalesce(loc_adm1.name,'Northern Ireland') as name,
			placename_id,full_name,
			sum(gridimage_loc_placenames.c) as images,count(distinct (placename_id)) as places,gridimage_id
			FROM gridimage_loc_placenames
			LEFT JOIN loc_adm1 ON(loc_adm1.adm1 = gridimage_loc_placenames.adm1 AND loc_adm1.country = gridimage_loc_placenames.country)
			GROUP BY adm1";
			$counts = $db->GetAssoc($sql);
			unset($counts['0']); //adm1 0 is bogus!
			$smarty->assign_by_ref('counts', $counts);
		}

		//load an example image
		if (count($counts)) {
			$key = array_rand($counts,1);
			$val = $counts[$key];
			if (!empty($val['gridimage_id'])) {
				$image=new GridImage();
				if ($image->loadFromId($val['gridimage_id'],true)) {
					$image->county = $val['name'];
					$image->placename = $val['full_name'];
					$smarty->assign_by_ref('image', $image);
				}
			}
		}

	###############################################
	// just select the grid

	} else {
		$sql = "SELECT reference_index,sum(imagecount) as c
		FROM gridsquare
		GROUP BY reference_index";
		$counts = $db->GetAssoc($sql);
		$smarty->assign_by_ref('counts', $counts);
	}

	$smarty->assign_by_ref('references',$CONF['references']);
}

$smarty->display($template, $cacheid);

