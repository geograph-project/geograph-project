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

if (!isset($_GET['real'])) {
	$cacheid .= ".p";
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
		
	if (isset($_GET['real'])) {
		$table['table']=$db->GetAll("
			select category,question,count(*) as Answered,avg(vote) as Average,sum(vote=1) as `1`,sum(vote=2) as `2`,sum(vote=3) as `3`,sum(vote=4) as `4` from feedback inner join vote_log using (id) where type = 'f' and `final` = 1 and category = 'Experience' group by id order by 4
			" );
	} else {
		$replies = $db->GetOne("select count(distinct user_id,ipaddr) from vote_log where type = 'f' and final = 1");
		
		$replies100 = $replies/100;
		
		$table['table']=$db->GetAll("
			select category,question,concat(round(count(*)/$replies100),'%') as Answered,round(avg(vote),2) as Average,concat(round(sum(vote=1)/$replies100),'%') as `1`,concat(round(sum(vote=2)/$replies100),'%') as `2`,concat(round(sum(vote=3)/$replies100),'%') as `3`,concat(round(sum(vote=4)/$replies100),'%') as `4` from feedback inner join vote_log using (id) where type = 'f' and `final` = 1 and category = 'Experience' group by id order by 4
			" );
		
	}
	
		$table['total'] = count($table['table']);
		
		

	$tables[] = $table;

	###################

	$table = array();
	
		$table['title'] = "Part B - Results - $replies";

	if (isset($_GET['real'])) {
		$table['table']=$db->GetAll("
			select category,question,sum(vote=-2) as 'Didn\'t Know',sum(vote=-1) as 'Not Tried',sum(vote>0) as Answered,sum(if(vote>0,vote,0))/sum(vote>0) as Average,std(vote) as `Std Dev` from feedback inner join vote_log using (id) where type = 'f' and `final` = 1 and category != 'Experience' group by id order by 4
			");
	} else {
		$table['table']=$db->GetAll("
			select category,question,concat(round(sum(vote=-2)/$replies100),'%') as 'Didn\'t Know',concat(round(sum(vote=-1)/$replies100),'%') as 'Not Tried',concat(round(sum(vote>0)/$replies100),'%') as Answered,round(sum(if(vote>0,vote,0))/sum(vote>0),2) as Average,round(std(vote),2) as `Std Dev` from feedback inner join vote_log using (id) where type = 'f' and `final` = 1 and category != 'Experience' group by id order by 4
			");
	}

		$table['total'] = count($table['table']);
		$table["footnote"] = '</small>';
		
	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
				
	$smarty->assign("h2title",'Feedback results');
	$smarty->assign("headnote",'Note: Due to an error in the feedback form, all scales (including `average`) are effectively 1-4<small>');
	
} 

$smarty->display($template, $cacheid);

?>
