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

init_session();


$smarty = new GeographPage;

dieUnderHighLoad(4);

customGZipHandlerStart();
customExpiresHeader(360,false,true);

$square=new GridSquare;

if (isset($_GET['inner'])) {
	$template='browse_inner.tpl';
} else {
	if (isset($_GET['old'])) {
		$_SESSION['old_browse'] = 1;
		if (isset($_GET['t'])) {
			$_GET['t'] = 0;
		}
	}

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

if (!empty($_GET['displayclass'])) {
	$smarty->assign('displayclass',$USER->setPreference('browse.displayclass',preg_replace('/[^\w]+/','',$_GET['displayclass']),true));
} else {
	$smarty->assign('displayclass',$USER->getPreference('browse.displayclass','full',true));
}
$displayclasses =  array(
			'tiles' => 'default',
			'full' => 'full listing',
			'thumbs' => 'thumbnails only',
			);
$smarty->assign_by_ref('displayclasses',$displayclasses);


//set by encoded p param
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
	$grid_ok=$square->setGridPos($_GET['gridsquare'], $_GET['eastings'], $_GET['northings'],true);
	$smarty->assign('gridrefraw', $square->grid_reference);
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
	
		$smarty->assign('gridrefraw', stripslashes($_GET['gridref']));
	}
	else
	{
		//preserve the input at least
		$smarty->assign('gridref', stripslashes($_GET['gridref']));
	}	
}

$cacheid='';

//what style should we use?
$style = $USER->getStyle();
	
$smarty->assign('maincontentclass', 'content_photo'.$style);

	#not ready for primetime yet, the user_id SHOULD to be replaced by visitor/has pending-or-rejects/mod switch 
# when ready to go live, should change the tpl file to remove most of the dynamic tags!
#$cacheid=($square->gridsquare_id).'.'.md5($_SERVER['QUERY_STRING']).'.'.($USER->user_id);

#if (!$smarty->is_cached($template, $cacheid))
#{

	
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
		
	}
	$smarty->assign('mode','normal');
	if ($grid_ok && (isset($_GET['takenfrom']) || isset($_GET['mentioning'])) ) {
		
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

			if ($square->totalimagecount < 25 || ($USER->registered && !empty($_GET['big']))) {
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
		$extra = '';
		if (!empty($_GET['user'])) {
			$custom_where .= " and gi.user_id = ".intval($_GET['user']);
			$profile=new GeographUser($_GET['user']);
			$filtered_title .= " by ".htmlentities2($profile->realname);
			$smarty->assign("bby",'user');
			$extra .= "&amp;user=".intval($_GET['user']);
		}
		if (!empty($_GET['status'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$filtered_title .= " moderated as '".htmlentities2($_GET['status'])."'";
			$extra .= "&amp;status=".urlencode($_GET['status']);
			$_GET['status'] = str_replace('supplemental','accepted',$_GET['status']);
			$custom_where .= " and moderation_status = ".$db->Quote($_GET['status']);
			$smarty->assign("bby",'status');
		}
		if (!empty($_GET['class'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imageclass = ".$db->Quote($_GET['class']);
			$filtered_title .= " categorised as '".htmlentities2($_GET['class'])."'";
			$smarty->assign("bby",'class');
			$extra .= "&amp;class=".urlencode($_GET['class']);
		}
		if (!empty($_GET['cluster'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and label = ".$db->Quote($_GET['cluster']);
			$filtered_title .= " labeled as '".htmlentities2($_GET['cluster'])."'";
			$smarty->assign("bby",'cluster');
			$extra .= "&amp;cluster=".urlencode($_GET['cluster']);
		}
		if (!empty($_GET['tag'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and tag = ".$db->Quote($_GET['tag']);
			$filtered_title .= " tagged as '".htmlentities2($_GET['tag'])."'";
			$smarty->assign("bby",'tag');
			$extra .= "&amp;tag=".urlencode($_GET['tag']);
		}
		if (!empty($_GET['taken'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imagetaken LIKE ".$db->Quote($_GET['taken']."%");
			$date = getFormattedDate($_GET['taken']);
			$filtered_title .= " Taken in $date";
			$smarty->assign("bby",'taken');
			$extra .= "&amp;taken=".urlencode($_GET['taken']);
		}
		if (!empty($_GET['takenyear'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and imagetaken LIKE ".$db->Quote($_GET['takenyear']."%");
			$date = getFormattedDate($_GET['takenyear']);
			$filtered_title .= " Taken in $date";
			$smarty->assign("bby",'takenyear');
			$extra .= "&amp;takenyear=".urlencode($_GET['takenyear']);
		}
		if (!empty($_GET['submitted'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and submitted LIKE ".$db->Quote($_GET['submitted']."%");
			$date = getFormattedDate($_GET['submitted']);
			$filtered_title .= " Submitted in $date";
			$smarty->assign("bby",'submitted');
			$extra .= "&amp;submitted=".urlencode($_GET['submitted']);
		}
		if (!empty($_GET['submittedyear'])) {
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$custom_where .= " and submitted LIKE ".$db->Quote($_GET['submittedyear']."%");
			$date = getFormattedDate($_GET['submittedyear']);
			$filtered_title .= " Submitted in $date";
			$smarty->assign("bby",'submittedyear');
			$extra .= "&amp;submittedyear=".urlencode($_GET['submittedyear']);
		}
		if (isset($_GET['direction']) && strlen($_GET['direction'])) {
			$direction = intval($_GET['direction']);
			$custom_where .= " and view_direction = $direction";
			
			$view_direction = ($direction%90==0)?strtoupper(heading_string($direction)):ucwords(heading_string($direction)) ;
			$filtered_title .= " Looking $view_direction";
			$smarty->assign("bby",'direction');
			$extra .= "&amp;direction=".intval($_GET['direction']);
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
			
				preg_match('/^[A-Z]{1,2}\d\d(\d)\d\d(\d)$/',$_GET['centi'],$matches);
				if (!isset($matches[2])) {
					die("invalid Grid Reference");
				}
				$custom_where .= " and nateastings != 0";//to stop XX0XX0 matching 4fig GRs
				$custom_where .= " and ((nateastings div 100) mod 10) = ".$matches[1];
				$custom_where .= " and ((natnorthings div 100) mod 10) = ".$matches[2];
				
				$grid_ok=$square->setByFullGridRef($_GET['centi'],false,true);
				$smarty->assign('gridrefraw', stripslashes($_GET['centi']));
			}
			$filtered_title .= " in ".htmlentities2($_GET['centi'])." Centisquare<a href=\"/help/squares\">?</a>";
			$smarty->assign("bby",'centi');
		}
		if (!empty($_GET['viewcenti'])) {
			if ($_GET['viewcenti'] == 'unspecified') {
				$custom_where .= " and viewpoint_eastings = 0";
			} else {
				preg_match('/^[A-Z]{1,2}\d\d(\d)\d\d(\d)$/',$_GET['viewcenti'],$matches);
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
				
				$smarty->assign('gridrefraw', stripslashes($_GET['viewcenti']));
			}
			$filtered_title .= " photographer in ".htmlentities2($_GET['viewcenti'])." Centisquare<a href=\"/help/squares\">?</a>";
			$smarty->assign("bby",'viewcenti');
		}
		if ($custom_where) {
			$smarty->assign('filtered_title', $filtered_title);
			$smarty->assign('filtered', 1);
		}
			
		if ($USER->user_id && !empty($_GET['nl'])) {
			$extra .= "&amp;nl=1";
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
				$extra .= "&amp;ht=1";
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
			
			$square->loadCollections();
			
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			
			if ($square->imagecount > 15 && $_GET['by'] !== '1') {
				$imagelist = new ImageList();
				
				$mkey = $square->grid_reference;
				$imagelist->images =& $memcache->name_get('bx',$mkey);
				
				if (empty($imagelist->images)) {
					$imagelist->_setDB($db);
				
					$columns = "gridimage_id,user_id,realname,credit_realname,title,imageclass,grid_reference,comment";
					$gis_where = "grid_reference = '{$square->grid_reference}'";
					$limit = 12;
					
					$methodsa = array('any'=>1,'random'=>1,'latest'=>1,'few'=>1,'groups'=>1); 
					
					if ($square->imagecount > 50) {
						$methodsa['user+category']=1;
					}
					if ($square->imagecount > $limit*2) {
						$methodsa['category']=1;
						$methodsa['spaced']=1;
					}
					
					$methods = array_keys($methodsa);
					
					$method = $methods[rand(0,count($methods)-1)];
					
					if ($method == 'groups') {
						$c = $db->getOne("SELECT c FROM gridsquare_group_stat WHERE gridsquare_id = {$square->gridsquare_id}");
						if (!$c || $c < 4) {
							unset($methodsa['groups']);
							$methods = array_keys($methodsa);
							$method = $methods[rand(0,count($methods)-1)];
						}
					}
					
					
					list($usec, $sec) = explode(' ',microtime());
					$starttime = ((float)$usec + (float)$sec);
					
					switch ($method) {
						case 'category':
							$sql = "SELECT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search WHERE $gis_where GROUP BY imageclass ORDER BY seq_no LIMIT $limit";
							break;
						
						case 'groups':
							$sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search INNER JOIN gridimage_group USING (gridimage_id) WHERE $gis_where GROUP BY label ORDER BY seq_no LIMIT $limit";
							break;
						
						case 'user+category': 
							//http://stackoverflow.com/questions/1138006/multi-column-distinct-in-mysql

							$table = $CONF['db_tempdb'].".tmp_browse";#.md5(uniqid());

							$db->Execute("CREATE TEMPORARY TABLE $table SELECT $columns FROM gridimage_search WHERE $gis_where ORDER BY ftf BETWEEN 1 AND 4 DESC, REVERSE(gridimage_id)");

							$db->Execute("ALTER IGNORE TABLE $table ADD UNIQUE (user_id),ADD UNIQUE (imageclass)");

							$sql = "/* {$square->grid_reference}/{$square->gridsquare_id} */ SELECT SQL_CALC_FOUND_ROWS * FROM $table LIMIT $limit";
							break;
							
						case 'spaced': 
							$db->Execute("set @c := -1");
							$space = max(1,floor($square->imagecount/$limit));
							
							$sql = "select SQL_CALC_FOUND_ROWS $columns,if(@c=$space,@c:=0,@c:=@c+1) AS c FROM gridimage_search WHERE $gis_where HAVING c=0 ORDER by seq_no";
							break;
						
						case 'any':
							$sql = "SELECT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search WHERE $gis_where ORDER BY ftf BETWEEN 1 AND 4 DESC LIMIT $limit";
							break;
						
						case 'random':
							$sql = "SELECT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search WHERE $gis_where ORDER BY ftf BETWEEN 1 AND 4 DESC, REVERSE(gridimage_id) LIMIT $limit";
							break;
						
						case 'latest':
							$sql = "SELECT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search WHERE $gis_where ORDER BY ftf BETWEEN 1 AND 4 DESC, seq_no DESC LIMIT $limit";
							break;
						
						case 'few':
						default:
							$sql = "SELECT SQL_CALC_FOUND_ROWS $columns FROM gridimage_search WHERE $gis_where ORDER BY seq_no LIMIT $limit";
						
					}
					$imagelist->_getImagesBySql($sql);
					
					list($usec, $sec) = explode(' ',microtime());
					$endtime = ((float)$usec + (float)$sec);
					$timetaken = $endtime - $starttime;
					
					$total = $db->getOne("SELECT FOUND_ROWS()"); 
					
					$updates = "timetaken = $timetaken,total = $total,results = ".count($imagelist->images);
					
					$db->Execute("INSERT INTO browse_cluster 
							SET gridsquare_id = {$square->gridsquare_id},method = '$method',$updates,created=NOW()
							ON DUPLICATE KEY UPDATE uses=uses+1,$updates");
					
					$memcache->name_set('bx',$mkey,$imagelist->images,$memcache->compress,$memcache->period_long);
				}
						
							
				
				$smarty->assign_by_ref('images', $imagelist->images);
				$smarty->assign('sample', count($imagelist->images) );
				
				$groupbys = array(''=>'','takendays'=>'Day Taken','submitted'=>'Day Submitted','submitted_month'=>'Month Submitted','submitted_year'=>'Year Submitted','  '=>'','auser_id'=>'Contributor','classcrc'=>'Image Category',' '=>'','scenti'=>'Centisquare');
				$smarty->assign_by_ref('groupbys', $groupbys);
			} else {
			
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
			$breakdowns[] = array('type'=>'user','name'=>'Contributor','count'=>$row['user'].' Contributors');
			$breakdowns[] = array('type'=>'centi','name'=>'Centisquare','count'=>$row['centi'].' Centisquares');
			$breakdowns[] = array('type'=>'class','name'=>'Category','count'=>$row['class'].' Categories');
			$breakdowns[] = array('type'=>'taken','name'=>'Month Taken','count'=>$row['taken'].' Months');
			$breakdowns[] = array('type'=>'takenyear','name'=>'Year Taken','count'=>$row['takenyear'].' Years');
			$breakdowns[] = array('type'=>'direction','name'=>'View Direction','count'=>$row['direction'].' Directions');
			$breakdowns[] = array('type'=>'viewpoint','name'=>'Photographer Location','count'=>$row['viewpoints'].' Gridsquares');
			$breakdowns[] = array('type'=>'viewcenti','name'=>'Photographer Centisquare','count'=>'unknown');
			$breakdowns[] = array('type'=>'status','name'=>'Classification','count'=>$row['status'].' Classifications');
			$breakdowns[] = array('type'=>'submitted','name'=>'Month Submitted','count'=>$row['submitted'].' Months');
			$breakdowns[] = array('type'=>'submittedyear','name'=>'Year Submitted','count'=>$row['submittedyear'].' Years');

			if ($square->imagecount > 15) {
				$c = $db->getOne("SELECT c FROM gridsquare_group_stat WHERE gridsquare_id = {$square->gridsquare_id}");
				if ($c > 1) {
					array_unshift($breakdowns,array('type'=>'cluster','name'=>'Automatic Cluster <sup style=color:red>New!</sup>','count'=>$c.' Groups'));
				}
			}

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
				$image=new GridImage;
				$image->fastInit($rec);
				$smarty->assign_by_ref('image', $image);
			}
			}
		} elseif (!empty($_GET['by'])) {
			$square->totalimagecount = $square->imagecount;
			
			if (!$db) $db=NewADOConnection($GLOBALS['DSN']);
			$breakdown = array();
			$i = 0;
			
			if (empty($_GET['ht'])) {
				//we only need these columsn if not hiding thumbnails
				$columns = ",title,user_id,gi.realname AS credit_realname,IF(gi.realname!='',gi.realname,user.realname) AS realname,user.realname AS user_realname,gi.comment";
				$gridimage_join = " INNER JOIN user USING(user_id)";
			} else {
				$columns = ',gi.comment';
				$gridimage_join = '';
			}
			
			if ($_GET['by'] == 'class') {
				$breakdown_title = "Category";
				$all = $db->cacheGetAll($cacheseconds,"SELECT imageclass,COUNT(*) AS count,
				gridimage_id $columns
				FROM gridimage gi $gridimage_join
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY imageclass");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"in category <b>{$row[0]}</b>",'count'=>$row[1]);
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
			} elseif ($_GET['by'] == 'cluster') {
				$breakdown_title = "Cluster";
				$all = $db->cacheGetAll($cacheseconds,"SELECT label,COUNT(*) AS count,
				gi.gridimage_id $columns
				FROM gridimage gi $gridimage_join
				INNER JOIN gridimage_group gg ON (gi.gridimage_id = gg.gridimage_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY label");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"in cluster <b>{$row[0]}</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image'] = new GridImage();
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 1) { //todo - browse.php?cluster=... doesnt currently work (square->getImages cant join on gridimage_cluster.)
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=score+desc&amp;displayclass=full&amp;cluster2=1&amp;label=".urlencode($row[0])."&amp;do=1";
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;cluster=".urlencode($row[0]).$extra;
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?cluster=".urlencode($row[0]).$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;cluster=".urlencode($row[0]).$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'tag') {
				$breakdown_title = "Tag";
				$columns = str_replace(',user_id',',gi.user_id',$columns);
				$all = $db->cacheGetAll($cacheseconds,"SELECT IF(prefix!='',CONCAT(prefix,':',tag),tag) AS tag,COUNT(*) AS count,
				gi.gridimage_id $columns
				FROM gridimage gi $gridimage_join
				INNER JOIN gridimage_tag gt ON (gi.gridimage_id = gt.gridimage_id)
				INNER JOIN tag t USING (tag_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND gt.status = 2
				AND $user_crit $custom_where
				GROUP BY tag_id");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"tagged with <b>".htmlentities($row[0])."</b>",'count'=>$row[1]);
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image'] = new GridImage();
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] > 1) {
						$breakdown[$i]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;displayclass=full&amp;searchtext=tags:%22".urlencode($row[0])."%22&amp;do=1";
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;tag=".urlencode($row[0]).$extra;
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?tag=".urlencode($row[0]).$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;tag=".urlencode($row[0]).$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'status') {
				$breakdown_title = "Classification";
				$all = $db->cacheGetAll($cacheseconds,"SELECT moderation_status,COUNT(*) AS count,
				gridimage_id $columns
				FROM gridimage gi $gridimage_join
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY moderation_status 
				ORDER BY moderation_status+0 DESC");
				foreach ($all as $row) {
					$rowname = str_replace('accepted','supplemental',$row[0]);
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
							$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;status=".urlencode($rowname).$extra;
						}
					} elseif ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row[2]}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?status=".urlencode($rowname).$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;status=".urlencode($rowname).$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'user') {
				$breakdown_title = "Contributor";
				$all = $db->cacheGetAll($cacheseconds,"SELECT user.realname AS user_realname,COUNT(*) AS count,
				gridimage_id, user_id $columns
				FROM gridimage gi INNER JOIN user USING(user_id)
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY user_id
				ORDER BY user.realname");
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					$breakdown[$i] = array('name'=>"contributed by <b>{$row[0]}</b>",'count'=>$row[1]);
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
				$breakdown_title = "View Direction";
				$all = $db->cacheGetAll($cacheseconds,"SELECT view_direction,COUNT(*) AS count,
				gridimage_id $columns
				FROM gridimage gi $gridimage_join
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY view_direction");
				$br = empty($_GET['ht'])?'<br/>':'';
				$start = rand(0,max(0,count($all)-20));
				$end = $start + 20;
				foreach ($all as $row) {
					if ($row[0] != -1) {
						$view_direction = ($row[0]%90==0)?strtoupper(heading_string($row[0])):ucwords(heading_string($row[0])) ;
						$breakdown[$i] = array('name'=>"looking <b>$view_direction</b>$br (about {$row[0]} degrees)",'count'=>$row[1]);
					} else {
						$breakdown[$i] = array('name'=>"unknown direction",'count'=>$row[1]);
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
				$breakdown_title = "Photographer Gridsquare";
				$all = $db->cacheGetAll($cacheseconds,"SELECT viewpoint_eastings,COUNT(*) as COUNT,viewpoint_northings,
				gridimage_id $columns
				FROM gridimage gi $gridimage_join
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
							$row[2],
							4,
							$square->reference_index,false);
						if ($posgr == $square->grid_reference) {
							$breakdown[$i] = array('name'=>"taken in this square",'count'=>$row[1]);
						} else {
							$breakdown[$i] = array('name'=>"taken in <b>$posgr</b>",'count'=>$row[1]);
						}
					} else {
						$breakdown[$i] = array('name'=>"photographer position unspecified",'count'=>$row[1]);
						$posgr = '-';
					}
					if (empty($_GET['ht']) && $i >= $start && $i< $end) {
						$breakdown[$i]['image'] = new GridImage();
						$row['grid_reference'] = $square->grid_reference;
						$breakdown[$i]['image']->fastInit($row);
					}
					if ($row[1] == 1) {
						$breakdown[$i]['link']="/photo/{$row['gridimage_id']}";
					} else {
						$breakdown[$i]['link']="/gridref/{$square->grid_reference}?viewpoint={$posgr}".$extra;
						$breakdown[$i]['centi']="/gridref/{$square->grid_reference}?by=centi&amp;viewpoint={$posgr}".$extra;
					}
					$i++;
				}
			} elseif ($_GET['by'] == 'centi') {
				$breakdown_title = "Centisquare<a href=\"/help/squares\">?</a>";
				$all = $db->cacheGetAll($cacheseconds,"SELECT (nateastings = 0),COUNT(*) AS count,gridimage_id,nateastings DIV 100, natnorthings DIV 100
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
					if ($row[1] > 20) {
						$breakdown[$y][$x]['link']="/search.php?searchtext=centi($centi)&amp;orderby=submitted&amp;do=1";
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
				$e = intval($square->getNatEastings()/1000);
				$n = intval($square->getNatNorthings()/1000);
				$breakdown_title = "Photographer Centisquare<a href=\"/help/squares\">?</a>";
				$all = $db->cacheGetAll($cacheseconds,"SELECT (viewpoint_eastings = 0),COUNT(*) AS count,gridimage_id,viewpoint_eastings DIV 100, viewpoint_northings DIV 100
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
						$breakdown[$y][$x]['link']="/search.php?gridref={$square->grid_reference}&amp;distance=1&amp;orderby=submitted&amp;do=1";
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
				$length = (preg_match('/year$/',$_GET['by']))?4:7;
				$column = (preg_match('/^taken/',$_GET['by']))?'imagetaken':'submitted';
				$title = (preg_match('/^taken/',$_GET['by']))?'Taken':'Submitted';
				$breakdown_title = "$title".((preg_match('/year$/',$_GET['by']))?'':' Month');
				$all = $db->cacheGetAll($cacheseconds,"SELECT SUBSTRING($column,1,$length) AS date,COUNT(*) AS count,
				gridimage_id $columns
				FROM gridimage gi $gridimage_join
				WHERE gridsquare_id = '{$square->gridsquare_id}'
				AND $user_crit $custom_where
				GROUP BY SUBSTRING($column,1,$length)");
				$column = (preg_match('/^taken/',$_GET['by']))?'taken':'submitted';
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
			$square->loadCollections();
		
			//todo ideally here we only want to forward teh user_id IF they have images in the square, or a mod, for greater cachablity, but the chicken and the egg thingy....
			$images=$square->getImages($inc_all_user,$custom_where,'order by if(ftf between 1 and 4,ftf,5),gridimage_id');
			$square->totalimagecount = count($images);
		
			//otherwise, lets gether the info we need to display some thumbs
			if ($square->totalimagecount)
			{
			
				if ($_GET['displayclass'] == 'tiles2') {
					$images2 = array();
					$images1 = array();
					foreach ($images as $idx => $image) {
						$images1[] = $image;
						if ($idx%4 == 3) {
							$images2[] = $images1;
							$images1 = array();
						}
					}
					
					$smarty->assign_by_ref('images2', $images2);
				}
					
			
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
		$smarty->assign('map_token', $mosaic->getGridSquareToken($square));
	
		if ($CONF['forums']) {
			$square->assignDiscussionToSmarty($smarty);
		}
		if ($db) 
			$smarty->assign_by_ref('hectad_row',$db->getRow("SELECT * FROM hectad_stat WHERE geosquares >= landsquares AND hectad = '$hectad' AND largemap_token != '' LIMIT 1"));
				
		//look for images from here...
		$sphinx = new sphinxwrapper();
		if (!isset($viewpoint_count) && $viewpoint_count = $sphinx->countImagesViewpoint($square->nateastings,$square->natnorthings,$square->reference_index,$square->grid_reference)) {
			$smarty->assign('viewpoint_count', $viewpoint_count);
			#$smarty->assign('viewpoint_query', $sphinx->q);
		}
		
		if (!isset($mention_count) && $mention_count = $sphinx->countQuery("{$square->grid_reference} -grid_reference:{$square->grid_reference}","_images")) {
			$smarty->assign('mention_count', $mention_count);
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

if (!isset($_GET['inner'])) {
	#//lets find some recent photos
	#new RecentImageList($smarty);

	//lets add an overview map too
	if ($grid_ok) {
		$overview=new GeographMapMosaic('largeoverview');
		$overview->type_or_user = -1;
		$overview->setCentre($square->x,$square->y); //does call setAlignedOrigin
		$smarty->assign('marker', $overview->getSquarePoint($square));


//TODO if centisquare is specified use that to plot a circle!

		//lets add an rastermap too
		$rastermap = new RasterMap($square,false,$square->natspecified);
		$rastermap->addLatLong($lat,$long);
		$smarty->assign_by_ref('rastermap', $rastermap);

	} else {
		$overview=new GeographMapMosaic('overview');	
	}
	$overview->assignToSmarty($smarty, 'overview');
}

#}

$smarty->display($template,$cacheid);

	
?>
