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





//lets find all unmoderated submissions
$images=new ImageList('pending', 'submitted asc', 50,true);
$images->assignSmarty($smarty, 'unmoderated');


		
$smarty->display('admin_moderation.tpl');

	
?>
