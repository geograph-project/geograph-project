<?php
/**
 * $Project: GeoGraph $
 * $Id: busyday_users.php 2176 2006-04-27 23:42:06Z barryhunter $
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

$u = (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] == $USER->user_id)?intval($_GET['u']):0;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$table = (isset($_GET['table']) && is_numeric($_GET['table']))?intval($_GET['table']):0;
	$smarty->assign('whichtable',$table);
	
	$template='statistics_tables_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".$table.csv\"");

	$cacheid='statistics|admin_performance.'.$table.'|'.$u;
} else {
	$template='statistics_tables.tpl';
	
	$cacheid='statistics|admin_performance|'.$u;
}

if (!$smarty->is_cached($template, $cacheid))
{

	$db = GeographDatabaseConnection(true); 
	 $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	if (!empty($u)) {
		$crit1 = "and l.user_id = ".$u;
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title = " for ".($profile->realname);
	} else {
		$title = '';
	}

	$tables = array();
	
	###################

	$table = array();
	
		$table['title'] = "Image Moderation Dummy Runs".$title;

		$table['table']=$db->GetAll("
		SELECT SUBSTRING(created,1,7) as Month,COUNT(*) AS Count,SUM(new_status != moderation_status) AS Mismatches,
			SUM(new_status != moderation_status)/COUNT(*)*100 as Percentage,COUNT(DISTINCT l.user_id) as Moderators
		FROM moderation_log l
		INNER JOIN gridimage gi USING (gridimage_id)
		WHERE created > moderated
			$crit1
		AND type = 'dummy'
		GROUP BY SUBSTRING(created,1,7)
		" );

		$table['total'] = count($table);


	$tables[] = $table;

	###################
	
	$smarty->assign_by_ref('tables', $tables);
		
	$smarty->assign('h2title','Admin Performance');
	
} else {
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}

$smarty->assign("filter",2);
$smarty->assign("nosort",1);
$smarty->display($template, $cacheid);

	
?>
