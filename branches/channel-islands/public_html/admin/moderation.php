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

init_session();

if (isset($CONF['curtail_level']) && $CONF['curtail_level'] > 5 && strpos($_SERVER['HTTP_REFERER'],'editimage') === FALSE) {
	header("HTTP/1.1 503 Service Unavailable");
	die("the servers are currently very busy - moderation is disabled to allow things to catch up, will be automatically re-enabled when load returns to normal");
}

if (!empty($_GET['style'])) {
	$USER->getStyle();
	if (!empty($_SERVER['QUERY_STRING'])) {
		$query = preg_replace('/style=(\w+)/','',$_SERVER['QUERY_STRING']);
		header("HTTP/1.0 301 Moved Permanently");
		header("Status: 301 Moved Permanently");
		header("Location: /admin/moderation.php?".$query);
		exit;
	}
	header("Location: /admin/moderation.php");
	exit;
}

customGZipHandlerStart();

$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');   

$smarty = new GeographPage;

//doing some moderating?
if (isset($_GET['gridimage_id']))
{
	//user may have an expired session, or playing silly buggers,
	//either way, we want to check for admin status on the session
	if ($USER->hasPerm('moderator') || isset($_GET['remoderate']))
	{
	
		$gridimage_id=intval($_GET['gridimage_id']);
		$status=$_GET['status'];

		$image=new GridImage;
		if ($image->loadFromId($gridimage_id))
		{
			if (isset($_GET['remoderate'])) 
			{
				if ($USER->hasPerm('basic'))
				{
					$status = $db->Quote($status);
					$db->Execute("REPLACE INTO moderation_log SET user_id = {$USER->user_id}, gridimage_id = $gridimage_id, new_status=$status, old_status='{$image->moderation_status}',created=now(),type = 'dummy'");
					print "classification $status recorded";
					
					
					$mkey = $USER->user_id;
					$memcache->name_delete('udm',$mkey);
				}
				else
				{
					echo "NOT LOGGED IN";
				}
			} 
			else
			{
				//we really need this not be interupted
				ignore_user_abort(TRUE);
				set_time_limit(3600);
				
				$status2 = $db->Quote($status);
				$db->Execute("INSERT INTO moderation_log SET user_id = {$USER->user_id}, gridimage_id = $gridimage_id, new_status=$status2, old_status='{$image->moderation_status}',created=now(),type = 'real'");
				
				$info=$image->setModerationStatus($status, $USER->user_id);
				echo $info;
				flush;

				if ($status == 'rejected')
				{
					$ticket=new GridImageTroubleTicket();
					$ticket->setSuggester($USER->user_id);
					$ticket->setModerator($USER->user_id);
					$ticket->setPublic('everyone');
					$ticket->setImage($gridimage_id);
					if (!empty($_GET['comment'])) {
						$ticket->setNotes("Auto-generated ticket, as a result of Moderation. Rejecting this image because: ".stripslashes($_GET['comment']));
					} else {
						$ticket->setNotes("Auto-generated ticket, as a result of Moderation. Please leave a comment to explain the reason for rejecting this image.");
					}
					$status=$ticket->commit('open');
					
					echo " <a href=\"/editimage.php?id={$gridimage_id}\"><B>View Ticket</b></a>";
					
				}

				//clear caches involving the image
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_0_0");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_0_1");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_1_0");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_1_1");

				//clear the users profile cache
				$smarty->clear_cache('profile.tpl', "{$image->user_id}_0");
				$smarty->clear_cache('profile.tpl', "{$image->user_id}_1");
				
				$memcache->name_delete('us',$image->user_id);
			}
		}
		else
		{
			echo "FAIL";
		}
	
		
	}
	else
	{
		echo "NOT LOGGED IN";
	}
	
	
	
	exit;
}

if (!empty($_GET['abandon'])) {
	$USER->hasPerm('moderator') || $USER->mustHavePerm("ticketmod");
	
	$db->Execute("DELETE FROM gridsquare_moderation_lock WHERE user_id = {$USER->user_id}");
	
	$db->Execute("DELETE FROM gridimage_moderation_lock WHERE user_id = {$USER->user_id}");
	
	header("Location: /admin/");
	exit;
}


$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(100,intval($_GET['limit'])):15;
if ($limit > 15) {
	dieUnderHighLoad(0.3);
} else {
	dieUnderHighLoad(0.8);
}


if (!empty($_GET['relinquish'])) {
	$USER->mustHavePerm('basic');
	$db->Execute("UPDATE user SET rights = REPLACE(REPLACE(rights,'traineemod',''),'moderator','') WHERE user_id = {$USER->user_id}");
	
	//reload the user object
	$_SESSION['user'] =& new GeographUser($USER->user_id);
	
	header("Location: /profile.php?edit=1");
	exit;

} elseif (!empty($_GET['apply'])) {
	$USER->mustHavePerm('basic');
	
	if ($_GET['apply'] == 2) {
	
		$db->Execute("UPDATE user SET rights = CONCAT(rights,',traineemod') WHERE user_id = {$USER->user_id}");
		
		$mods=$db->GetCol("select email from user where FIND_IN_SET('admin',rights)>0;");			
		
		$url = 'http://'.$_SERVER['HTTP_HOST'].'/admin/moderator_admin.php?stats='.$USER->user_id;
		
		mail(implode(',',$mods), "[Geograph] Moderator Application ({$USER->user_id})", 
"Dear Admin, 

I have just completed verification.

Comments: 
{$_POST['comments']}

click the following link to review the application:	

$url

Regards, 

{$USER->realname}".($USER->nickname?" (aka {$USER->nickname})":''),
				"From: {$USER->realname} <{$USER->email}>");
				
		header("Location: /profile.php");
		exit;
	} 
	
	$count = $db->getRow("select count(*) as total,sum(created > date_sub(now(),interval 60 day)) as recent from moderation_log WHERE user_id = {$USER->user_id} AND type = 'dummy'");
	if ($count['total'] > 0) {
		$limit = 10;
	}
	
	//make sure they only do verifications
	$_GET['remoderate'] = 1;
	
	$smarty->assign('apply', 1);
	
} elseif (isset($_GET['moderator'])) {
	$USER->mustHavePerm('admin');
} else {
	$USER->mustHavePerm('moderator');
}


#############################

//lock the table so nothing can happen in between! (leave others as READ so they dont get totally locked)
$db->Execute("LOCK TABLES 
gridsquare_moderation_lock WRITE, 
gridsquare_moderation_lock l WRITE,
moderation_log WRITE,
gridsquare READ,
gridsquare gs READ,
gridimage gi READ,
user READ,
gridprefix READ,
user v READ,
user m READ");

#############################
# find the list of squares with self pending images, and exclude them...

$sql = "select distinct gridsquare_id 
from 
	gridimage as gi
where
	(moderation_status = 2) and
	gi.user_id = {$USER->user_id}
order by null";

$recordSet = &$db->Execute($sql);
while (!$recordSet->EOF) 
{
	$db->Execute("REPLACE INTO gridsquare_moderation_lock SET user_id = {$USER->user_id}, gridsquare_id = {$recordSet->fields['gridsquare_id']},lock_type = 'cantmod'");

	$recordSet->MoveNext();
}
$recordSet->Close(); 

#############################
# define the images to moderate

if ($CONF['remod_enable'] && !isset($_GET['moderator']) && !isset($_GET['review']) && !isset($_GET['remoderate'])) {
#
	$count = $db->getRow("select count(*) as total,sum(created > date_sub(now(),interval {$CONF['remod_recent_days']} day)) as recent from moderation_log WHERE user_id = {$USER->user_id} AND type='dummy'");
	#$imgrow = $db->getRow("select * from gridimage as gi inner join gridsquare as gs using(gridsquare_id) where moderation_status != 2 and moderator_id != {$USER->user_id} and submitted > date_sub(now(),interval {$CONF['remod_recent_days']} day) limit 1");
	#$imgrow = $db->getRow("select * from gridimage where moderation_status != 2 and moderator_id != {$USER->user_id} and submitted > date_sub(now(),interval {$CONF['remod_recent_days']} day) limit 1");
	$imgrow = $db->getRow("select * from gridimage as gi where moderation_status != 2 and moderator_id != {$USER->user_id} and submitted > date_sub(now(),interval {$CONF['remod_days']} day) limit 1");
	$imgexists = count($imgrow); trigger_error("---- <{$imgexists}> <{$count['total']}> <{$count['recent']}>", E_USER_NOTICE);
	# 0 0 ""
	if (count($imgrow)) {
		if ($count['total'] == 0) {
			$_GET['remoderate'] = 1;
			$limit = $CONF['remod_count_init'];
		} elseif ($count['recent'] < $CONF['remod_recent_count']) {
			$_GET['remoderate'] = 1;
			$limit = $CONF['remod_count'];
		}
	}
}
$sql_where2 = "
	and (l.gridsquare_id is null OR 
			(l.user_id = {$USER->user_id} AND lock_type = 'modding') OR
			(l.user_id != {$USER->user_id} AND lock_type = 'cantmod')
		)";
$sql_columns = $sql_from = '';
if (isset($_GET['review'])) {
	$mid = intval($USER->user_id);
	
	$sql_columns = ", new_status,moderation_log.user_id as ml_user_id,v.realname as ml_realname";
	$sql_from = " inner join moderation_log on(moderation_log.gridimage_id=gi.gridimage_id AND moderation_log.type='real')
				inner join user v on(moderation_log.user_id=v.user_id)";
	
	$sql_where = "(moderation_log.user_id = $mid && gi.moderator_id != $mid)";
	
	$sql_where = "($sql_where and moderation_status != new_status)";
			
	$sql_order = "gridimage_id desc";
	
	$smarty->assign('review', 1);
	$sql_where2 = '';
} elseif (isset($_GET['moderator'])) {
	$mid = intval($_GET['moderator']);
		
	if (isset($_GET['verify'])) {
		$sql_columns = ", new_status,moderation_log.user_id as ml_user_id,v.realname as ml_realname";
		$sql_from = " inner join moderation_log on(moderation_log.gridimage_id=gi.gridimage_id AND moderation_log.type='dummy')
					inner join user v on(moderation_log.user_id=v.user_id)";
		if ($mid == 0) {
			$sql_where = "1";
		} else {
			$sql_where = "(moderation_log.user_id = $mid or gi.moderator_id = $mid)";
		}
		
		if ($_GET['verify'] == 2) {
			$sql_where = "($sql_where and moderation_status != new_status)";
		}
		$sql_order = "gridimage_id desc";
	} elseif ($mid == 0) {
		$sql_columns = ", m.realname as mod_realname";
		$sql_where = "(moderation_status != 2) and moderator_id != {$USER->user_id}";
		$sql_from = " inner join user m on(moderator_id=m.user_id)";
		$sql_order = "gridimage_id desc";
	} else {
		$sql_where = "(moderation_status != 2) and moderator_id = $mid";
		$sql_order = "gridimage_id desc";
	}
	
	if (isset($_GET['status']) && ($statuses = $_GET['status']) ) {
		if (is_array($statuses))
			$sql_where.=" and moderation_status in ('".implode("','", $statuses)."') ";
		elseif (strpos($statuses,',') !== FALSE)
			$sql_where.=" and moderation_status in ('".implode("','", explode(',',$statuses))."') ";
		elseif (is_int($statuses)) 
			$sql_where.=" and moderation_status = $statuses ";
		else
			$sql_where.=" and moderation_status = '$statuses' ";
	}
	
	$smarty->assign('moderator', 1);
	$sql_where2 = '';
} elseif (isset($_GET['user_id'])) {
	$sql_where = "gi.user_id = ".intval($_GET['user_id']);
	$sql_order = "gridimage_id desc";
	$smarty->assign('remoderate', 1);
} elseif (isset($_GET['image'])) {
	$sql_where = "gridimage_id = ".intval($_GET['image']);
	$sql_order = "gridimage_id desc";
	$smarty->assign('remoderate', 1);
} elseif (isset($_GET['remoderate'])) {
	$sql_where = "moderation_status != 2 and moderator_id != {$USER->user_id} and submitted > date_sub(now(),interval {$CONF['remod_days']} day) ";
	$sql_order = "rand()";
	#$sql_order = "gridimage_id desc";
	$smarty->assign('remoderate', 1);
} else {
	$sql_where = "(moderation_status = 2)";
	$sql_order = "gridimage_id asc";
}

if (isset($_GET['xmas'])) {
	
	$ii = $db->getAll("select gridsquare_id from gridimage as gi where imageclass like '%christmas%' and moderation_status = 'pending' and submitted > date_sub(now(),interval 1 day) limit 10");
	
	foreach ($ii as $i => $row) {
		$db->Execute("REPLACE INTO gridsquare_moderation_lock SET user_id = {$USER->user_id}, gridsquare_id = {$row['gridsquare_id']}");
	}
	$sql_where .= " AND (lock_type = 'modding' OR imageclass like '%christmas%')";
}


$sql = "select gi.*,grid_reference,user.realname,imagecount,coalesce(images,0) as images $sql_columns
from 
	gridimage as gi
	inner join gridsquare as gs
		using(gridsquare_id)
	$sql_from
	left join gridsquare_moderation_lock as l
		on(gi.gridsquare_id=l.gridsquare_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) )
	inner join user
		on(gi.user_id=user.user_id)
	left join user_stat us
		on(gi.user_id=us.user_id)
where
	$sql_where
	$sql_where2
	and submitted < date_sub(now(),interval 30 minute)
group by gridimage_id
order by
	$sql_order
limit $limit";
//implied: and user_id != {$USER->user_id}
// -> because squares with users images are locked


#############################
# fetch the list of images...

$images=new ImageList(); 
$images->_setDB($db);
$c = $images->_getImagesBySql($sql);


$realname = array();
foreach ($images->images as $i => $image) {
	$token=new Token;
	$fix6fig = 0;
	if ($image->use6fig && ($image->natgrlen > 6 || $image->viewpoint_grlen > 6)) {
		$fix6fig = 1;
		$images->images[$i]->use6fig = 0;
	}
	$token->setValue("g", $images->images[$i]->getSubjectGridref(true));
	if ($image->viewpoint_eastings) {
		//note $image DOESNT work non php4, must use $images->images[$i]
		//move the photographer into the center to match the same done for the subject
		$correction = ($images->images[$i]->viewpoint_grlen > 4)?0:500;
		$images->images[$i]->distance = sprintf("%0.2f",
			sqrt(pow($images->images[$i]->grid_square->nateastings-$images->images[$i]->viewpoint_eastings-$correction,2)+pow($images->images[$i]->grid_square->natnorthings-$images->images[$i]->viewpoint_northings-$correction,2))/1000);
		
		if (intval($images->images[$i]->grid_square->nateastings/1000) != intval($images->images[$i]->viewpoint_eastings/1000)
			|| intval($images->images[$i]->grid_square->natnorthings/1000) != intval($images->images[$i]->viewpoint_northings/1000))
			$images->images[$i]->different_square_true = true;
		
		if ($images->images[$i]->different_square_true && $images->images[$i]->subject_gridref_precision==1000)
			$images->images[$i]->distance -= 0.5;
		
		if ($images->images[$i]->different_square_true && $images->images[$i]->distance > 0.1)
			$images->images[$i]->different_square = true;
	
		$token->setValue("p", $images->images[$i]->getPhotographerGridref(true));
	}	
	if (isset($image->view_direction) && strlen($image->view_direction) && $image->view_direction != -1) {
		$token->setValue("v", $image->view_direction);
	}
	$images->images[$i]->reopenmaptoken = $token->getToken();
	if ($fix6fig) {
		$images->images[$i]->subject_gridref = '';//kill the cache so will be done again with use6fig;
		$images->images[$i]->photographer_gridref = '';
		$images->images[$i]->use6fig = 1;
	}
	
	$db->Execute("REPLACE INTO gridsquare_moderation_lock SET user_id = {$USER->user_id}, gridsquare_id = {$image->gridsquare_id}");

	$fullpath=$images->images[$i]->_getFullpath();
	list($width, $height, $type, $attr)=getimagesize($_SERVER['DOCUMENT_ROOT'].$fullpath);
	if (max($width,$height) < 500 || min($width,$height) < 100)
		$images->images[$i]->sizestr = $attr;
}

#############################

$db->Execute("UNLOCK TABLES");

#############################

$images->assignSmarty($smarty, 'unmoderated');
		
//what style should we use?
$style = $USER->getStyle();
$smarty->assign('maincontentclass', 'content_photo'.$style);

$smarty->display('admin_moderation.tpl',$style);

