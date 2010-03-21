<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

$cacheid='statistics|images';

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();
	
	$db = GeographDatabaseConnection(true);	

	$title = "Geograph Images";

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	

	$sql = "SELECT 
	CONCAT(ELT(ftf+1, '','first ','second ','third ','fourth '),moderation_status) as `Classification`, 
	SUM(submitted > DATE_SUB(NOW() , interval 1 HOUR)) as `In last Hour`,
	SUM(submitted > DATE_SUB(NOW() , interval 1 DAY)) as `In last 24 Hours`,
	SUM(submitted > DATE_SUB(NOW() , interval 7 DAY)) as `In last 7 Days`,
	SUM(submitted > DATE_SUB(NOW() , interval 1 MONTH)) as `In last 7 Days`,
	SUM(submitted > DATE_SUB(NOW() , interval 1 YEAR)) as `In last Year`,
	COUNT(*) as `All Time Count`
	FROM gridimage 
	GROUP BY `Classification` 
	ORDER BY BETWEEN 1 AND 4 DESC, ftf ASC, moderation_status+0 DESC";
	
	$table = $db->getAll($sql);	
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));

		

} 

$smarty->display($template, $cacheid);


	
?>
