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

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

if (isset($_GET['output']) && $_GET['output'] == 'csv') {
	$template='statistics_table_csv.tpl';
	# let the browser know what's coming
	header("Content-type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"".basename($_SERVER['SCRIPT_NAME'],'.php').".csv\"");
} else {
	$template='statistics_table.tpl';
}

$u = (isset($_GET['u']) && is_numeric($_GET['u']) && $_GET['u'] == $USER->user_id)?intval($_GET['u']):0;

$u2 = (isset($_GET['u2']) && is_numeric($_GET['u2']) && $USER->hasPerm('admin'))?intval($_GET['u2']):0;


$cacheid='statistics|contributer'.$u.'.'.$u2;

if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();
	
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db = GeographDatabaseConnection(true); 

	$title = "Statistics";

	if (!empty($u)) {
		$wherewhere = "where user_id=".$u;
		$andwhere = "and user_id=".$u;
		$fandwhere = "and poster_id=".$u;
		$ftpandwhere = "and topic_poster=".$u;
		$ftpwherewhere = "where topic_poster=".$u;
		
		$smarty->assign('u', $u);

		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".($profile->realname);
		
		$ismod = $profile->hasPerm('moderator');
	} 
	
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$table = array();

	$sql = "select count(*) from gridimage_search where moderation_status = 'geograph' and ftf = 1 $andwhere";
	calc("Points",$sql);
	
	$sql = "select count(*) from gridimage_search where moderation_status = 'geograph' $andwhere";
	calc("Geographs",$sql);

	$sql = "select count(*) from gridimage_search $wherewhere";
	calc("Images",$sql);
	
	$sql = "select count(distinct grid_reference) from gridimage_search where moderation_status = 'geograph' $andwhere";
	calc("GeoSquares",$sql);
	
	$sql = "select count(distinct grid_reference) from gridimage_search $wherewhere";
	calc("Squares",$sql);
		
	$table[] = array();
	$table[] = array();

	
	$sql = "select count(*) from geobb_topics $ftpwherewhere";
	calc("Forum Topics Started",$sql);
		
	$sql = "select count(DISTINCT p.topic_id)
		from  
			geobb_posts as p
			left join geobb_topics as t 
				on(t.topic_id=p.topic_id)
		where 
			abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) > 10 $fandwhere";
	calc("Forum Topics Replied To",$sql);
	

	$sql = "select count(*)
		from  
			geobb_posts as p
			left join geobb_topics as t 
				on(t.topic_id=p.topic_id)
		where 
			abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) > 10 $fandwhere";
	calc("Forum Replies",$sql);	
		
	$table[] = array();
	
	$forums = $db->getAll("select forum_id,forum_name from geobb_forums order by forum_id");
	foreach ($forums as $c => $forum) {
		$sql = "select count(*) from geobb_topics where forum_id = {$forum['forum_id']} $ftpandwhere";
		calc("Forum '{$forum['forum_name']}' Topics",$sql);

		$sql = "select count(DISTINCT p.topic_id)
			from  
				geobb_posts as p
				left join geobb_topics as t 
					on(t.topic_id=p.topic_id)
			where 
				p.forum_id = {$forum['forum_id']} and
				abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) > 10 $fandwhere";
		calc("Forum '{$forum['forum_name']}' Topics Replied To",$sql);
		
		$sql = "select count(*)
			from  
				geobb_posts as p
				left join geobb_topics as t 
					on(t.topic_id=p.topic_id)
			where 
				p.forum_id = {$forum['forum_id']} and
				abs(unix_timestamp(t.topic_time) - unix_timestamp(p.post_time) ) > 10 $fandwhere";
		calc("Forum '{$forum['forum_name']}' Replies",$sql);	
		
		$table[] = array();
	}
	

	$table[] = array();
	
	if ($ismod) {
			$sql = "select count(*) from gridimage where moderator_id=".$u;
			calc("Images Moderated",$sql);
	}
	
	$sql = "select count(*) from gridimage_ticket $wherewhere";
	calc("Change Suggestions Submitted",$sql);
		
	$sql = "select count(*) from gridimage_ticket_comment $wherewhere";
	calc("Change Suggestions Comments",$sql);		
		
	if ($ismod) {
			$sql = "select count(*) from gridimage_ticket where moderator_id=".$u;
			calc("Change Suggestions Moderated",$sql);
	}
	

	$table[] = array();
	
	$sql = "select count(*) from queries $wherewhere";
	calc("Searches Performed",$sql);
	
	
	$smarty->assign_by_ref('table', $table);
	
	$smarty->assign("h2title",$title);
	$smarty->assign("nosort",1);
	$smarty->assign("total",count($table));
	

} 

$smarty->display($template, $cacheid);

function calc($name,$sql) {
	global $db,$table,$u,$u2;
	
	if ($u) {
		$val = $db->getOne($sql);
	} else {
		$val = $db->CacheGetOne(3600,$sql);	
	}
	#if (!$val) return;
	if ($u2) {
		$sql2 = preg_replace('/\w+ \w+=\d+$/','',$sql);
		$val2 = $db->CacheGetOne(3600,$sql2);	

		$perc = sprintf('%.2f',$val/$val2*100);
	
		$sql3 = preg_replace('/\d+$/',$u2,$sql);		
		$val3 = $db->getOne($sql3);	

		$perc3 = sprintf('%.2f',$val3/$val2*100);
		
		$table[] = array("Quality"=>$name,"Overall"=>number_format($val2),"Count"=>'<b>'.number_format($val).'</b>',"Percent"=>$perc.'%',"Count2"=>number_format($val3),"Percent2"=>$perc3.'%');
	} elseif ($u) {
		$sql2 = preg_replace('/\w+ \w+=\d+$/','',$sql);
		$val2 = $db->CacheGetOne(3600,$sql2);	

		$perc = sprintf('%.2f',$val/$val2*100);

		$table[] = array("Quality"=>$name,"Overall"=>number_format($val2),"Count"=>'<b>'.number_format($val).'</b>',"Percent"=>$perc.'%');
	} else {
		$table[] = array("Quality"=>$name,"Overall"=>number_format($val));
	}
}
	
?>
