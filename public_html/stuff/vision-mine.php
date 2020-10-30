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

?>
<h2>Automatic Labels extracted via Computer Vision</h2>

<p>This page just shows an arbitary selection of your images, that have already been processed (if any!). As a demo of the types of labels retrieved.

<p>Remember these labels are completely produced by computer, using only the image itself as input. No text from the image submission is used.


<?

$u = $USER->user_id;

if (!empty($_GET['user_id']) && preg_match('/^\d+/',$_GET['user_id']))
	$u = intval($_GET['user_id']);

$db = GeographDatabaseConnection(true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


$andwhere = $leftjoin = $extra = '';
if (isset($_GET['v'])) {
	$andwhere = " AND validated = ".intval($_GET['v']);
	$extra = '&v='.intval($_GET['v']);
}

if (!empty($_GET['filter'])) {
	if ($_GET['filter'] == 'tags') {
		$leftjoin .= " LEFT JOIN gridimage_tag USING (gridimage_id)";
		$andwhere .= " AND tag_id IS NULL";
		$extra .= '&filter='.$_GET['filter'];

	} elseif ($_GET['filter'] == 'plaintags') {
		$leftjoin .= " LEFT JOIN tag_public t ON (gi.gridimage_id = t.gridimage_id AND prefix != 'top' AND prefix != 'bucket' AND prefix != 'type')";
		$andwhere .= " AND tag_id IS NULL";
		$extra .= '&filter='.$_GET['filter'];

	}
}



if (!empty($_GET['tab'])) {

	$list = $db->getAll("SELECT gi.gridimage_id,gi.user_id,title,realname,grid_reference,
			description
                        FROM gridimage_search gi
                        INNER JOIN vision_results ON (id=gridimage_id)
			$leftjoin
                        WHERE gi.user_id = $u AND description != '' $andwhere
                        ORDER BY PASSWORD(description) DESC
                        LIMIT 400");


	if (count($list)) {
		print "<p><a href=\"?$extra\">View By Image</a> / <b>View by Label</b></p>";
		$last = '';
		$c = $t = 0;
                foreach ($list as $idx => $row) {
			if ($last != $row['description']) {
				print "<hr style=clear:both><b>".htmlentities($row['description'])."</b><br>";
				$last = $row['description'];
				$c=0;
			} else {
				$c++;
				if ($c > 3)
					continue;
			}

                        $image = new GridImage();
                        $image->fastInit($row);
                        $thumbh = 120;
                        $thumbw = 120;


?>

	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
	  </div>

<?
			$t++;
			if ($t>100)
				break;
                }
		print "<br style=\"clear:both\"/>";

	} else {
		print "nothing to display";
	}


} else {

	$list = $db->getAll($sql = "SELECT gi.gridimage_id,gi.user_id,title,realname,grid_reference,
			GROUP_CONCAT(v.description ORDER BY score DESC) as descriptions
                        FROM gridimage_search gi
                        INNER JOIN vision_results v ON (v.id=gi.gridimage_id)
			$leftjoin
                        WHERE gi.user_id = $u AND v.description != '' $andwhere
                        GROUP BY gi.gridimage_id
                        ORDER BY gi.gridimage_id DESC
                        LIMIT 100");

if (!empty($_GET['d']))
	print_r($sql);


	if (count($list)) {
		print "<p><b>View By Image</b> / <a href=\"?tab=label$extra\">View by Label</a></p>";

                foreach ($list as $idx => $row) {
                        $image = new GridImage();
                        $image->fastInit($row);
                        $thumbh = 120;
                        $thumbw = 120;


?>
	<div style="clear:both">

	  <div style="float:left;position:relative; width:130px; height:130px">
	  <div align="center">
	  <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?> - click to view full size image" href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a></div>
	  </div>

<?
		print "<b>".htmlentities($image->title)."</b><br><br>";

		print htmlentities(str_replace(',',', ',$row['descriptions']));

		print "<br><br><a href=\"#\" onclick=\"return open_tagging({$image->gridimage_id},'{$image->grid_reference}','');\">Tags</a>";
			print "<div class=\"interestBox\" id=\"div{$image->gridimage_id}i\" style=\"display:none\">";
                        print "<iframe src=\"about:blank\" height=\"300\" width=\"100%\" id=\"tagframe{$image->gridimage_id}\">";
                        print "</iframe></div>";

	print "</div>";

                }
		print "<br style=\"clear:both\"/>";

	} else {
		print "nothing to display";
	}
}

?>
<script>
function open_tagging(gid,gr) {
	document.getElementById('div'+gid+'i').style.display='';
        document.getElementById('tagframe'+gid).src='/tags/tagger.php?gridimage_id='+gid+'&gr='+gr;
        return false;
}
</script>

<?


	$smarty->display('_std_end.tpl');
	exit;

