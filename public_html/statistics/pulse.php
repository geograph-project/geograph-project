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

$template='statistics_table.tpl';

$cacheid='pulse';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 600; //10min cache

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad(5);
	
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 	

	$title = "Geograph Pulse";

	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = array();

	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , interval 10 MINUTE)";
	calc("Images Submitted in last 10 minutes",$sql);
	
	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , interval 1 HOUR)";
	calc("Images Submitted in last hour",$sql);
	
	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , interval 24 HOUR)";
	calc("Images Submitted in last 24 hours",$sql);

	$sql = "SELECT COUNT(*) FROM sessions WHERE EXPIRY > UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 24 MINUTE))";
	calc("Visitors in last 24 minutes",$sql);
	
	$sql = "select count(distinct user_id)-1 from autologin where created > date_sub(now(), interval 1 hour)";
	calc("Regular Users visited in last hour",$sql);

	$sql = "SELECT COUNT(*) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , interval 1 HOUR)";
	calc("Forum Posts in last hour",$sql);
	
	$sql = "SELECT COUNT(*) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , interval 24 HOUR)";
	calc("Forum Posts in last 24 hours",$sql);

	
	if (strpos($_ENV["OS"],'Windows') === FALSE) {
		//check load average
		$buffer = "0 0 0";
		$f = fopen("/proc/loadavg","r");
		if ($f)	{
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
				$loads = explode(" ",$buffer);
				$load = (float)$loads[0];
				
				$name = "Hamster's currently sweating*";
				$table[] = array("Parameter"=>$name,"Value"=>sprintf("%d",$load*10));
				$smarty->assign("footnote","<p>* below 10 is good, above 20 is worse, above 40 is bad.</p>");
			}
			fclose($f);			
		}
	}
	
	$sql = "select count(*) from event where status='pending'";
	calc("Pending Hamster Tasks",$sql);
	
	$sql = "select count(*) from mapcache where age > 0";
	calc("Map tiles to redraw",$sql);
	
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign("nosort",1);
		

} 

$smarty->display($template, $cacheid);

function calc($name,$sql) {
	global $db,$table;
	
	$val = $db->getOne($sql);
	
	$table[] = array("Parameter"=>$name,"Value"=>$val);
	
}
	
?>
