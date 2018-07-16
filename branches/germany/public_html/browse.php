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
require_once('geograph/rastermap.class.php');
include_messages('browse');

init_session();


$smarty = new GeographPage;

dieUnderHighLoad(4);

customGZipHandlerStart();

$square=new GridSquare;

if (isset($_GET['inner'])) {
	$template='browse_inner.tpl';
} else {
	$template='browse.tpl';
	$smarty->assign('prefixes', $square->getGridPrefixes());
	$smarty->assign('kmlist', $square->getKMList());
}


//we can be passed a gridreference as gridsquare/northings/eastings 
//or just gridref. So lets initialise our grid square
$grid_given=false;
$grid_ok=false;

if (isset($_GET['nl']))
{
	$_SESSION['nl']=intval($_GET['nl']);
}
elseif (isset($_SESSION['nl']))
{
	$_GET['nl']=intval($_SESSION['nl']);
}

if (isset($_GET['ht']))
{
	$_SESSION['ht']=intval($_GET['ht']);
}
elseif (isset($_SESSION['ht']))
{
	$_GET['ht']=intval($_SESSION['ht']);
}

//set by grid components?
if (isset($_GET['p']))
{	
	$grid_given=true;
	//p=900y + (900-x);
	$p = intval($_GET['p']);
	$x = ($p % 900); // only works if 0 =< x < 900
	$y = ($p - $x) / 900;
	$x = 900 - $x;
	$grid_ok=$square->loadFromPosition($x, $y, true);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
	$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
}

else if (isset($_GET['x']) && isset($_GET['y'])) {
	$x = intval($_GET['x']);
	$y = intval($_GET['y']);
	$dx = 0;
	$dy = 0;
	if (isset($_GET['dx']))
		$dx = intval($_GET['dx']);
	if (isset($_GET['dy']))
		$dy = intval($_GET['dy']);
	$grid_ok=$square->loadFromPosition($x, $y, true, false, $dx, $dy);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
	$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);

}
//set by grid components?
elseif (isset($_GET['setpos']))
{	
	$grid_given=true;
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings'],true);
	$smarty->assign('gridrefraw', $square->grid_reference);
	$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
}
//set by latitude/longitude?
elseif (isset($_GET['ll']) && preg_match("/^(-?\d+\.\d+),(-?\d+\.\d+) *$/", $_GET['ll'], $ll)) {
	require_once('geograph/conversions.class.php');
	$conv = new Conversions;
	list($x,$y,$reference_index) = $conv->wgs84_to_internal($ll[1],$ll[2]);
	$grid_ok = $square->loadFromPosition($x, $y, true);
	$grid_given=true;
	$smarty->assign('gridrefraw', $square->grid_reference);
	$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
}
//set by grid ref?
elseif (isset($_GET['gridref']) && strlen($_GET['gridref']))
{
	$grid_given=true;
	$grid_ok=$square->setByFullGridRef($_GET['gridref'],false,true);
	
	//preserve inputs in smarty
	if ($grid_ok)
	{
		//redirect a myriad/hectad reference to clean url (which then uses rewriterule to load the relevent page directly) 
		if (preg_match('/^[a-z]{1,3}(\s*\d{2}|)$/i',trim($_GET['gridref']))) {
			$gr = strtoupper(preg_replace('/^([a-z]{1,3})\s*(\d{2}|)$/i','$1$2',trim($_GET['gridref'])));
			header("Location: /gridref/$gr");
			print "<a href='/gridref/$gr'>Go here</a>";
			exit;
		}

		$smarty->assign('gridrefraw', preg_replace('/\W+/',' ',$_GET['gridref']));
		$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', preg_replace('/\W+/',' ',$_GET['gridref']));
	}	
}

$cacheid='';

//what style should we use?
$style = $USER->getStyle();

$cacheid.=$style;
	

$map_suffix = get_map_suffix();
$cacheid .= $map_suffix;

if (empty($CONF['google_maps_api_key'])) {
	$cacheid .= '.nogmap';
}

	#not ready for primetime yet, the user_id SHOULD to be replaced by visitor/has pending-or-rejects/mod switch 
# when ready to go live, should change the tpl file to remove most of the dynamic tags!
#$cacheid=($square->gridsquare_id).'.'.md5($_SERVER['QUERY_STRING']).'.'.($USER->user_id);

#if (!$smarty->is_cached($template, $cacheid))
#{

	$smarty->assign('maincontentclass', 'content_photo'.$style);

function smarty_modifier_colerize($input) {
	global $maximages;
	if ($input) {

		$hex = str_pad(dechex(255 - $input/$maximages*255), 2, '0', STR_PAD_LEFT); 
		return "ffff$hex";
	} 
	return 'ffffff';
}

$smarty->register_modifier("colerize", "smarty_modifier_colerize");
				
				

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
		$smarty->assign('hectad', $hectad = $square->gridsquare.intval($square->eastings/10).intval($square->northings/10));
		$smarty->assign('x', $square->x);
		$smarty->assign('y', $square->y);
		$smarty->assign('neighbours', $square->nextNeighbours());
		
		//store details the browser manager has figured out
		$smarty->assign('showresult', 1);
		$smarty->assign('imagecount', $square->imagecount);
		
		//is this just a closest match?
		if (is_object($square->nearest))
		{
			$smarty->assign('nearest_distance', $square->nearest->distance);
			$smarty->assign('nearest_gridref', $square->nearest->grid_reference);
		}

		if ($square->percent_land > 0) {
			//find a possible place within 25km
			$smarty->assign('place', $place = $square->findNearestPlace(75000));
			
			$place_name = strip_tags(smarty_function_place(array('place'=>$place)));
			
			$smarty->assign('meta_description', "Geograph currently has {$square->imagecount} photos in {$square->grid_reference}, $place_name");

			
		}

		#if (isset($_GET['showhier'])) {
		#	$smarty->assign('hier', $square->getRegionList(!empty($_GET['showhier'])));
		#}
		$smarty->assign('hier', $square->getRegionList(isset($_GET['showhier'])?!empty($_GET['showhier']):$USER->hasPerm("admin")||$USER->hasPerm("moderator")||$USER->hasPerm("mapmod")||$USER->hasPerm("ticketmod")));
	}
	$smarty->assign('mode','normal');
	if ($grid_ok && !empty($CONF['sphinx_host']) && (isset($_GET['takenfrom']) || isset($_GET['mentioning'])) ) {
		
		$sphinx = new sphinxwrapper();
		$sphinx->pageSize = 15;
		
		if (isset($_GET['takenfrom'])) {
				
			$ids = $sphinx->returnIdsViewpoint($square->getNatEastings(),$square->getNatNorthings(),$square->reference_index,$square->grid_reference);
			$smarty->assign('viewpoint_query', $sphinx->q);
			
			$viewpoint_count = 0; //set this to zero to suppress the prompt!
			
			$smarty->assign('mode','takenfrom');
		} else {
			$sphinx->prepareQuery("{$square->grid_reference} -grid_reference:{$square->grid_reference}");
			$ids = $sphinx->returnIds(1,"_images");
			$smarty->assign('mode','mentioning');
			
			$mention_count = 0; //set this to zero to suppress the prompt!
		}
		
		if (!empty($ids) && count($ids)) {
			
			$images=new ImageList();
			$images->getImagesByIdList($ids);
			
			$square->totalimagecount = $sphinx->resultCount;

			//otherwise, lets gether the info we need to display some thumbs
			if ($square->totalimagecount)
			{
				$smarty->assign_by_ref('images', $images->images);
			}

			$smarty->assign('totalimagecount', $sphinx->resultCount);
			$smarty->assign('imagecount', $sphinx->resultCount);

			if ($square->totalimagecount < 10 || ($USER->registered && !empty($_GET['big']))) {
				$smarty->assign('thumbw',213);
				$smarty->assign('thumbh',160);
			} else {
				$smarty->assign('thumbw',120);
				$smarty->assign('thumbh',120);
			}

		} else {
			$smarty->assign('imagecount', 0);
		}
		
	} elseif ($grid_ok) {
		$db = null;
		$custom_where = '';
		#$extra = '';
		if (!empty($_GET['user'])) {
			$custom_where .= " and gi.user_id = ".intval($_GET['user']);
			$profile=new GeographUser($_GET['user']);
			$filtered_title .= " by ".htmlentities2($profile->realname);
			$smarty->assign("bby",'user');
			#$extra .= "&amp;user=".intval($_GET['user']);
		}
		if (!empty($_GET['status'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$filtered_title .= " moderated as '".htmlentities2($_GET['status'])."'";
			#$extra .= "&amp;status=".urlencode($_GET['status']);
			$_GET['status'] = str_replace('supplemental','accepted',$_GET['status']);
			$custom_where .= " and moderation_status = ".$db->Quote($_GET['status']);
			$smarty->assign("bby",'status');
		}
		if (!empty($_GET['class'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imageclass = ".$db->Quote($_GET['class']);
			$filtered_title .= " categorised as '".htmlentities2($_GET['class'])."'";
			$smarty->assign("bby",'class');
			#$extra .= "&amp;class=".urlencode($_GET['class']);
		}
		if (!empty($_GET['taken'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imagetaken LIKE ".$db->Quote($_GET['taken']."%");
			$date = getFormattedDate($_GET['taken']);
			$filtered_title .= " Taken in $date";
			$smarty->assign("bby",'taken');
			#$extra .= "&amp;taken=".urlencode($_GET['taken']);
		}
		if (!empty($_GET['takenyear'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imagetaken LIKE ".$db->Quote($_GET['takenyear']."%");
			$date = getFormattedDate($_GET['takenyear']);
			$filtered_title .= " Taken in $date";
			$smarty->assign("bby",'takenyear');
			#$extra .= "&amp;takenyear=".urlencode($_GET['takenyear']);
		}
		if (!empty($_GET['submitted'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and submitted LIKE ".$db->Quote($_GET['submitted']."%");
			$date = getFormattedDate($_GET['submitted']);
			$filtered_title .= " Submitted in $date";
			$smarty->assign("bby",'submitted');
			#$extra .= "&amp;submitted=".urlencode($_GET['submitted']);
		}
		if (!empty($_GET['submittedyear'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and submitted LIKE ".$db->Quote($_GET['submittedyear']."%");
			$date = getFormattedDate($_GET['submittedyear']);
			$filtered_title .= " Submitted in $date";
			$smarty->assign("bby",'submittedyear');
			#$extra .= "&amp;submittedyear=".urlencode($_GET['submittedyear']);
		}
		if (isset($_GET['direction']) && strlen($_GET['direction'])) {
			$direction = intval($_GET['direction']);
			$custom_where .= " and view_direction = $direction";
			
			$view_direction = ($direction%90==0)?strtoupper(heading_string($direction)):ucwords(heading_string($direction)) ;
			$filtered_title .= " Looking $view_direction";
			$smarty->assign("bby",'direction');
			#$extra .= "&amp;direction=".intval($_GET['direction']);
		}
		if (!empty($_GET['viewpoint'])) {
			$viewpoint_square = new GridSquare;
			if ($_GET['viewpoint'] == '-') {
				$custom_where .= " and viewpoint_eastings = 0";
				
				$filtered_title = "photographer position unspecified";
			} elseif ($viewpoint_square->setByFullGridRef($_GET['viewpoint'],true,true)) {
			
				$e = intval($viewpoint_square->nateastings /1000);
				$n = intval($viewpoint_square->natnorthings /1000);
				$custom_where .= " and viewpoint_eastings DIV 1000 = $e AND viewpoint_northings DIV 1000 = $n";

				$filtered_title .= " Taken in ".$viewpoint_square->grid_reference;
			}
			$smarty->assign("bby",'viewpoint');
		}
		if (!empty($_GET['centi'])) {
			if ($_GET['centi'] == 'unspecified') {
				$custom_where .= " and nateastings = 0";
			} else {
				if ($_GET['centi'] == 'X') {
					require_once('geograph/conversions.class.php');
					$conv = new Conversions;
					list($_GET['centi'],$len) = $conv->national_to_gridref(
					$square->getNatEastings()-$correction,
					$square->getNatNorthings()-$correction,
					6,
					$square->reference_index,$spaced);
				}
			
				preg_match('/^[A-Z]{1,3}\d\d(\d)\d\d(\d)$/',$_GET['centi'],$matches);
				if (!isset($matches[2])) {
					die("invalid Grid Reference");
				}
				$custom_where .= " and nateastings != 0";//to stop XX0XX0 matching 4fig GRs
				$custom_where .= " and ((nateastings div 100) mod 10) = ".$matches[1];
				$custom_where .= " and ((natnorthings div 100) mod 10) = ".$matches[2];
				
				$grid_ok=$square->setByFullGridRef($_GET['centi'],false,true);
				$smarty->assign('gridrefraw', preg_replace('/\W+/',' ',$_GET['centi']));
				$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
			}
			$filtered_title .= " in ".htmlentities2($_GET['centi'])." Centisquare<a href=\"/help/squares\">?</a>";
			$smarty->assign("bby",'centi');
		}
		if (!empty($_GET['viewcenti'])) {
			if ($_GET['viewcenti'] == 'unspecified') {
				$custom_where .= " and viewpoint_eastings = 0";
			} else {
				preg_match('/^[A-Z]{1,3}\d\d(\d)\d\d(\d)$/',$_GET['viewcenti'],$matches);
				if (!isset($matches[2])) {
					die("invalid Grid Reference");
				}
				$custom_where .= " and viewpoint_eastings != 0";//to stop XX0XX0 matching 4fig GRs
				$custom_where .= " and ((viewpoint_eastings div 100) mod 10) = ".$matches[1];
				$custom_where .= " and ((viewpoint_northings div 100) mod 10) = ".$matches[2];
				
				$grid_ok=$square->setByFullGridRef($_GET['viewcenti'],true,true);
				
				$e = intval($square->nateastings /1000);
				$n = intval($square->natnorthings /1000);
				$custom_where .= " and viewpoint_eastings DIV 1000 = $e AND viewpoint_northings DIV 1000 = $n";
				
				$smarty->assign('gridrefraw', preg_replace('/\W+/',' ',$_GET['viewcenti']));
				$smarty->assign('gridref2', strlen($square->grid_reference) <= 2 + $CONF['gridpreflen'][$square->reference_index]);
			}
			$filtered_title .= " photographer in ".htmlentities2($_GET['viewcenti'])." Centisquare<a href=\"/help/squares\">?</a>";
			$smarty->assign("bby",'viewcenti');
		}
		if ($custom_where) {
			$smarty->assign('filtered_title', $filtered_title);
			$smarty->assign('filtered', 1);
		}
			
		if ($USER->user_id && !empty($_GET['nl'])) {
			#$extra .= "&amp;nl=1";
			$extra = "&amp;nl=1";
			$smarty->assign('nl', 1);
			
			if (!empty($_GET['ht'])) {
				$extra .= "&amp;ht=1";
				$smarty->assign('ht', 1);
			}
			if ($USER->hasPerm('moderator')) {
				$user_crit = "1";
				$cacheseconds = 600;
				$inc_all_user=">0";
			} else {
				$user_crit = "(moderation_status in ('accepted', 'geograph') or gi.user_id = {$USER->user_id})";
				$cacheseconds = 60;
				$inc_all_user=$USER->user_id;
			}
		} else {
			if (!empty($_GET['ht'])) {
				#$extra .= "&amp;ht=1";
				$extra = "&amp;ht=1";
				$smarty->assign('ht', 1);
			}
			$user_crit = "moderation_status in ('accepted', 'geograph')";
			$cacheseconds = 1500;
			$inc_all_user=0;
		}
		if (!empty($extra)) {
			$smarty->assign('extra', $extra);
		}
			
		if (($square->imagecount > 15 && !isset($_GET['by']) && !$custom_where) || (isset($_GET['by']) && $_GET['by'] == 1)) {
			$square->totalimagecount = $square->imagecount;
			
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			
			$row = $db->cacheGetRow($cacheseconds,"SELECT 
			count(distinct user_id) as user,
			count(distinct imageclass) as class,
			count(distinct SUBSTRING(imagetaken,1,7)) as taken,
			count(distinct SUBSTRING(imagetaken,1,4)) as takenyear,
			count(distinct SUBSTRING(submitted,1,7)) as submitted,
			count(distinct SUBSTRING(submitted,1,4)) as submittedyear,
			count(distinct moderation_status) as status,
			(count(distinct nateastings DIV 100, natnorthings DIV 100) - (sum(nateastings = 0) > 0) ) as centi,
			count(distinct view_direction) as direction,
			count(distinct viewpoint_eastings DIV 1000, viewpoint_northings DIV 1000) as viewpoints
			FROM gridimage gi
			WHERE gridsquare_id = {$square->gridsquare_id}
			AND $user_crit");
			
			$breakdowns = array();
			$breakdowns[] = array('type'=>'user','count'=>$row['user']);
			$breakdowns[] = array('type'=>'centi','count'=>$row['centi']);
			$breakdowns[] = array('type'=>'class','count'=>$row['class']);
			$breakdowns[] = array('type'=>'taken','count'=>$row['taken']);
			$breakdowns[] = array('type'=>'takenyear','count'=>$row['takenyear']);
			$breakdowns[] = array('type'=>'submitted','count'=>$row['submitted']);
			$breakdowns[] = array('type'=>'submittedyear','count'=>$row['submittedyear']);
			$breakdowns[] = array('type'=>'direction','count'=>$row['direction']);
			$breakdowns[] = array('type'=>'viewpoint','count'=>$row['viewpoints']);
			$breakdowns[] = array('type'=>'viewcenti','count'=>'?');
			$breakdowns[] = array('type'=>'status','count'=>$row['status']);
			foreach ($breakdowns as &$bdrow) {
				$bdrow['name'] = $MESSAGES['browse']['breakdowns'][$bdrow['type']];
			}
			unset($bdrow);
			$smarty->assign_by_ref('breakdowns', $breakdowns);
			
			if (rand(1,10) > 7) {
				$order = "(moderation_status = 'geograph') desc,rand()";
			} else {
				$order = "moderation_status+0 desc,seq_no";
			}
			//find the first geograph
			$sql="select gi.*,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname from gridimage as gi inner join user using(user_id) where gridsquare_id={$square->gridsquare_id} 
			and moderation_status in ('accepted','geograph') order by $order limit 1";

			$rec=$db->GetRow($sql);
			if (count($rec))
			{
				$rec['grid_reference'] = $square->grid_reference;
				$image=new GridImage;
				$image->fastInit($rec);
				$smarty->assign_by_ref('image', $image);
			}
		} elseif (!empty($_GET['by'])) {
			$square->totalimagecount = $square->imagecount;
			
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$breakdown = array();
			$i = 0;		
			
			if ($_GET['by'] == 'class') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_class'];
				$title = $MESSAGES['browse']['title_class'];
				$all = $db->cacheGetAll($cacheseconds,"SELECT imageclass,count(*) as count,
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,user.realname as user_realname
				FROM gridimage gi inner join user using(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY imageclass");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"{$title} <b>{$row[0]}</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image'] = new GridImage();
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 20) {
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;imageclass=".urlencode($row[0])."&amp;do=1";
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;class=".urlencode($row[0]).$extra;
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?class=".urlencode($row[0]).$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;class=".urlencode($row[0]).$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'status') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_status'];
				$substs = $MESSAGES['browse']['substs_status'];
				$linksubsts = array('accepted'=>'supplemental');
				$all = $db->cacheGetAll($cacheseconds,"SELECT moderation_status,count(*) as count,
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname,user.realname as user_realname
				FROM gridimage gi inner join user using(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY moderation_status 
				ORDER BY ftf DESC,moderation_status+0 DESC");
				foreach ($all as $row) {
					$rowname = isset($substs[$row[0]]) ? $substs[$row[0]] : $row[0];
					$linkname = isset($linksubsts[$row[0]]) ? $linksubsts[$row[0]] : $row[0];
					$breakdown[$i] = array('name'=>"<b>{$rowname}</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i< 20) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 20) {
						if ($row[0] == 'pending' || $row[0] == 'rejected') {
							$breakdown[$i]['link']="/profile/{$USER->user_id}";
						} else {
							$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;moderation_status=".urlencode($row[0])."&amp;do=1";
							$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;status=".urlencode($linkname).$extra;
						}
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?status=".urlencode($linkname).$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;status=".urlencode($linkname).$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'user') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_user'];
				$title = $MESSAGES['browse']['title_user'];
				$all = $db->cacheGetAll($cacheseconds,"SELECT user.realname as user_realname,count(*) as count,
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname
				FROM gridimage gi
				INNER JOIN user USING(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY user_id
				ORDER BY user.realname");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"{$title} <b>{$row[0]}</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 20) {
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;user_id={$row['user_id']}&amp;do=1";
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;user={$row['user_id']}".$extra;
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?user={$row['user_id']}".$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;user={$row['user_id']}".$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'direction') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_direction'];
				$title = $MESSAGES['browse']['title_direction'];
				$titleunknown = $MESSAGES['browse']['title_unknown_direction'];
				$formatdegree = $MESSAGES['browse']['format_direction'];
				$all = $db->cacheGetAll($cacheseconds,"SELECT view_direction,count(*),
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname
				FROM gridimage gi inner join user using(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY view_direction");
				$br = empty($_GET['ht'])?'<br/>':'';
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					if ($row[0] != -1) {
						$titledegree = sprintf($formatdegree, $row[0]);
						$view_direction = ($row[0]%90==0)?strtoupper(heading_string($row[0])):ucwords(heading_string($row[0])) ;
						$breakdown[$i] = array('name'=>"$title <b>$view_direction</b>$br ($titledegree)",'count'=>$row[1]);
					} else {
						$breakdown[$i] = array('name'=>$titleunknown,'count'=>$row[1]);
					}
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?direction={$row[0]}".$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;direction={$row[0]}".$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'viewpoint') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_viewpoint'];
				$title = $MESSAGES['browse']['title_viewpoint'];
				$titlehere = $MESSAGES['browse']['title_here_viewpoint'];
				$titleunknown = $MESSAGES['browse']['title_unknown_viewpoint'];
				$all = $db->cacheGetAll($cacheseconds,"SELECT viewpoint_eastings,count(*),gridimage_id,viewpoint_northings,
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname
				FROM gridimage gi inner join user using(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY viewpoint_eastings DIV 1000, viewpoint_northings DIV 1000");
				$conv = new Conversions('');
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					if ($row[0]) {
						list($posgr,$len) = $conv->national_to_gridref(
							$row[0],
							$row[3],
							4,
							$square->reference_index,false);
						if ($posgr == $square->grid_reference) {
							$breakdown[$i] = array('name'=>$titlehere,'count'=>$row[1]);
						} else {
							$breakdown[$i] = array('name'=>"$title <b>$posgr</b>",'count'=>$row[1]);
						}
					} else {
						$breakdown[$i] = array('name'=>$titleunknown,'count'=>$row[1]);
						$posgr = '-';
					}
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?viewpoint={$posgr}".$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;viewpoint={$posgr}".$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'centi') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_centi'];
				$all = $db->cacheGetAll($cacheseconds,"SELECT (nateastings = 0),count(*),gridimage_id,nateastings DIV 100, natnorthings DIV 100
				FROM gridimage gi
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY nateastings DIV 100, natnorthings DIV 100,(nateastings = 0)");
				
				$maximages = 0;
				$hasnone = 0;
				foreach ($all as $row) {
					if ($row[0]) {
						$centi = "unspecified";
						$x = $y = 50; 
						$hasnone = 1;
					} else {
						$x = ($row[3]%10);
						$y = ($row[4]%10);
						$centi=$square->gridsquare.$square->eastings.$x.$square->northings.$y;
						if (!isset($breakdown[$y])) {
							$breakdown[$y] = array();
						}
					}
					$maximages = max($row[1],$maximages);
					$breakdown[$y][$x] = array('name'=>"in $centi centisquare",'count'=>$row[1]);
					if ($row[1] > 2000000) {
						//todo
						$breakdown[$y][$x]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;user_id={$row[3]}&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$y][$x]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$y][$x]['link']="/gridref/{$square->grid_reference}?centi=$centi".$extra;
					}
				}
				$smarty->assign('allcount', count($all)-$hasnone);
				$smarty->assign('tenup', range(0,9));
				$smarty->assign('tendown', range(9,0));
			} elseif ($_GET['by'] == 'viewcenti') {
				$breakdown_title = $MESSAGES['browse']['bdtitle_viewcenti'];
				$e = intval($square->getNatEastings()/1000);
				$n = intval($square->getNatNorthings()/1000);
				$all = $db->cacheGetAll($cacheseconds,"SELECT (viewpoint_eastings = 0),count(*),gridimage_id,viewpoint_eastings DIV 100, viewpoint_northings DIV 100
				FROM gridimage gi
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				AND ((viewpoint_eastings DIV 1000 = $e AND viewpoint_northings DIV 1000 = $n) OR viewpoint_eastings = 0)
				GROUP BY viewpoint_eastings DIV 100, viewpoint_northings DIV 100,(viewpoint_eastings = 0)");
				$maximages = 0;
				$hasnone = 0;
				foreach ($all as $row) {
					if ($row[0]) {
						$centi = "unspecified";
						$x = $y = 50; 
						$hasnone = 1;
					} else {
						$x = ($row[3]%10);
						$y = ($row[4]%10);
						$centi=$square->gridsquare.$square->eastings.$x.$square->northings.$y;
						if (!isset($breakdown[$y])) {
							$breakdown[$y] = array();
						}
					}
					$maximages = max($row[1],$maximages);
					$breakdown[$y][$x] = array('name'=>"in $centi centisquare",'count'=>$row[1]);
					if ($row[1] > 2000000) {
						//todo
						$breakdown[$y][$x]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;user_id={$row[3]}&amp;do=1";
					} elseif ($row[1] == 1) {
						$breakdown[$y][$x]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$y][$x]['link']="/gridref/{$square->grid_reference}?viewcenti=$centi".$extra;
					}
				}
				$smarty->assign('allcount', count($all)-$hasnone);
				$smarty->assign('tenup', range(0,9));
				$smarty->assign('tendown', range(9,0));
			} else { //must be a date (unless something has gone wrong!)
				$year = preg_match('/year$/',$_GET['by']) === 1;
				$taken = preg_match('/^taken/',$_GET['by']) === 1;
				$length = $year?4:7;
				$column = $taken?'imagetaken':'submitted';
				$msgid = ($year?'year':'month').'_'.($taken?'taken':'submitted');
				$breakdown_title = $MESSAGES['browse']['bdtitle_'.$msgid];
				$title = $MESSAGES['browse']['title_'.$msgid];
				$all = $db->cacheGetAll($cacheseconds,"SELECT SUBSTRING($column,1,$length) as date,count(*),
				gridimage_id,title,user_id,gi.realname as credit_realname,if(gi.realname!='',gi.realname,user.realname) as realname
				FROM gridimage gi inner join user using(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY SUBSTRING($column,1,$length)");
				$column = $taken?'taken':'submitted';
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$date = getFormattedDate($row[0]);
					$breakdown[$i] = array('name'=>"$title <b>$date</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 20) {
						$datel = $row[0].substr('-00-00',0, 10-$length);

						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;{$column}_start=$datel&amp;{$column}_end=$datel&amp;do=1";
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;{$_GET['by']}={$row[0]}".$extra;
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?{$_GET['by']}={$row[0]}".$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;{$_GET['by']}={$row[0]}".$extra;
					}
					$i++;
				}
			}
			
			$smarty->assign('by', $_GET['by']);
			if (!empty($breakdown_title))
				$smarty->assign_by_ref('breakdown_title', $breakdown_title);
			if (count($breakdown)) {
				$smarty->assign_by_ref('breakdown', $breakdown);
				$smarty->assign('breakdown_count', count($breakdown));
			}
		} else {
			//todo ideally here we only want to forward teh user_id IF they have images in the square, or a mod, for greater cachablity, but the chicken and the egg thingy....
			$images=$square->getImages($inc_all_user,$custom_where,'order by ftf desc,gridimage_id');
			$square->totalimagecount = count($images);
			foreach ($images as $img) {
				$img->grid_reference = $square->grid_reference;
			}
		
			//otherwise, lets gether the info we need to display some thumbs
			if ($square->totalimagecount)
			{
				$smarty->assign_by_ref('images', $images);
			}
		}
		
		$smarty->assign('totalimagecount', $square->totalimagecount);
		
		if ($square->totalimagecount < 10 || ($USER->registered && !empty($_GET['big']))) {
			$smarty->assign('thumbw',213);
			$smarty->assign('thumbh',160);
		} else {
			$smarty->assign('thumbw',120);
			$smarty->assign('thumbh',120);
		}
	}
	
	if ($grid_ok) {
		//geotag the page	
		require_once('geograph/conversions.class.php');
		$conv = new Conversions;
		list($lat,$long) = $conv->gridsquare_to_wgs84($square);
		$smarty->assign('lat', $lat);
		$smarty->assign('long', $long);
		$smarty->assign_by_ref('square', $square);
		
		//get a token to show a suroudding geograph map
		$mosaic=new GeographMapMosaic;
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square, false));
		$smarty->assign('map_token2', $mosaic->getGridSquareToken($square, true));
	
		if ($CONF['forums']) {
			$square->assignDiscussionToSmarty($smarty);
		}
		
		if (!empty($CONF['sphinx_host'])) {
			//look for images from here...
			$sphinx = new sphinxwrapper();
			if (!isset($viewpoint_count) && $viewpoint_count = $sphinx->countImagesViewpoint($square->nateastings,$square->natnorthings,$square->reference_index,$square->grid_reference)) {
				$smarty->assign('viewpoint_count', $viewpoint_count);
				#$smarty->assign('viewpoint_query', $sphinx->q);
			}
			
			if (!isset($mention_count) && $mention_count = $sphinx->countQuery("{$square->grid_reference} -grid_reference:{$square->grid_reference}","_images")) {
				$smarty->assign('mention_count', $mention_count);
			} 
		}
		
		if ($square->natspecified && $square->natgrlen >= 6) {
			$conv = new Conversions('');
			list($gr6,$len) = $conv->national_to_gridref(
				$square->getNatEastings(),
				$square->getNatNorthings(),
				6,
				$square->reference_index,false);
			$smarty->assign('gridref6', $gr6);
		}
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
				$smarty->assign('map_token', $mosaic->getGridSquareToken($square, false));
				$smarty->assign('map_token2', $mosaic->getGridSquareToken($square, true));
			}
		}
	}
}
else
{
	//no square specifed - populate with remembered values
	@$smarty->assign('gridsquare', $_SESSION['gridsquare']);
	@$smarty->assign('eastings', $_SESSION['eastings']);
	@$smarty->assign('northings', $_SESSION['northings']);
	
}

if (!isset($_GET['inner'])) {
	#//lets find some recent photos
	#new RecentImageList($smarty);

	//lets add an overview map too
	if ($grid_ok) {
		#$overview=new GeographMapMosaic('largeoverview');
		#$overview->setCentre($square->x,$square->y); //does call setAlignedOrigin
		$overview=new GeographMapMosaic('largeoverview'.$map_suffix,$square->x,$square->y);
		$smarty->assign('marker', $overview->getSquarePoint($square));


//TODO if centisquare is specified use that to plot a circle!

		//lets add an rastermap too
		if (isset($_GET['sid']) && isset($square->services[intval($_GET['sid'])])) {
			$sid = intval($_GET['sid']);
		} elseif (count($square->services) != 0) {
			$sids = array_keys($square->services);
			$sid = $sids[0];
		} else {
			$sid = -1;
		}
		$cacheid.=".".$sid;
		$smarty->assign('sid', $sid);
		$rastermap = new RasterMap($square,false,$square->natspecified, false, 'latest', $sid);
		#if ($square->grid_reference == "UNV1930" || $square->grid_reference == "TNT8481") { //FIXME
		#	$rastermap = new RasterMap($square,false,$square->natspecified, false, 'latest', 1);
		#} else {
		#	$rastermap = new RasterMap($square,false,$square->natspecified);
		#}
		$rastermap->addLatLong($lat,$long);
		$smarty->assign_by_ref('rastermap', $rastermap);

	} else {
		$overview=new GeographMapMosaic('overview'.$map_suffix);
	}
	$overview->assignToSmarty($smarty, 'overview');
}

#}

$smarty->display($template,$cacheid);

	
?>
