<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

########################################################

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$template = "curated_selector.tpl";
$cacheid = md5($_SERVER['PHP_SELF']).filemtime(__FILE__);

$smarty->assign('regions', json_encode($db->getCol("select region from curated1 where region != '' group by region")));

$smarty->assign('title', "Geography Related");
$smarty->assign('page_title', "Geography Related Curated Images");

########################################################

if (!empty($_GET['loc'])) {

	require "geograph/location-decode.inc.php";

	if (!empty($lat) && isset($lng)) {

		//need to use sphinx for this, to get WITHIN GROUP ORDER BY!
		$sph = GeographSphinxConnection('sphinxql',true);
	        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$where = array();
                if (!empty($sphinxq))
                        $where[] = "match(".$sph->Quote($sphinxq).")";
                $lat = deg2rad($lat);
                $lng = deg2rad($lng);
                $columns = ", GEODIST($lat, $lng, wgs84_lat, wgs84_long) as distance";
                //$where[] = "distance < $distance";
		//todo, BBOX??? or at least use the TILES function from near.php (but will need fields adding to index!)

		$where[] = "stack != ''";
		$where = implode(' and ',$where);

	        $data = $sph->getAll($sql = "
                                select stack,name,label,count(*) as images, gridimage_id $columns
                                from curated
                                where $where
				group by label
				within group order by distance asc, score desc, sequence asc
                                order by stack asc, label asc
                                limit 50
                                option ranker=none, max_query_time=1200");

		//header("X-DEBUG: ".preg_replace('/\s+/',' ',$sql));

		if (!empty($data))
			$cacheid .= sprintf('%.5f:%.5f',$lat,$lng);
		$smarty->assign('loc',$_GET['loc']);
	}
}

########################################################

if (!$smarty->is_cached($template, $cacheid)) {

	if (empty($data)) {

		$data = $db->getAll("
		select stack,name,label,count(gridimage_id) as images, gridimage_id
		from curated_label l
		 inner join curated1 c using (label)
		 inner join curated_headword h using (label)
		where label is not null and stack != '' and gridimage_id > 0 and c.active=1 and c.score >= 10
		group by name
		order by stack,name
		");
	}

	$last = array();
	$images = array();

	foreach ($data as $idx => $row) {
		$bits = explode(' > ',$row['stack']);
		$head = array();
		foreach($bits as $idx => $bit) {
			if ($bit != @$last[$idx])
				$head[] = str_repeat('&nbsp;&nbsp;&nbsp;',$idx)."<b>".htmlentities($bit)."</b>";
		}
		$last = $bits;

	        $image = new GridImage($row['gridimage_id'],true);
		if ($head) {
			$image->head = implode("<br>",$head);
		}
		$image->images = $row['images'];

		$hash = $image->_getAntiLeechHash();
		$image->download = "{$CONF['TILE_HOST']}/stamp.php?id={$image->gridimage_id}&title=on&gravity=SouthEast&hash=$hash&download=1&large=1024";

	        //$link1 = "sample.php?label=".urlencode($row['label']);
        	$image->link = "/photoset/view.php?label=".urlencode($row['label']);
		if (!empty($_GET['loc']))
			$image->link .= "&loc=".urlencode($_GET['loc']);

	        if (!empty($row['name']))
        	        $image->label = $row['name']; //in this case (from curate_label), use name if possible for display purpsoes
		else
			$image->label = $row['label'];

		$images[] = $image;
	}
	$smarty->assign('images', $images);
}

########################################################


$smarty->display($template, $cacheid);


