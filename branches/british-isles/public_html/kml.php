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
require_once('geograph/searchcriteria.class.php');
require_once('geograph/searchengine.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/kmlfile.class.php');
require_once('geograph/kmlfile2.class.php');


if (isset($_GET['id']))  {
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	$image=new GridImage;
	
	$ok = $image->loadFromId($_GET['id']);

	if ($ok && $image->moderation_status=='rejected') {
		header("HTTP/1.0 410 Gone");
		header("Status: 410 Gone");
	} elseif ($ok) {
		if ((strpos($_SERVER["REQUEST_URI"],'/photo/') === FALSE && isset($_GET['id'])) || strlen($_GET['id']) !== strlen(intval($_GET['id']))) {
			//keep urls nice and clean - esp. for search engines!
			header("HTTP/1.0 301 Moved Permanently");
			header("Status: 301 Moved Permanently");
			header("Location: /photo/".intval($_GET['id']).".kml");
			print "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".intval($_GET['id']).".kml\">View file</a>";
			exit;
		}
		$version42plus = false;
		if ($_GET['new']) {
			$version42plus = true;
		} 
		//if (check version) {
		//	$version42plus = true;
		//}
	
		//when this image was modified
		$mtime = strtotime($image->upd_timestamp);

		customCacheControl($mtime,$image->gridimage_id.'|'.$version42plus);	
		
		customExpiresHeader(3600*24*48,true);
		
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		
		//because we not loading from the search cache need to recalculate this
		list($image->wgs84_lat,$image->wgs84_long) = $conv->gridsquare_to_wgs84($image->grid_square);
		
		$kml = new kmlFile();
		$kml->atom = true;
		$stylefile = "http://{$CONF['KML_HOST']}/kml/style.kmz";

		$kml->filename = "Geograph".$image->gridimage_id.".kml";

		$point = new kmlPoint($image->wgs84_lat,$image->wgs84_long);

		$placemark = $kml->addChild(new kmlPlacemark_Photo('id'.$image->gridimage_id,$image->grid_reference." : ".$image->title,$point));
		$placemark->useHoverStyle();
		$placemark->useCredit($image->realname,"http://{$_SERVER['HTTP_HOST']}/photo/".$image->gridimage_id);

		$linkTag = "<a href=\"http://{$_SERVER['HTTP_HOST']}/photo/".$image->gridimage_id."\">";
		$details = $image->getThumbnail(120,120,2);

		$thumb = $details['server'].$details['url']; 
		$thumbTag = $details['html'];

		$description = $linkTag.$thumbTag."</a><br/>".GeographLinks(htmlnumericentities($image->comment))." (".$linkTag."view full size</a>)"."<br/><br/> &copy; Copyright <a title=\"view user profile\" href=\"http://".$_SERVER['HTTP_HOST'].$image->profile_link."\">".$image->realname."</a> and licensed for reuse under this <a rel=\"license\" href=\"http://creativecommons.org/licenses/by-sa/2.0/\">Creative Commons Licence</a><br/><br/>";

		$placemark->setItemCDATA('description',$description);

		//yes that is uppercase S!
		$placemark->setItemCDATA('Snippet',strip_tags($description));

		$placemark->setItem('visibility',1);

		$placemark->useImageAsIcon($thumb);

		if (!empty($image->imagetaken) && strpos($image->imagetaken,'-00') === FALSE) {
			$placemark->setTimeStamp(str_replace('-00','',$image->imagetaken));
		}

			$different_square_true = (intval($image->nateastings/1000) != intval($image->viewpoint_eastings/1000)
						|| intval($image->natnorthings/1000) != intval($image->viewpoint_northings/1000));

			$show_viewpoint = (intval($image->viewpoint_grlen) > 4) || ($different_square_true && ($image->viewpoint_grlen == '4'));

		if ($image->viewpoint_eastings && $show_viewpoint) {
			list($line['eLat'],$line['eLong']) = $conv->national_to_wgs84($image->viewpoint_eastings,$image->viewpoint_northings,$image->grid_square->reference_index);

			$point2 = new kmlPoint($line['eLat'],$line['eLong']);

			if ($version42plus) {
				$placemark->addPhotographerPhoto($point2,$image->view_direction,$image->realname,$image->_getFullpath(true,true));
			} else {
				$placemark->addPhotographerPoint($point2,$image->view_direction,$image->realname);
			}
		} elseif (isset($image->view_direction) && strlen($image->view_direction) && $image->view_direction != -1) {
			$placemark->addViewDirection($image->view_direction);
		}

		$kml->outputKML();
	} else {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
	}
	exit;
}

init_session();

$smarty = new GeographPage;

$template='kml.tpl';
$cacheid = '';

	if (isset($_REQUEST['i']) && $i = intval($_REQUEST['i'])) {
		$pg = $_REQUEST['page'];
		if ($pg == '' or $pg < 1) {$pg = 1;}

		if ($i < 1) {
			if ($USER->registered) {
				$data = array();
				$data['user_id'] = $USER->user_id; 
				$data['orderby'] = 'gridimage_id'; 
				$data['reverse_order_ind'] = 1; 
				$sortorders = array('gridimage_id'=>'Date Submitted');

				$data['adminoverride'] = 0; //prevent overriding it
				
				$engine = new SearchEngineBuilder('#'); 
				$i = $engine->buildAdvancedQuery($data,false);
			} else {
				$i = 1522;
			}
		}
		
		$engine = new SearchEngine($i);
		
		if (isset($_REQUEST['submit'])) {
			$simple = $_REQUEST['simple'];
			if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'view') {
				$url = "http://{$_SERVER['HTTP_HOST']}/earth.php?i=$i&simple=$simple";
			} elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'mapsview') {
				$url = "http://{$_SERVER['HTTP_HOST']}/feed/results/$i.nl";
				$_REQUEST['type'] = 'maps';
			} else {
				$url = "http://{$_SERVER['HTTP_HOST']}/feed/results/$i/$pg.kml";
			}
			if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'static') {
				header("Status:302 Found");
				header("Location:$url");
				$url = str_replace('&','&amp;',$url);
				print "<a href=\"$url\">Open KML</a>";
				exit;
			} elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'live') {
				header("Status:302 Found");
				$url = "http://maps.live.com/default.aspx?v=2&mapurl=$url"; //no need to urlencode as we using rest style url
				header("Location:$url");
				print "<a href=\"$url\">Open 'Maps Live'</a>";
				exit;
			} elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'maps') {
				header("Status:302 Found");
				$url = "http://maps.google.co.uk/maps?q=$url"; //no need to urlencode as we using rest style url
				header("Location:$url");
				print "<a href=\"$url\">Open Google Maps</a>";
				exit;
			} else {
				customExpiresHeader(3600*24*14,true);
				
				$kml = new kmlFile();
				$kml->filename = "Geograph.kml";

				$NetworkLink = $kml->addChild('NetworkLink');
				$NetworkLink->setItem('name','Geograph NetworkLink');
				$NetworkLink->setItemCDATA('description',"Images<i>{$engine->criteria->searchdesc}</i>");
				$NetworkLink->setItem('open',0);
				$UrlTag = $NetworkLink->useUrl($url);
				$NetworkLink->setItem('visibility',0);

				if ($_REQUEST['type'] == 'time') {
					$UrlTag->setItem('refreshMode','onInterval');
					$UrlTag->setItem('refreshInterval',intval($_REQUEST['refresh']));
				} else {
					$UrlTag->setItem('viewRefreshMode','onStop');
					$UrlTag->setItem('viewRefreshTime',4);
					$UrlTag->setItem('viewFormat','BBOX=[bboxWest],[bboxSouth],[bboxEast],[bboxNorth]&amp;LOOKAT=[lookatLon],[lookatLat],[lookatRange],[lookatTilt],[lookatHeading],[horizFov],[vertFov]');
				}

				$kml->outputKML();
				exit;
			}
		} else {
			$engine->countOnly = true;
			$smarty->assign('querytime', $engine->Execute($pg)); 
			
			$smarty->assign('i', $i);
			$smarty->assign('currentPage', $pg);
			$smarty->assign_by_ref('engine', $engine);
		
		}
		
	} else {
		$is = array(1522=>'Recent Submissions',
			46131 => 'Selection of Photos across the British Isles',
			-1 => 'Your Pictures',
			25680 => 'one random image from each myriad, in Great Britain',
			25681 => 'one random image from each myriad, in Ireland',
			25677 => 'one random image from every user',
			25678 => 'one random image from each category',
			46002 => 'Random Images',
			44622 => 'Moderated in the last 24 Hours',
		);
		$smarty->assign_by_ref('is', $is);
		$smarty->assign('currentPage', 1);
		
		$db = GeographDatabaseConnection(true);
		$updatetime = $db->CacheGetOne(86400,"select avg(unix_timestamp(ts))-stddev(unix_timestamp(ts)) from kmlcache where rendered = 1");
		
		$smarty->assign('superlayer_updated', strftime("%A, %d %b at %H:%M",intval($updatetime)));
		$smarty->assign('coverage_updated', strftime("%A, %d %b at %H:%M",@filemtime("kml/hectads-points.kmz")));
		
	}
		

$smarty->assign('adv', $_GET['adv']);


$smarty->display($template, $cacheid);

	
?>
