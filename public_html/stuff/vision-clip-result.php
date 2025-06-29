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

	$db = GeographDatabaseConnection(false);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


        print "<h2>Example Prediction Labels</h2>";

	print "<p style=max-width:900px>This page shows some auto-detected labels produced by custom AI model (trained on Geograph data specifically). ";

if (empty($_GET['model'])) {
	$_GET['model'] = 'clip';
	print " These specific results are via a model produced by https://github.com/SpaceTimeLab/ClipTheLandscape (specifically the one using title+image - with mixup)";
}

	if (true) {
                        $thumbh = 120;
                        $thumbw = 120;

		$sql = array();
		$sql['wheres'] = array();
		$sql['columns'] = explode(',','gridimage_id,user_id,realname,title,grid_reference,reference_index,model,label,l.score');
		$sql['tables'] = array('gridimage_search gi');
		if (false) {
			$sql['tables'][] = "inner join gridimage_label_single l using (gridimage_id)";
		} else {
			$sql['tables'][] = "inner join gridimage_label l using (gridimage_id)";
			$sql['wheres'][] = 'l.score > 0.5'; //that mostly garentees one lable per image anyway!
			$sql['wheres'][] = "model not in ('type','typev2','city')";
			$sql['wheres'][] = "label != 'None'";
			$sql['order'] = 'seq_id desc';
		}
		$sql['limit'] = 99; //so devisible three, nomincal number of models currently!
		if (!empty($_GET['more']))
			$sql['limit'] *= 3;

if (!empty($_GET['user_id'])) {
	$sql['wheres'][] = "user_id = ".intval($_GET['user_id']);
}
if (!empty($_GET['model']) && preg_match('/^\w+$/',_GET['model'])) {
	$sql['wheres'][] = "model = ".$db->Quote($_GET['model']);
}


		$data = $db->getAll(sqlBitsToSelect($sql));

		$images = $models = $labels = array();
		if (!empty($_GET['labels'])) {

			foreach ($data as $row) {
				if ($row['model'] == 'top' || $row['model'] == 'subject')
					$row['label'] = "{$row['model']}:{$row['label']}";
				@$images[$row['label']][$row['gridimage_id']] = $row;
			}

			print "<p><a href=?>Labels By Image</a> / <b>Images by Label</b></p>";

			print "Arbitary sample of ".count($data)." images for ".count($images)." labels";

			print "<table cellspacing=0 cellpadding=2 border=1 bordercolor=#eee>";
			print "<tr><th>Label";
			print "<td>Images";

			foreach ($images as $label => $rows) {
				$row = reset($rows);

				print "<tr>";
				print "<td>".htmlentities($label);
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
				@$images[$row['gridimage_id']][$row['model']] = $row;
				@$tags[$row['gridimage_id']][$row['model']][] = $row['label'];
				$models[$row['model']]=1;
			}

			print "<p><b>Labels By Image</b> / <a href=\"?labels=1\">Images by Label</a></p>";

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

						print implode(';<br>',array_map('htmlentities',$column));
					}
				}
	                }
			print "</table>";
		}
	}




	$smarty->display('_std_end.tpl');

