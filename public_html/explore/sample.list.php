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

pageMustBeHTTPS();


$sph = GeographSphinxConnection('sphinxql',true);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

################################################################################

$dimensions = array('context','subject','region','decade');

$title = array();
$query = '';
$where = array();

$query .= " @types Geograph"; //todo, should be at end really!
$where[] = "`context` != ''";
if ($CONF['template']=='ireland') {
	$query .= " @region Ireland";
} else {
	$query .= " @region -Ireland"; //only works because we will always have a query (@types!)
}

foreach ($dimensions as $dimension2) {
	if (!empty($_GET[$dimension2])) {
		$title[] = htmlentities($_GET[$dimension2]).(($dimension2 == 'decade')?'0s':'');
		$query .= " @$dimension2 ".$_GET[$dimension2];
	}
}

if (!empty($query))
        $where[] = "MATCH(".$sph->Quote($query).")";

################################################################################

if (!empty($title)) {
	$smarty->assign('page_title', implode(", ",$title));

	$smarty->display('_std_begin.tpl', $_SERVER['PHP_SELF'].md5(implode(", ",$title)) );
} else {
	$smarty->display('_std_begin.tpl');
}

function showtitle($count = '') {
	global $title;

	if (!empty($title))
		print "<h2>$count Photos : ".implode(", ",$title)."</h2>";
	else
		print "<h2>$count Geograph Photos - Quick Explore</h2>";

	print "<p>View a small selection of Geograph images here. To explore more images, use the search box above.</p>";

}

function getlink(...$data) {
	global $row;
	$s=array();
	foreach ($data as $key)
		if (!empty($row[$key]))
			$s[] = "$key=".urlencode2($row[$key]);
	return "?".implode("&amp;",$s);
}

################################################################################

if (empty($_GET)) {
	$sql = "select context,region,decade,count(*) as images FROM sample_selection
	WHERE ".implode(" AND ",$where)." group by context,region,decade having images > 5 order by context asc,region asc,decade asc limit 1000";
        $recordSet = $sph->Execute($sql);

	showtitle();

	$context = null;
	$region = null;
        while ($recordSet && !$recordSet->EOF) {
                $row = $recordSet->fields;
                if ($context != $row['context']) {
                        if ($context)
                                print "</ul>";
			$link = getlink('context');
                        print "<h4><a href=\"$link\">".htmlentities($row['context'])."</a></h4>";
                        print "<ul>";
                        $context = $row['context'];
                }

                if ($region != $row['region']) {
                        if ($region)
                                print "</li>";
			$link = getlink('context','region');
                        print "<li><a href=\"$link\">".htmlentities($row['region'])."</a> : ";
                        $region = $row['region'];
                }

		$link = getlink('context','region','decade');
                print "<a href=\"$link\">".htmlentities($row['decade'])."0s</a>[{$row['images']}] ";

                $recordSet->MoveNext();
        }
        $recordSet->Close();
        print "</ul>";

} else {
	print "<style>ul.images li img { vertical-align: middle; } </style>";

	$cols = "`".implode("`,`",$dimensions)."`";
	$sql1 = "SELECT id,user_id,realname,title,grid_reference,takenday,$cols,larger,credit_realname,
			wgs84_lat,wgs84_long, if(larger='',0,1) as has_larger, if(types = '_SEP_ Geograph _SEP_',1,0) as is_geo
		FROM sample_selection WHERE ";
	$sql2 = "ORDER BY context ASC,has_larger DESC,sequence ASC LIMIT 1000";
        $recordSet = $sph->Execute($sql1." ".implode(" AND ",$where)." ".$sql2);

	$count = $recordSet->RecordCount();
	showtitle($count);

	$context = null;
	if (!empty($_GET['context'])) {
		$context = $_GET['context'];
		print "<ul class=images>";
	}

	$i=0;
        while ($recordSet && !$recordSet->EOF) {
		$row = $recordSet->fields;

		//convert sphinx column names to mysql names
		$row['gridimage_id'] = $row['id'];
		if (preg_match('/(\d{4})(\d{2})(\d{2})/',$row['takenday'],$m))
		        $row['imagetaken'] = $m[1].'-'.$m[2].'-'.$m[3];
		$row['largest'] = intval($row['larger']); //larger is a (sorted!) list of bigger sizes, latest is expeced to be a number.

		if ($context != $row['context']) {
			if ($context)
				print "</ul>";
			print "<h4>".htmlentities($row['context'])."</h4>";
			print "<ul class=images>";
			$context = $row['context'];
		}

		$profile_link = "/profile/{$row['user_id']}";
		if (!empty($row['credit_realname']))
			$profile_link .= "?a=".urlencode($row['realname']);

		print "<li>";

		if ($i < 10) {
			$image = new GridImage;
	                $image->fastInit($row);
                	print $image->getThumbnail(120,120);
		}

		print " {$row['grid_reference']}";
		//print " <a href=\"/gridref/{$row['grid_reference']}\">".htmlentities($row['grid_reference'])."</a>";
		print " <a href=\"/photo/{$row['gridimage_id']}\">".htmlentities($row['title'])."</a>";
		print " by <a href=\"{$profile_link}\">".htmlentities($row['realname'])."</a>";

                $recordSet->MoveNext();
		$i++;
	}
        $recordSet->Close();
	print "</ul>";

}


$smarty->display('_std_end.tpl');
