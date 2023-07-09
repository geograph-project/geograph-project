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

// customExpiresHeader(3600,false,true);

	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(false);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


        print "<h2>Typical City Images</h2>";

	$geofilter = " AND tags NOT like '%type:Close Look%'
                AND tags NOT like '%type:Extra%'
                AND tags NOT like '%type:Inside%'";
	//AND moderation_status = 'geograph' OR  tags like '%type:Cross Grid%'
		//want cross grid, as they may be close geos, just lost on technicality
		//but excluse explicit, so that exclude inside crossgrid etc

####################################################

	if (!empty($_GET['label']) && !empty($_GET['mis'])) {
		$imagelist=new ImageList;

		$thumbw=213; $thumbh=160;

		print "<p>AI model <b>failed</b> to predict ".htmlentities($_GET['label'])." (so in theory look very similar to other cities!) - the city did choose instead is shown in tooltip</p>";

		$imagelist->cols = preg_replace('/(\w+)/','gi.$1',$imagelist->cols);
		$sql = "SELECT {$imagelist->cols} ,l.label as grid_reference
		FROM gridimage_search gi
		INNER JOIN gridimage_label_single l USING (gridimage_id)
		INNER JOIN gridimage_label_training t USING (gridimage_id)
		WHERE t.label = ".$db->Quote($_GET['label'])." AND model = 'city'
		AND folder = 'geograph_visiondata015'
		and t.label != l.label
		$geofilter
		ORDER BY l.score DESC LIMIT 24";

		$imagelist->_getImagesBySql($sql);
		$imagelist->outputThumbs($thumbw,$thumbh);

####################################################

	//the join with gridimage_label_training means we only show images where the AI model predicted correctly!

	} elseif (!empty($_GET['label'])) {
		$imagelist=new ImageList;

		$thumbw=213; $thumbh=160;

		print "<p>AI model predicted ".htmlentities($_GET['label'])." (Correctly!)</p>";

		$imagelist->cols = preg_replace('/(\w+)/','gi.$1',$imagelist->cols);
		$sql = "SELECT {$imagelist->cols}
		FROM gridimage_search gi
		INNER JOIN gridimage_label_single l USING (gridimage_id)
		INNER JOIN gridimage_label_training USING (gridimage_id,label)
		WHERE label = ".$db->Quote($_GET['label'])." AND model = 'city'
		AND folder = 'geograph_visiondata015'
		$geofilter
		ORDER BY l.score DESC LIMIT 24";

		$imagelist->_getImagesBySql($sql);
		$imagelist->outputThumbs($thumbw,$thumbh);

####################################################

	} else {
                $thumbh = 120;
                $thumbw = 120;


		$data = $db->getAll("
			select gridimage_id,label,count(*) from gridimage_label_single l inner join gridimage_label_training using (gridimage_id,label)
				inner join gridimage_search using (gridimage_id)
			where model = 'city' and l.score > 0.8  and folder = 'geograph_visiondata015'
			$geofilter
			group by label
		");

		print "<p style=max-width:700px>These are images the AI correctly predicted to be that City, so in theory <i>are the most quintessential images for that city</i>.";
		print " Note this AI model is just a bit of fun, results may not be totally accurate.";
		print " In particular, is biased by the training sample data, eg if training data happened to include a lot of Interiors, or images of a particular Event, then most similar images will get well matched.";


		print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
		print "<tr><th>Example<th>Predicted<th>Count";
		foreach ($data as $row) {
			print "<tr>";
			print "<td>";
			if (!empty($row['label'])) { //with rollup, so empty labels are total rows!
				$image = new GridImage($row['gridimage_id'], true); //should be moderateod!
                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
                                print ' href="/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';
			}

			print "<td>".htmlentities($row['label'])."</td>";

			print "<td align=right>x {$row['count(*)']}</td>";

			//if (!empty($row['label']) && $row['count(*)'] > 1) {
				$url = "?label=".urlencode($row['label']);
				print "<td><a href=$url><b>More</b></a>";
			//}

				$url .= "&mis=1";
				print "<td><a href=$url>Mismatchs</a>";
                }
		print "</table>";
	}




	$smarty->display('_std_end.tpl');

