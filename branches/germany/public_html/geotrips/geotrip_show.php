<?php
/**
 * $Project: GeoGraph $
 * $Id: geotrip_show.php 7817 2013-03-31 19:47:52Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Rudi Winter (http://www.geograph.org.uk/profile/2520)
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

$trip_id = isset($_GET['trip']) ? intval($_GET['trip']) : 0;

$cacheid = "trip|$trip_id|show";
$template = 'geotrips_show.tpl';

if (!$smarty->is_cached($template, $cacheid)) {

	require_once('geograph/searchcriteria.class.php');
	require_once('geograph/searchengine.class.php');
	require_once('geograph/gridimage.class.php');
	require_once('geograph/conversionslatlong.class.php');
	include('./geotrip_func.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');

	// get track from database
	$trk=$db->getRow("select * from geotrips where id=$trip_id");
	if (empty($trk)) {
		header("HTTP/1.0 404 Not Found");
		$smarty->display('static_404.tpl');
		exit;
	}

	$foll=$db->getRow("select id from geotrips where contfrom=$trip_id"); # FIXME error handling

	$bbox=explode(' ',$trk['bbox']);
	$trk['latcen'] = 0.5*($bbox[0]+$bbox[2]);
	$trk['loncen'] = 0.5*($bbox[1]+$bbox[3]);
	$trk['latmin'] = $bbox[0];
	$trk['lonmin'] = $bbox[1];
	$trk['latmax'] = $bbox[2];
	$trk['lonmax'] = $bbox[3];
	$trk['nicetype'] = whichtype($trk['type']);
	$trk['nextpart'] = $foll['id'];
	$trk['track'] = array_chunk(explode(' ',trim($trk['track'])), 2);

	// fetch Geograph data
	$conv = new ConversionsLatLong;
	$geograph = array();
	$realnames = array();
	$engine = new SearchEngine($trk['search']);
	$engine->criteria->resultsperpage = 250; // FIXME really?
	$recordSet = $engine->ReturnRecordset(0, true);
	while (!$recordSet->EOF) {
		$image = $recordSet->fields;
		if (    $image['nateastings']
		    &&  $image['viewpoint_eastings']
		    &&  $image['user_id'] == $trk['uid']
		    &&  $image['viewpoint_grlen'] > 4
		    &&  $image['natgrlen'] > 4
		    && (   $image['view_direction'] != -1
		        || $image['viewpoint_eastings']  != $image['nateastings']
		        || $image['viewpoint_northings'] != $image['natnorthings']
		        || $image['viewpoint_refindex']  != $image['reference_index'])
		    &&  $image['imagetaken'] === $trk['date']
		) {
			$gridimage = new GridImage($image['gridimage_id']); //FIXME fast init?
			if (!$gridimage->isValid()) {
				continue;
				//FIXME?
			}
			// shift marker to centre of square indicated by GR
			fake_precision($image);
			$latlon = $conv->national_to_wgs84($image['viewpoint_eastings'], $image['viewpoint_northings'], $image['viewpoint_refindex'], true);
			$image['gridimage'] = $gridimage;
			$image['viewpoint'] = $latlon;
			$ea=$image['nateastings'];
			$no=$image['natnorthings'];
			if (
			       $image['viewpoint_eastings']  == $image['nateastings']
			    && $image['viewpoint_northings'] == $image['natnorthings']
			    && $image['viewpoint_refindex']  == $image['reference_index']
			    && $image['view_direction'] != -1
			) {  // subject GR == camera GR and view direction given
				$ea += round(20.*sin(deg2rad($image['view_direction'])));
				$no += round(20.*cos(deg2rad($image['view_direction'])));
			}
			$latlon = $conv->national_to_wgs84($ea, $no, $image['reference_index'], true);
			$image['subject'] = $latlon;

			$geograph[] = $image;
			if ($image['credit_realname']) {
				$realnames[$image['realname']] = $image['realname'];
			}
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close();

	$selected = array_rand($geograph, 3); // select three random images

	$smarty->assign_by_ref('images', $geograph);
	$smarty->assign_by_ref('selectedimages', $selected);
	$smarty->assign_by_ref('realnames', $realnames);
	$smarty->assign('lonmin', $CONF['gmlonrange'][0][0]);
	$smarty->assign('lonmax', $CONF['gmlonrange'][0][1]);
	$smarty->assign('latmin', $CONF['gmlatrange'][0][0]);
	$smarty->assign('latmax', $CONF['gmlatrange'][0][1]);
	$smarty->assign('google_maps_api_key',$CONF['google_maps_api_key']);
	$smarty->assign_by_ref('trip', $trk);
}

$smarty->display($template, $cacheid);

?>
