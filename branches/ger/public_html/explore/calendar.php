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

$month=(!empty($_GET['Month']))?intval($_GET['Month']):'';
$year=(!empty($_GET['Year']))?intval($_GET['Year']):date('Y');


$template=($month)?'explore_calendar_month.tpl':'explore_calendar_year.tpl';
$cacheid="$year-$month";
if (isset($_REQUEST['image'])) {
	$cacheid .= ".".intval($_REQUEST['image']);
}
if (isset($_GET['blank'])) {
	$cacheid .= "blank";
}
if (isset($_GET['both'])) {
	$cacheid .= "sub";
}
if (isset($_GET['geo'])) {
        $cacheid .= "geo";
}
if (isset($_GET['supp'])) {
        $cacheid .= "supp";
}


$smarty->caching = 2; // lifetime is per cache
if ($month == date('n') && $year == date('Y')) {
	$smarty->cache_lifetime = 3600*24; //1day cache
} else {
	$smarty->cache_lifetime = 3600*24*7; //7day cache
}

function print_rp(&$in,$exit = false) {
	print "<pre>";
	print_r($in);
	print "</pre>";
	if ($exit)
		exit;
}

function smarty_modifier_colerize($input) {
	global $maximages;
	if ($input) {

		$hex = str_pad(dechex(255 - $input/$maximages*255), 2, '0', STR_PAD_LEFT); 
		return "ffff$hex";
	} 
	return 'ffffff';
}

$smarty->register_modifier("colerize", "smarty_modifier_colerize");


if (!$smarty->is_cached($template, $cacheid))
{
	dieUnderHighLoad();
	
	require_once('geograph/gridimage.class.php');
	require_once('geograph/gridsquare.class.php');
	require_once('geograph/imagelist.class.php');

	$db=NewADOConnection($GLOBALS['DSN']);
	if (!$db) die('Database connection failed');  

	if (isset($_REQUEST['image']))
	{
		//initialise message
		require_once('geograph/gridsquare.class.php');
		require_once('geograph/gridimage.class.php');

		$image=new GridImage();
		$image->loadFromId($_REQUEST['image']);

		if ($image->moderation_status=='rejected' || $image->moderation_status=='pending') {
			//clear the image
			$image=new GridImage;
		} else {
			$smarty->assign_by_ref('image', $image);
		}
	}

	if (!empty($month)) {
	
		$like = sprintf("%04d-%02d",$year,$month);
	
		$maximages=0;
		if (isset($_GET['blank'])) {
			$images = array();
			$smarty->assign('blank', 1);
		} else {
			if (isset($_GET['both'])) {
				$where = " AND submitted LIKE CONCAT(imagetaken,' %')";
			} else {
				$where = "";
			}
			if (isset($_GET['geo'])) {
				$where .= " AND moderation_status = 'geograph'";
			} elseif (isset($_GET['supp'])) {
                                $where .= " AND moderation_status = 'accept'";
                        }
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$images=&$db->GetAssoc($sql= "SELECT 
			imagetaken, 
			gridimage_id, title, user_id, realname, grid_reference,
			COUNT(*) AS images,
			SUM(moderation_status = 'accepted') AS `supps`
			FROM `gridimage_search`
			WHERE imagetaken LIKE '$like%' AND imagetaken not like '%-00%' $where
			GROUP BY imagetaken" );
			if (!empty($_GET['debug'])) {
				print "<pre>$sql</pre>";
			}
			foreach ($images as $day=>$arr) {
				if ($maximages < $arr['images'])
					$maximages = $arr['images'];
			}
		}
	
		$timeStamp = mktime(0, 0, 0, $month, 1, $year);
		#mktime($hour, $minute, $second, $month, $day, $year);
		$daysInMonth = date('t',$timeStamp);
		
		$dayNumber = 2-date('w',$timeStamp);
		if ($dayNumber == 2) $dayNumber = -5;
		
		$weeks = array();
		$w=1;
		while($w<7 && $dayNumber > -7) {
			$week = array();
			for($i=1; $i<=7; $i++) {
				$day = array();
				if ($dayNumber > 0) {
					$day['number'] = $dayNumber;
					$day['key'] = sprintf("%04d-%02d-%02d",$year,$month,$dayNumber);
					if ($images[$day['key']]) {
						$day['image']=& new GridImage();
						$day['image']->fastInit($images[$day['key']]);	
						$day['image']->compact();
					}
				} else 
					$day['number'] = '';
				$week[] = $day;
			
				$dayNumber++;
				if ($dayNumber > $daysInMonth)
					$dayNumber = -20; //just big so that we get the full row of blank squares
			}			
			
			$weeks[] = $week;	
			$w++;
		}

		$smarty->assign_by_ref("weeks",$weeks);
	
		$smarty->assign("month_name",strftime("%B", $timeStamp));
	} else {
		$months = array();
		
		$like = sprintf("%04d-",$year);
		
		$maximages=0;
		if (isset($_GET['blank'])) {
			$images = array();
			$smarty->assign('blank', 1);
		} else {
                        if (isset($_GET['both'])) {
				$where = " AND submitted LIKE CONCAT(imagetaken,' %')";
                        } else {
                                $where = "";
                        }
			if (isset($_GET['geo'])) {
				$where .= " AND moderation_status = 'geograph'";
			} elseif (isset($_GET['supp'])) {
                                $where .= " AND moderation_status = 'accept'";
                        }
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$images=&$db->GetAssoc("SELECT 
			imagetaken, 
			COUNT(*) AS images,
			SUM(moderation_status = 'accepted') AS `supps`
			FROM `gridimage_search`
			WHERE imagetaken LIKE '$like%' AND imagetaken not like '%-00%' $where
			GROUP BY imagetaken" );

			foreach ($images as $day=>$arr) {
				if ($maximages < $arr['images'])
					$maximages = $arr['images'];
			}
		}
		
		for($month=1; $month<=12; $month++) {
			$timeStamp = mktime(0, 0, 0, $month, 1, $year);
			#mktime($hour, $minute, $second, $month, $day, $year);
			$daysInMonth = date('t',$timeStamp);
			
			$dayNumber = 2-date('w',$timeStamp);
			if ($dayNumber == 2) $dayNumber = -5;
			
			$weeks = array();
			$w=1;
			while($w<7 && $dayNumber > -7) {
				$week = array();
				for($i=1; $i<=7; $i++) {
					$day = array();
					if ($dayNumber > 0) {
						$day['number'] = $dayNumber;
						$day['key'] = sprintf("%04d-%02d-%02d",$year,$month,$dayNumber);
						if ($images[$day['key']]) {
							$day['image']=& new GridImage();
							$day['image']->fastInit($images[$day['key']]);	
							$day['image']->compact();
						}
					} else 
						$day['number'] = '';
					$week[] = $day;
				
					$dayNumber++;
					if ($dayNumber > $daysInMonth)
						$dayNumber = -20; //just big so that we get the full row of blank squares
				}			
				
				$weeks[] = $week;
				$w++;		
			}
			$name = date('F',mktime(0,0,0,$month,1,2005)); 
			$months[$name] = $weeks;			
		}
		$month = 0;
		$smarty->assign_by_ref("months",$months);

	}
	
	//array of day names to use
	$days = array();
	for($i=1; $i<=7; $i++)
		$days[] = date('D',mktime(0,0,0,8,$i,2005));//just a month that happens to start on a monday 
	$smarty->assign_by_ref("days",$days);
	$month = sprintf("%02d",$month);
	$smarty->assign("month",$month);
	$smarty->assign("year",$year);
	$smarty->assign("date","$year-$month-00");
}

$smarty->display($template, $cacheid);

	
?>
