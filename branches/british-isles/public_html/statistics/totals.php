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

$template='statistics_totals.tpl';
$cacheid='statistics|totals';

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);


	$tables=$db->MetaTables();
	foreach ($tables as $table)
	{
		$count[$table]=$db->GetOne("select count(*) from $table");
	}
	
	$count['loc_placenames__ppl']=$db->CacheGetOne(14*24*3600,"select count(*) from loc_placenames where dsg = 'PPL'");
	

	$count['gridsquare__land']=$db->CacheGetOne(14*24*3600,"select count(*) from gridsquare where percent_land > 0");
	$count['gridprefix__land']=$db->CacheGetOne(14*24*3600,"select count(*) from gridprefix where landcount > 0");
	$count['geobb_posts__users']=$db->GetOne("select count(distinct poster_id) from geobb_posts");
	$count['geobb_topics__views']=$db->GetOne("select sum(topic_views) from geobb_topics");
	$count['gridimage_ticket__users']=$db->GetOne("select count(distinct user_id) from gridimage_ticket");
	$count['gridimage_ticket__users_others']=$db->GetOne("select count(distinct gi.user_id) from gridimage_ticket gi inner join gridimage g on (gi.gridimage_id = g.gridimage_id and gi.user_id != g.user_id)");
	$count['gridimage__users']=$count['user_stat']-1;
	//-1 beucase will count all anon users as 1 user (user_id = 0)
	$count['queries__users']=$db->GetOne("select count(distinct user_id)-1 from queries");
	$count['queries'] +=$count['queries_archive'];
	$count['apikeys']=$db->GetOne("select count(*) from apikeys where enabled = 'Y'");
	
	$count['autologin__30dayusers']=$db->GetOne("select count(distinct user_id)-1 from autologin where created > date_sub(now(), interval 30 day)");
	
	$smarty->assign_by_ref("count", $count);
	
	
function count_files($dir,$ext) {
	$c = 0;
	$dir = "../".$dir;
	if (is_dir($dir) && $dh = opendir($dir)) {
		while (($file = readdir($dh)) !== false) 
			if (preg_match('/\.'.$ext.'$/',$file) )
				$c++;
		closedir($dh);
	}
	return $c;
}
	
	$files['rss'] = count_files("rss/","...");
	$files['memorymap'] = count_files("memorymap/","csv");
	$files['tpraw'] = count_files("templates/basic/","tpl");
	$files['tpcompiled'] = count_files("templates/basic/compiled/","php");
	
	$smarty->assign_by_ref("files", $files);
}


$smarty->display($template, $cacheid);

	
?>