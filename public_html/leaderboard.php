<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

$date = (isset($_GET['date']) && ctype_lower($_GET['date']))?intval($_GET['date']):'submitted';

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';


$smarty = new GeographPage;

$template='leaderboard.tpl';
$cacheid=$type.$date.$when;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //3hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  
	$sql_table = "gridimage_search i";
	$sql_column = "count(*)";
	$sql_having_having = '';
	if ($type == 'squares') {
		$sql_column = "count(distinct grid_reference)";
		$sql_where = "1";
		$heading = "Squares<br/>Photographed";
		$desc = "different squares photographed";
	} elseif ($type == 'geosquares') {
		$sql_column = "count(distinct grid_reference)";
		$sql_where = "i.moderation_status='geograph'";
		$heading = "Squares<br/>Geographed";
		$desc = "different squares geographed (aka Personal Points)";
	} elseif ($type == 'geographs') {
		$sql_where = "i.moderation_status='geograph'";
		$heading = "Geograph Images";
		$desc = "'geograph' images submitted";
	} elseif ($type == 'images') {
		$sql_where = "1";
		$heading = "Images";
		$desc = "images submitted";
	} elseif ($type == 'depth') {
		$sql_column = "count(*)/count(distinct grid_reference)";
		$sql_where = "1";
		$sql_having_having = "having count(*) > 25";
		$heading = "Depth";
		$desc = "the depth score, and having submitted over 25 images";
	} elseif ($type == 'myriads') {
		$sql_column = "count(distinct substring(grid_reference,1,3 - reference_index))";
		$sql_where = "1";
		$heading = "Myriads";
		$desc = "different myriads";
	} elseif ($type == 'days') {
		$sql_column = "count(distinct imagetaken)";
		$sql_where = "1";
		$heading = "Days";
		$desc = "different days";
	} elseif ($type == 'classes') {
		$sql_column = "count(distinct imageclass)";
		$sql_where = "1";
		$heading = "Categories";
		$desc = "different categories";
	} elseif ($type == 'centi') {
/*	SELECT COUNT(DISTINCT nateastings div 100, natnorthings div 100), COUNT(*) AS `_count_all`
	FROM gridimage
	WHERE  moderation_status in ('geograph','accepted') and nateastings div 1000 > 0
	ORDER BY _count_all DESC
	LIMIT 30; */
		//NOT USED AS REQUIRES A NEW INDEX ON gridimage!
		$sql_table = "gridimage i inner join user u using (user_id)";
		$sql_column = "COUNT(DISTINCT nateastings div 100, natnorthings div 100)";
		$sql_where = "i.moderation_status='geograph' and nateastings div 1000 > 0";
		$heading = "Centigraph<br/>Points";
		$desc = "centigraph points awarded";
	} else { #if ($type == 'points') {
		$sql_where = "i.ftf=1 and i.moderation_status='geograph'";
		$heading = "Geograph<br/>Points";
		$desc = "geograph points awarded";
		$type = 'points';
	} 
	
	if ($when) {

		$column = ($date == 'taken')?'imagetaken':'submitted';  
		$sql_where .= " and $column LIKE '$when%'";
		$iamge = new GridImage();
		$iamge->imagetaken = $when;
		$title = ($date == 'taken')?'taken':'submitted'; 
		$desc .= ", <b>for images $title during ".$iamge->getFormattedTakenDate()."</b>";
	}
	
	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);

	$topusers=$db->GetAll("select 
	i.user_id,realname, $sql_column as imgcount,max(gridimage_id) as last
	from $sql_table 
	where $sql_where
	group by user_id 
	$sql_having_having
	order by imgcount desc,last asc"); 
	$lastimgcount = 0;
	$toriserank = 0;
	foreach($topusers as $idx=>$entry)
	{
		$i=$idx+1;
			
		if ($lastimgcount == $entry['imgcount']) {
			if ($type == 'points' && !$when)
				$db->query("UPDATE user SET rank = $lastrank,to_rise_rank = $toriserank WHERE user_id = {$entry['user_id']}");
			if ($i > 50) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
			}
		} else {
			$toriserank = ($lastimgcount - $entry['imgcount']);
			if ($type == 'points' && !$when)
				$db->query("UPDATE user SET rank = $i,to_rise_rank = $toriserank WHERE user_id = {$entry['user_id']}");
			if ($i > 50) {
				unset($topusers[$idx]);
			} else {
				$units=$i%10;
				switch($units)
				{
					case 1:$end=($i==11)?'th':'st';break;
					case 2:$end=($i==12)?'th':'nd';break;
					case 3:$end=($i==13)?'th':'rd';break;
					default: $end="th";	
				}
				$topusers[$idx]['ordinal']=$i.$end;
			}
			$lastimgcount = $entry['imgcount'];
			$lastrank = $i;
		}
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	
	$smarty->assign('types', array('points','geosquares','images','depth'));
	
	
	$extra = array();
	$extralink = '';
	
	foreach (array('when','date') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
			$extralink .= "&amp;$key={$_GET[$key]}";
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
	$smarty->assign_by_ref('extralink',$extralink);	
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
