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



	$db = GeographDatabaseConnection(true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$db->Execute("USE geograph_live");

print "<h2>Curated Education images</h2>";

#######################################################################



//show images!

$bys = array(
	'region'=>'Region',
	'hectad'=>'Hectad',
	'myriad'=>'Myriad',
	'super'=>'Super-Myriad',
	'curator'=>'Curator',
	'decade'=>'Decade',
);
$tables = '';

if (empty($_GET['by']))
	$_GET['by'] = 'region';

if ($_GET['by'] == 'region') {
	$section = "`region`"; //MySQL expression!

} elseif ($_GET['by'] == 'curator') {
	$section = "user.realname";
	$section = "if(length(value)>4,user.realname,'Anonymous')";
	$tables = " left join user_preference p on (p.user_id = c.user_id and pkey = 'curated.credit')";

} elseif ($_GET['by'] == 'decade') {
	$section = $_GET['by'];

} elseif ($_GET['by'] == 'myriad') {
	$section = "SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-4)";

} elseif ($_GET['by'] == 'super') {
	$section = "SUBSTRING(LPAD(gi.grid_reference,6,'I'),1,1)";

} elseif ($_GET['by'] == 'hectad') {
        $section = "CONCAT(SUBSTRING(gi.grid_reference,1,LENGTH(gi.grid_reference)-3),SUBSTRING(gi.grid_reference,LENGTH(gi.grid_reference)-1,1))";

} else {
	$section = "`region`"; //MySQL expression!
	$_GET['by'] = 'region';
}
$title = $bys[$_GET['by']];


 $imagelist = new ImageList();

	$imagelist->cols = str_replace(',',',gi.',$imagelist->cols);

	$sql = "SELECT {$imagelist->cols}, $section as section, c.user_id as cuid
		FROM curated1 c
		INNER JOIN gridimage_search gi USING (gridimage_id)
			INNER JOIN user ON (user.user_id = c.user_id)
			$tables
		WHERE label = ".$db->Quote($_GET['label'])." AND `group` = ".$db->Quote($_GET['group'])."
		AND active = 1
		ORDER BY $section, c.score desc, (moderation_status = 'geograph') DESC, sequence
		LIMIT 500";

 $imagelist->_getImagesBySql($sql);

if ($cnt = count($imagelist->images)) {
	//todo. (group selctor
	print "<form method=get>";
		print "<input type=hidden name=group value=\"".htmlentities($_GET['group'])."\">";
		print "<input type=hidden name=label value=\"".htmlentities($_GET['label'])."\">";
	print "<h3>{$cnt} current images for ".htmlentities($_GET['label']).", by <select name=by onchange=this.form.submit()>";
	foreach ($bys as $key => $value)
		printf('<option value="%s"%s>%s</option>', $key, ($_GET['by'] == $key)?' selected':'', $value);
	print "</select></h3>";
	print "</form>";

	$last = -1; $cnt=0;

	$stat = array();
	foreach ($imagelist->images as $image)
		@$stat[$image->section]++;

	foreach ($imagelist->images as $image) {
		if ($image->section != $last) {
			$last = $image->section;
			if (empty($image->section))
				$image->section = 'unknown';
			print "<div style=\"clear:both;margin-top:10px;\" class=interestBox>$title: <b>".htmlentities($image->section)."</b>";
			if ($stat[$last] > 6) {
				if ($_GET['by'] == 'decade' || $_GET['by'] == 'region') {
					$link = "?group=".urlencode($_GET['group'])
					."&label=".urlencode($_GET['label'])
					."&{$_GET['by']}=".urlencode($last);
					print " (<a href=sample.php$link target=_blank>{$stat[$last]} images</a>)";
				} else {
					print " ({$stat[$last]} images)";
				}
			}
			print "</div>";
			$cnt=0;
		}

		if ($cnt > 5)
			continue;

		?>
		 <div class="thumb shadow" id="t<? echo $image->gridimage_id; ?>">
                          <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?>" target=_blank href="/photo/<? echo $image->gridimage_id; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true); ?></a>
		<?
		print "</div>";

		$cnt++;
	}
	print "<hr style=clear:both>";
	if (count($imagelist->images) > 2) {
		$link = "?group=".urlencode($_GET['group'])
                       ."&label=".urlencode($_GET['label']);

		print "<br><a href=\"map.php$link\" target=_blank>View images on map</a> (note currently implemeneted via marked list, so will add these images to your marked list!)</p>";
		print "<hr style=clear:both>";
	}

	if ($_GET['by'] == 'region') {
		$regions = $db->getCol("select distinct region from curated1 where `group` = 'Geography and Geology' and region != ''");
		$missing = array();
		foreach ($regions as $region)
			if (empty($stat[$region]))
				$missing[] = $region;
		if (!empty($missing))
			print "Region(s) without any images currently: <b>".implode(', ',$missing)."</b><hr>";
	}
}

#######################################################################

?>
<style>
div.thumb {
	float:left;width:130px;height:140px;
}
</style>

<?


$smarty->display('_std_end.tpl');



