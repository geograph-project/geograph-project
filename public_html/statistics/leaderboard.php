<?php
/**
 * $Project: GeoGraph $
 * $Id: leaderboard.php 8908 2019-02-27 13:05:29Z barry $
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

init_session_or_cache(3600, 360); //cache publically, and privately

pageMustBeHTTPS();

if (isset($_GET['type']) && preg_match('/^\w+$/' , $_GET['type'])) {
        $type = $USER->setPreference('statistics.leaderboard.type',$_GET['type'],true);
} else {
        $type = $USER->getPreference('statistics.leaderboard.type','points',true);
}

$date = (isset($_GET['date']) && ctype_lower($_GET['date']))?$_GET['date']:'submitted';

if (!empty($_GET['whenYear'])) {
	if (!empty($_GET['whenMonth'])) {
		$_GET['when'] = sprintf("%04d-%02d",$_GET['whenYear'],$_GET['whenMonth']);
	} else {
		$_GET['when'] = sprintf("%04d",$_GET['whenYear']);
	}
}

$ri = (isset($_GET['ri']) && is_numeric($_GET['ri']))?intval($_GET['ri']):0;

$when = (isset($_GET['when']) && preg_match('/^\d{4}(-\d{2}|)(-\d{2}|)$/',$_GET['when']))?$_GET['when']:'';

$myriad = (isset($_GET['myriad']) && ctype_upper($_GET['myriad']))?$_GET['myriad']:'';
if (strlen($myriad) == 2) {
	$ri = 1;
} elseif (strlen($myriad) == 1) {
	$ri = 2;
}

$minimum = (isset($_GET['minimum']) && is_numeric($_GET['minimum']))?intval($_GET['minimum']):25;
$maximum = (isset($_GET['maximum']) && is_numeric($_GET['maximum']))?intval($_GET['maximum']):0;

//we dont treat 'when' as as filtered now, as have user_date_stat, based on user_stat
$filtered = ($ri || $myriad);

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min($filtered?250:1000,intval($_GET['limit'])):250;

$quarter = (isset($_GET['quarter']) && preg_match('/^\d{4}\/\d$/',$_GET['quarter']))?$_GET['quarter']:'';


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
$cacheid="statistics|".$minimum.'-'.$maximum.$type.$date.$when.$limit.'.'.$ri.'.'.$u.$myriad.'.'.$quarter;

$smarty->caching = 2; // lifetime is per cache
$smarty->cache_lifetime = 3600*3; //3hour cache

if (!$smarty->is_cached($template, $cacheid))
{
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db = GeographDatabaseConnection(true);

	if ($filtered) {
		$sql_table = "gridimage_search i";
	} elseif ($when && strlen($when)<=7) {
		$sql_table = "user_date_stat i";
	} else {
		$sql_table = "user_stat i";
	}
	$sql_where = "1";
	$sql_orderby = '';
	$sql_column = "count(*)";
	$sql_having_having = '';

	if ($type == 'squares') {
		if ($filtered) {
			$sql_column = "count(distinct grid_reference)";
		} else {
			$sql_column = "squares";
		}
		$heading = "Squares<br/>Photographed";
		$desc = "different squares photographed";

	} elseif ($type == 'geosquares') {
		if ($filtered) {
			$sql_column = "count(distinct grid_reference)";
			$sql_where = "i.moderation_status='geograph'";
		} else {
			$sql_column = "geosquares";
		}
		$heading = "Squares<br/>Geographed";
		$desc = "different squares geographed".(empty($when)?" (aka Personal Points)":'');

        } elseif ($type == 'personal') {
                if ($filtered) {
                        $sql_where = "i.ftf>0 and i.moderation_status='geograph'";
                } elseif ($sql_table == "user_date_stat i" && $when == '2021') {
			$sql_column = "personals"; //user_date_stat is already filtered, so can't just use geosquares.
		} else {
                        $sql_column = "geosquares"; //if NOT filtered, personal happens to be the same as geosquares.
                }
                $heading = "Personal<br/>Points";
                $desc = "personal points awarded".(empty($when)?" (aka different squares geographed)":'');

        } elseif ($type == 'revisit') {
                if ($filtered) {
                        $sql_column = "count(distinct grid_reference)-sum(i.ftf>0)";
                        $sql_where = "i.moderation_status='geograph'";
                } else {
                        $sql_column = "0"; //will always be zero!
                }
                $heading = "Revisited<br/>Squares";
                $desc = "squares geographed that been to before";

	} elseif ($type == 'tpoints') {
		if ($filtered) {
			$sql_where = "i.points='tpoint'";
		} else {
			$sql_column = "tpoints";
		}
		$heading = "TPoints";
		$desc = "'TPoints' awarded";

	} elseif ($type == 'tnf') {
		if ($filtered) {
			$sql_where = "i.points='tpoint' and ftf!=1";
		} else {
			$sql_column = "round(tpoints*1.0-points)";
		}
		$heading = "TPoints-Firsts";
		$desc = "number of TPoints minus number of Firsts";

	} elseif ($type == 'geographs') {
		if ($filtered) {
			$sql_where = "i.moderation_status='geograph'";
		} else {
			$sql_column = "geographs";
		}
		$heading = "Geograph Images";
		$desc = "'geograph' images submitted";

	} elseif ($type == 'additional') {
		$sql_where = "i.moderation_status='geograph' and ftf != 1";
		$heading = "Non-First Geograph Images";
		$desc = "non first 'geograph' images submitted";

	} elseif ($type == 'supps') {
		if ($filtered) {
			$sql_where = "i.moderation_status='accepted'";
		} else {
			$sql_column = "images-geographs";
		}
		$heading = "Supplemental Images";
		$desc = "'supplemental' images submitted";

	} elseif ($type == 'images') {
		if ($filtered) {
			$sql_column = "sum(i.ftf=1 and i.moderation_status='geograph') as points, count(*)";
			$sql_where = "1";
		} else {
			$sql_column = "points, images";
		}
		$sql_orderby = ',points desc';
		$heading = "Images";
		$desc = "images submitted";

	} elseif ($type == 'test_points') {
		if ($filtered) {
			$sql_column = "sum((i.moderation_status = 'geograph') + (ftf=1) + 1)";
		} else {
			$sql_column = "images, (geographs+points+images)";
		}
		$heading = "G-Points";
		$desc = "test points";

	} elseif ($type == 'reverse_points') {
		if ($filtered) {
			$sql_column = "count(*) as images, count(*)/(sum(ftf=1)+1)";
			$sql_having_having = "having count(*) > $minimum";
		} else {
			$sql_column = "images, images/(points+1)";
			$sql_having_having = "having images > $minimum";
		}
		$heading = "Depth";
		$desc = "the <b>approx</b> images/points ratio, and having submitted over $minimum images";

	} elseif ($type == 'depth') {
		if ($filtered) {
			$sql_column = "count(*)/count(distinct grid_reference)";
			if ($maximum) {
				$sql_having_having = "having count(*) between $minimum and $maximum";
				$desc = "the depth score, and having submitted between $minimum and $maximum images";
			} else {
				$sql_having_having = "having count(*) > $minimum";
				$desc = "the depth score, and having submitted over $minimum images";
			}
		} else {
			$sql_column = "images, depth";
			if ($maximum) {
				$sql_having_having = "having images between $minimum and $maximum";
				$desc = "the depth score, and having submitted between $minimum and $maximum images";
			} else {
				$sql_having_having = "having images > $minimum";
				$desc = "the depth score, and having submitted over $minimum images";
			}
		}
		$heading = "Depth";

	} elseif ($type == 'depth2') {
		if ($filtered) {
			$sql_column = "round(pow(count(*),2)/count(distinct grid_reference))";
			$sql_having_having = "having count(*) > $minimum";
		} else {
			$sql_column = "images, round(pow(images,2)/squares";
			$sql_having_having = "having images > $minimum";
		}
		$heading = "High Depth";
		$desc = "the depth score X images, and having submitted over $minimum images";

	} elseif ($type == 'myriads') {
		if ($filtered) {
			$sql_column = "count(distinct substring(grid_reference,1,3 - reference_index))";
		} else {
			$sql_column = "myriads";
		}
		$heading = "Myriads";
		$desc = "different myriads";

	} elseif ($type == 'antispread') {
		if ($filtered) {
			$sql_column = "count(*)/count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )";
		} else {
			$sql_column = "images/hectads";
		}
		$heading = "AntiSpread Score";
		$desc = "antispread score (images/hectads)";

	} elseif ($type == 'spread') {
		if ($filtered) {
			$sql_column = "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )/count(*)";
			$sql_having_having = "having count(*) > $minimum";
		} else {
			$sql_column = "images, hectads/images";
			$sql_having_having = "having images > $minimum";
		}
		$heading = "Spread Score";
		$desc = "spread score (hectads/images), and having submitted over $minimum images";

	} elseif ($type == 'hectads') {
		if ($filtered) {
			$sql_column = "count(distinct concat(substring(grid_reference,1,length(grid_reference)-3),substring(grid_reference,length(grid_reference)-1,1)) )";
		} else {
			$sql_column = "hectads";
		}
		$heading = "Hectads";
		$desc = "different hectads";

	} elseif ($type == 'days') {
		if ($filtered) {
			$sql_column = "count(distinct imagetaken)";
		} else {
			$sql_column = "days";
		}
		$heading = "Days";
		$desc = "different days";

	} elseif ($type == 'seasons') {
		$sql_column = "count(distinct concat( (month(imagetaken)%12) div 4,grid_reference))";
		$heading = "Square Seasons";
		$desc = "seasons within a square";

	} elseif ($type == 'classes') {
		$sql_column = "count(distinct imageclass)";
		$heading = "Categories";
		$desc = "different categories";

	} elseif ($type == 'clen') {
		if ($filtered) {
			$sql_column = "avg(length(comment))";
			$sql_having_having = "having count(*) > $minimum";
		} else {
			$sql_column = "images, comment_len";
			$sql_having_having = "having images > $minimum";
		}
		$heading = "Average Description Length";
		$desc = "average length of the description, and having submitted over $minimum images";

	} elseif ($type == 'tlen') {
		$sql_column = "avg(length(title))";
		$sql_having_having = "having count(*) > $minimum";
		$heading = "Average Title Length";
		$desc = "average length of the title, and having submitted over $minimum images";

	} elseif ($type == 'category_depth') {
		$sql_column = "count(*)/count(distinct imageclass)";
		$heading = "Category Depth";
		$desc = "the category depth score";

	} elseif ($type == 'centi') {
		//lets hobble this!
		header("HTTP/1.1 503 Service Unavailable");
		$smarty->display('function_disabled.tpl');
		exit;

		//NOT USED AS REQUIRES A NEW INDEX ON gridimage!
		$sql_table = "gridimage i ";
		$sql_column = "COUNT(DISTINCT nateastings div 100, natnorthings div 100)";
		$sql_where = "i.moderation_status='geograph' and nateastings div 1000 > 0";
		$heading = "Centigraph<br/>Points";
		$desc = "centigraph points awarded (centisquares photographed)";

	} elseif ($type == 'content') {
		if ($filtered) {
			die("invalid request");
		} else {
			$sql_column = "content";
			$sql_where = "content > 0";
		}
		$heading = "Content Items";
		$desc = "items submitted";

	} elseif ($type == 'second') {
		if ($filtered) {
			$sql_where = "i.ftf=2 and i.moderation_status='geograph'";
		} else {
			$sql_column = "points,seconds";
		}
		$heading = "Second Visit<br/>Points";
		$desc = "'Second Visit' points awarded";

	} elseif ($type == 'third') {
		if ($filtered) {
			$sql_where = "i.ftf=3 and i.moderation_status='geograph'";
		} else {
			$sql_column = "thirds";
		}
		$heading = "Third Visit<br/>Points";
		$desc = "'Third Visit' points awarded";

	} elseif ($type == 'fourth' || $type == 'forth') {
		if ($filtered) {
			$sql_where = "i.ftf=4 and i.moderation_status='geograph'";
		} else {
			$sql_column = "images,fourths";
		}
		$heading = "Fourth Visit<br/>Points";
		$desc = "'Fourth Visit' points awarded";

	} elseif ($type == 'allpoints') {
		if ($filtered) {
			$sql_where = "i.ftf between 1 and 4 and i.moderation_status='geograph'";
		} else {
			$sql_column = "images,(points+seconds+thirds+fourths)";
		}
		$heading = "Geograph<br/>Points";
		$desc = "First/Second/Third/Fourth Visit points awarded";

	} else { #if ($type == 'first') {
		if ($filtered) {
			$sql_where = "i.ftf=1 and i.moderation_status='geograph'";
		} else {
			$sql_column = "depth,points";
		}
		$heading = "First Geograph<br/>Points";
		$desc = "geograph points awarded";
		$type = 'first';
	}

	if ($when) {
		if ($sql_table == "user_date_stat i") {
			if (strlen($when) == 4) {
				$sql_where .= " and year = '$when' and type=".$db->Quote(($date == 'taken')?'imagetaken':'submitted');
			} else {
				$year = substr($when,0,4); //include the year just because its indexed
				$sql_where .= " and year = '$year' and month = '$when' and type=".$db->Quote(($date == 'taken')?'imagetaken':'submitted');
			}
			$title = ($date == 'taken')?'taken':'submitted';
			$desc .= ", <b>for images $title during ".getFormattedDate($when)."</b>";
		} elseif ($date == 'both') {
			$sql_where .= " and imagetaken LIKE '$when%' and submitted LIKE '$when%'";
			$desc .= ", <b>for images taken and submitted during ".getFormattedDate($when)."</b>";
		} else {
			$column = ($date == 'taken')?'imagetaken':'submitted';
			$sql_where .= " and $column LIKE '$when%'";
			$title = ($date == 'taken')?'taken':'submitted';
			$desc .= ", <b>for images $title during ".getFormattedDate($when)."</b>";
		}
	} elseif ($quarter) {
		list($year,$quart) = explode('/',$quarter);
		$sql_table .= " INNER JOIN user USING (user_id)";
		$sql_where .= sprintf(" and signup_date between '%04d-%02d-01' and DATE_SUB('%04d-%02d-01',INTERVAL 1 DAY)",
				$year,($quart*3)-2,$year+(($quart == 4)?1:0),($quart < 4)?($quart*3+1):1);
		$desc .= ", <b>for users signed up during quarter $quart of $year</b>";
	}
	if ($myriad) {
		$sql_where .= " and grid_reference LIKE '{$myriad}____'"; //todo, gridimage search no longer uses this index. convert to spatial index!
		$desc .= " in Myriad $myriad";
	}
	if ($ri) {
		$sql_where .= " and reference_index = $ri";
		$desc .= " in ".$CONF['references_all'][$ri];
	}

	$smarty->assign('heading', $heading);
	$smarty->assign('desc', $desc);
	$smarty->assign('type', $type);

	$start_rank = 1;

	if ($u && !$filtered && $sql_table == "user_stat i" && ($sql_column == "depth,points" || $sql_column == "geosquares")) {
		$rank_column = ($sql_column == "depth,points")?'points_rank':'geo_rank';
		$user_rank = $db->getOne("select $rank_column from user_stat where user_id = $u");

		$start_rank = max(1,$user_rank-intval($limit/5)); //todo kinda arbitary ??

		$sql_where .= " and $rank_column >= $start_rank";

	} elseif (strpos($sql_table,'_stat i') === FALSE) {
		$sql_column = "max(gridimage_id) as last,$sql_column";
	}

	$limit2 = intval($limit * 1.6);
	$topusers=$db->GetAll($sql = "
	select t2.*,u.realname
	from (
		select i.user_id, $sql_column as imgcount
		from $sql_table
		where $sql_where AND i.user_id != 124913
		group by user_id
		$sql_having_having
		order by imgcount desc $sql_orderby,last asc limit $limit2
	) t2 inner join user u using (user_id) ");

	if (isset($_GET['debug']))
		print $sql;

	$lastimgcount = 0;
	$toriserank = 0;
	$points = 0;
	$images = 0;
	$started_truncate = 0;
	if (!empty($topusers))
	foreach($topusers as $idx=>$entry)
	{
		$i=$idx+$start_rank;

		if ($lastimgcount == $entry['imgcount']) {
			if ($u && $u == $entry['user_id']) {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal((!empty($user_rank))?$user_rank:$i);
			} elseif ($started_truncate && ($i > $limit+$start_rank)) {
				unset($topusers[$idx]);
			} else {
				$topusers[$idx]['ordinal'] = '&nbsp;&nbsp;&nbsp;&quot;';
			}
		} else {
			$toriserank = ($lastimgcount - $entry['imgcount']);
			if ($u && $u == $entry['user_id']) {
                                $topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
                        } elseif ($i > $limit+$start_rank) {
				unset($topusers[$idx]);
				$started_truncate = 1;
			} else {
				$topusers[$idx]['ordinal'] = smarty_function_ordinal($i);
				if (array_key_exists('points',$entry)) {
					$points += $entry['points'];
					if ($points && empty($entry['points'])) $topusers[$idx]['points'] = '';
				}
				if (array_key_exists('images',$entry)) {
					$images += $entry['images'];
					if ($images && empty($entry['images'])) $topusers[$idx]['images'] = '';
				}
			}
			$lastimgcount = $entry['imgcount'];
			$lastrank = $i;

		}
	}

	$smarty->assign_by_ref('topusers', $topusers);
	$smarty->assign('points', $points);
	$smarty->assign('images', $images);

	$smarty->assign('types', array('tpoints','first','second','allpoints','personal','images','depth'));

	$extra = array();
	$extralink = '';

	foreach (array('when','date','ri','myriad','quarter') as $key) {
		if (isset($_GET[$key])) {
			$extra[$key] = $_GET[$key];
			$extralink .= "&amp;$key={$_GET[$key]}";
		}
	}
	$smarty->assign_by_ref('extra',$extra);
	$smarty->assign_by_ref('extralink',$extralink);
	$smarty->assign_by_ref('limit',$limit);
	$smarty->assign('filtered',$filtered || $when); //actully on the page consider when a filter!
	$smarty->assign_by_ref('u',$u);

	//lets find some recent photos
	new RecentImageList($smarty);
}

$smarty->display($template, $cacheid);

