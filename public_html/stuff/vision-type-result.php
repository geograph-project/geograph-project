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


        print "<h2>Moderation Comparsion</h2>";

if (!empty($_GET['views'])) {
	$db->Execute("drop table if exists gridimage_label_single");
	$db->Execute("create table gridimage_label_single (primary key(gridimage_id,model),key(model))
	select gridimage_id,model,group_concat(label order by score desc limit 1) as label,max(score) as score from gridimage_label group by gridimage_id, model");
exit;
}


	if (!empty($_GET['label']) || !empty($_GET['type'])) {
		$imagelist=new ImageList;

		$thumbw=213; $thumbh=160;

		print "<p>Moderator selected ".htmlentities($_GET['type']).", but AI model predicted ".htmlentities($_GET['label'])."</p>";

			//STRAIGHT_JOIN is bcause otherwise it will use tag table first (due to prefix filter), but very ineffient that way round!
		$imagelist->cols = preg_replace('/(\w+)/','gi.$1',$imagelist->cols);
		$sql = "SELECT {$imagelist->cols}
		FROM gridimage_search gi
		INNER JOIN gridimage_label_single l USING (gridimage_id)
		STRAIGHT_JOIN  tag_public t on (t.gridimage_id = l.gridimage_id)
		WHERE label = ".$db->Quote($_GET['label'])." AND model = 'type'
		AND prefix = 'type'
		AND tag = ".$db->Quote($_GET['type'])."
		ORDER BY gridimage_id DESC LIMIT 24";

		$imagelist->_getImagesBySql($sql);
		$imagelist->outputThumbs($thumbw,$thumbh);
	} else {
                        $thumbh = 120;
                        $thumbw = 120;


		$data = $db->getAll("
			select l.gridimage_id,count(*),tag as type,label,avg(label = replace(tag,' ',''))*100 as correct from gridimage_label_single l STRAIGHT_JOIN  tag_public t on (t.gridimage_id = l.gridimage_id) where model = 'type' and prefix = 'type' and l.gridimage_id > 7530000 group by tag,label with rollup limit 100
		");

		print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
		print "<tr><th>Example<th>Moderator Selected</th><th>Predicted<th>Count<th>Correct";
		foreach ($data as $row) {
			print "<tr>";
			print "<td>";
			if (!empty($row['label'])) { //with rollup, so empty labels are total rows!
				$image = new GridImage($row['gridimage_id'], true); //should be moderateod!
                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
                                print ' href="/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';
			}

			print "<td>".htmlentities($row['type'])."</td>";
			print "<td>".htmlentities($row['label'])."</td>";

			print "<td align=right>x {$row['count(*)']}</td>";
			if ($row['correct']==0.00)
				print "<td>";
			elseif ($row['correct'] == 100)
				print "<td>Y";
			else
				printf('<td align=right>%.2f%%</td>',$row['correct']);
			if (!empty($row['label']) && $row['count(*)'] > 1) {
				$url = "?label=".urlencode($row['label'])."&type=".urlencode($row['type']);
				print "<td><a href=$url>More</a>";
			}
                }
		print "</table>";
	}




	$smarty->display('_std_end.tpl');

