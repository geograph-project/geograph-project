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

if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*24; //24 hour cache
}

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
	$trk['track'] = trim($trk['track']);
	$trk['track'] = $trk['track'] !== "" ? array_chunk(explode(' ',trim($trk['track'])), 2) : array();

	// fetch Geograph data
	$conv = new ConversionsLatLong;
	$geograph = array();
	$realnames = array();
	$cells = array(); # $cells("$x_$y") = array($x, $y, array(images taken from cell: index in geograph array))
	$groupdist = 5; # group images below that distance (metres)
	$scale_lat_y = 40000000/360.0;
	$scale_lon_x = $scale_lat_y * cos(deg2rad($trk['latcen'])); # for small areas this is nearly constant
	$cell_height = $groupdist/$scale_lat_y;
	$cell_width =  $groupdist/$scale_lon_x;
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

			if ($image['credit_realname']) {
				$realnames[$image['realname']] = $image['realname'];
			}
			$image['nice_view_direction'] = $image['view_direction'] < 0 ? '' : heading_string($image['view_direction']);
			$image['group'] = -1;

			# reduce complexity for finding other images taken nearby
			$cell_key_x = floor(($image['viewpoint'][1]+180)/$cell_width);
			$cell_key_y = floor(($image['viewpoint'][0]+90)/$cell_height);
			$cell_key = $cell_key_x.'_'.$cell_key_y; # php does not allow pairs/tuples as keys
			if (!array_key_exists($cell_key, $cells)) {
				$cells[$cell_key] = array($cell_key_x, $cell_key_y, array(count($geograph)));
			} else {
				$cells[$cell_key][2][] = count($geograph);
			}
			$image['cell_key'] = $cell_key;

			$geograph[] = $image;
		}
		$recordSet->MoveNext();
	}
	$recordSet->Close();
	

	# Find other images taken nearby and group images if necessary.
	# There are two obvious strategies:
	# * Put all points connected by at least one short distance in one group and assign
	#   a new position afterwards.
	# * When iterating over pairs, move the second point to the first one if the distance is short.
	#
	# The first method is symmetric, the order of evaluating distances does not matter.
	# The second method can yield smaller groups but has the problem that the points
	# move during evaluation. Currently, try the first method.
	$groups = array(); # $group[$groupid] = array(ctrlat, ctrlon, array(photos: index in geograph array)) # $groupid=photo index of a member
	foreach($geograph as $idx=>&$image) {
		$curcell =& $cells[$image['cell_key']];
		for ($i = -1; $i <= 1; ++$i) {
			$cell_key_x = $curcell[0] + $i;
			for ($j = -1; $j <= 1; ++$j) {
				$cell_key_y = $curcell[1] + $j;
				$cell_key = $cell_key_x.'_'.$cell_key_y;
				if (array_key_exists($cell_key, $cells)) {
					foreach ($cells[$cell_key][2] as $idx2) {
						if ($idx2 <= $idx) {
							continue; # pair already done
						}
						$image2 =& $geograph[$idx2];
						$group1 = $image['group'];
						$group2 = $image2['group'];
						if ($group1 != -1 && $group1 == $group2) {
							continue; # already belonging to the same group
						}
						$dx = ($image['viewpoint'][1]-$image2['viewpoint'][1]) * $scale_lon_x;
						$dy = ($image['viewpoint'][0]-$image2['viewpoint'][0]) * $scale_lat_y;
						if ($dx*$dx + $dy*$dy > $groupdist*$groupdist) {
							continue; # too far away to form a group
						}
						# $image and $image2 are too close, form a group
						if ($group1 == -1) {
							if ($group2 == -1) { # new group containing both images
								$groups[$idx] = array(
									$image['viewpoint'][0]+$image2['viewpoint'][0],
									$image['viewpoint'][1]+$image2['viewpoint'][1],
									array($idx, $idx2)
								);
								$image['group'] = $idx;
								$image2['group'] = $idx;
							} else { # add $image to group containing $image2
								$groups[$group2][0] += $image['viewpoint'][0];
								$groups[$group2][1] += $image['viewpoint'][1];
								$groups[$group2][2][] = $idx;
								$image['group'] = $group2;
							}
						} elseif ($group2 == -1) { #FIXME can this shappen? # add $image2 to group containing $image
							$groups[$group1][0] += $image2['viewpoint'][0];
							$groups[$group1][1] += $image2['viewpoint'][1];
							$groups[$group1][2][] = $idx2;
							$image2['group'] = $group1;
						} else { # merge groups
							if (count($groups[$group2][2]) < count($groups[$group1][2])) { # keep larger group, add members of smaller group
								$keepgroup = $group1;
								$delgroup = $group2;
							} else {
								$keepgroup = $group2;
								$delgroup = $group1;
							}
							$groups[$keepgroup][0] += $groups[$delgroup][0];
							$groups[$keepgroup][1] += $groups[$delgroup][1];
							$groups[$keepgroup][2] += $groups[$delgroup][2];
							foreach ($groups[$delgroup][2] as $chgidx) {
								$geograph[$chgidx]['group'] = $keepgroup;
							}
							unset($groups[$delgroup]);
						}
					}
				}
			}
		}
	}
	unset($image);

	function viewcmp($idx1, $idx2)
	{
		global $geograph;
		return $geograph[$idx1]['view_direction'] - $geograph[$idx2]['view_direction'];
	}

	#groups:
	#* calculate group centre and use closest image position as group position
	#* sort group members by view direction
	foreach ($groups as &$group) {
		$len = count($group[2]);
		$group[0] /= $len;
		$group[1] /= $len;
		$bestdsq = -1;
		$bestidx = -1;
		foreach ($group[2] as $idx) {
			$dx = ($group[1]-$geograph[$idx]['viewpoint'][1]) * $scale_lon_x;
			$dy = ($group[0]-$geograph[$idx]['viewpoint'][0]) * $scale_lat_y;
			$dsq = $dx*$dx + $dx*$dy;
			if ($bestdsq < 0 || $dsq < $bestdsq) {
				$bestidx = $idx;
				$bestdsq = $dsq;
			}
		}
		$group[0] = $geograph[$bestidx]['viewpoint'][0];
		$group[1] = $geograph[$bestidx]['viewpoint'][1];
		usort($group[2], viewcmp);
	}
	unset($group);

	$selected = array_rand($geograph, 3); // select three random images

	$smarty->assign_by_ref('images', $geograph);
	$smarty->assign_by_ref('groups', $groups);
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
