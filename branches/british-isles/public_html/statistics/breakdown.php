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

$bys = array('status' => 'Classification','class' => 'Category','takenyear' => 'Date Taken (Year)','taken' => 'Date Taken (Month)','myriad' => 'Myriad','user' => 'Contributor');
$smarty->assign_by_ref('bys',$bys);

$smarty->assign('by', $by);

if (!$smarty->is_cached($template, $cacheid))
{
	$db = GeographDatabaseConnection(true);
	
	$smarty->assign('ri', $ri);

	$mysql_fields = '';
	if ($by == 'status') {
		$sql_group = $sql_fieldname = "CONCAT(moderation_status,ELT(ftf+1, '',' (ftf)'))";
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
		$smarty->assign('linkprefix', "/search.php?".($u?"u=$u&amp;":'')."first=");
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
			$sql_where = "AND $sql_where";
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
	
$sql = "select 
$sql_fieldname as field,
count(*) as c $mysql_fields
from gridimage_search as gi $sql_from
where 1 $sql_where
 $sql_crit
group by $sql_group 
$mysql_order";
	if ($_GET['debug'])
		print $sql;
	$breakdown=$db->cacheGetAll(3600*6,$sql);
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
			$friendly = array('rejected' => 'Rejected', 'pending' => 'Pending', 'geograph (ftf)' => 'First Geograph', 'accepted' => 'Supplemental', 'geograph' => 'Geograph');
			foreach($breakdown as $idx=>$entry) {
				$breakdown[$idx]['field'] = $friendly[$entry['field']];
			}
		} elseif ($by == 'user') {
			foreach($breakdown as $idx=>$entry) {
				$y = $breakdown[$idx]['field'];

				$breakdown[$idx]['link'] = "/profile/".$entry['user_id'];
			}
		} elseif ($by == 'takenyear') {
			foreach($breakdown as $idx=>$entry) {
				$y = $entry['field'];

				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;taken_endYear=$y&amp;taken_startYear=$y&amp;orderby=imagetaken&amp;do=1";
				if ($y < 100) {
					$breakdown[$idx]['field'] = ''; //ie unspecified!
				}
			}
		} elseif ($by == 'taken') {
			foreach($breakdown as $idx=>$entry) {
				list($y,$m)=explode('-', $entry['field']);
				
				$breakdown[$idx]['link'] = "/search.php?".($u?"u=$u&amp;":'')."reference_index=$ri&amp;taken_endMonth=$m&amp;taken_endYear=$y&amp;taken_startMonth=$m&amp;taken_startYear=$y&amp;orderby=imagetaken&amp;do=1";
			
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

	
?>
