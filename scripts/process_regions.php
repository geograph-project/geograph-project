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

if (empty($param)) { //special support so this script can be 'include()ed'.
	$param = array('table' => 'sample_selection', 'debug'=>0);

	chdir(__DIR__);
	require "./_scripts.inc.php";

	$db = GeographDatabaseConnection(false);
}

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$c=0;
<<<<<<< HEAD
$sql = "SELECT DISTINCT gridimage_id,wgs84_lat,wgs84_long FROM {$param['table']} INNER JOIN gridimage_search USING (gridimage_id) WHERE region ='' OR region='Scotland' LIMIT 10000";
$recordSet = $db->Execute($sql);
=======
$sql = "SELECT DISTINCT gridimage_id,wgs84_lat,wgs84_long FROM {$param['table']} INNER JOIN gridimage_search USING (gridimage_id) WHERE region ='' LIMIT 10000";
$recordSet = &$db->Execute($sql);
>>>>>>> b0fa2f0de... Remove unneeded filter, was added when wanted to reprocess Scotland
if ($recordSet->RecordCount()) {

	if (!empty($param['debug']))
		print "Start = {$recordSet->fields['gridimage_id']}: ";

	while (!$recordSet->EOF) {
		$region = getRegion($recordSet->fields['wgs84_lat'],$recordSet->fields['wgs84_long']);
		$sql = "UPDATE {$param['table']} SET region = '$region' WHERE gridimage_id = {$recordSet->fields['gridimage_id']}";
		$db->Execute($sql);
		$recordSet->MoveNext();
		$c++;
		if (!empty($param['debug']) && !($c%100)) print "$c. ";
	}
}

if (!empty($param['debug']))
	print "Done $c\n";

$recordSet->Close();







function getRegion($lat,$lng) {
static $raw = "
54.7246202,-6.6357422,Northern Ireland
55.2661055,-8.4375688,Republic of Ireland
53.4488793,-7.3278438,Republic of Ireland
54.1173828,-9.6569824,Republic of Ireland
52.4493141,-7.5476074,Republic of Ireland
51.773762,-4.0649546,Wales
53.0747225,-3.8562916,Wales
50.9722649,-3.4277344,South West England
50.7091992,-2.1648831,South West England
50.6963913,0.9563003,South East England
52.4157494,-1.658972,Central England
52.1953086,0.978101,East Anglia
53.6084303,-0.4164043,North East England
54.3613576,-3.0871582,North West England
53.6117969,-2.944417,North West England
55.7589074,-3.5046942,East Scotland
55.8371938,-5.2299845,West Scotland
54.9650017,-1.4941406,North East England
54.0554321,-3.5543452,Isle of Man
55.091299,-4.350421,West Scotland
50.7364551,-0.9887695,South East England
";

//53.9062094,-4.152609,Isle of Man

static $points = array();
if (empty($points)) {
	foreach(explode("\n",$raw) as $line)
		if (preg_match('/(-?\d+\.?\d*),(-?\d+\.?\d*),(\w.*)/',$line,$m))
			$points[] = $m;
}
	$dist = 9999999999999;
	$best = null;
	foreach ($points as $point) {
		$d = pow($lat-$point[1],2) + pow($lng-$point[2],2);
		if ($d < $dist) {
			$dist = $d;
			$best = $point[3];
		}
	}
	return $best;
}




/*

## Backwards view, to pwer the streight join!
CREATE ALGORITHM=UNDEFINED DEFINER=`geograph`@`%` SQL SECURITY DEFINER VIEW `public_tag` AS select `tag`.`tag_id` AS `tag_id`,`tag`.`prefix` AS `prefix`,`tag`.`tag` AS `tag`,`gridimage_tag`.`gridimage_id` AS `gridimage_id`,`gridimage_tag`.`user_id` AS `user_id`,`gridimage_tag`.`created` AS `created`,`tag`.`canonical` AS `canonical`,`tag`.`description` AS `description` from (`gridimage_tag` join `tag` on((`tag`.`tag_id` = `gridimage_tag`.`tag_id`))) where ((`tag`.`status` = 1) and (`gridimage_tag`.`status` = 2))


create table sample_selection 
select straight_join gi.gridimage_id,imageclass,ttop.tag as context,tsub.tag as subject, baysian, sequence, greatest(original_width,original_height) as largest from gridimage_search gi inner join gridimage_size using (gridimage_id) left join public_tag ttop on (ttop.gridimage_id = gi.gridimage_id and ttop.prefix = 'top') left join public_tag tsub on (tsub.gridimage_id = gi.gridimage_id and tsub.prefix = 'subject') where baysian > 3.6;

alter table sample_selection add index(gridimage_id);

alter table sample_selection add region varchar(32) not null default '';

update sample_selection s inner join category_mapping m using (imageclass) set s.context = m.context1 where s.context is null and context1 != '';
update sample_selection s inner join category_mapping m using (imageclass) set s.subject = m.subject where s.subject is null;

update sample_selection set context = null where context in ('-bad-','>>> bucket','forum alerted');


update sample_selection set context = 'Wild Animals, Plants and Mushrooms' where context = 'Wild Animals, Plants and Mushroo';

alter table sample_selection add decade char(3) not null;

update sample_selection s inner join gridimage_search using (gridimage_id) set decade = substring(imagetaken,1,3) where imagetaken NOT like '0000%';

*/
