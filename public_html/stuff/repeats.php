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

 customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(true);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


	?>
	<h2>RecreatoGraphs</h2>
	<p>This page is looking at recent submissions, that seem to be taken in (almost) the same location as older images. It of course relies on how accurately the photos have been located.
	<hr>
	<?

$mkey = md5(json_encode($_GET));

$str = $memcache->name_get('repeats',$mkey);

if (!empty($str) && empty($_GET['refresh'])) {
	print $str;
} else {
	ob_start();


                        $thumbw = 213;
                        $thumbh = 160;

	$where = array();
	$dist = 20;

	if (!empty($_GET['d']))
		$dist = intval($_GET['d']);

//	$where[] = "geo1 < $dist"; //subject
	$where[] = "geo2 < $dist"; //viewpoint
	$where[] = "diff = 0";
	$where[] = "days > 350";

	if (!empty($_GET['m']))
		$where[] = "gridimage_id <= ".intval($_GET['m']);

	$where = implode(" AND ",$where);

	$limit = 25;
	if (!empty($_GET['l']))
		$limit = min(100,intval($_GET['l']));


	$clusters = $db->getAll("select gridimage_id,group_concat(id order by geo1) as ids from cluster3
			 where $where group by gridimage_id desc
			 limit $limit");



	if (count($clusters)) {
		$done = array();
		foreach ($clusters as $idx => $cluster) {
			if (isset($done[$cluster['gridimage_id']]))
				continue; //if the main image features in a previous cluster, skip

			if (!$idx)
				print "<div style=float:right><a href=\"?m={$cluster['gridimage_id']}\">Permalink</a></div>";

			$ids = array_merge(array($cluster['gridimage_id']), explode(',',$cluster['ids']));

			$imagelist=new ImageList;
			$imagelist->_setDB($db);//to reuse the same connection
			$imagelist->getImagesByIdList($ids);

			if (!empty($imagelist->images) && count($imagelist->images) > 1) {
				$image =& $imagelist->images[0];
				?>
				<h3><? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?></h3>
				<?
		        	foreach ($imagelist->images as $idx => $image) {

?>
	  <div style="float:left;position:relative; width:<? echo $thumbw+10; ?>px; height:<? echo $thumbh+50; ?>px">
		  <div align="center">
		  <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a><br>
		  <? echo date('M, Y',strtotime(str_replace('-00','01',$image->imagetaken))).' : <i>'.$image->getTakenAgo().'</i>'; ?></div>
	  </div>
<?
					$done[$image->gridimage_id] = 1;
				}
				print "<hr style=\"clear:both\"/>";
			}
                }
	} else {
		print "nothing to display";
	}

	$str = ob_get_flush();

	$memcache->name_set('repeats',$mkey,$str,$memcache->compress,$memcache->period_long);
}


	$smarty->display('_std_end.tpl');

