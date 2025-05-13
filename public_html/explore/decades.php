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

//customExpiresHeader(3600,false,true);

	$smarty->assign('responsive',true);
	$smarty->display('_std_begin.tpl');

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array('/explore/quick.php'=>'Topics','/finder/recent.php'=>'Recent', '/explore/decades.php'=>'Decades', 
	'/stuff/viewgaz4.php' => 'Great Britain','/stuff/viewgaz3.php' => 'Ireland', '/stuff/viewgaz5.php' => 'Isle of Man', '/search.php'=>'Search', '/mapper/combined.php'=>'Map',
	'/browser/' => 'Advanced Browser','/content/explore.php'=>'Collections');

print '<div class="tabHolder" style="max-width:940px">';
foreach ($links as $link => $name) {
	if (basename($link) == 'viewgaz4.php')
		print " Places in: ";
        if (basename($link) == basename($_SERVER['PHP_SELF'])) {
                if (!empty($_GET)) { //having the link is useful to return to "homepage"
                        print "<a class=tabSelected href=$link>$name</a> ";
                } else {
                        print "<a class=tabSelected>$name</a> ";
                }
        } else {
                print "<a class=tab href=$link>$name</a> ";
        }
}
print '</div>';

	print '<div class="interestBox">';
        print "<h2>Explore Images over time</h2>";
	print "</div>";

#######################################
$where = $match = array();

	if (true) {

		if (!empty($match))
			$where[] = "MATCH(".$sph->Quote(implode(' ',$match)).")";

//		print_r($where);

		$thumbw = 120;
                $thumbh = 120;

			$sql = "SELECT id,user_id,title,realname,grid_reference,takenyear,decade,country,count(*) as images FROM sample8"; // WHERE ".implode(' and ',$where);
			$sql .= " GROUP BY decade,country limit 1000";

		$imagelist = new ImageList();
		$imagelist->_setSph($sph);

		//use this as will create us proper image objects!
		$count = $imagelist->getImagesBySphinxQL($sql, true);

		$matrix = $country = array();
		foreach ($imagelist->images as $i => $image) {
			$decade = str_replace('tt','0s',$image->decade);
			if ($decade == '0000s') continue;
			$country = $image->country;
			$metrix[$decade][$country] = $image;
			@$countries[$country]++;
		}

#######################################

		print "<table cellpadding=6>";
		print "<tr>";
		print "<td>";
		foreach($countries as $country => $count)
			print "<th>$country";

		krsort($metrix);
		foreach($metrix as $decade => $rows) {
			print "<tr>";
			print "<th>$decade";
			foreach($countries as $country => $count) {
				print "<td align=center>";
				if (!empty($rows[$country])) {
					$image = $rows[$country];
					$url = "/browser/#!/decade+".urlencode('"'.str_replace('0s','tt',$decade).'"')."/country+".urlencode('"'.$country.'"')."/display=date_slider";

					$image->realname .= " [taken {$image->takenyear}]";
			?>
 <a title="<? echo $image->grid_reference; ?> : <? echo htmlentities($image->title) ?> by <? echo htmlentities($image->realname); ?>" href="<? echo $url; ?>"><? echo $image->getThumbnail($thumbw,$thumbh,false,true,'loading=lazy src'); ?></a>
			<?
					print "<br>".number_format($image->images,0);
				}
			}
		}

		print "<tr>";
		print "<td>";
		foreach($countries as $country => $count)
			print "<th>$country";

		print "</table>";
	}

#######################################

$smarty->display('_std_end.tpl');


