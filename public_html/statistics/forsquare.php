<?php
/**
 * $Project: GeoGraph $
 * $Id: pulse.php 8074 2014-04-09 19:40:22Z barry $
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

require_once('geograph/global.inc.php');
init_session();

$smarty = new GeographPage;

if (isset($_GET['ri'])) {
	$ri = intval($_GET['ri']);

	$cacheid='statistics|forsquare'.$ri;

} else {
	if (empty($_GET['gr']) || !preg_match('/^[A-Z]{1,2}\d{4}$/',$_GET['gr']))
		die("specifify valid 4fig GR");

	$d = 10;
	if (!empty($_GET['d']))
		$d = intval($_GET['d']);
	$d = min(30,$d);
	$d = max(1,$d);

	$cacheid='statistics|forsquare'.$d.$_GET['gr'];
}

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}


$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 24000;

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad(5);



if (!empty($CONF['db_read_connect2'])) {
        if (!empty($DSN_READ))
                $DSN_READ = str_replace($CONF['db_read_connect'],$CONF['db_read_connect2'],$DSN_READ);
        if (!empty($CONF['db_read_connect']))
                $CONF['db_read_connect'] = $CONF['db_read_connect2'];
}



	$db=GeographDatabaseConnection(false);
	if (!$db) die('Database connection failed');


	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = array();

#############################

	$where = array();
	$title = "Photo Stats";

	if (!empty($_GET['gr'])) {
	 	$gr = $db->getRow("SELECT * FROM gridsquare WHERE grid_reference = ".$db->Quote($_GET['gr']));

		if (empty($gr))
			die("unknown square!");

		$title = "Photos Stats within {$d}km of ".$_GET['gr'];

		$x = $gr['x'];
		$y = $gr['y'];

		$where[] = sprintf("x BETWEEN %d AND %d",$x-$d,$x+$d);
		$where[] = sprintf("y BETWEEN %d AND %d",$y-$d,$y+$d);
		$where[] = "((gs.x - $x) * (gs.x - $x) + (gs.y - $y) * (gs.y - $y)) < ".($d*$d);

		$smarty->assign('ri',$gr['reference_index']);

	} elseif (!empty($_GET['ri'])) {

		$where[] = "reference_index = $ri";
		$smarty->assign('ri',$ri);

		$title .= " in ".$CONF['references_all'][$ri];

	} else {
		$where[] = "1";
	}

	$where2 = implode(" AND ",$where); //hacky, used on gridimage_search based query!

	$where[] = "percent_land > 0";

	$tables = "gridsquare gs";
	$where = implode(" AND ",$where);

#############################

	$sql = "SELECT COUNT(*) AS total, SUM(imagecount>0) AS value FROM $tables WHERE $where";
	calc("Total Photographed Squares",$sql);


	$sql = "SELECT COUNT(*) AS total, SUM(imagecount >=4) AS value FROM $tables WHERE $where AND imagecount >0";
	calc("Photographed Squares with at least 4 photos",$sql);

	$sql = "SELECT COUNT(*) AS total, SUM(imagecount >= 10) AS value FROM $tables WHERE $where AND imagecount >0";
	calc("Photographed Squares with at least 10 photos",$sql);

	$sql = "SELECT COUNT(*) AS total, SUM(max_ftf >= 4) AS value FROM $tables WHERE $where AND imagecount >0";
	calc("Photographed Squares with at least 4 contributors of 'Geograph' images",$sql);

	$sql = "SELECT COUNT(*) AS total, SUM(max_ftf >= 10) AS value FROM $tables WHERE $where AND imagecount >0";
	calc("Photographed Squares with at least 10 contributors of 'Geograph' images",$sql);

	$sql = "SELECT COUNT(*) AS total, SUM(has_geographs=1) AS value FROM $tables WHERE $where AND imagecount >0";
	calc("Photographed Squares with 'Geograph' images",$sql);


	$sql = "SELECT COUNT(*) AS total, SUM(has_recent>0) AS value FROM $tables WHERE $where";
	calc("Squares with at least 1 recent image (5 years)",$sql);

	$sql = "SELECT COUNT(*) AS total, SUM(has_recent>=4) AS value FROM $tables WHERE $where";
	calc("Squares with at least 4 recent images (5 years)",$sql);

	if (!empty($_GET['gr'])) {
		$sql = "SELECT COUNT(*) AS total, SUM(points>=2) AS value FROM (
			select grid_reference,count(*) points from gridimage_search gs where $where2  and points='tpoint' group by grid_reference order by null
		) t2";
		calc("Squares with at least 2 TPoints",$sql);
	}


#############################

	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);

	$smarty->assign("footnote","Note: only counting squares with land here!");

	$smarty->assign("total",count($table));
	$smarty->assign("nosort",1);

	$smarty->assign("filter",1); //ri only, 2 would filter by user too!
	$smarty->assign_by_ref('references',$CONF['references_all']);
}

$smarty->display($template, $cacheid);

function calc($name,$sql,$cache = 0) {
	global $db,$table;

	if ($cache) {
		$row = $db->cacheGetRow($cache,$sql);
	} else {
		$row = $db->getRow($sql);
	}

	$table[] = array("Parameter"=>$name,
			"Total Squares"=>$row['total'],
			"Squares Done"=>$row['value'],
			"Done as Percentage"=>sprintf('%.1f',$row['value']/$row['total']*100),
			"Squares Left"=>$row['total']-$row['value'],
			);
}

