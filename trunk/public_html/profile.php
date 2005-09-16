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
	
init_session();

$smarty = new GeographPage;
$template='profile.tpl';	
$cacheid='';

//this script works in two modes - editing the currently logged in users profile
//or viewing any users profile in read-only fashion - here we decide which to do
if (isset($_REQUEST['edit']))
{
	//must be logged in to proceed with an edit
	$USER->login();
	
	$template='profile_edit.tpl';
	$profile=new GeographUser($USER->user_id);
	
	//save changes?
	if (isset($_POST['savechanges']))
	{
		$errors=array();
		$ok=$USER->updateProfile($_POST, $errors);
		
		if ($ok)
		{
			//show the user their new profile
			$template='profile.tpl';	
			
			//clear anything with a cache id userxyz|
			$smarty->clear_cache(null, "user{$USER->user_id}");
		}
		else
		{
			$smarty->assign('errors', $errors);
			//ensure we keep submission intact
			foreach($_POST as $name=>$value)
			{
				$profile->$name=stripslashes($value);
			}
		}
	}
	
	$smarty->assign_by_ref('profile', $profile);
}


if ($template=='profile.tpl')
{
	//assume viewing logged in user
	$uid=$USER->user_id;

	//see if we were passed a param
	if (isset($_GET['u']) && preg_match('/^[0-9]+$/' , $_GET['u']))
	{
		$uid=$_GET['u'];
	}

	if ($uid==0)
	{
		//no uid given, so we'll assume user was trying to access their own
		//profile, in which case, they must login...
		$USER->login();

		//to reach here, user must be logged in...
		$uid=$USER->user_id;
	}

	$isself=($uid==$USER->user_id)?1:0;

	$cacheid="user{$uid}|{$isself}";
	
	
	if (!$smarty->is_cached($template, $cacheid))
	{
		require_once('geograph/imagelist.class.php');
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');

		$profile=new GeographUser($uid);
		$profile->getStats();

		$smarty->assign('page_title', 'Profile for '.$profile->realname);
		$smarty->assign_by_ref('profile', $profile);
		
		$images=new ImageList;
		
		if ($uid==$USER->user_id)
			$statuses=array('rejected', 'pending', 'accepted', 'geograph');
		else
			$statuses=array('pending', 'accepted', 'geograph');
		
		$images->getImagesByUser($uid, $statuses,'submitted desc');
		$images->assignSmarty($smarty, 'userimages');
		
		//find images with outstanding trouble tickets
		if ($uid==$USER->user_id)
		{
			$troubled=new ImageList;
			$troubled->getTroubledImagesByUser($uid);
			$troubled->assignSmarty($smarty, 'troubled');
		}
		
	}
}

$smarty->display($template, $cacheid);

	
?>
