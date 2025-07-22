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

if (empty($_GET['model'])) {
	$_GET['model'] = 'top';
}

	$smarty->display('_std_begin.tpl');

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


        print "<h2>Verified Context Tags</h2>";

	print "<p style=max-width:900px>This page shows a small sample of images from a dataset we are currating of high qaulity context tags";

$extra = "";

	if (true) {
                        $thumbh = 120;
                        $thumbw = 120;

//SELECT gridimage_id,GROUP_CONCAT(tag separator '; ') AS tags,user_id FROM `curated_tag` GROUP BY gridimage_id,user_id HAVING tags LIKE '%Checked%';


		$sql = array();
		$sql['wheres'] = array();
		$sql['columns'] = explode(',','gridimage_id,gi.user_id,realname,title,grid_reference,reference_index,t.tag');
		$sql['tables'] = array('gridimage_search gi');

			$sql['tables'][] = "inner join curated_tag t using (gridimage_id)";
			$sql['tables'][] = "inner join curated_tag v using (gridimage_id)";
			$sql['wheres'][] = "v.tag = 'Verified'";
			$sql['wheres'][] = "t.tag NOT IN ('Skip','Checked','Verified','Errors','Saved')";
			$sql['wheres'][] = "v.status = 1";
			$sql['wheres'][] = "t.status = 1";

		$sql['limit'] = 99; //so devisible three, nomincal number of models currently!
		if (!empty($_GET['more']))
			$sql['limit'] *= 3;

if (!empty($_GET['user_id'])) {
	$sql['wheres'][] = "user_id = ".intval($_GET['user_id']);
}


		$data = $db->getAll(sqlBitsToSelect($sql));

//print_r($sql);
//print sqlBitsToSelect($sql);

		$tagstart = "<span class=tag><span>"; //double because the inner could be <a>
		$tagend = "</span></span>";


		$images = $tags = $models = array();
		if (!empty($_GET['tags'])) {

			foreach ($data as $row) {
				@$images[$row['tag']][$row['gridimage_id']] = $row;
			}

			print "<p><a href=?$extra>tags By Image</a> / <b>Images by tag</b></p>";

			print "Arbitary sample of ".count($data)." images for ".count($images)." tags";

			print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
			print "<tr><th>tag";
			print "<td>Images";

			foreach ($images as $tag => $rows) {
				$row = reset($rows);

				print "<tr>";
				print "<td>$tagstart".htmlentities($tag).$tagend;
				print "<td>";
				foreach ($rows as $row) {
					$image = new GridImage();
					$image->fastInit($row);
	                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
	                                print ' href="'.$CONF['canonical_domain'][$image->reference_index].'/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';
				}
	                }
			print "</table>";


		} else {
			$tags = array();
			foreach ($data as $row) {
				$row['model'] = 'top'; //fake for now!

				@$images[$row['gridimage_id']][$row['model']] = $row;
				@$tags[$row['gridimage_id']][$row['model']][] = $row['tag'];
				$models[$row['model']]=1;
			}

			print "<p><b>tags By Image</b> / <a href=\"?tags=1$extra\">Images by tag</a></p>";

			print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
			print "<tr><th>Example";
				print "<td>";
			foreach ($models as $model => $one)
				print "<th>$model";
			foreach ($images as $rows) {
				$row = reset($rows);

				print "<tr>";
				print "<td>";
					$image = new GridImage();
					$image->fastInit($row);
	                                print '<a title="'.$image->grid_reference.' : '.htmlentities($image->title).' by '.htmlentities($image->realname).' - click to view full size image"';
	                                print ' href="'.$CONF['canonical_domain'][$image->reference_index].'/photo/'.$image->gridimage_id.'">'.$image->getThumbnail($thumbw,$thumbh,false,true).'</a>';

				print "<td>";

				foreach ($models as $model => $one) {
					print "<td>";
					if (!empty($rows[$model])) {
						$column = $tags[$row['gridimage_id']][$model];
						print $tagstart.implode("$tagend $tagstart",array_map('htmlentities',$column)).$tagend;
					}
				}
	                }
			print "</table>";
		}
	}
?>
<style>
table span.tag span {
	padding:3px;background-color:#90ee9091;border-radius:2px;
}
</style>

<?



	$smarty->display('_std_end.tpl');

