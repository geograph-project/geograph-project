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

require_once('geograph/global.inc.php');
require_once('geograph/feedcreator.class.php');
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');



if (!empty($CONF['db_read_connect2'])) {
        if (!empty($DSN_READ))
                $DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);
        if (!empty($CONF['db_read_connect']))
                $CONF['db_read_connect'] = $CONF['db_read_connect2'];
}

$images=new ImageList();

###########################################

$format="MEDIA";
if (!empty($_GET['format']) && $_GET['format'] == 'JSON') {
        $format = 'JSON';
}

$rss = new UniversalFeedCreator();

if ($CONF['template'] == 'ireland') {
	$rss->title = 'Geograph Ireland';
	$ri = 2;
} else {
	$rss->title = 'Geograph Britain and Ireland';
	$ri = 1;
}
if ($CONF['template'] == 'api') {
        $rss->link = "{$CONF['CONTENT_HOST']}/";
} else {
        $rss->link = "{$CONF['SELF_HOST']}/";
}
$baselink = $rss->link;

###########################################

$large = 1600; $minsize = 1600;
if (isset($_GET['large']) && in_array($_GET['large'],array('',800,1024,1600))) {//can specify an empty string!
	$large = $_GET['large'];
	$minsize = intval($_GET['large']);
}

###########################################

$tables = $where = array();
$cols = ""; //any extra columns needed. required columns from gridimage_search are included anyway
$tables['gallery'] = ' inner join gallery_ids on (id = gridimage_id)'; //this usuall needed for the sorting
$tables['size'] = ' inner join gridimage_size using (gridimage_id)'; // and this needed for size filtering
$where['size'] = "greatest(original_width,original_height) > $minsize";
$where['ratio'] = "original_width/original_height < 50";
$where['users'] = "users > 3";
$order = "gallery_ids.baysian desc";
$limit = 24;

###########################################

if (!empty($_GET['country'])) {
	$db = $images->_getDB(true);
	$tables[] = " INNER JOIN gridsquare gs USING (grid_reference)";
	$tables[] = " INNER JOIN sphinx_placenames p ON (p.placename_id = gs.placename_id)";
	$where[] = "country = ".$db->Quote($_GET['country']);
}

if (!empty($_GET['ratio'])) {
	if (is_numeric($_GET['ratio'])) {
		$min = $_GET['ratio'] - 0.05; //todo, make this dynamic?
		$max = $_GET['ratio'] + 0.05;
		$where['ratio'] = "(original_width/original_height) BETWEEN $min AND $max";
	} elseif ($_GET['ratio'] == 'l')
		$where['ratio'] = "(original_width/original_height) BETWEEN 1.3 and 3";
	elseif ($_GET['ratio'] == 'p')
		$where['ratio'] = "(original_width/original_height) BETWEEN 0.6 and 0.8";
}

if (isset($_GET['geo'])) {
	if ($_GET['geo'] == 2)
		$where[] = "(moderation_status = 'geograph' OR tags LIKE '%type:Cross Grid%')";
	elseif ($_GET['geo'] == 1)
		$where[] = "moderation_status = 'geograph'";
	else
		$where[] = "moderation_status = 'accepted'";
}

if (!empty($_GET['taken'])) {
	if ($_GET['taken'] == 'recent')
        	$where[] = "imagetaken > DATE_SUB(NOW(),interval 5 year)";
	elseif ($_GET['taken'] == 'historic') {
	        $where[] = "imagetaken < DATE_SUB(NOW(),interval 20 year)";
        	$where[] = "imagetaken NOT LIKE '0000-%'";
	} elseif (is_numeric($_GET['taken']))
		$where[] = sprintf("imagetaken LIKE '____-%02d-%%'",$_GET['taken']);
}

if (!empty($_GET['size'])) {
	$where['size'] = "original_width > ".intval($_GET['size']/3); //this limits the aspect ratio, but not normally this extream?
	$where[] = "greatest(original_width,original_height) > ".intval($_GET['size']);
}

###########################################

if (!empty($_GET['tab'])) {
	//these selections typically mess with ordering, so need a minimum filter!
	$minimum = 4.0;
	$minimum -= count($where)*0.1; //the more filters, more permissive be to allow more images!

	$where['baysian'] = "gallery_ids.baysian >= $minimum";

	if ($_GET['tab'] == 'daily') {
		$limit = 5;

		$days = 365;
		$start = "2020-03-01"; //date of selection start, update before days runs out below!
			//todo, could ultimately do something like FROM_DAYS((TO_DAYS(NOW()) DIV $days) * $days)` sort of thing
			//but not sure how to ensure smooth transition when rollover!

		$date = "date(from_unixtime(unix_timestamp('$start') + (crc32(CONCAT(gridimage_id,'$start')) mod $days)*86400))"; //using $start inside crc means the hashing is differnt each cycle

		$tables = implode(" ",$tables);
		$where = implode(" AND ",$where);

		//do this in two steps, because of selecting the id via group_concat (to emulate 'within group order by')
		$lookup = "select SUBSTRING_INDEX(GROUP_CONCAT(gridimage_id ORDER BY $order),',',1) as id, $date as result
		from gridimage_search
                $tables
	        where $where
		group by result desc
		having result <= date(now())
		limit $limit";

		header("X-Query: ".preg_replace('/\s+/',' ',$lookup));

		if (empty($db)) $db = $images->_getDB(true);

		if ($ids = $db->getCol($lookup))
			$where = array("gridimage_id IN (".implode(',',$ids).")");
		else
			$where = array("0");

		$cols .= ", $date as result";
		$tables = array(''); //dont need extra tables, as $where already done!
		$order = "result desc";

	} elseif ($_GET['tab'] == 'fresher') {
		$order = "last_vote desc";

	} elseif ($_GET['tab'] == 'submitted') {
		$count = 1000;
		$count += count($where)*500;

		if (empty($db)) $db = $images->_getDB(true);
		$maxid = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

		$where[] = "gridimage_id > ".($maxid-$count);

		unset($tables['gallery']); unset($where['users']); unset($where['baysian']);
		$order = "score desc"; //this does update with hits, so works okish on recent images (not in gallery)

	} elseif ($_GET['tab'] == 'recent') {
		$order = "imagetaken desc, sequence";

	} elseif ($_GET['tab'] == 'twitter') {
		$tables[] = " inner join twitter using (gridimage_id)";

		$where[] = "gridimage_id > ".($maxid-$count);

		unset($tables['gallery']); unset($where['users']); unset($where['baysian']);
		$order = "score desc"; //this does update with hits, so works okish on recent images (not in gallery)

	} elseif ($_GET['tab'] == 'poty') {

		$where[] = "type = 'I'";
		$where[] = "forum_id in (7,17)";
		$tables[] = " inner join gridimage_post using (gridimage_id)";
		$tables[] = " inner join geobb_topics using (topic_id)";

		unset($tables['gallery']); unset($where['users']); unset($where['baysian']);

                $tables = implode(" ",$tables);
                $where = implode(" AND ",$where);

                //do this in two steps, because need to group by gridimage_id
                $lookup = "select gridimage_id, count(*) as uses
                from gridimage_search
                $tables
                where $where
                group by gridimage_id
		order by uses desc
                limit $limit";

                header("X-Query: ".preg_replace('/\s+/',' ',$lookup));

                if (empty($db)) $db = $images->_getDB(true);

                if ($ids = $db->getCol($lookup))
                        $where = array("gridimage_id IN (".implode(',',$ids).")");
                else
                        $where = array("0");

                $tables = array(''); //dont need extra tables, as $where already done!
                $order = "sequence"; //already selected by 'uses', now just mix it up a bit
	}
}

###########################################

	$tables = implode(" ",$tables);
	$where = implode(" AND ",$where);

	if (strpos($tables,'gridimage_size') !== FALSE) //if have this table, might as well include the dimensions!
		$cols .= " ,original_width,original_height";

	$sql = "select gridimage_id,user_id,realname,credit_realname,title,grid_reference,imagetaken,wgs84_lat,wgs84_long,tags,comment $cols
	from gridimage_search
	$tables
	where $where
	order by $order limit $limit";

	header("X-QueryFull: ".preg_replace('/\s+/',' ',$sql));

	$images->_getImagesBySql($sql);

###########################################


$cnt=count($images->images);

for ($i=0; $i<$cnt; $i++) {
	$item = new FeedItem();
	$item->title = $images->images[$i]->grid_reference." : ".$images->images[$i]->title;
	$item->guid = $item->link = $baselink."photo/{$images->images[$i]->gridimage_id}";
	if (!empty($images->images[$i]->comment))
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

	if (!empty($images->images[$i]->result))
		$item->featuredDay = $images->images[$i]->result;

        $item->lat = $images->images[$i]->wgs84_lat;
        $item->long = $images->images[$i]->wgs84_long;

	$item->licence = "http://creativecommons.org/licenses/by-sa/2.0/";

	$hash = $images->images[$i]->_getAntiLeechHash();
	//$item->content = $images->images[$i]->_getFullpath(true,true);
	$item->enclosure = "{$CONF['TILE_HOST']}/stamp.php?id={$images->images[$i]->gridimage_id}&hash={$hash}&large=$large&title=on&gravity=North&pointsize=20";


	if (!empty($minsize) && !empty($images->images[$i]->original_width)) {
		//include the dimensions of the enclosure
		$srcw = $images->images[$i]->original_width;
		$srch = $images->images[$i]->original_height;
		$maxw = $minsize;

                                                        if ($srcw>$srch)
                                                        {
                                                                //landscape
                                                                $destw=$maxw;
                                                                $desth=round(($destw * $srch)/$srcw);
                                                        }
                                                        else
                                                        {
                                                                //portrait
                                                                $desth=$maxh;
                                                                $destw=round(($desth * $srcw)/$srch);
                                                        }
		$item->dimensions = "{$destw}x{$desth}";
	}

	$rss->addItem($item);
}

//we dont use the inbuilt functions for outputting, becasuse we never want it cached :)
$rss->_setFormat($format);
header("Content-Type: ".$rss->_feed->contentType."; charset=".$rss->_feed->encoding);
header('Access-Control-Allow-Origin: *');

$rss_timeout = 3600*6;
 customExpiresHeader(3600*24,true);

print $rss->_feed->createFeed();

