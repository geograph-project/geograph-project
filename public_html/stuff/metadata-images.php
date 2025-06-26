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

	$smarty->assign('page_title','Image Data');
	$smarty->display('_std_begin.tpl',$_SERVER['PHP_SELF']);

#####################################################################

$links = array('metadata-images.php' => 'Totals','labelled-images.php' => 'Tag Stats');

print '<div class="tabHolder" style="max-width:940px">Places in: ';
foreach ($links as $link => $name) {
        if ($link == basename($_SERVER['PHP_SELF'])) {
                if (!empty($_GET)) { //having the link is useful to return to "homepage"
                        print "<a class=tabSelected  href=$link>$name</a> ";
                } else {
                        print "<a class=tabSelected>$name</a> ";
                }
        } else {
                print "<a class=tab href=$link>$name</a> ";
        }
}
print '</div>';

print "<div class=interestBox>";
print "<h2>Human Curated Data</h2>";
print "</div>";

#####################################################################

	$db = GeographDatabaseConnection(false);
	$sph = GeographSphinxConnection('sphinxql',true);

	$data = array();

	$data['Total Images'] = $total = $db->getOne("select sum(imagecount) from gridsquare");
	$data['With Title'] = $total;
	$data['With Description'] = $total - $db->cacheGetOne(3600*24,"select count(*) from gridimage_search where comment = ''");

	$data['With Year Taken'] = $db->getOne("select sum(images) from date_stat where month = '' and type = 'imagetaken' and reference_index = 0");
	$data['With Day Taken'] = $db->getOne("select sum(images) from imagetaken_stat where imagetaken NOT like '%-00%'");

	$data['With Category'] = $db->getOne("select sum(c) from category_stat");
	$data['With Free-Form Tags'] = $sph->getOne("select count(*) from sample8 where match('@tags _SEP_')");
	//$data['With Type Tags'] = $sph->getOne("select count(*) from sample8 where match('@types _SEP_')"); //this includes polyfill from moderatio_status!
	$data['With Type Tags'] = $db->getOne("select sum(count) from tag inner join tag_stat using (tag_id) where prefix = 'type' and canonical =0");
	$data['With Context Tags'] = $sph->getOne("select count(*) from sample8 where match('@contexts _SEP_')");
	$data['With Subject Tag'] = $db->getOne("select sum(count) from tag inner join tag_stat using (tag_id) where prefix = 'subject' and canonical =0");
	$data['With Subject (inc implied)'] = $sph->getOne("select count(*) from sample8 where match('@subjects _SEP_')"); //includes polyfill from category!!
	$data['With Shared Descriptions'] = $sph->getOne("select count(*) from sample8 where match('@snippets _SEP_')");

	$data['With 4fig Subject Loc'] = $total;

$grlens = $sph->getAssoc("select natgrlen,count(*) from sample8 group by natgrlen");
	$data['With 6fig+ Subject Loc'] = $grlens['6'] + $grlens['8'] + $grlens['10'];


$vgrlens = $sph->getAssoc("select vgrlen,count(*) from sample8 group by vgrlen");
	$data['With 6fig+ Camera Loc'] = $vgrlens['6'] + $vgrlens['8'] + $vgrlens['10'];

	$data['With View Direction'] = $total - $sph->getOne("select count(*) from sample8 where match('@direction Unknown')");

		print "<table style='font-family:verdana;' cellpadding=5>";
		foreach($data as $name => $count) {
			print "<tr>";
			print "<th>$name";
			print "<td align=right>".number_format($count,0);
			if ($count < $total)
				print "<td align=right>".sprintf('%.1f%%',$count/$total*100);
		}
		print "</table>";

print "<br><hr><br>";
print "<p>We have a mapping from (older) Category to (newer) Subject, so for many images with a Category can imply a Subject";
print "<p>By 6fig+, mean a location using a 6figure or better grid-reference, hence 100m or better resolution";
print "<p>The view direction is generally implied from Subject+Camera location, but may be supplied seperately, particully if image is very close range (cant get from GRs)";

print "<p>The above is only looking at the raw data, provided by contributors. Not generated data from processing, eg could use a reverse geocoder to guessimate a placename based on coordinates. Nor extracting placenames from title/description etc. Such generated data not listed here";

#####################################################################

	$smarty->display('_std_end.tpl');

