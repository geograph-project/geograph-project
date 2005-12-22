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


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'points';


$smarty = new GeographPage;

$template='moversboard.tpl';
$cacheid=$type;

if (isset($_GET['refresh']) && $USER->hasPerm('admin'))
	$smarty->clear_cache($template, $cacheid);

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed'); 
	
	/////////////
	// in the following code 'geographs' is used a column for legacy reasons, but dont always represent actual geographs....
	
	if ($type == 'squares' || $type == 'geosquares') {
		if ($type == 'geosquares') {
			$sql_where = " and i.moderation_status='geograph'";
			$heading = "Squares<br/>Geographed";
			$desc = "different squares geographed";
		} else {
			$sql_where = '';
			$heading = "Squares<br/>Photographed";
			$desc = "different squares photographed";
		}
		//squares has to use a count(distinct ...) meaning cant have pending in same query... possibly could do with a funky subquery but probably would lower performance...
		$sql="select i.user_id,i.realname,
		count(distinct grid_reference) as geographs
		from gridimage_search as i 
		where i.submitted > date_sub(now(), interval 7 day) $sql_where
		group by i.user_id 
		order by geographs desc";
		$topusers=$db->GetAssoc($sql);
	

		//now we want to find all users with pending images and add them to this array
		$sql="select i.user_id,u.realname,0 as geographs, count(*) as pending from gridimage as i
		inner join user as u using(user_id)  
		where i.submitted > date_sub(now(), interval 7 day) and
		i.moderation_status='pending'
		group by i.user_id
		order by pending desc";
		$pendingusers=$db->GetAssoc($sql);
		foreach($pendingusers as $user_id=>$pending) {
			if (isset($topusers[$user_id])) {
				$topusers[$user_id]['pending']=$pending['pending'];
			} else {
				$topusers[$user_id]=$pending;
			}
		}
		//no need to resort the combined array as should have imlicit ordering!
	} elseif ($type == 'geographs') {
		$sql_column = "sum(i.moderation_status='geograph')";
		$heading = "New<br/>Geographs";
		$desc = "'geograph' images submitted";
	} elseif ($type == 'images') {
		$sql_column = "sum(i.moderation_status in ('geograph','accepted'))";
		$heading = "New<br/>Images";
		$desc = "images submitted";
	} else { #if ($type == 'points') {
		$sql_column = "sum(i.ftf=1 and i.moderation_status='geograph')";
		$heading = "New<br/>Geograph<br/>Points";
		$desc = "geograph points awarded";
	} 
	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);
	
	if (!count($topusers)) {
		//we want to find all users with geographs/pending images 
		$sql="select i.user_id,u.realname,
		$sql_column as geographs, 
		sum(i.moderation_status='pending') as pending from gridimage as i 
		left join user as u using(user_id) 
		where i.submitted > date_sub(now(), interval 7 day) 
		group by i.user_id 
		having (geographs > 0 or pending > 0)
		order by geographs desc,pending desc ";
		$topusers=$db->GetAssoc($sql);
	}		
	//assign an ordinal

	$i=1;$lastgeographs = '?';
	$geographs = 0;
	$pending = 0;
	foreach($topusers as $user_id=>$entry)
	{
		if ($lastgeographs == $entry['geographs'])
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
			$lastgeographs = $entry['geographs'];
		}
		$i++;
		$geographs += $entry['geographs'];
		$pending += $entry['pending'];
	}	
	
	
	$smarty->assign('geographs', $geographs);
	$smarty->assign('pending', $pending);
	
	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('cutoff_time', time()-86400*7);
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
