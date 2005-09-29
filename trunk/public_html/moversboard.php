<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$template='moversboard.tpl';
$cacheid='';

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 
	
	//get counts of geographs by user for last 7 days
	$sql="select i.user_id,u.realname,count(*) as geographs,0 as pending from gridimage as i ".
		"inner join user as u using(user_id) ".
		"where i.submitted > date_sub(now(), interval 7 day) and ".
		"i.ftf=1 and i.moderation_status='geograph' ".
		"group by i.user_id,i.moderation_status ".
		"order by geographs desc";
	$topusers=$db->GetAssoc($sql);
	
	//now we want to find all users with pending images and add them to this array
	$sql="select user_id,realname,0 as geographs, count(*) as pending from gridimage_search ".
			"where submitted > date_sub(now(), interval 7 day) and ".
			"moderation_status='pending' ".
			"group by user_id,moderation_status ".
			"order by pending desc";
	$pendingusers=$db->GetAssoc($sql);
	foreach($pendingusers as $user_id=>$pending)
	{
		if (isset($topusers[$user_id]))
		{
			$topusers[$user_id]['pending']=$pending['pending'];
		}
		else
		{
			$topusers[$user_id]=$pending;
		}
	}
	
	//assign an ordinal

	$i++;
	foreach($topusers as $user_id=>$entry)
	{
		if ($lastgeographs == $topusers[$user_id]['geographs'])
			$topusers[$user_id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {
			
			$units=$i%10;
			switch($units)
			{
				case 1:$end=($i==11)?'th':'st';break;
				case 2:$end=($i==12)?'th':'nd';break;
				case 3:$end=($i==13)?'th':'rd';break;
				default: $end="th";	
			}

			$topusers[$user_id]['ordinal']=$i.$end;
			$lastgeographs = $topusers[$user_id]['geographs'];
		}
		$i++;
	}	
	
	
	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('generation_time', time());
	$smarty->assign('cutoff_time', time()-86400*7);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
