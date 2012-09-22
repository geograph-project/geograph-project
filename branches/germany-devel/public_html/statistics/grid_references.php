<?php
/**
 * $Project: GeoGraph $
 * $Id: images.php 2380 2006-08-13 10:41:07Z barry $
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

	$table = (isset($_GET['table']) && is_numeric($_GET['table']))?intval($_GET['table']):0;

	$smarty->assign('whichtable',$table);
	
	$template='statistics_tables_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".$table.csv\"");

	$cacheid='statistics|grid_references.'.$table;

} else {
	$template='statistics_tables.tpl';
	
	$cacheid='statistics|grid_references';
}

if (!$smarty->is_cached($template, $cacheid)) {
	dieUnderHighLoad();
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Subject Grid References";

		$table['table']=$db->GetAll("
		select if(use6fig,if(natgrlen in ('6','8','10'),'6',natgrlen),natgrlen) as Figures ,count(*) as Number from gridimage where moderation_status in ('geograph','accepted') group by if(use6fig,if(natgrlen in ('6','8','10'),'6',natgrlen),natgrlen)" );

		$table['total'] = count($table);


	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Photographer Grid References";

		$table['table']=$db->GetAll("
		select if(use6fig,if(viewpoint_grlen in ('6','8','10'),'6',viewpoint_grlen),viewpoint_grlen) as Figures ,count(*) as Number from gridimage where moderation_status in ('geograph','accepted') group by if(use6fig,if(viewpoint_grlen in ('6','8','10'),'6',viewpoint_grlen),viewpoint_grlen)");

		$table['total'] = count($table);

	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
	
	$smarty->assign("h2title",'Grid Reference Lengths');
} 

$smarty->display($template, $cacheid);

?>
