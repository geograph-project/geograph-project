<?php
/**
 * $Project: GeoGraph $
 * $Id: pulse.php 7327 2011-07-08 20:57:31Z barry $
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

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$cacheid='statistics|tables';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 600; //10min cache

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad(5);

	$db=GeographDatabaseConnection(false);
	if (!$db) die('Database connection failed');

	$title = "Geograph Database Tables";

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = array();

	$tables = $db->getAll("SELECT table_name,title FROM _tables WHERE title NOT IN ('','no') ORDER BY title");
	$status = $db->getAssoc("SHOW TABLE STATUS");

	$rows = $total = 0;
	foreach ($tables as $row) {
		$count = $db->getOne("SELECT COUNT(*) FROM {$row['table_name']}");
		$rows +=$count;

		$bytes = '';
		if (!empty($status[$row['table_name']])) {
			$bytes = number_format($status[$row['table_name']]['Data_length'],0);
			$total += $status[$row['table_name']]['Data_length'];
		}
		$table[] = array("Table"=>$row['title'],"Rows"=>number_format($count,0),"Bytes"=>$bytes);
	}

	$table[] = array("Parameter"=>"","Rows"=>'',"Bytes"=>'');
	$table[] = array("Parameter"=>"Primary Database Total","Rows"=>number_format($rows,0),"Bytes"=>number_format($total,0));

	if (!empty($CONF['filesystem_dsn'])) {
		$filedb=NewADOConnection($CONF['filesystem_dsn']);

		$files = $filedb->getOne("select max(file_id) from file"); //yes its not quite the same as COUNT(*) but its an innodb table, so count(*) is not optimized!

		$status = $filedb->getAssoc("SHOW TABLE STATUS LIKE 'file'");
		$bytes = number_format($status['file']['Data_length'],0);

		$table[] = array("Parameter"=>"","Rows"=>'',"Bytes"=>'');
		$table[] = array("Parameter"=>"FileSystem Objects","Rows"=>number_format($files,0),"Bytes"=>$bytes);
	}

	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign("footnote","Note, some of these totals might be slightly higher than the objects visible on the site, as tables often contain records for deleted items");
}

$smarty->display($template, $cacheid);

