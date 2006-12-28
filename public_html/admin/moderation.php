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



$db = NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');   

$smarty = new GeographPage;

//doing some moderating?
if (isset($_GET['gridimage_id']))
{
	//user may have an expired session, or playing silly buggers,
	//either way, we want to check for admin status on the session
	if ($USER->hasPerm('moderator'))
	{
	
		$gridimage_id=intval($_GET['gridimage_id']);
		$status=$_GET['status'];

		$image=new GridImage;
		if ($image->loadFromId($gridimage_id))
		{
			if (isset($_GET['remoderate'])) 
			{
				$status = $db->Quote($status);
				$db->Execute("REPLACE INTO moderation_log SET user_id = {$USER->user_id}, gridimage_id = $gridimage_id, new_status=$status, old_status='{$image->moderation_status}',created=now()");
				print "status $status recorded";
			} 
			else
			{
				$info=$image->setModerationStatus($status, $USER->user_id);
				echo $info;

				//clear caches involving the image
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_0_0");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_0_1");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_1_0");
				$smarty->clear_cache('view.tpl', "{$gridimage_id}_1_1");

				//clear the users profile cache
				$smarty->clear_cache('profile.tpl', "{$image->user_id}_0");
				$smarty->clear_cache('profile.tpl', "{$image->user_id}_1");
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

///////////////////////////////
// moderator only!
$USER->mustHavePerm('moderator');

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit']))?min(100,intval($_GET['limit'])):50;

#############################

//lock the table so nothing can happen in between! (leave others as WRITE so they dont get totally locked)
$db->Execute("LOCK TABLES 
gridsquare_moderation_lock WRITE, 
gridsquare_moderation_lock l WRITE,
moderation_log WRITE,
gridsquare WRITE,
gridsquare gs WRITE,
gridimage gi WRITE,
user WRITE,
gridprefix WRITE");

#############################
# find the list of squares with self pending images, and exclude them...

$sql = "select distinct gridsquare_id 
from 
	gridimage as gi
where
	(moderation_status = 2 or user_status!='') and
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

if (!isset($_GET['moderator']) && !isset($_GET['remoderate'])) {

	$count = $db->getRow("select count(*) as total,sum(created > date_sub(now(),interval 60 day)) as recent from moderation_log WHERE user_id = {$USER->user_id}");
	if ($count['total'] == 0) {
		$_GET['remoderate'] = 1;
	} elseif ($count['recent'] < 5) {
		$_GET['remoderate'] = 1;
		$limit = 10;
	}
}

$sql_columns = $sql_from = '';
if (isset($_GET['moderator'])) {
	
	if (isset($_GET['verify'])) {
		$sql_columns = ", new_status";
		$sql_from = " inner join moderation_log on(moderation_log.gridimage_id=gi.gridimage_id)";
		$sql_where = "moderation_log.user_id = ".intval($_GET['moderator']);
		$sql_order = "gridimage_id desc";
		
		//todo also show images originally moderated by this user, and verified by someone else, will require displaying who the verifier moderator is
	} else {
		$sql_where = "(moderation_status != 2) and moderator_id = ".intval($_GET['moderator']);
		$sql_order = "gridimage_id desc";
	}
	$smarty->assign('moderator', 1);
	
} elseif (isset($_GET['remoderate'])) {
	$sql_where = "moderation_status != 2 and moderator_id != {$USER->user_id}";
	$sql_order = "gridimage_id desc";
	$smarty->assign('remoderate', 1);
} else {
	$sql_where = "(moderation_status = 2 or user_status!='')";
	$sql_order = "gridimage_id asc";
}


$sql = "select gi.*,grid_reference,user.realname,imagecount $sql_columns
from 
	gridimage as gi
	inner join gridsquare as gs
		using(gridsquare_id)
	$sql_from
	left join gridsquare_moderation_lock as l
		on(gi.gridsquare_id=l.gridsquare_id and lock_obtained > date_sub(NOW(),INTERVAL 1 HOUR) )
	inner join user
		on(gi.user_id=user.user_id)
where
	$sql_where
	and (l.gridsquare_id is null OR 
			(l.user_id = {$USER->user_id} AND lock_type = 'modding') OR
			(l.user_id != {$USER->user_id} AND lock_type = 'cantmod')
		)
order by
	$sql_order
limit $limit";
//implied: and user_id != {$USER->user_id}
// -> because squares with users images are locked



#############################
# fetch the list of images...

$images=new ImageList(); 

$c = $images->_getImagesBySql($sql);


foreach ($images->images as $i => $image) {
	if ($image->viewpoint_eastings) {
		//note $image DOESNT work non php4, must use $images->images[$i]
		$images->images[$i]->getSubjectGridref();
		$images->images[$i]->distance = sprintf("%0.3f",
			sqrt(pow($images->images[$i]->grid_square->nateastings-$images->images[$i]->viewpoint_eastings,2)+pow($images->images[$i]->grid_square->natnorthings-$images->images[$i]->viewpoint_northings,2))/1000);
			
		if (intval($images->images[$i]->grid_square->nateastings/1000) != intval($images->images[$i]->viewpoint_easting/1000)
			&& intval($images->images[$i]->grid_square->natnorthings/1000) != intval($images->images[$i]->viewpoint_northings/1000)
			&& $images->images[$i]->distance > 0.2) {
			$images->images[$i]->different_square = true;
		}
	}	
	$db->Execute("REPLACE INTO gridsquare_moderation_lock SET user_id = {$USER->user_id}, gridsquare_id = {$image->gridsquare_id}");

}

#############################

$db->Execute("UNLOCK TABLES");

#############################

$images->assignSmarty($smarty, 'unmoderated');
		
$smarty->display('admin_moderation.tpl');
	
?>
