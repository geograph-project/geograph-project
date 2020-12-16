<?php
/**
 * $Project: GeoGraph $
 * $Id: recreate_maps.php 2996 2007-01-20 21:39:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array(
	'interval' => "2 day",
	'tag_days' => false,
	'execute' => false,
	'debug' => false,
);

chdir(__DIR__);
require "./_scripts.inc.php";

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$s = array();

######################################################################################################################################################

if (!empty($param['tag_days']))
	//special version to note updated tags. Sometimes tags are renamed without updating gridimage_tag :(
	$s[] = "create temporary table tagids (primary key(gridimage_id))
		select distinct gridimage_id FROM gridimage_tag INNER JOIN tag USING (tag_id) WHERE tag.updated > DATE_SUB(NOW(),interval {$param['tag_days']} day) and gridimage_id < 4294967296 and gridimage_tag.status = 2 AND tag.updated != tag.created";

elseif (!empty($param['interval']))
	$s[] = "create temporary table tagids (primary key(gridimage_id))
		select distinct gridimage_id FROM gridimage_tag WHERE updated > DATE_SUB(NOW(),interval {$param['interval']}) and gridimage_id < 4294967296";

######################################################################################################################################################

$copy = "update gridimage_search inner join tagtest using (gridimage_id)
	set tags = newtags, upd_timestamp = upd_timestamp";

$truncate = "truncate tagtest";

######################################################################################################################################################

$create = "create temporary table tagtest (gridimage_id int unsigned primary key,newtags text)";
$insert = "insert into tagtest";
$select = "select STRAIGHT_JOIN gridimage_id,group_concat(distinct if(prefix!='',concat(prefix,':',tag),tag) order by prefix = 'top' desc,tag SEPARATOR '?') as newtags";
if (!empty($s))
	$from = "from tagids inner join gridimage_tag gt using (gridimage_id) inner join tag t using (tag_id)";
else
	$from = "from gridimage_tag gt inner join tag t using (tag_id)";
$where = "where gt.status = 2 and t.status = 1 and gridimage_id < 4294967296";
$group = "group by gridimage_id order by null";

if (preg_match('/(\d+) day/',$param['interval'],$m) && $m[1]>10) {
	$max = $db->getOne("SELECT MAX(gridimage_id) FROM gridimage_search");

	for($start = 1;$start<$max;$start+=100000) {
		if (!empty($s) && !(count($s)%10)) {
			$s[] = $copy;
			$s[] = $truncate;
		}
	        $end = $start+99999;
	        $s[] = (($start==1)?$create:$insert)." $select $from $where AND gridimage_id BETWEEN $start AND $end $group";
	}
} else {
	$s[] = "$create $select $from $where $group";
}

$s[] = $copy;

######################################################################################################################################################

foreach ($s as $sql) {
	if (!empty($param['debug']))
		print "---\n$sql;\n---\n";
	if (!empty($param['execute'])) {
		if (!empty($param['debug']))
			print date('r')." (started)\n";
		$db->Execute($sql);
		if (!empty($param['debug'])) {
			print date('r')." (done)\n";
			print "Rows Affected: ".$db->Affected_Rows()."\n";
		}
	}
}
if (!empty($param['debug']))
	print ".\n";



