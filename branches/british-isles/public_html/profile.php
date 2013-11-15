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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');

if (empty($_POST) && empty($_GET['style']) && empty($_REQUEST['edit'])) {
        init_session_or_cache(3600, 360); //cache publically, and privately
} else {
	init_session();
}

$smarty = new GeographPage;
$template='profile.tpl';
$cacheid='';
$profile = array();

if (isset($_REQUEST['edit']))
{
	//must be logged in to proceed with an edit
	$USER->login();
	
	$template='profile_edit.tpl';
	
	//save changes?
	if (isset($_POST['savechanges']))
	{
		$errors=array();
		$ok=$USER->updateProfile($_POST, $errors);
		
		if ($ok)
		{
			//show the user their new profile
			$template='profile.tpl';	
			
			$ab=floor($USER->user_id/10000);
			
			//clear anything with a cache id userxyz|
			$smarty->clear_cache(null, "user$ab|{$USER->user_id}");
			
			$profile =& $USER;
		}
		else
		{
			$profile=new GeographUser($USER->user_id);
			
			$smarty->assign('errors', $errors);
			//ensure we keep submission intact
			foreach($_POST as $name=>$value)
			{
				$profile->$name=strip_tags(stripslashes($value));
			}
		}
	} 
	else
	{
		$profile=new GeographUser($USER->user_id);
	}
	$smarty->assign('pagesizes', array(5,10,15,20,30,50));
	$smarty->assign('delays', array(2,3,4,5,6,10,12));
	$smarty->assign('ticket_options', array(
	'all' => 'Notifications for all suggestions' ,
	'major' => 'Only Major suggestions',
	//'digest' => 'Receive Digest emails Once per Day',
	'none' => 'No Initial Notifications' ));

	$profile->getStats();
	$profile->md5_email = md5(strtolower($profile->email));

	$smarty->assign_by_ref('profile', $profile);
}
elseif (isset($_REQUEST['notifications']))
{
	//must be logged in to proceed with an edit
	$USER->login();

	$template='profile_notifications.tpl';

	//save changes?
	if (isset($_POST['savechanges'])) {
		if (!empty($_POST['freq'])) {
			$str = $_POST['freq'].'|'.implode(',',$_POST['items']);
			$USER->setPreference('notification.myphotos',$str);

			header("Location: /profile.php");
			exit;
		}
	} else {
		$profile=new GeographUser($USER->user_id);

		$freqs = array('disabled','daily','weekly','monthly');

		$items = array(
		'featured'=>array('title'=>'Featured Images'),
		'collection'=>array('title'=>'In Collections'),
		'forum'=>array('title'=>'Forum/Galleries'),
		'thumbed'=>array('title'=>'Thumbed'),
		'gallery'=>array('title'=>'Showcase Gallery'),
		'squares'=>array('title'=>'My Squares (images submitted to squares you\'ve submitted images to)'),
		'photos'=>array('title'=>'Photo Descriptions (Other photos that link directly to yours)'),
		);


		$bits = explode('|',$USER->getPreference('notification.myphotos',''));
		$smarty->assign('freq',$bits[0]);
		if (!empty($bits[1])) {
			foreach (explode(",",$bits[1]) as $key) {
				$items[$key]['checked'] = true;
			}
		}

		$smarty->assign_by_ref('freqs', $freqs);
		$smarty->assign_by_ref('items', $items);

	}
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
	elseif (isset($_GET['id']) && preg_match('/^[0-9]+$/' , $_GET['id']))
	{
		$uid=$_GET['id'];
	} 
	elseif (isset($_GET['user']) && isValidRealName($_GET['user']))
	{
		if ($_GET['user'] == $USER->nickname)
		{
			$uid=$USER->user_id;
		} 
		else 
		{
			$profile=new GeographUser();
			$profile->loadByNickname($_GET['user']);
			$uid=$profile->user_id;
		}
		if ($uid==0)
		{
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$smarty->display('static_404.tpl');
			exit;
		}
	}

	if ($uid==0 || $uid==$USER->user_id)
	{
		//no uid given, so we'll assume user was trying to access their own
		//profile, in which case, they must login...
		$USER->login();

		//to reach here, user must be logged in...
		$uid=$USER->user_id;
		
		if (isset($_GET['hide_message'])) {
			#header("HTTP/1.0 200 OK");
			#header("Content-Length: 0");
			
			$USER->countTickets();
			
			$_SESSION['last_ticket_time'] = $USER->last_ticket_time;

			print '1';
			
			exit;
		}
		
	} else {
		if (isset($_GET['user']) || isset($_GET['u'])) {
			header("HTTP/1.0 301 Moved Permanently");
			header("Status: 301 Moved Permanently");
			header("Location: /profile/{$uid}".(isset($_GET['all'])?'/all':''));
			exit;
		}
	}
	
	
	if ($uid==$USER->user_id) {
		$level = 1;
		customExpiresHeader(60,false,true);
	} elseif ($USER->hasPerm('moderator')) {
		$level = 2;
		customExpiresHeader(120,false,true);
	} else {
		$level = 0;
		#customExpiresHeader(600,false,true);
	}
	
	$ab=floor($uid/10000);
	
	$cacheid="user$ab|{$uid}|{$level}";
	
	if (isset($_GET['all']) && $USER->registered) {
		$limit = 5000;
	} elseif (isset($_GET['more'])) {
		$limit = 1000;
	} else {
		$limit = 100;
	}
	$cacheid.="_$limit";
	
	if ($CONF['template']=='archive') {
		 $_GET['expand'] = true;
	}
	
	if (!empty($_GET['expand']) || $USER->expand_about == 1) {
		$cacheid .= "E";
	}
	if (isset($_GET['reject']) && empty($_GET['reject'])) {
		$cacheid .= "N";
	}
	if (empty($_GET['id'])) {
		$cacheid .= "S";
	}

	if (!$smarty->is_cached($template, $cacheid))
	{
		if (isset($_GET['all']) || isset($_GET['more']) || isset($_GET['expand'])) {
			dieUnderHighLoad();
		}
		
		require_once('geograph/imagelist.class.php');
		require_once('geograph/gridimage.class.php');
		require_once('geograph/gridsquare.class.php');

		if (!$profile) {
			$profile=new GeographUser($uid);
		}
		
		if (!empty($_GET['a']) && $_GET['a'] == $profile->realname) {
			header("HTTP/1.0 301 Moved Permanently");
			header("Status: 301 Moved Permanently");
			header("Location: /profile/{$uid}");
			exit;
		}
		
		if ($profile->user_id==0)
		{
			header("HTTP/1.0 404 Not Found");
			header("Status: 404 Not Found");
			$smarty->display('static_404.tpl');
			exit;
		}
		
		$profile->getStats(!empty($_GET['id']));
		
		if ($uid==$USER->user_id && empty($_GET['id'])) {
			$profile->countTickets();
			$USER->tickets = $profile->tickets;
			$USER->last_ticket_time = $profile->last_ticket_time;
			if (!empty($_SESSION['last_ticket_time']) && $profile->last_ticket_time <= $_SESSION['last_ticket_time']) {
				$profile->tickets = 0;
			}
		}
		
		if (empty($_GET['id'])) {
			$smarty->assign('simplified',1);
		} elseif ($CONF['forums']) {
			$profile->getLatestBlogEntry();
		}

		if (!empty($_GET['a'])) {
			$smarty->assign('page_title', 'Profile for '.$_GET['a'].'/'.$profile->realname);
			$smarty->assign('meta_description', 'Profile page for '.$_GET['a'].'/'.$profile->realname.', listing recent images, statistics and links to further information.');
		} else {
			$smarty->assign('page_title', 'Profile for '.$profile->realname);
			$smarty->assign('meta_description', 'Profile page for '.$profile->realname.', listing recent images, statistics and links to further information.');
		}
		
		$smarty->assign_by_ref('profile', $profile);
		
		$images=new ImageList;
		
		if ($uid==$USER->user_id || $USER->hasPerm('moderator')) {
			if (isset($_GET['reject']) && empty($_GET['reject'])) {
				$statuses=array('pending', 'accepted', 'geograph');
			} else {
				$statuses='';//all
			} 
		} else
			$statuses=array('accepted', 'geograph');
		
		$images->getImagesByUser($uid, $statuses,'gridimage_id desc',$limit,true);
		$images->assignSmarty($smarty, 'userimages');
		
		if (count($images->images) == $limit) {
			$smarty->assign('limit',$limit);
		}	
		
		if (count($images->images)) {
			$overview=new GeographMapMosaic('overview');
			$overview->type_or_user = $uid;
			$overview->assignToSmarty($smarty, 'overview');
		}
		
		$profile->md5_email = md5(strtolower($profile->email));
	} else {
		$profile=new GeographUser();
		$profile->user_id = $uid;
		if ($uid==$USER->user_id) {
			if (empty($_SESSION['last_ticket_time']) || $USER->last_ticket_time > $_SESSION['last_ticket_time']) {
				$profile->tickets = $USER->tickets;
			}
		}
		$smarty->assign_by_ref('profile', $profile);
	}
}

function smarty_function_TruncateWithExpand($input,$more = 'Click here to Read More...') {
	global $USER;
	
	if (strlen($input) > 300 && empty($_GET['expand'])
			&& (empty($USER->expand_about) || $USER->expand_about == 2)
		) {
	
		if (strpos($input,'[--more--]') !== FALSE && $USER->expand_about != 2) {
			$bits = explode('[--more--]',$input,2);
			preg_match('/^(.{2000,}?)(?![\w\d\[\]\'])/s',$bits[0],$m); //still impose a hard limit of 2000 chars!
			$input = $m[1]?$m[1]:$bits[0];
			$after = $bits[1];
		} else {
			preg_match('/^(.{300,}?)(?![\w\d\[\]\'])(.*)/s',$input,$m);
			$input = str_replace('[--more--]',' ',$m[1]);
			if (!preg_match("/[\.\n]+\$/",$input)) {
				$input .= " ...";
			}
			$after = $m[2];
		}
		if (trim($after)) {
			if (preg_match('/\(<small>(.*)<\/small>\)\s*/',$more,$m)) {
				$more = preg_replace('/\(<small>.*<\/small>\)\s*/','',$more);
				$before = $m[0];
			}
			if (!trim($input)) {
				$before = "";
			} 
			
			$input .= "<div style=\"text-align:center;border-top:1px solid silver;margin-top:10px\">$before<a href=\"?expand=1\">$more</a></div>";
		
		}
	} else {
		$input = str_replace('[--more--]',' ',$input);
	}
	
	return $input;
}
$smarty->register_modifier("TruncateWithExpand", "smarty_function_TruncateWithExpand");


if (!empty($_GET['a']))
	$smarty->assign('credit_realname', $_GET['a']);

$smarty->display($template, $cacheid);

?>
