<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
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
$USER->mustHavePerm("basic");

//customExpiresHeader(3600,false,true);

//	$smarty->assign('responsive',true);

	//$db = GeographDatabaseConnection(true);
	$sph = GeographSphinxConnection('sphinxql',true);
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

$links = array(
	'' => 'All Time',
	'recent=1' => 'In Last Year',
	'five=1' => 'In Last 5 Years',
	'oldest=1'=> 'Oldest',
	'newest=1'=> 'Newest',
	'random=1'=> 'Random',
	'random=1&recent=1'=> 'Recent Random',
	'random2=1'=> 'Alternative',
	'spread=1'=> 'Geographical',
);
$smarty->assign('links',$links);
$smarty->assign('selected', trim($_SERVER['QUERY_STRING'] ?? '','&'));

$cacheid = md5($_SERVER['QUERY_STRING'] ?? '');

##############################
$where = array();
$select = '';
$order = "score desc"; //this is the 'within' order!!

$where[] = "user_id = ".$USER->user_id;
$smarty->assign('user_id',intval($USER->user_id));


if (!empty($_GET['recent'])) {
	$db = GeographDatabaseConnection(true);
	$days = $db->getOne("select to_days(date_sub(now(),interval 400 day))");

	$where[] = "takendays > $days";
}
if (!empty($_GET['five'])) {
	$db = GeographDatabaseConnection(true);
	$days = $db->getOne("select to_days(date_sub(now(),interval 5 year))");

	$where[] = "takendays > $days";
}

if (!empty($_GET['random']))	 $order = "hash asc";
elseif (!empty($_GET['random'])) $order = "hash desc";
elseif (!empty($_GET['spread'])) $order = "sequence asc";
elseif (!empty($_GET['oldest'])) $order = "takendays asc";
elseif (!empty($_GET['newest'])) $order = "takendays desc";


		$sql = "select monthname,count(*) as images, score,  id,title,realname,grid_reference,takenyear,user_id $select
			 from sample8 where ".implode(' and ',$where)." group 4 by monthname within group order by $order limit 100";

		$imagelist = new ImageList();
		$imagelist->_setSph($sph);

		//use this as will create us proper image objects!
		$count = $imagelist->getImagesBySphinxQL($sql, true);

##############################

$monthOrder = [
    'January'   => 1,
    'February'  => 2,
    'March'     => 3,
    'April'     => 4,
    'May'       => 5,
    'June'      => 6,
    'July'      => 7,
    'August'    => 8,
    'September' => 9,
    'October'   => 10,
    'November'  => 11,
    'December'  => 12,
'Unknown' => 13,
];

// Use usort() with a custom comparison function
usort($imagelist->images, function($a, $b) use ($monthOrder) {
    // First, compare by month name using the custom order
    $monthComparison = $monthOrder[$a->monthname] <=> $monthOrder[$b->monthname];

    // If the months are the same, sort by score in descending order
    if ($monthComparison === 0) {
        // Cast to integer for a proper numerical comparison
        return (int)$b->score <=> (int)$a->score;
    }

    // Return the result of the month comparison
    return $monthComparison;
});

	$smarty->assign_by_ref('images', $imagelist->images);

##############################

	if (!empty($_COOKIE['markedImages'])) {
		$ids = array_map('intval',explode(',',$_COOKIE['markedImages']));
		$ids = array_filter($ids);

		// 2. Check if the array is empty. If it is, there's nothing to query.
		if (!empty($ids)) {
			$placeholders = implode(',', array_fill(0, count($ids), '?'));

			$sql = "SELECT monthname, count(*) as images FROM sample8 WHERE id IN ($placeholders) GROUP BY monthname";
			$stats = $sph->getAll($sql, $ids);

usort($stats, function($a, $b) use ($monthOrder) {
    return $monthOrder[$a['monthname']] <=> $monthOrder[$b['monthname']];
});

			$smarty->assign_by_ref('stats',$stats);
			$smarty->assign('marked_count',count($ids));
		}
	}

##############################


$smarty->display('calendar_picker.tpl', $cacheid);


