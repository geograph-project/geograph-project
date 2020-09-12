<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
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
$template = 'photoset_view.tpl';

$db = GeographDatabaseConnection(true);

######################################

if (!empty($_GET['id'])) {
	$cacheid = "set:".intval($_GET['id']);

	$set = $db->getRow("SELECT * FROM photoset WHERE photoset_id = ".intval($_GET['id'])." AND enabled=1");

	if (!empty($set))
		$ids = $db->getCol("SELECT gridimage_id FROM gridimage_photoset WHERE photoset_id = ".intval($_GET['id'])." ORDER BY sort_order"); //and sort by imagetakne?

######################################

} elseif (!empty($_GET['label'])) {
	$cacheid = md5($_GET['label']).filemtime(__FILE__);

	$set = $db->getRow("SELECT * FROM curated_headword WHERE label = ".$db->Quote($_GET['label']));

	if (empty($set))
		$set = $db->getRow("SELECT * FROM curated_label WHERE label = ".$db->Quote($_GET['label']));

	$limit = 50;

	if (!empty($set)) {
		$sql = "select gridimage_id,wgs84_lat,wgs84_long,sequence from curated1 inner join gridimage_search gi using (gridimage_id) where label = ".$db->Quote($_GET['label'])." and active = 1";

		$count = empty($_GET['count'])?$limit:intval($_GET['count']);

		if (!empty($_GET['loc'])) {
			require "geograph/location-decode.inc.php";

			if (!empty($lat)) {
				$distance = "pow(wgs84_lat - {$lat},2)+pow(wgs84_long - {$lng},2)"; //dont need to sqrt it, only ordering

				$sql = str_replace(' from ', ", $distance as distance from ",$sql);
				$sql = "($sql order by distance asc limit $count) order by distance div 100000, sequence";

				$cacheid .= sprintf('%.5f:%.5f',$lat,$lng);
				$smarty->assign('loc',$_GET['loc']);
			} else {
				$sql .= " order by sequence"; //no limit - deliberately!
			}

		} elseif (!empty($_GET['focus'])) {
			$cacheid .= "|".intval($_GET['focus'])."-".$count;
			$row = $db->getRow("SELECT wgs84_lat,wgs84_long FROM gridimage_search WHERE gridimage_id = ".intval($_GET['focus']));

			$distance = "pow(wgs84_lat - {$row['wgs84_lat']},2)+pow(wgs84_long - {$row['wgs84_long']},2)"; //dont need to sqrt it, only ordering

			$sql = "($sql order by $distance asc limit $count) order by sequence";

			$smarty->assign('loc',sprintf('%.5f:%.5f',$row['wgs84_lat'],$row['wgs84_long']));

		} else {
			$sql .= " order by sequence"; //no limit - deliberately!
		}

		$clusters = array();
		$count = 0; //we can count directly as we are fetching all!
		$rows = $db->getAssoc($sql);

                foreach ($rows as $gridimage_id => $row) {
			if ($count < $limit) {
				//first are always included :)
				$clusters[$gridimage_id] = 1;
			} else {
				//find the nearest existing images!
				$distance = $id = null;
				foreach ($clusters as $id2 => $dummy) {
					$calc = pow($row['wgs84_lat']-$rows[$id2]['wgs84_lat'],2) + pow($row['wgs84_long']-$rows[$id2]['wgs84_long'],2); //dont need to sqrt it, only ordering
					if (empty($distance) || $calc < $distance) {
						$distance = $calc;
						$id = $id2;
					}
				}
				if ($id) { //shouldnt ever be missed!
					$clusters[$id]++;
				}
			}
			$count++;
		}

		$ids = array_keys($clusters);
		if ($count > $limit) {
			$smarty->assign("imagecount", $count);
		}

		$set['label'] =  to_title_case($set['label']);
		$smarty->assign("label", $_GET['label']);

	} elseif (!empty($set)) {
                $ids = $db->getCol("select gridimage_id from curated1 inner join gridimage_search gi using (gridimage_id) where label = ".$db->Quote($_GET['label'])." and active = 1 limit $limit"); //todo, add a sort order!
		$set['label'] =  to_title_case($set['label']);

		if (count($ids) == $limit) {
                	$count = $db->getOne("select count(*) from curated1 inner join gridimage_search gi using (gridimage_id) where label = ".$db->Quote($_GET['label'])." and active = 1");
			$smarty->assign("imagecount", $count);
		}
		$smarty->assign("label", $_GET['label']);
	}
	$smarty->assign("limit", $limit);
}

######################################

if (!empty($ids))
	pageMustBeHTTPS();

if (!empty($ids) && !$smarty->is_cached($template, $cacheid)) {

	$smarty->assign("page_title", $set['label']. " (set of ".count($ids)." images)");
	$smarty->assign("title", $set['label']);
	if (!empty($set['description']) && strpos($set['description'],'welcome to select images') === FALSE)
		$smarty->assign("description", $set['description']);

	$images=new ImageList();
	$images->_setDB($db);
	$images->getImagesByIdList($ids,'*',true);

	if (!empty($images->images)) {

######################################

		$s = array('grid_reference'=>array(),'imagetaken'=>array(),'realname'=>array(),'title'=>array());
		$years = array();
                foreach ($images->images as $image) {
                        foreach ($s as $key => $dummy)
                                @$s[$key][$image->{$key}]++;
			@$years[substr($image->imagetaken,0,4)]++;
		}
                $v = array();
                if (count($s['grid_reference']) == 1 && reset($s['grid_reference']) && ($value = key($s['grid_reference'])) )
                        $v[] = "in <a href=\"/gridref/$value\">$value</a>";
                if (count($s['imagetaken']) == 1 && reset($s['imagetaken']) && ($value = key($s['imagetaken'])) )
                        $v[] = "taken <b>".getFormattedDate($value)."</b>";
                if (count($s['realname']) == 1 && reset($s['realname']) && ($value = key($s['realname'])) )
                        $v[] = "by <a href=\"/profile/{$imagelist->images[0]->user_id}\">".htmlentities2($value)."</a>";
                if (!empty($v))
                        $smarty->assign("headlinks_html", implode(', ',$v));

                $l = array('grid_reference'=>null,'imagetaken'=>null,'realname'=>null);
                        function ooo($image,$attribute,$value) {
                                global $l;
                                if ($l[$attribute] == $image->{$attribute})
                                        return $value;
                                $l[$attribute] = $image->{$attribute};
                                return "<b>$value</b>";
                        }

		if (isset($years['0000']))
			unset($years['0000']);

		if (count($years) == 1) {
			reset($years);
			$smarty->assign('year',key($years));
		} elseif (count($years) >= 2) {
			$keys = array_keys($years);
			sort($keys);
			$smarty->assign('year',array_shift($keys)."-".array_pop($keys));
		}

######################################

                $conv = new Conversions;

		foreach ($images->images as $i => $image) {

	                //get the grid references
        	        $image->getSubjectGridref();
	                $image->getPhotographerGridref();

######################################

			//this by 'magic' uses the exact eastings/northings, if it can!
        	        list($lat1,$long1) = $conv->gridsquare_to_wgs84($image->grid_square);
			$image->lat1 = $lat1;
			$image->long1 = $long1;

######################################

	                if (!empty($image->viewpoint_northings)) {
        	              //  $rastermap->addViewpoint($image->viewpoint_eastings,$image->viewpoint_northings,$image->viewpoint_grlen,$image->view_direction);


				  $ve = $image->viewpoint_eastings;        $vn = $image->viewpoint_northings;
                                        if (false) { //this isn't done by gridsquare_to_wgs84 - so doesnt make sence to do it here...
                                                if ($image->viewpoint_grlen == '4') {
                                                        $ve +=500; $vn += 500;
                                                }
                                                if ($image->viewpoint_grlen == '6') {
                                                        $ve +=50; $vn += 50;
                                                }
                                        }
                                        list($lat2,$long2) = $conv->national_to_wgs84($ve,$vn,$image->grid_square->reference_index,true,true);


	                } else {
				//TODO, use view_direction to 'project' a point (but will still be at arbitary distance!)

				$lat2 = $lat1+0.001;
				$long2 = $long1+0.001;
			}

			$image->lat2 = $lat2;
			$image->long2 = $long2;

######################################

			$links = $v = array();
                        if (count($s['title']) > 1)
                                $v[] = '<span class=title>'.highlight_changes(htmlentities2($image->title)).'</span>';
                        if (count($s['grid_reference']) > 1)
                                $v[] = '<span style="color:gray">In:</span> '.ooo($image,'grid_reference',"<a href=\"/gridref/{$image->grid_reference}\">{$image->grid_reference}</a>");
                        if (count($s['imagetaken']) > 1)
                                $v[] = '<span style="color:gray">When:</span> '.ooo($image,'imagetaken',getFormattedDate2($image->imagetaken));
                        if (count($s['realname']) > 1)
                                $v[] = '<span style="color:gray">By:</span> '.ooo($image,'realname',"<a href=\"/profile/{$image->user_id}\">".htmlentities2($image->realname)."</a>");

			if (!empty($_GET['label'])) {
				$hash = $image->_getAntiLeechHash();
				$download = "https://t0.geograph.org.uk/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash=$hash&download=1";
				if (empty($image->cached_size))
					$image->_getFullSize();
				$largest = max($image->original_width,$image->original_height);
	                        if ($largest > 1024)
	                                $download .= "&large=1024";
	                        elseif ($largest > 640)
	                                $download .= "&large=full";
				$size = $more = '';
				if ($largest > 640) {
					$size = "<small style=font-family:verdana>{$image->original_width}<span style=color:gray>x</span>{$image->original_height}</small> ";
					$links[] = "$size <a href=\"/more.php?id={$image->gridimage_id}\" class=nowrap>Size Options...</a>";
				}
				$links[] = "<a href=\"$download\" class=\"download nowrap\">Download Stamped Image</a>";
                        }

			if (!empty($clusters) && !empty($clusters[$image->gridimage_id]) && $clusters[$image->gridimage_id] > 1) {
				$image->more = $clusters[$image->gridimage_id];
				$link = "?label=".urlencode($set['label'])."&amp;focus={$image->gridimage_id}&count={$image->more}";
				$links[] = "<a href=\"$link\">+ ".($image->more-1)." more</a> like this";
			}

			if (!empty($links))
				$image->links = implode("<br>",$links);
			if (!empty($v))
				$image->htmltext = implode("<br>",$v);

######################################

		}
		$smarty->assign_by_ref('first',$images->images[0]);

		if (count($s['grid_reference']) == 1)
			$smarty->assign('place', $place = $images->images[0]->grid_square->findNearestPlace(75000));

		if (count($s['realname']) == 1 && reset($s['realname']) && ($value = key($s['realname'])) )
			$smarty->assign('singlename', $value);
	}

	$smarty->assign('images',$images->images);

######################################

} elseif (empty($ids)) {
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        $template = "static_404.tpl";
}


$smarty->display($template,$cacheid);

function highlight_changes($str) {
        static $prev = '';
        $c = common_prefix($str,$prev);
        $prev = $str;
        if ($c > 0)
                return substr($str,0,$c)."<b>".substr($str,$c)."</b>";
        else
                return "<b>$str</b>";
}

function common_prefix($one,$two) {
        $limit = min(strlen($one),strlen($two));
        $i=1;
        while(substr($one,0,$i)==substr($two,0,$i) && $i <= $limit) //case sensitive!
                $i++;
        return $i-1;
}

//basic wrapper, to remove the day of the week. Too much detail
function getFormattedDate2($in) {
        return preg_replace('/^[A-Z]\w+, *(\d+ \w+,)/','$1',getFormattedDate($in));
}

