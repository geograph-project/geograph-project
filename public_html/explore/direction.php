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

	$smarty->assign('responsive',true);
	$smarty->display('_std_begin.tpl');

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$title = "Explore Images over time";
$index = "sample8"; //note, can add WHERE ... on the end!

if (!empty($_GET['snippet_id'])) {

                        $db = GeographDatabaseConnection(true);
                        $id = intval($_GET['snippet_id']);
                        if ($row = $db->getRow("SELECT content_id,title FROM content WHERE foreign_id = $id AND source = 'snippet'")) {
				//use content, because, could use content_id= in the browser URL!
				$extra = "/snippets+".urlencode('"'.$row['title'].'"');
                        } else {
				die("The browser can't view images from this particular shared description");
                        }

	$index .= " WHERE snippet_ids = ".intval($_GET['snippet_id']); //works even on a MVA!

} else {
	die("todo - other filter methods not supported yet");
}

$rowname = 'takenyear'; //NEEDS to be sortable (ksort)
$colname = 'direction'; //and this gets 'natsort'ed;

	print '<div class="interestBox">';
        print "<h2>$title</h2>";
	print "</div>";

#######################################
$where = $match = array();

	if (true) {

		$thumbw = 120;
                $thumbh = 120;

			$sql = "SELECT id,user_id,title,realname,grid_reference,takenday,$colname,$rowname,count(*) as images FROM $index"; // WHERE ".implode(' and ',$where);
			$sql .= " GROUP BY $colname,$rowname limit 100";

		$imagelist = new ImageList();
		$imagelist->_setSph($sph);

		//use this as will create us proper image objects!
		$count = $imagelist->getImagesBySphinxQL($sql, true);

		$metrix = $columns = array();
		foreach ($imagelist->images as $i => $image) {
			$rowvalue = str_replace('tt','0s',$image->{$rowname}); //allows for decade!
			if ($rowvalue == '0000s') continue;
			$colvalue = $image->{$colname};
			@$metrix[$rowvalue][$colvalue] = $image;
			@$columns[$colvalue]++;
		}

#######################################

		print "<table cellspacing=0 cellpadding=1 style=position:relative>";
		print "<tr style=position:sticky;top:0;background-color:white>";
		print "<td>";
		uksort($columns, 'strnatcmp');
		foreach($columns as $colvalue => $count)
			print "<th style='border-left:1px solid silver'>".htmlentities($colvalue);

		ksort($metrix);
		foreach($metrix as $rowvalue => $rows) {
			print "<tr>";
			print "<th style='position:sticky;left:0;background-color:white;border-top:1px solid silver'>".htmlentities($rowvalue);
			foreach($columns as $colvalue => $count) {
				print "<td align=center>";
				if (!empty($rows[$colvalue])) {
					$image = $rows[$colvalue];
					if ($image->images>1)
						$url = "/browser/#!/$rowname+".urlencode('"'.str_replace('0s','tt',$rowvalue).'"')."/$colname+".urlencode('"'.$colvalue.'"').$extra;
					else
						$url = "/photo/{$image->gridimage_id}";

					$image->realname .= " [taken {$image->takenday}]";
			?>
 <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?>" href="<? echo $url; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src'); ?></a>
			<?
					if ($image->images>1)
						print "<br>".number_format($image->images,0);
				}
			}
			print "<th style='border-top:1px solid silver'>".htmlentities($rowvalue);
		}

		print "<tr>";
		print "<td>";
		foreach($columns as $colvalue => $count)
			print "<th style='border-left:1px solid silver'>".htmlentities($colvalue);

		print "</table>";
	}

#######################################

$smarty->display('_std_end.tpl');


