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

$UTF8PAGE=true;
require_once('geograph/global.inc.php');
include_messages('leaderboard');
init_session();


$type = (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type']))?$_GET['type']:'points';

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$date = (isset($_GET['date']) && ctype_lower($_GET['date']))?$_GET['date']:'submitted';

$timerel = (isset($_GET['timerel']) && in_array($_GET['timerel'], array('during','dbefore','dafter','between'))) ? $_GET['timerel'] : 'during';

if (!empty($_GET['whenYear'])) {
	if (!empty($_GET['whenMonth'])) {
		$_GET['when'] = sprintf("%04d-%02d",$_GET['whenYear'],$_GET['whenMonth']);
	} else {
		$_GET['when'] = sprintf("%04d",$_GET['whenYear']);
	}
}

if (!empty($_GET['when2Year'])) {
	if (!empty($_GET['when2Month'])) {
		$_GET['when2'] = sprintf("%04d-%02d",$_GET['when2Year'],$_GET['when2Month']);
	} else {
		$_GET['when2'] = sprintf("%04d",$_GET['when2Year']);
	}
}

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';
$when2 = (isset($_GET['when2']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when2']))?$_GET['when2']:'';
if (!empty($_GET['when']) && !isset($_GET['whenYear']))  @list($_GET['whenYear'], $_GET['whenMonth'], $_GET['whenDay'])  = explode('-', $_GET['when']);
if (!empty($_GET['when2']) && !isset($_GET['when2Year'])) @list($_GET['when2Year'],$_GET['when2Month'],$_GET['when2Day']) = explode('-', $_GET['when2']);

if ($timerel == 'during' || $timerel == 'dbefore') {
	$_GET['when2Year'] = isset($_GET['whenYear']) ? $_GET['whenYear'] : '';
	$_GET['when2Month'] = isset($_GET['whenMonth']) ? $_GET['whenMonth'] : '';
	$when2 = $when;
}
if ($timerel == 'dbefore') {
	$_GET['whenYear'] = '';
	$_GET['whenMonth'] = '';
	$when = '';
} elseif ($timerel == 'dafter') {
	$_GET['when2Year'] = '';
	$_GET['when2Month'] = '';
	$when2 = '';
}

if (!empty($_GET['whenYear'])) {
	$year = intval($_GET['whenYear']);
	if (!empty($_GET['whenMonth'])) {
		$month = intval($_GET['whenMonth']);
	} else {
		$month = 1;
	}
	$whenlow = sprintf("%04d-%02d-01",$year,$month);
}

if (!empty($_GET['when2Year'])) {
	$year = intval($_GET['when2Year']);
	if (!empty($_GET['when2Month'])) {
		$month = intval($_GET['when2Month']);
		if ($month != 12) {
			$month += 1;
		} else {
			$month = 1;
			$year += 1;
		}
	} else {
		$month = 1;
		$year += 1;
	}
	$whenhigh = sprintf("%04d-%02d-01",$year,$month);
}

trigger_error("$timerel, {$_GET['whenYear']}-{$_GET['whenMonth']} ... {$_GET['when2Year']}-{$_GET['when2Month']} : {$_GET['when']} ... {$_GET['when2']} : $whenlow ... $whenhigh : $when ... $when2", E_USER_NOTICE);

$timedesc = '';
$timesql = '';
$timesqlrel = '';
if ($timerel == 'between') {
	if (!empty($when) && !empty($when2)) {
		$timesqlrel = "BETWEEN YEAR('$whenlow')*12+MONTH('$whenlow') AND YEAR('$whenhigh')*12+MONTH('$whenhigh')-1";
		$timedescrel = sprintf($MESSAGES['leaderboard']['time_between'], getFormattedDate($when), getFormattedDate($when2));
	}
} else if ($timerel == 'during') {
	if (!empty($when) && !empty($when2)) {
		$timesqlrel = "BETWEEN YEAR('$whenlow')*12+MONTH('$whenlow') AND YEAR('$whenhigh')*12+MONTH('$whenhigh')-1";
		$timedescrel = sprintf($MESSAGES['leaderboard']['time_during'], getFormattedDate($when));
	}
} else if ($timerel == 'dbefore') {
	if (!empty($when2)) {
		$timesqlrel = "< YEAR('$whenhigh')*12+MONTH('$whenhigh')";
		$timedescrel = sprintf($MESSAGES['leaderboard']['time_until'], getFormattedDate($when2));
	}
} else if ($timerel == 'dafter') {
	if (!empty($when)) {
		$timesqlrel = ">= YEAR('$whenlow')*12+MONTH('$whenlow')";
		$timedescrel = sprintf($MESSAGES['leaderboard']['time_from'], getFormattedDate($when));
	}
}
if ($timesqlrel !== '') {
	if ($date == 'both') {
		$timesql = " and year(imagetaken) != 0 and year(submitted) != 0 and month(imagetaken) != 0 and month(submitted) != 0 and year(imagetaken)*12+month(imagetaken) $timesqlrel and year(submitted)*12+month(submitted) $timesqlrel";
		$timedesc = sprintf($MESSAGES['leaderboard']['taken_submitted'], $timedescrel);
	} else {
		$column = ($date == 'taken')?'imagetaken':'submitted';
		$timesql = " and year($column) != 0 and month($column) != 0 and year($column)*12+month($column) $timesqlrel";
		$timedesc = sprintf($MESSAGES['leaderboard'][$date == 'taken' ? 'taken' : 'submitted'], $timedescrel);
	}
}

if (isset($_GET['region']) &&  preg_match('/^\d+_\d+$/',$_GET['region'])) {
	list($level,$cid) = explode('_',$_GET['region']);
	$level = intval($level);
	$cid = intval($cid);
	$has_region = in_array($level, $CONF['hier_statlevels']);
} else {
	$has_region = false;
}

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(250,intval($_GET['limit'])):150;

$myriad = (isset($_GET['myriad']) && ctype_upper($_GET['myriad']))?$_GET['myriad']:'';


$minimum = (isset($_GET['minimum']) && is_numeric($_GET['minimum']))?intval($_GET['minimum']):25;
$maximum = (isset($_GET['maximum']) && is_numeric($_GET['maximum']))?intval($_GET['maximum']):0;



$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

if (isset($_GET['me']) && $USER->registered) {
	$u = $USER->user_id;
}

$smarty = new GeographPage;

if (isset($_GET['inner'])) {
	$template='statistics_leaderboard_inner.tpl';
} else {
	$template='statistics_leaderboard.tpl';
}
$cacheid=$minimum.'-'.$maximum.$type.$date.$when.':'.$when2.$timerel.$limit.'.'.$ri.'.'.$u.$myriad.':'.($has_region?($level.'_'.$cid):'').':';

#$smarty->caching = 0;
if ($smarty->caching) {
	$smarty->caching = 2; // lifetime is per cache
	$smarty->cache_lifetime = 3600*3; //3hour cache
}

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$filtered = ($when || $when2 || $ri || $myriad || $has_region);
	
	$db=GeographDatabaseConnection();
	if ($has_region) {
		$region_name = $db->GetOne("select name from loc_hier where level=$level and community_id=$cid");
		$sql_table = "gridimage_search i inner join gridsquare_percentage using (gridsquare_id)";
		$sql_where = "level=$level and community_id=$cid and percent>0";
	} else {
		$sql_table = "gridimage_search i";
		$sql_where = "1";
	}
	$sql_orderby = '';
	$sql_column = "count(*)";
	$sql_having_having = '';
	if ($maximum) {
		$minimax = sprintf($MESSAGES['leaderboard']['minimax'], $minimum, $maximum);
		$sql_minimax = "between $minimum and $maximum";
	} else {
		$minimax = sprintf($MESSAGES['leaderboard']['minimum'], $minimum);
		$sql_minimax = "> $minimum";
	}

	$sql_qtable_filtered = array (
		'squares' => array(
			'column' => "count(distinct grid_reference)",
		),
		'geosquares' => array(
			'column' => "count(distinct grid_reference)",
			'where' => "i.moderation_status='geograph'",
		),
		'geographs' => array(
			'where' => "i.moderation_status='geograph'",
		),
		'additional' => array(
			'where' => "i.moderation_status='geograph' and ftf = 0",
		),
		'supps' => array(
			'where' => "i.moderation_status='accepted'",
		),
		'images' => array(
			'column' => "sum(i.ftf=1 and i.moderation_status='geograph') as points, count(*)",
			'where' => "1",
			'orderby' => ",points desc",
		),
		'test_points' => array(
			'column' => "sum((i.moderation_status = 'geograph') + ftf + 1)",
		),
		'reverse_points' => array(
			'column' => "count(*) as images, count(*)/(sum(ftf=1)+1)",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'depth' => array(
			'column' => "count(*)/count(distinct grid_reference)",
			'having_having' => "having count(*) $sql_minimax",
			'isfloat' => true,
		),
		'depth2' => array(
			'column' => "round(pow(count(*),2)/count(distinct grid_reference))",
			'having_having' => "having count(*) > $minimum",
		),
		'myriads' => array(
			'column' => "count(distinct substring(grid_reference,1,length(grid_reference) - 4))",
		),
		'antispread' => array(
			'column' => "count(*)/count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )",
			'isfloat' => true,
		),
		'spread' => array(
			'column' => "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )/count(*)",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'hectads' => array(
			'column' => "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )",
		),
		'days' => array(
			'column' => "count(distinct imagetaken)",
		),
		'classes' => array(
			'column' => "count(distinct imageclass)",
		),
		'clen' => array(
			'column' => "avg(length(comment))",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'tlen' => array(
			'column' => "avg(length(title))",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'category_depth' => array(
			'column' => "count(*)/count(distinct imageclass)",
			'isfloat' => true,
		),
		'centi' => array(
		//NOT USED AS REQUIRES A NEW INDEX ON gridimage!
			'table' => "gridimage i ",
			'column' => "COUNT(DISTINCT nateastings div 100, natnorthings div 100)",
			'where' => "i.moderation_status='geograph' and nateastings div 1000 > 0",
		),
		'points' => array(
			'where' => "i.ftf=1 and i.moderation_status='geograph'",
		),
	);
	$sql_qtable_unfiltered = array (
		'squares' => array(
			'table' => "user_stat i",
			'column' => "squares",
		),
		'geosquares' => array(
			'table' => "user_stat i",
			'column' => "geosquares",
		),
		'geographs' => array(
			'table' => "user_stat i",
			'column' => "geographs",
		),
		'additional' => array(
			'where' => "i.moderation_status='geograph' and ftf = 0",
		),
		'supps' => array(
			'table' => "user_stat i",
			'column' => "images-geographs",
		),
		'images' => array(
			'table' => "user_stat i",
			'column' => "points, images",
			'orderby' => ",points desc",
		),
		'test_points' => array(
			'table' => "user_stat i",
			'column' => "images, images/(points+1)",
			'isfloat' => true,
		),
		'reverse_points' => array(
			'table' => "user_stat i",
			'column' => "images, images/(points+1)",
			'having_having' => "having images > $minimum",
			'isfloat' => true,
		),
		'depth' => array(
			'table' => "user_stat i",
			'column' => "images, depth",
			'having_having' => "having images $sql_minimax",
			'isfloat' => true,
		),
		'depth2' => array(
			'column' => "round(pow(images,2)/squares)",
			'having_having' => "having images > $minimum",
			'isfloat' => true,
		),
		'myriads' => array(
			'table' => "user_stat i",
			'column' => "myriads",
		),
		'antispread' => array(
			'table' => "user_stat i",
			'column' => "images/hectads",
			'isfloat' => true,
		),
		'spread' => array(
			'table' => "user_stat i",
			'column' => "hectads/images",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'hectads' => array(
			'table' => "user_stat i",
			'column' => "hectads",
		),
		'days' => array(
			'table' => "user_stat i",
			'column' => "days",
		),
		'classes' => array(
			'column' => "count(distinct imageclass)",
		),
		'clen' => array(
			'column' => "avg(length(comment))",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'tlen' => array(
			'column' => "avg(length(title))",
			'having_having' => "having count(*) > $minimum",
			'isfloat' => true,
		),
		'category_depth' => array(
			'column' => "count(*)/count(distinct imageclass)",
			'isfloat' => true,
		),
		'centi' => array(
		//NOT USED AS REQUIRES A NEW INDEX ON gridimage!
			'table' => "gridimage i ",
			'column' => "COUNT(DISTINCT nateastings div 100, natnorthings div 100)",
			'where' => "i.moderation_status='geograph' and nateastings div 1000 > 0",
		),
		'content' => array(
			'table' => "user_stat i",
			'column' => "content",
			'where' => "content > 0",
		),
		'points' => array(
			'table' => "user_stat i",
			'column' => "depth,points",
		),
	);

	if ($filtered) {
		$sql_qtable =& $sql_qtable_filtered;
	} else {
		$sql_qtable =& $sql_qtable_unfiltered;
	}

	if (!isset($sql_qtable[$type])) {
		$type = 'points';
	}

	$isfloat = false;
	if (isset($sql_qtable[$type]['isfloat'])) $isfloat = $sql_qtable[$type]['isfloat'];

	if (isset($sql_qtable[$type]['column'])) $sql_column = $sql_qtable[$type]['column'];
	if (isset($sql_qtable[$type]['having_having'])) $sql_having_having = $sql_qtable[$type]['having_having'];
	if (isset($sql_qtable[$type]['where'])) $sql_where .= ' and '. $sql_qtable[$type]['where'];
	if (isset($sql_qtable[$type]['table'])) $sql_table = $sql_qtable[$type]['table'];
	if (isset($sql_qtable[$type]['orderby'])) $sql_orderby = $sql_qtable[$type]['orderby'];

	$heading = $MESSAGES['leaderboard']['headings'][$type];
	$desc = str_replace(array('@minimum', '@minimax'), array($minimum, $minimax), $MESSAGES['leaderboard']['descriptions'][$type]);

	$sql_where .= $timesql;
	$desc .= $timedesc;
	if ($has_region) {
		$desc .= sprintf($MESSAGES['leaderboard']['in_region'], $region_name);
	}
	if ($myriad) {
		$sql_where .= " and grid_reference LIKE '{$myriad}____'";
		$desc .= sprintf($MESSAGES['leaderboard']['in_myriad'], $myriad);
	}
	if ($ri) {
		$sql_where .= " and reference_index = $ri";
		$desc .= sprintf($MESSAGES['leaderboard']['in_grid'], $CONF['references_all'][$ri]);
	}
	
	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);
	$smarty->assign('isfloat', $isfloat);

	if ($sql_table != 'user_stat i') {
		$sql_column = "max(gridimage_id) as last,$sql_column";
	}
	$limit2 = intval($limit * 1.6);
	$sql="select 
	i.user_id,u.realname, $sql_column as imgcount
	from $sql_table inner join user u using (user_id)
	where $sql_where
	group by user_id 
	$sql_having_having
	order by imgcount desc $sql_orderby,last asc limit $limit2";
	#trigger_error("-----$sql", E_USER_NOTICE);
	$topusers=$db->GetAll($sql);
	$lastimgcount = 0;
	$toriserank = 0;
	$points = 0;
	$images = 0;
	foreach($topusers as $idx=>$entry)
	{
		$i=$idx+1;
			
		if ($lastimgcount == $entry['imgcount']) {
			if ($u && $u == $entry['user_id']) {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
			} elseif ($i > $limit) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
			}
		} else {
			$toriserank = ($lastimgcount - $entry['imgcount']);
			if ($u && $u == $entry['user_id']) {
                                $topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
                        } elseif ($i > $limit) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
				if (isset($entry['points']))
					$points += $entry['points'];
				#if ($points && empty($entry['points'])) $topusers[$idx]['points'] = ''; // this would also be needed in the other branch
				if (isset($entry['images']))
					$images += $entry['images'];
				#if ($images && empty($entry['images'])) $topusers[$idx]['images'] = '';
			}
			$lastimgcount = $entry['imgcount'];
			$lastrank = $i;

		}
	}
	
	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('points', $points);
	$smarty->assign('images', $images);


	$smarty->assign('types', array('points','geosquares','images','depth'));
	$smarty->assign('typenames', $MESSAGES['leaderboard']['type_names']);
	
	
	$extra = array();
	$extralink = '';
	
	foreach (array('when','when2','timerel','date','ri','myriad','region') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
			$extralink .= "&amp;$key={$_GET[$key]}";
		}
	}
	$smarty->assign_by_ref('extra',$extra);	
	$smarty->assign_by_ref('extralink',$extralink);	
	$smarty->assign_by_ref('limit',$limit);	
	
	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

	
?>
