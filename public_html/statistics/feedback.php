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

	$cacheid='statistics|feedback.'.$table;
} else {
	$template='statistics_tables.tpl';
	
	$cacheid='statistics|feedback';
}

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (!$smarty->is_cached($template, $cacheid)) {
	dieUnderHighLoad();
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Part A - Experience";

		$table['table']=$db->GetAll("
			select category,question,count(*) as replies,sum(vote=1) as `1`,sum(vote=2) as `2`,sum(vote=3) as `3`,sum(vote=4) as `4`,sum(vote=5) as `5` from feedback inner join vote_log using (id) where type = 'f' and category = 'Experience' group by id order by 4
			" );
	
		$table['total'] = count($table['table']);
		
		

	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Part B - Results";

		$table['table']=$db->GetAll("
		select category,question,sum(vote=-2) as 'Didn\'t Know',sum(vote=-1) as 'Not Tried',sum(vote>0) as Votes,sum(if(vote>0,vote,0))/sum(vote>0) as Average,std(vote) as `Std Dev` from feedback inner join vote_log using (id) where type = 'f' and category != 'Experience' group by id order by 4
		");

		$table['total'] = count($table['table']);

	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
				
	$smarty->assign("h2title",'Feedback results');
} 

$smarty->display($template, $cacheid);

?>
