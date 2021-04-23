<?php
/**
 * $Project: GeoGraph $
 * $Id: build_sitemap.php 6839 2010-09-15 20:20:57Z geograph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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

$param = array('hectad'=>'ST82', 'execute'=>false);

chdir(__DIR__);
require "./_scripts.inc.php";

############################################

$db = GeographDatabaseConnection(false);

$hectad = $param['hectad'];
if (preg_match('/([A-Z]+)(\d)(\d)/',$hectad,$m)) {
	$like = $m[1].$m[2].'_'.$m[3].'_';
} else {
	die("unable to decode hectad $hectad\n");
}


$str = "title";
foreach (range(0,8) as $i) //skip 9!
	$str = "REPLACE($str,'$i','9')";

print "$str\n";
exit;

	###############################

	$start = microtime(true);

	$recordSet = $db->Execute($sql = "
		select title,grid_reference,user_id
		from gridimage_search as i
		where grid_reference LIKE '$like'
		and title rlike '[[:<:]][[:digit:]]{1,3}[^[:alnum:]]*$' ");

	$end = microtime(true);

	print "time = ".($end-$start)."\n";


	$cnt = $recordSet->recordCount();
	print "rows = ".$cnt."\n";



	$s = array();
	while (!$recordSet->EOF)
	{
		$r = $recordSet->fields;

			##... (1) ... [1]  ... {1}
		$title=preg_replace('/\s*[\[\(\{]\s*\d{1,3}\s*[\]\)\}][^\w]*$/', ' #', $r['title']);

			##... (detail 1)
		$title=preg_replace('/\s*\(detail \d{1,3}\)$/i', ' #', $r['title']);

			##... [No 1]
		$title=preg_replace('/\s*\[No\.? \d{1,3}\]$/i', ' #', $r['title']);

			##... #1  (wont match entities, because semicoln! &#8470;)
		$title=preg_replace('/\s*#\d{1,3}\s*$/', ' #', $r['title']);

			##... /1  (requires space, so doesnt match  "gateway on bridleway W21/82")
		$title=preg_replace('/\s+\/\d{1,3}\s*$/', ' #', $r['title']);



			##also have examples like... can exploit sphinx/manticore support for * word wildecard (in phrases)
			## NOTE: however, the RLIKE above doesnt pick these up!
			###Token exchange (1) at Hampton Loade Station, Shropshire
			###Token exchange (2) at Hampton Loade Station, Shropshire
		$title=preg_replace('/\(\d{1,3}\)/', '*', $title);


		if (substr($title,-1) == '#') { //the RLIKE finds a few rows, not changed by the preg_replace above

			@$s[ $r['grid_reference'] ][ $title ][ 'images' ]++;
			@$s[ $r['grid_reference'] ][ $title ][ 'users' ][ $r['user_id'] ]++;
		}

		$recordSet->MoveNext();
	}

	$recordSet->Close();

	###############################

$updates = array();
foreach ($s as $gr => $r) {
	$updates['grid_reference'] = $gr;
	foreach ($r as $title => $rr) {
		if ($rr['images'] > 1) {

print "$title // {$rr['images']}\n";

if (!$param['execute'])
	continue;

			$updates['title'] = $title;
			$updates['images'] = $rr['images'];
			$updates['users'] = count($rr['users']);

			$db->Execute('INSERT INTO gridimage_duplicate SET `'.implode('` = ?,`',array_keys($updates)).'` = ? ',
		             array_values($updates)) or die(print_r($updates,1)."\n".$db->ErrorMsg()."\n\n");

			print_r($updates);
		}
	}
}
