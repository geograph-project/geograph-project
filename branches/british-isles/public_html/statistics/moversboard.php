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

init_session_or_cache(3600, 360); //cache publically, and privately

if (isset($_GET['debug'])) {
	print_r($USER);
	print_r($_SESSION);
}


if (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type'])) {
        $type = $USER->setPreference('statistics.moversboard.type',$_GET['type'],true);
} else {
        $type = $USER->getPreference('statistics.moversboard.type','tpoints',true);
}
if (isset($_GET['debug'])) {
	print "TYPE=$type\n";
	print date('r');
	exit;
}

$smarty = new GeographPage;

$template='statistics_moversboard.tpl';
$cacheid="statistics|".$type;

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	$db = GeographDatabaseConnection(true); 
	
	/////////////
	// in the following code 'geographs' is used a column for legacy reasons, but dont always represent actual geographs....
	$sql_column = '';
	$sql_orderby = '';
	$sql_table = " gridimage as i ";
	$sql_where = '';
	if ($type == 'squares' || $type == 'geosquares') {
		if ($type == 'geosquares') {
			$sql_where = " and i.moderation_status='geograph'";
			$heading = "Squares<br/>Geographed";
			$desc = "different squares geographed";
		} else {
			$heading = "Squares<br/>Photographed";
			$desc = "different squares photographed";
		}
		//squares has to use a count(distinct ...) meaning cant have pending in same query... possibly could do with a funky subquery but probably would lower performance...
		$sql="select i.user_id,u.realname,
		count(distinct grid_reference) as geographs
		from gridimage_search as i 
		inner join user as u using(user_id)  
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
	} elseif ($type == 'tpoints') {
		$sql_column = "sum(i.points='tpoint')";
               ## $sql_table = " gridimage_search i ";
		$heading = "TPoints";
		$desc = "TPoints";
	} elseif ($type == 'tnf') {
		$sql_column = "sum(i.points='tpoint' and ftf!=1)";
                $heading = "TPoints-Firsts";
                $desc = "number of TPoints minus number of Firsts";
	} elseif ($type == 'geographs') {
		$sql_column = "sum(i.moderation_status='geograph')";
		$heading = "New<br/>Geographs";
		$desc = "'geograph' images submitted";
	} elseif ($type == 'additional') {
		$sql_column = "sum(i.moderation_status='geograph' and ftf != 1)";
		$heading = "Non-First<br/>Geographs";
		$desc = "non first 'geograph' images submitted";
	} elseif ($type == 'supps') {
		$sql_column = "sum(i.moderation_status='accepted')";
		$heading = "New<br/>Supplemental";
		$desc = "'supplemental' images submitted";
	} elseif ($type == 'images') {
		$sql_orderby = ',points desc';
		$sql_column = "sum(i.ftf=1 and i.moderation_status='geograph') as points, sum(i.moderation_status in ('geograph','accepted'))";
		$heading = "New<br/>Images";
		$desc = "images submitted";
	} elseif ($type == 'test_points') {
		$sql_column = "sum((i.moderation_status = 'geograph') + (ftf=1) + 1)";
		$sql_table = " gridimage_search i ";
		$heading = "G-Points";
		$desc = "test points";
	} elseif ($type == 'depth') {
		$sql_column = "count(*)/count(distinct grid_reference)";
		$sql_table = " gridimage_search i ";
		$heading = "Depth";
		$desc = "depth score";
	} elseif ($type == 'myriads') {
		//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
		$sql_column = "count(distinct substring(grid_reference,1,3 - reference_index))";
		$sql_table = " gridimage_search i ";
		$heading = "Myriads";
		$desc = "different myriads";
	} elseif ($type == 'hectads') {
		//we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
		$sql_column = "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )";
		$sql_table = " gridimage_search i ";
		$heading = "Hectads";
		$desc = "different hectads";
	} elseif ($type == 'days') {
		$sql_column = "count(distinct imagetaken)";
		$sql_table = " gridimage_search i ";
		$heading = "Days";
		$desc = "different days";
        } elseif ($type == 'antispread') {
                //we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
                $sql_column = "count(*)/count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )";
		$sql_table = " gridimage_search i ";
                $heading = "AntiSpread Score";
                $desc = "antispread score (images/hectads)";
        } elseif ($type == 'spread') {
                //we dont have access to grid_reference - possibly join with grid_prefix, but for now lets just exclude pending!
                $sql_column = "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )/count(*)";
                $sql_table = " gridimage_search i ";
                $heading = "Spread Score";
                $desc = "spread score (hectads/images)";
	} elseif ($type == 'classes') {
		$sql_column = "count(distinct imageclass)";
		$sql_table = " gridimage_search i ";
		$heading = "Categories";
		$desc = "different categories";
	} elseif ($type == 'clen') {
		$sql_column = "avg(length(comment))";
		$sql_table = " gridimage_search i ";
		$heading = "Average Description Length";
		$desc = "average length of the description";
	} elseif ($type == 'tlen') {
		$sql_column = "avg(length(title))";
		$sql_table = " gridimage_search i ";
		$heading = "Average Title Length";
		$desc = "average length of the title";
	} elseif ($type == 'category_depth') {
		$sql_column = "count(*)/count(distinct imageclass)";
		$sql_table = " gridimage_search i ";
		$heading = "Category Depth";
		$desc = "the category depth score";
	} elseif ($type == 'centi') {
		//NOT RECOMMENDED AS REQUIRES A NEW INDEX ON gridimage!
		$sql_column = "COUNT(DISTINCT nateastings div 100, natnorthings div 100)";
		$sql_where = "and i.moderation_status='geograph' and nateastings div 1000 > 0";
		$heading = "Centisquares";
		$desc = "centisquares photographed";
	} elseif ($type == 'second') {
		$sql_column = "sum(i.ftf=2 and i.moderation_status='geograph')";
		$heading = "Second Visit<br/>Points";
		$desc = "'Second Visit' points awarded";
	} elseif ($type == 'third') {
		$sql_column = "sum(i.ftf=3 and i.moderation_status='geograph')";
		$heading = "Third Visit<br/>Points";
		$desc = "'Third Visit' points awarded";
	} elseif ($type == 'forth' || $type == 'fourth') {
		$sql_column = "sum(i.ftf=4 and i.moderation_status='geograph')";
		$heading = "Fourth Visit<br/>Points";
		$desc = "'Fourth Visit' points awarded";
	} elseif ($type == 'allpoints') {
		$sql_column = "sum(i.ftf between 1 and 4 and i.moderation_status='geograph')";
		$heading = "All Geograph<br/>Points";
		$desc = "First/Second/Third/Fourth Visit points awarded";
	} elseif ($type == 'personal') {
		$sql_column = "sum(i.ftf>0 and i.moderation_status='geograph')";
		$heading = "Personal<br/>Points";
		$desc = "Personal points awarded";
	} else { #if ($type == 'first') {
		$sql_column = "sum(i.ftf=1 and i.moderation_status='geograph')";
		$heading = "First Geograph<br/>Points";
		$desc = "'First Geograph' points awarded";
		$type = 'first';
	} 
	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);
	
	if ($sql_column) {
		$sql_pending = (strpos($sql_table,'_search') === FALSE)?"sum(i.moderation_status='pending')":'0';
		//we want to find all users with geographs/pending images 
		$sql="select i.user_id,u.realname,
		$sql_column as geographs, 
		$sql_pending as pending
		from $sql_table left join user as u using(user_id) 
		where i.submitted > date_sub(now(), interval 7 day) $sql_where
		group by i.user_id 
		having (geographs > 0 or pending > 0)
		order by geographs desc $sql_orderby, pending desc ";
		if ($_GET['debug'])
			print $sql;
		$topusers=$db->GetAssoc($sql);
	}		
	//assign an ordinal

	$i=1;$lastgeographs = '?';
	$geographs = 0;
	$pending = 0;
	$points = 0;
	foreach($topusers as $user_id=>$entry)
	{
		if ($lastgeographs == $entry['geographs'])
			$topusers[$user_id]['ordinal'] = '&quot;&nbsp;&nbsp;&nbsp;';
		else {
			$topusers[$user_id]['ordinal'] = smarty_function_ordinal($i);
			$lastgeographs = $entry['geographs'];
		}
		$i++;
		$geographs += $entry['geographs'];
		$pending += $entry['pending'];
		$points += $entry['points'];
		if (empty($entry['points'])) $topusers[$user_id]['points'] = '';
	}	
	
	
	$smarty->assign('geographs', $geographs);
	$smarty->assign('pending', $pending);
	$smarty->assign('points', $points);
	
	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('cutoff_time', time()-86400*7);
	
	$smarty->assign('types', array('tpoints','first','second','allpoints','personal','images','depth'));
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
