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

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$cacheid='statistics|pulse';

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 600; //10min cache

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad(5);

	$db=GeographDatabaseConnection(false);
	if (!$db) die('Database connection failed');

	$title = "Geograph Pulse";

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = array();

	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 10 MINUTE)";
	calc("Images Submitted in last 10 minutes",$sql);

	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 1 HOUR)";
	calc("Images Submitted in last hour",$sql);

	if ($upper_limit = $db->getOne("SELECT MIN(gridimage_id) FROM gridimage WHERE moderation_status = 'pending'")) {
		$sql = "SELECT MAX(gridimage_id) FROM gridimage_search WHERE gridimage_id < $upper_limit";
	} else {
		$sql = "SELECT MAX(gridimage_id) FROM gridimage_search";
	}
	calc("All moderated upto ID",$sql);

	$data = $db->GetRow("select count(*) as `count`,(unix_timestamp(now()) - unix_timestamp(min(submitted))) as age from gridimage where moderation_status='pending'");

	$table[] = array("Parameter"=>"Images Pending","Value"=>$data['count']);
	$table[] = array("Parameter"=>"Oldest Pending","Value"=>intval($data['age']/3600).' hours');


$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Images Submitted in last 24 hours",$sql,600);

	$sql = "SELECT COUNT(DISTINCT user_id) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Image Contributors in last 24 hours",$sql,3600);

	$sql = "SELECT COUNT(DISTINCT moderator_id) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 48 HOUR) and moderator_id > 0 and moderated > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Active Moderators in last 24 hours",$sql,3600);

/*
$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM category_top";
	calc("Categories mapped to Geographical Context",$sql,3600);
*/
$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 7 DAY)";
	calc("Images Submitted in last 7 days",$sql,3600*3);

	$sql = "SELECT COUNT(DISTINCT user_id) FROM gridimage WHERE submitted > DATE_SUB(NOW() , INTERVAL 7 DAY)";
	calc("Image Contributors in last 7 days",$sql,3600*3);

$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM  gridimage_daily where showday is NULL and vote_baysian > 3.2";
        calc("Images in Daily Photo Upcoming Pool",$sql);

$table[] = array("Parameter"=>'',"Value"=>'');

if (!empty($redis_handler)) {
	$table[] = array("Parameter"=>"Approx Visitors in last 24 <u>minutes</u>","Value"=> $redis_handler->DBsize());

} else {
	$sql = "SELECT COUNT(DISTINCT ipaddr) FROM sessions WHERE EXPIRY > UNIX_TIMESTAMP(DATE_SUB(NOW(),INTERVAL 24 MINUTE))";
	$db2 = ADODB_Session::_conn();
	$table[] = array("Parameter"=>"Approx Visitors in last 24 <u>minutes</u>","Value"=>$db2->getOne($sql));
}

	$sql = "SELECT COUNT(DISTINCT user_id)-1 FROM autologin WHERE created > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
	calc("Approx Regular Users visited in last hour",$sql);

$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , INTERVAL 1 HOUR)";
	calc("Forum Posts in last hour",$sql);

	$sql = "SELECT COUNT(DISTINCT poster_id) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , INTERVAL 1 HOUR)";
	calc("Forum Posters in last hour",$sql);

$table[] = array("Parameter"=>'',"Value"=>'');

	$sql = "SELECT COUNT(*) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Forum Posts in last 24 hours",$sql,3600);

	$sql = "SELECT COUNT(DISTINCT poster_id) FROM geobb_posts WHERE post_time > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Forum Posters in last 24 hours",$sql,3600);

	$sql = "SELECT COUNT(DISTINCT user_id) FROM geobb_lastviewed WHERE ts > DATE_SUB(NOW() , INTERVAL 24 HOUR)";
	calc("Forum Viewers in last 24 hours",$sql,3600);

$table[] = array("Parameter"=>'',"Value"=>'');

	$filedb=NewADOConnection($CONF['filesystem_dsn']);

	$files = $filedb->getOne("select max(file_id) from file"); //yes its not quite the same as COUNT(*) but its an innodb table, so count(*) is not optimized!
	$recent = $filedb->getOne("select count(*) from file where file_id > $files-1000 and file_created > date_sub(now(),interval 1 hour)");
	$table[] = array("Parameter"=>"Total FileSystem Objects","Value"=>$files);
	$table[] = array("Parameter"=>"Created in last Hour","Value"=>$recent);

$table[] = array("Parameter"=>'',"Value"=>'');

	if (strpos($_ENV["OS"],'Windows') === FALSE) {
		//check load average
		$buffer = "0 0 0";
		$f = fopen("/proc/loadavg","r");
		if ($f)	{
			if (!feof($f)) {
				$buffer = fgets($f, 1024);
				$loads = explode(" ",$buffer);
				$load = (float)$loads[0];

				$name = "Hamsters currently sweating*";
				$table[] = array("Parameter"=>$name,"Value"=>sprintf("%d",$load*10));
				$smarty->assign("footnote","<p>* below 10 is good, above 20 is worse, above 40 is bad.</p>");
				$table[] = array("Parameter"=>'',"Value"=>'');
			}
			fclose($f);
		}
	}

	$sql = "SELECT count(*) FROM event WHERE status='pending'";
	calc("Pending Hamster Tasks",$sql,100);

#	$sql = "select count(distinct id) from vote_stat where type = 'i19618112'";
#	calc("Rated Images",$sql);

#	$sql = "SELECT COUNT(*) FROM kmlcache WHERE rendered = 0";
#	calc("Superlayers tiles to update",$sql,3600);

#	$sql = "SELECT COUNT(DISTINCT url) FROM gridimage_link WHERE next_check < NOW()";
#	calc("Links waiting to be checked",$sql);

	$sql = "SELECT COUNT(*) FROM gridimage_queue";
	calc("Images in prepending queue",$sql);

	$smarty->assign_by_ref('table', $table);

	$smarty->assign("h2title",$title);
	$smarty->assign("total",count($table));
	$smarty->assign("nosort",1);
}

$smarty->display($template, $cacheid);

function calc($name,$sql,$cache = 0) {
	global $db,$table;

	if ($cache) {
		$val = $db->cacheGetOne($cache,$sql);
	} else {
		$val = $db->getOne($sql);
	}

	$table[] = array("Parameter"=>$name,"Value"=>$val);
}

