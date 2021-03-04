<?php
/**
 * $Project: GeoGraph $
 * $Id: breakdown.php 7980 2013-09-02 11:43:46Z geograph $
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

$by = (isset($_GET['by']) && preg_match('/^\w+$/' , $_GET['by']))?$_GET['by']:'myriad';

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$u = (isset($_GET['u']) && is_numeric($_GET['u']))?intval($_GET['u']):0;

$order = (isset($_GET['order']) && preg_match('/^\w+$/' , $_GET['order']))?$_GET['order']:'';

$i=(!empty($_GET['i']))?intval($_GET['i']):'';

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';


$template='statistics_breakdown.tpl';
$cacheid='statistics|'.$i.$by.'_'.$ri.'_'.$u.'_'.$order.$when;


if (isset($_GET['since']) && preg_match("/^\d+-\d+-\d+$/",$_GET['since']) ) {
	$sql_crit = " AND upd_timestamp >= '{$_GET['since']}'";
	$link .= "since={$_GET['since']}&amp;";
	$cacheid.=md5($sql_crit);
} elseif (isset($_GET['last']) && preg_match("/^\d+ \w+$/",$_GET['last']) ) {
	$_GET['last'] = preg_replace("/s$/",'',$_GET['last']);
	$sql_crit = " AND upd_timestamp > date_sub(now(), interval {$_GET['last']})";
	$link .= "last={$_GET['last']}&amp;";
	$cacheid.=md5($sql_crit);
} elseif ($when) {
	if (strlen($when) == 7) {
		$sql_crit = " and submitted < DATE_ADD('$when-01',interval 1 month)";
	} elseif (strlen($when) == 4) {
		$sql_crit = " and submitted < DATE_ADD('$when-01-01',interval 1 year)";
	} else {
		$sql_crit = " and submitted < '$when'";
	}
	$link .= "when=$when&amp;";
	$smarty->assign_by_ref('when',$when);
	$smarty->assign('whenname',getFormattedDate($when));
} else {
	$sql_crit = '';
}

if ($ri) {
	$sql_crit .= " AND reference_index = $ri";
	$link .= "ri=$ri&amp;";
}

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*24; //24hr cache

$smarty->assign_by_ref('references',$CONF['references_all']);	

$bys = array('status' => 'Classification','class' => 'Category','takenyear' => 'Date Taken (Year)','taken' => 'Date Taken (Month)','myriad' => 'Myriad (100km Square)','user' => 'Contributor');
if (empty($i) && empty($when) && empty($sql_crit)) {
	//these are likely to be furfilled by (user_)date_stat, so we we can be more permissive now!
	$bys['submittedyear'] = "Submitted (Year)";
	$bys['submitted'] = "Submitted (Month)";
	$bys['hectad'] = "Hectad (10km Square)";
}
$smarty->assign_by_ref('bys',$bys);

$smarty->assign('by', $by);

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$smarty->assign('ri', $ri);

	$mysql_fields = '';
	if ($by == 'status') {
		$sql_group = $sql_fieldname = "CONCAT(IF(ftf BETWEEN 1 AND 4,ELT(ftf,'first ','second ','third ','fourth '),''),moderation_status)";
	} else if ($by == 'class') {
		$sql_group = $sql_fieldname = 'imageclass';
		$smarty->assign('linkprefix', "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;imageclass=");
	} else if ($by == 'myriad' || $by == 'gridsq') {
		$by = 'myriad';
		$smarty->assign('linkprefix', "/search.php?".($u?"u=$u&amp;":'')."gridsquare=");
		if ($ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			$sql_group = $sql_fieldname = "SUBSTRING(gi.grid_reference,1,$letterlength)";
		} else {
			$sql_group = $sql_fieldname = "SUBSTRING(gi.grid_reference,1,3 - reference_index)";
		}
	} else if ($by == 'hectad') {
		$smarty->assign('linkprefix', "/search.php?".($u?"user_id=$u&amp;":'')."&do=1&searchtext=hectad:");
		if ($ri) {
			$letterlength = 3 - $ri; #should this be auto-realised by selecting a item from gridprefix?
			$ll1 = $letterlength+1;
			$ll3 = $letterlength+3;
			$sql_group = $sql_fieldname = "concat(substring(gi.grid_reference,1,$ll1),substring(gi.grid_reference,$ll3,1))";
		} else {
			$sql_group = $sql_fieldname = "concat(substring(gi.grid_reference,1,length(gi.grid_reference)-3),substring(gi.grid_reference,length(gi.grid_reference)-1,1))";
		}

	} else if ($by == 'taken') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(imagetaken,1,7)";
	} else if ($by == 'takenyear') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(imagetaken,1,4)";
	} else if ($by == 'takenday') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "imagetaken";

	} else if ($by == 'submitted') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(submitted,1,7)";
	} else if ($by == 'submittedyear') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(submitted,1,4)";
	} else if ($by == 'submittedday') {
		$smarty->assign('linkpro', 1);
		$sql_group = $sql_fieldname = "SUBSTRING(submitted,1,10)";

	} else if ($by == 'user') {
		$smarty->assign('linkpro', 1);
		$sql_group = "user_id";
		$sql_fieldname = "realname";
		$mysql_fields = ',gi.user_id';
	} else if ($by == 'count') {
		$sql_group = $sql_fieldname = "imagecount";
	} else {
		$by = 'status';
		$sql_group = $sql_fieldname = 'moderation_status';
	}

	$smarty->assign('title', $bys[$by]);

	$title = "Breakdown of Photos by ".$bys[$by];
	if ($when)
		$title .= ", March 2005 though ".getFormattedDate($when);
	if ($ri)
		$title .= " in ".$CONF['references_all'][$ri];
	$link = "by=$by";

	if ($i) {
		require_once('geograph/searchcriteria.class.php');
		require_once('geograph/searchengine.class.php');
		
		$engine = new SearchEngine($i);
		if (empty($engine->criteria)) {
			print "Invalid search";
			exit;
		}
		$engine->criteria->getSQLParts();
		extract($engine->criteria->sql,EXTR_PREFIX_ALL^EXTR_REFS,'sql');

		if (preg_match("/(left |inner |)join ([\w\,\(\) \.\'!=]+) where/i",$sql_where,$matches)) {
			$sql_where = preg_replace("/(left |inner |)join ([\w\,\(\) \.!=\']+) where/i",'',$sql_where);
			$sql_from .= " {$matches[1]} join {$matches[2]}";
		}
		
		if (preg_match("/group by ([\w\,\(\) ]+)/i",$sql_where)) {
			print "Unable to run on this search";
			exit;
		}
		
		if (!empty($sql_where)) {
			$sql_where = "AND ($sql_where)";
			$engine->islimited = true;
		}
		
		if (strpos($sql_where,'gs') !== FALSE) {
			$sql_where = str_replace('gs.','gi.',$sql_where);
		}
		if (strpos($sql_from,'gs') !== FALSE) {
			$sql_from = str_replace('gs.','gi.',$sql_from);
		}
		
		
		$engine->criteria->searchdesc = preg_replace("/, in [\w ]* order/",'',$engine->criteria->searchdesc);
		
		$title .= "<i>".htmlentities2($engine->criteria->searchdesc)."</i>";
		
		//preserve input
		$link .= "&amp;i=$i";
		$smarty->assign('i', $i);
	} elseif ($u) {
		$sql_from = '';
		$sql_where = " and user_id = $u";
		$link .= "&amp;u=$u";
		$smarty->assign_by_ref('u', $u);


		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$title .= " for ".htmlentities2($profile->realname);
	} else {
		$sql_from = '';
		$sql_where = '';
	}
	$smarty->assign_by_ref('link', str_replace(' ','+',$link));
	$smarty->assign_by_ref('h2title', $title);

	if (strpos($order,'2') !== FALSE) {
		$sql_dir = ' DESC';
		$jsdir = 'desc';
	} else {
		$sql_dir = '';
		$jsdir = 'asc';
	}
	$smarty->assign_by_ref('jsdir', $jsdir);

	if (strpos($order,'c') !== FALSE) {
		$mysql_order = "ORDER BY c$sql_dir";
		$smarty->assign('order', 'c');
	} else {
		$mysql_order = "ORDER BY field$sql_dir";
	}

	$cacheseconds = 3600*12;

	//we now have a wide range of pre-grouped tables! (some can even be used with user and/or ri filter!) 
		//todo, the date tables, could cope with (some!) 'when' filtering!
	###########################################

	if ($sql_where == " and user_id = $u" && $sql_crit == '') {
		$date_table = 'user_date_stat';
	} elseif ($sql_where == '' && ($sql_crit == '' || $sql_crit = " AND reference_index = $ri")) {
		$date_table = 'date_stat';
		//note ALWAYS filter by $ri, as this table copes needs =0 for both grids! (its pre-agrigated by $ri with rollup)
		$sql_where = " AND reference_index = $ri"; 
	}

	###########################################

	if ($by == 'class' && $sql_where == '' && $sql_crit == '' && $sql_from == '') {
		$sql = "select $sql_fieldname as field,c from category_stat $mysql_order limit 5000";	

	###########################################

	} elseif (($by == 'myriad' || $by == 'gridsq') && $sql_where == " and user_id = $u" && ($sql_crit == '' || $sql_crit = " AND reference_index = $ri") && $sql_from == '') {
		$sql_group = $sql_fieldname = "substring(hectad,1,3 - reference_index)";
		$sql = "select $sql_fieldname as field,SUM(images) as c from hectad_user_stat WHERE squares > 0 $sql_where $sql_crit GROUP BY $sql_group $mysql_order";	

	} elseif (($by == 'myriad' || $by == 'gridsq') && $sql_where == '' && ($sql_crit == '' || $sql_crit = " AND reference_index = $ri") && $sql_from == '') {
		$sql = "select prefix as field,imagecount as c from gridprefix WHERE landcount > 0 $sql_crit $mysql_order";	

	###########################################

	} elseif ($by == 'hectad' && $sql_where == " and user_id = $u" && ($sql_crit == '' || $sql_crit = " AND reference_index = $ri") && $sql_from == '') {
		$sql = "select hectad as field,images as c from hectad_user_stat WHERE squares > 0 $sql_where $sql_crit $mysql_order";

	} elseif ($by == 'hectad' && $sql_where == '' && ($sql_crit == '' || $sql_crit = " AND reference_index = $ri") && $sql_from == '') {
		$sql = "select hectad as field,images as c from hectad_stat WHERE landsquares > 0 $sql_crit $mysql_order";

	###########################################

	} elseif ($by == 'submitted' && !empty($date_table) && $sql_from == '') { 
		$sql = "select month as field,images as c from $date_table WHERE month != '' AND type = 'submitted' $sql_where $mysql_order limit 5000";

	} elseif ($by == 'taken' && !empty($date_table) && $sql_from == '') { 
		$sql = "select month as field,images as c from $date_table WHERE month != '' AND type = 'imagetaken' $sql_where $mysql_order limit 5000";	

	###########################################

	} elseif ($by == 'submittedyear' && !empty($date_table) && $sql_from == '') { 
		$sql = "select year as field,images as c from $date_table WHERE month = '' AND type = 'submitted' $sql_where $mysql_order";

	} elseif ($by == 'takenyear' && !empty($date_table) && $sql_from == '') { 
		$sql = "select year as field,images as c from $date_table WHERE month = '' AND type = 'imagetaken' $sql_where $mysql_order";

	###########################################

	} elseif ($by == 'takenday' && $sql_where == '' && $sql_crit == '' && $sql_from == '') {
		$sql = "select $sql_fieldname as field,images as c from imagetaken_stat $mysql_order limit 5000";	

	###########################################

	} elseif ($by == 'user' && $sql_where == '' && $sql_crit == " AND reference_index = $ri" && $sql_from == '') {
		$sql = "select $sql_fieldname as field, sum(images) as c, user_id from hectad_user_stat inner join user using (user_id) WHERE 1 $sql_crit group by user_id $mysql_order";

	} elseif ($by == 'user' && $sql_where == '' && $sql_crit == '' && $sql_from == '') {
		$sql = "select $sql_fieldname as field, images as c, user_id from user_stat inner join user using (user_id) $mysql_order limit 5000";
		$cacheseconds = 600; //this table updates hourly

	###########################################

	} else {
		$sql = "select 
		$sql_fieldname as field,
		count(*) as c $mysql_fields
		from gridimage_search as gi $sql_from
		where 1 $sql_where
		 $sql_crit
		group by $sql_group 
		$mysql_order
		limit 5000";
	}

	if ($_GET['debug'])
		print $sql;

	###########################################

	$breakdown=$db->cacheGetAll($cacheseconds,$sql);
	$total = 0;
	foreach($breakdown as $idx=>$entry) {
		$total += $entry['c'];
	}
	if ($total > 0) {
		$totalperc = 100 /$total;

		foreach($breakdown as $idx=>$entry)
		{
			$breakdown[$idx]['per'] = sprintf("%.2f",$breakdown[$idx]['c'] * $totalperc);
		}

		if ($by == 'status') {
			foreach($breakdown as $idx=>$entry) {
				$breakdown[$idx]['field'] = ($entry['field'] == 'accepted')?'Supplemental':ucwords($entry['field']);
			}
		} elseif ($by == 'user') {
			foreach($breakdown as $idx=>$entry) {
				$y = $breakdown[$idx]['field'];

				$breakdown[$idx]['link'] = "/profile/".$entry['user_id'];
			}
		} elseif ($by == 'takenyear' || $by == 'submittedyear') {
			$kk = preg_replace('/(year|day)/','',$by);

			foreach($breakdown as $idx=>$entry) {
				$y = $entry['field'];

				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;{$kk}_endYear=$y&amp;{$kk}_startYear=$y&amp;orderby=imagetaken&amp;do=1";
				if ($y < 100) {
					$breakdown[$idx]['field'] = ''; //ie unspecified!
				}
			}
		} elseif ($by == 'taken' || $by == 'submitted') {
			$kk = preg_replace('/(year|day)/','',$by);

			foreach($breakdown as $idx=>$entry) {
				list($y,$m)=explode('-', $entry['field']);

				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;{$kk}_endMonth=$m&amp;{$kk}_endYear=$y&amp;{$kk}_startMonth=$m&amp;{$kk}_startYear=$y&amp;orderby=imagetaken&amp;do=1";

				if ($m>0) {
					//well, it saves having an array of months...
					$t=strtotime("2000-$m-01");
					if ($y > 0) {
						$breakdown[$idx]['field']=strftime("%B", $t)." $y";
					} else {
						$breakdown[$idx]['field']=strftime("%B", $t);
					}
				} elseif ($y > 0) {
					$breakdown[$idx]['field']=$y;
				} else {
					$breakdown[$idx]['field'] = ''; //ie unspecified!
				}
			}
		} elseif ($by == 'takenday' || $by == 'submittedday') {
			$kk = preg_replace('/(year|day)/','',$by);

			foreach($breakdown as $idx=>$entry) {
				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;{$kk}_start={$entry['field']}&amp;{$kk}_end={$entry['field']}&amp;orderby=$kk&amp;do=1";
			}
		}
	}

	$smarty->assign_by_ref('total', $total);
	$smarty->assign('breakdown_count', count($breakdown));
	$smarty->assign_by_ref('breakdown', $breakdown);
} else {
	//bare minimum for the dynamic section
	if ($u) {
		$profile=new GeographUser($u);
		$smarty->assign_by_ref('profile', $profile);
		$smarty->assign_by_ref('u', $u);
	}
}


$smarty->display($template, $cacheid);

	
