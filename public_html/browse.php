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
require_once('geograph/gridimage.class.php');
require_once('geograph/gridsquare.class.php');
require_once('geograph/imagelist.class.php');
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

init_session();


$smarty = new GeographPage;

dieUnderHighLoad(4);

$square=new GridSquare;

$smarty->assign('prefixes', $square->getGridPrefixes());
$smarty->assign('kmlist', $square->getKMList());


//we can be passed a gridreference as gridsquare/northings/eastings 
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;


//set by grid components?
if (isset($_GET['p']))
{	
	$grid_given=true;
	//p=900y + (900-x);
	$p = intval($_GET['p']);
	$x = ($p % 900);
	$y = ($p - $x) / 900;
	$x = 900 - $x;
	$grid_ok=$square->loadFromPosition($x, $y, true);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
}

//set by grid components?
elseif (isset($_GET['setpos']))
{	
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings']);
	$smarty->assign('gridrefraw', $square->grid_reference);
}

//set by grid ref?
elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($_GET['gridref']);
	
	//preserve inputs in smarty	
	if ($grid_ok)
	{
		$smarty->assign('gridrefraw', stripslashes($_GET['gridref']));
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($_GET['gridref']));
	}	
}

$template='browse.tpl';
$cacheid='';
#not ready for primetime yet, the user_id SHOULD to be replaced by visitor/has pending-or-rejects/mod switch 
# when ready to go live, should change the tpl file to remove most of the dynamic tags!
#$cacheid=($square->gridsquare_id).'.'.md5($_SERVER['QUERY_STRING']).'.'.($USER->user_id);

#if (!$smarty->is_cached($template, $cacheid))
#{

//process grid reference
if ($grid_given)
{
	$square->rememberInSession();

	//now we see if the grid reference is actually available...
	if ($grid_ok)
	{
		$smarty->assign('gridref', $square->grid_reference);
		$smarty->assign('gridsquare', $square->gridsquare);
		$smarty->assign('eastings', $square->eastings);
		$smarty->assign('northings', $square->northings);
		$smarty->assign('x', $square->x);
		$smarty->assign('y', $square->y);
		
		//store details the browser manager has figured out
		$smarty->assign('showresult', 1);
		$smarty->assign('imagecount', $square->imagecount);
		
		//is this just a closest match?
		if (is_object($square->nearest))
		{
			$smarty->assign('nearest_distance', $square->nearest->distance);
			$smarty->assign('nearest_gridref', $square->nearest->grid_reference);
		}
		
		$custom_where = '';
		if (!empty($_GET['user'])) {
			$custom_where .= " and gi.user_id = ".$_GET['user'];
			$profile=new GeographUser($_GET['user']);
			$filtered_title = "by ".($profile->realname);
		}
		if (!empty($_GET['status'])) {
			$filtered_title = "moderated as '".$_GET['status']."'";
			if (preg_match('/^first /',$_GET['status'])) {
				$_GET['status'] = preg_match('/^first /','',$_GET['status']);
				$custom_where .= " and ftf = 1";
			} else {
				$custom_where .= " and ftf = 0";
			}
			$_GET['status'] = str_replace('supplemental','accepted',$_GET['status']);
			$custom_where .= " and moderation_status = '".$_GET['status']."'";
		}
		if (!empty($_GET['class'])) {
			$custom_where .= " and imageclass = '".$_GET['class']."'";
			$filtered_title = "categorised as '".$_GET['class']."'";
		}
		$iamge = new GridImage();
		if (!empty($_GET['taken'])) {
			$custom_where .= " and imagetaken LIKE '".$_GET['taken']."%'";
			$iamge->imagetaken = $_GET['taken'];
			$date = $iamge->getFormattedTakenDate();
			$filtered_title = "Taken in $date";
		}
		if (!empty($_GET['takenyear'])) {
			$custom_where .= " and imagetaken LIKE '".$_GET['takenyear']."%'";
			$iamge->imagetaken = $_GET['takenyear'];
			$date = $iamge->getFormattedTakenDate();
			$filtered_title = "Taken in $date";
		}
		if (!empty($_GET['submitted'])) {
			$custom_where .= " and submitted LIKE '".$_GET['submitted']."%'";
			$iamge->imagetaken = $_GET['submitted'];
			$date = $iamge->getFormattedTakenDate();
			$filtered_title = "Submitted in $date";
		}
		if (!empty($_GET['submittedyear'])) {
			$custom_where .= " and submitted LIKE '".$_GET['submittedyear']."%'";
			$iamge->imagetaken = $_GET['submittedyear'];
			$date = $iamge->getFormattedTakenDate();
			$filtered_title = "Submitted in $date";
		}
		if (!empty($_GET['centi'])) {
			if ($_GET['centi'] == 'unspecified') {
				$custom_where .= " and nateastings = 0";
			} else {
				preg_match('/^[A-Z]{1,2}\d\d(\d)\d\d(\d)$/',$_GET['centi'],$matches);
				$custom_where .= " and nateastings != 0";//to stop XX0XX0 matching 4fig GRs
				$custom_where .= " and ((nateastings div 100) mod 10) = ".$matches[1];
				$custom_where .= " and ((natnorthings div 100) mod 10) = ".$matches[2];
			}
			$filtered_title = "in {$_GET['centi']} Centisquare<a href=\"/help/squares\">?</a>";
		}
		if ($custom_where) {
			$smarty->assign('filtered_title', $filtered_title);
			$smarty->assign('filtered', 1);
		}
			
		$user_crit = $USER->user_id?" or gridimage.user_id = {$USER->user_id}":'';
				
			
		if (($square->imagecount > 15 && !isset($_GET['by']) && !$custom_where) || (isset($_GET['by']) && $_GET['by'] == 1)) {
			$square->totalimagecount = $square->imagecount;
			
			$db=NewADOConnection($GLOBALS['DSN']);
			
			$row = $db->getRow("SELECT 
			count(distinct user_id) as user,
			count(distinct imageclass) as class,
			count(distinct SUBSTRING(imagetaken,1,7)) as taken,
			count(distinct SUBSTRING(imagetaken,1,4)) as takenyear,
			count(distinct SUBSTRING(submitted,1,7)) as submitted,
			count(distinct SUBSTRING(submitted,1,4)) as submittedyear,
			count(distinct CONCAT(ELT(ftf+1, '','first '),moderation_status)) as status,
			count(distinct nateastings DIV 100, natnorthings DIV 100) as centi,
			sum(nateastings = 0) as centi_blank
			FROM gridimage
			WHERE gridsquare_id = {$square->gridsquare_id}
			AND (moderation_status in ('accepted', 'geograph') $user_crit)");
			
			$breakdowns = array();
			$breakdowns[] = array('type'=>'user','name'=>'Contributors','count'=>$row['user']);
			$breakdowns[] = array('type'=>'class','name'=>'Categories','count'=>$row['class']);
			$breakdowns[] = array('type'=>'taken','name'=>'Taken Months','count'=>$row['taken']);
			$breakdowns[] = array('type'=>'takenyear','name'=>'Taken Years','count'=>$row['takenyear']);
			$breakdowns[] = array('type'=>'submitted','name'=>'Submitted Months','count'=>$row['submitted']);
			$breakdowns[] = array('type'=>'submittedyear','name'=>'Submitted Years','count'=>$row['submittedyear']);
			$breakdowns[] = array('type'=>'status','name'=>'Status','count'=>$row['status']);
			$breakdowns[] = array('type'=>'centi','name'=>'Centisquares','count'=>$row['centi']-($row['centi_blank'] > 0));
			$smarty->assign_by_ref('breakdowns', $breakdowns);
			
			$sql="select gridimage.*,user.realname,user.nickname from gridimage  inner join user using(user_id) where gridsquare_id={$square->gridsquare_id} 
			and moderation_status in ('accepted','geograph') order by moderation_status+0 desc,seq_no limit 1";

			$rec=$db->GetRow($sql);
			if (count($rec))
			{
				$image=new GridImage;
				$image->fastInit($rec);
				$smarty->assign_by_ref('image', $image);
			}
		} elseif (!empty($_GET['by'])) {
			$square->totalimagecount = $square->imagecount;
			
			$db=NewADOConnection($GLOBALS['DSN']);
			$old_ADODB_FETCH_MODE =  $ADODB_FETCH_MODE;
			$ADODB_FETCH_MODE = ADODB_FETCH_NUM;
			$breakdown = array();
			$i = 0;		
			
			if ($_GET['by'] == 'class') {
				$breakdown_title = "Category";
				$all = $db->getAll("SELECT imageclass,count(*),gridimage_id
				FROM gridimage
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND (moderation_status in ('accepted', 'geograph') $user_crit )
				GROUP BY imageclass");
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"in category <b>{$row[0]}</b>",'count'=>$row[1]);
					if ($row[1] > 20) {
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;imageclass=".urlencode($row[0])."&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?class=".urlencode($row[0]);
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'status') {
				$breakdown_title = "Status";
				$all = $db->getAll("SELECT CONCAT(ELT(ftf+1, '','first '),moderation_status),count(*),gridimage_id
				FROM gridimage
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND (moderation_status in ('accepted', 'geograph') $user_crit )
				GROUP BY CONCAT(ELT(ftf+1, '','first '),moderation_status) 
				ORDER BY ftf DESC,moderation_status+0 DESC");
				foreach ($all as $row) {
					$rowname = str_replace('accepted','supplemental',$row[0]);
					$breakdown[$i] = array('name'=>"<b>{$rowname}</b>",'count'=>$row[1]);
					if ($row[1] > 20) {
						$row[0] = str_replace('first ','',$row[0]);//we have to ignore it for now! ButButBut there should only ever be one first :)
						if ($row[0] == 'pending' || $row[0] == 'rejected') {
							$breakdown[$i]['link']="/profile.php?u={$USER->user_id}";
						} else {
							$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;moderation_status=".urlencode($row[0])."&amp;do=1";
						}
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?status=".urlencode($row[0]);
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'user') {
				$breakdown_title = "Contributor";
				$all = $db->getAll("SELECT realname,count(*),gridimage_id,gridimage.user_id
				FROM gridimage
				INNER JOIN user USING(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND (moderation_status in ('accepted', 'geograph') $user_crit )
				GROUP BY user_id");
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"contributed by <b>{$row[0]}</b>",'count'=>$row[1]);
					if ($row[1] > 20) {
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;user_id={$row[3]}&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?user={$row[3]}";
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'centi') {
				$breakdown_title = "Centisquare<a href=\"/help/squares\">?</a>";
				$all = $db->getAll("SELECT (nateastings = 0),count(*),gridimage_id,nateastings DIV 100, natnorthings DIV 100
				FROM gridimage
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND (moderation_status in ('accepted', 'geograph') $user_crit )
				GROUP BY nateastings DIV 100, natnorthings DIV 100,(nateastings = 0)");
				foreach ($all as $row) {
					if ($row[0]) {
						$centi = "unspecified";
					} else {
						$centi=$square->gridsquare.$square->eastings.($row[3]%10).$square->northings.($row[4]%10);
					}
					$breakdown[$i] = array('name'=>"in <b>$centi</b> centisquare",'count'=>$row[1]);
					if ($row[1] > 2000000) {
						//todo
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;user_id={$row[3]}&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?centi=$centi";
					}
					$i++;
				}
			} else { //must be a date (unless something has gone wrong!)
				$length = (preg_match('/year$/',$_GET['by']))?4:7;
				$column = (preg_match('/^taken/',$_GET['by']))?'imagetaken':'submitted';
				$title = (preg_match('/^taken/',$_GET['by']))?'Taken':'Submitted';
				$breakdown_title = "$title".((preg_match('/year$/',$_GET['by']))?'':' Month');
				$all = $db->getAll("SELECT SUBSTRING($column,1,$length) as date,count(*),gridimage_id
				FROM gridimage
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND (moderation_status in ('accepted', 'geograph') $user_crit )
				GROUP BY SUBSTRING($column,1,$length)");
				foreach ($all as $row) {
					$iamge->imagetaken = $row[0];
					$date = $iamge->getFormattedTakenDate();
					$breakdown[$i] = array('name'=>"$title <b>$date</b>",'count'=>$row[1]);
					if ($row[1] > 20) {
						$datel = $row[0].substr('-00-00',0, 10-$length);

						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;{$column}_start=$datel&amp;{$column}_end=$datel&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?{$_GET['by']}={$row[0]}";
					}
					$i++;
				}
			}
			$ADODB_FETCH_MODE = $old_ADODB_FETCH_MODE;
			if (!empty($breakdown_title))
				$smarty->assign_by_ref('breakdown_title', $breakdown_title);
			if (count($breakdown))
				$smarty->assign_by_ref('breakdown', $breakdown);
		} else {
			$images=$square->getImages($USER->user_id,$custom_where);
			$square->totalimagecount = count($images);
		
			//otherwise, lets gether the info we need to display some thumbs
			if ($square->totalimagecount)
			{
				$smarty->assign_by_ref('images', $images);
			}
		}
		
		$smarty->assign('totalimagecount', $square->totalimagecount);
		
		if ($square->totalimagecount < 10) {
			$smarty->assign('thumbw',213);
			$smarty->assign('thumbh',160);
		} else {
			$smarty->assign('thumbw',120);
			$smarty->assign('thumbh',120);
		}
		
		//geotag the page	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$smarty->assign_by_ref('square', $square);
		
		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
	
		$square->assignDiscussionToSmarty($smarty);
			
	}
	else
	{
		$smarty->assign('errormsg', $square->errormsg);	
		
		//includes a closest match?
		if (is_object($square->nearest))
		{
			$smarty->assign('nearest_distance', $square->nearest->distance);
			$smarty->assign('nearest_gridref', $square->nearest->grid_reference);
		
			if (!empty($square->x) && !empty($square->y) && $square->nearest->distance < 15) {
				//we where still able to work out the location, so
				//get a token to show a suroudding geograph map
				$mosaic=new GeographMapMosaic;
				$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
			}
		}
	}
}
else
{
	//no square specifed - populate with remembered values
	$smarty->assign('gridsquare', $_SESSION['gridsquare']);
	$smarty->assign('eastings', $_SESSION['eastings']);
	$smarty->assign('northings', $_SESSION['northings']);
	
}

//lets find some recent photos
new RecentImageList($smarty);

//lets add an overview map too
$overview=new GeographMapMosaic('overview');
$overview->assignToSmarty($smarty, 'overview');
if ($grid_ok)
	$smarty->assign('marker', $overview->getSquarePoint($square));

#}

$smarty->display($template,$cacheid);

	
?>
