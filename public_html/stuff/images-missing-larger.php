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

$USER->mustHavePerm("basic");

	$smarty->display('_std_begin.tpl');

?>
<h2>Images in need of reupload</h2>

<p>These are your images idenfified as the larger image been lost. Alas while the 640px version was saved succesfully the larger version was lost.</p>

<p>You can use the 'Upload Larger' to reattach a larger version. The size that selected during the original upload is shown, can select the same or different during the reupload.</p>

<p> Can also <a href="javascript:history.go(0);">Reload this page</a> and it will update to show already uploaded. Note that the new larger upload will still be subject to moderation, so will be some delay before it actully available publically - the current status will be shown in last column.

<hr>
<style>
p {
	max-width:900px;
}
</style>
<?

	$db = GeographDatabaseConnection(false);

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



	$sql = "select gridimage_id,preview_key,t.user_id,modified,folder,method,timetaken,largestsize,width,original_width,original_height,gridimage_pending.status 
, title, realname, 'unknown' as grid_reference
	from tmp_zero_file t 
	inner join tmp_submission_method using (preview_key) 
		inner join gridimage using (gridimage_id)
	left join gridimage_size using (gridimage_id) 
	left join gridimage_pending using (gridimage_id)
	where t.user_id = {$USER->user_id}
	";


$thumbw = $thumbh = 120;


	$images = $db->GetAll($sql);

	if (!empty($images)) {
			print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
			print "<tr>";
				print "<th>image";
				print "<th>time";
				print "<th>selected";
				print "<th>current size";
				print "<th>status";
			foreach ($images as $row) {
				print "<tr>";
				print "<td>";
					$image = new GridImage();
					$image->fastInit($row);
	                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
	                                print ' href="/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';

				print "<td>".htmlentities($row['modified']);
				print "<td>".htmlentities($row['largestsize']);
				if ($row['original_width'] && $row['original_height']) {
					print "<td>".htmlentities($row['original_width'])." x ".htmlentities($row['original_height']);
				} else {
					print "<td>missing";
					if (empty($row['status'])) //somethign already uploaded!
						print "<br><a href=\"/resubmit.php?id={$row['gridimage_id']}\">Upload Larger</a>";
				}
				print "<td>".htmlentities($row['status']);
	                }
			print "</table>";
		print count($images)." images";
	} else {
		print "None Found!";
	}





	$smarty->display('_std_end.tpl');

