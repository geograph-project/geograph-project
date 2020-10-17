<?php
/**
 * $Project: GeoGraph $
 * $Id: index.php 7147 2011-03-10 14:18:54Z geograph $
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
        require_once('geograph/gridimage.class.php');
        require_once('geograph/imagelist.class.php');


$smarty = new GeographPage;

$data = array();

/////////////////////////////
// photo of the day
	require_once('geograph/pictureoftheday.class.php');
	$potd=new PictureOfTheDay;
	$potd->initToday();

	$data['potd'] = array();
	$data['potd']['gridimage_id']=$potd->gridimage_id;
        $data['potd']['image']=new GridImage($potd->gridimage_id);
	$data['potd']['image']->title = latin1_to_utf8($data['potd']['image']->title);
	$data['potd']['thumbnail'] = $data['potd']['image']->getFixedThumbnail(360,263);
        $data['potd']['image']->compact();

/////////////////////////////
// lets find some recent photos
	if ($CONF['template']=='ireland') {
		$recent = new RecentImageList($smarty,2);
	} else {
		$recent = new RecentImageList($smarty);
	}

	foreach ($recent->images as $idx => $image) {
		$recent->images[$idx]->title = latin1_to_utf8($image->title);
		$recent->images[$idx]->thumbnail = $image->getThumbnail(120,120);
		if (isset($recent->images[$idx]->db))
			unset($recent->images[$idx]->db);
	}
	$data['recent'] = $recent->images;

/////////////////////////////
// statistics
	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	if ($CONF['template']=='ireland') {
		$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares AND reference_index=2 ORDER BY last_submitted DESC LIMIT 5");
	} else {
		$hectads= $db->getAll("SELECT * FROM hectad_stat WHERE geosquares >= landsquares ORDER BY last_submitted DESC LIMIT 5");
	}
	$data['hectads'] = $hectads;

	if ($CONF['template']=='ireland') {
		$stats= $db->GetRow("SELECT SUM(imagecount) AS images, SUM(has_geographs>0) AS squares,
				COUNT(*) AS total,SUM(imagecount in (1,2,3)) AS fewphotos FROM gridsquare WHERE reference_index = 2 AND percent_land > 0");
		$stats += $db->cacheGetRow(3600*24, "SELECT COUNT(distinct user_id) as users from gridimage_search where x<410 and y<648 and reference_index = 2");

	} else {
		$stats= $db->GetRow("select * from user_stat where user_id = 0");
		$stats += $db->GetRow("select count(*)-1 as users from user_stat");
		$stats += $db->cacheGetRow(3600,"select SUM(imagecount) AS images, count(*) as total,sum(imagecount in (1,2,3)) as fewphotos from gridsquare where percent_land > 0");
	}

	$stats['nophotos'] = $stats['total'] - $stats['squares'];
	$stats['percentage'] = sprintf("%.1f",$stats['squares']/$stats['total']*100);

	$data['stats'] = $stats;



outputJSON($data);
