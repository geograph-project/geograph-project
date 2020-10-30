<?php

/**
 * $Project: GeoGraph $
 * $Id: syndicator.php 7627 2012-06-26 17:49:40Z geograph $
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

$_SERVER['HTTPS'] = 'on'; //cheeky, but forces generation of https:// urls :)

require_once('geograph/global.inc.php');
require_once('geograph/feedcreator.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');


$valid_formats=array('RSS0.91','RSS1.0','RSS2.0','MBOX','OPML','ATOM','ATOM0.3','HTML','JS','PHP','KML','BASE','GeoRSS','GeoPhotoRSS','GPX','TOOLBAR','MEDIA','JSON');

$format="ATOM";
if (isset($_GET['format']) && in_array($_GET['format'], $valid_formats)) {
	$format=$_GET['format'];
}

$rss = new UniversalFeedCreator();

if ($CONF['template'] == 'ireland') {
	$rss->title = 'Geograph Ireland';
	$ri = 2;
} else {
	$rss->title = 'Geograph Britain and Ireland';
	$ri = 1; //this is delibeate, sarch engines etc, index only GB images on .org.uk, uses .ie for all ireland photos. (.org.uk will redirect!)
}
if ($CONF['template'] == 'api') {
        $rss->link = "{$CONF['CONTENT_HOST']}/";
} else {
        $rss->link = "{$CONF['SELF_HOST']}/";
}
$baselink = $rss->link;

	//lets find some photos
	$images=new ImageList();
	$cols = "gi.gridimage_id,title,comment,gi.grid_reference,imagetaken,submitted,upd_timestamp,gi.user_id,credit_realname,tags,gi.wgs84_lat,gi.wgs84_long";

	$db = $images->_getDB(true);

	$db->Execute("CREATE TABLE IF NOT EXISTS geograph_tmp.newimages (gridimage_id INT UNSIGNED NOT NULL, lookup VARCHAR(255) NOT NULL, created timestamp not null default current_timestamp, unique(`gridimage_id`,`lookup`) )");

	$lookup = $db->Quote($_SERVER['HTTP_USER_AGENT']); //cant use IP as Goolgbot has many

	$filter = '';
	if (!empty($_GET['f'])) {
		if ($_GET['f'] == 1) {
			$filter .= ' AND ftf>0';
		} elseif ($_GET['f'] == 2) {
                        $filter .= ' AND length(comment) > 100';
                }
	}

        if (!empty($_GET['v'])) {
                if ($_GET['v'] == 1) {
                        //most recent sent!
                        $sql = "SELECT $cols FROM gridimage_search gi
                                INNER JOIN geograph_tmp.newimages ni ON (gi.gridimage_id = ni.gridimage_id AND lookup = $lookup)
                        WHERE reference_index = $ri $filter
                        ORDER BY gi.gridimage_id DESC LIMIT 100";
                } elseif ($_GET['v'] == 4) {
                        //mid level sent!
                        $sql = "SELECT $cols FROM gridimage_search gi
                                INNER JOIN geograph_tmp.newimages ni ON (gi.gridimage_id = ni.gridimage_id AND lookup = $lookup)
                        WHERE reference_index = $ri $filter
                        ORDER BY gi.gridimage_id DESC LIMIT 500,100";
                } elseif ($_GET['v'] == 2) {
                        //oldest sent!
                        $sql = "SELECT $cols FROM gridimage_search gi
                                INNER JOIN geograph_tmp.newimages ni ON (gi.gridimage_id = ni.gridimage_id AND lookup = $lookup)
                        WHERE reference_index = $ri $filter
                        ORDER BY gi.gridimage_id ASC LIMIT 100";
                } elseif ($_GET['v'] == 3) {
                        //newest before setup!
                        $id = $db->getOne("SELECT MIN(gridimage_id) FROM geograph_tmp.newimages");
                        $sql = "SELECT $cols FROM gridimage_search gi
                        WHERE reference_index = $ri $filter AND gi.gridimage_id < $id
                        ORDER BY gi.gridimage_id DESC LIMIT 100";
                } elseif ($_GET['v'] == 5) {
                        //just list updated (but excluding recent submissions)
                        $sql = "SELECT $cols FROM gridimage_search gi
                        WHERE reference_index = $ri $filter and submitted < date_sub(now(),interval 2 day)
                        ORDER BY upd_timestamp DESC LIMIT 100";
		}
        } elseif ($_GET['g'] == 1) {
		//show images from newly created clusters (in theory more unique images!)
		$sql = "SELECT $cols FROM gridimage_search gi
			INNER JOIN gridimage_group_stat gs ON (gi.gridimage_id = gs.gridimage_id)
			LEFT JOIN geograph_tmp.newimages ni ON (gi.gridimage_id = ni.gridimage_id AND lookup = $lookup)
		WHERE reference_index = $ri $filter AND ni.gridimage_id IS NULL
		ORDER BY gs.created DESC LIMIT 100";
        } else {
                //constantly refreshing list!
                $sql = "SELECT $cols FROM gridimage_search gi
                        LEFT JOIN geograph_tmp.newimages ni ON (gi.gridimage_id = ni.gridimage_id AND lookup = $lookup)
			LEFT JOIN vote_log ON (id = gi.gridimage_id and vote < 3)
                WHERE reference_index = $ri $filter AND ni.gridimage_id IS NULL AND vote is NULL
                ORDER BY gi.gridimage_id DESC LIMIT 100";
        }

if (!empty($_GET['dd']))
	die($sql);


	$images->_getImagesBySql($sql);


$cnt=count($images->images);
$rows = array();

for ($i=0; $i<$cnt; $i++) {
	$item = new FeedItem();
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title;
	$item->guid = $item->link = $baselink."photo/{$images->images[$i]->gridimage_id}";
	$item->description = $images->images[$i]->comment;

	if (!empty($images->images[$i]->imagetaken) && strpos($images->images[$i]->imagetaken,'-00') === FALSE) {
		$item->imageTaken = $images->images[$i]->imagetaken;
	}

	$item->date = strtotime($images->images[$i]->submitted);
	$item->dateUpdated = strtotime($images->images[$i]->upd_timestamp);
	$item->source = $baselink.preg_replace('/^\//','',$images->images[$i]->profile_link);
	$item->author = $images->images[$i]->realname;
	if (!empty($images->images[$i]->tags))
		$item->tags = $images->images[$i]->tags;

        $item->lat = $images->images[$i]->wgs84_lat;
        $item->long = $images->images[$i]->wgs84_long;

	$item->licence = "http://creativecommons.org/licenses/by-sa/2.0/";

	$rss->addItem($item);
	$rows[] = "{$images->images[$i]->gridimage_id},$lookup,NOW()";
}

//we dont use the inbuilt functions for outputting, becasuse we never want it cached :)
$rss->_setFormat($format);
header("Content-Type: ".$rss->_feed->contentType."; charset=".$rss->_feed->encoding);
print $rss->_feed->createFeed();


if (empty($_GET['v']))
	$db->Execute($sql = "INSERT INTO geograph_tmp.newimages VALUES (".implode("),(",$rows).")");

/*
if (rand(1,10)==7) {
	$db->Execute("CREATE TEMPORARY TABLE geograph_tmp.recentimages (UNIQUE(gridimage_id))
			SELECT gridimage_id FROM gridimage_search WHERE reference_index = $ri ORDER BY gridimage_id DESC LIMIT 1000");

	$db->Execute("DELETE newimages.* FROM geograph_tmp.newimages LEFT JOIN geograph_tmp.recentimages USING(gridimage_id)
			WHERE recentimages.gridimage_id IS NULL");
}
*/
