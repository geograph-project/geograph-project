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
	
init_session();

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
	if (isset($_POST['savechanges']))
	{
		
	} 
	else
	{
		$profile=new GeographUser($USER->user_id);
		
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');  

		$subs = $db->getAll("select topic_id,topic_title from geobb_send_mails Ts inner join geobb_topics Tt using (topic_id) where user_id = {$USER->user_id}");		
		$smarty->assign_by_ref("subs",$subs);
		$smarty->assign("sub_count",count($subs));
		
		$arr = array('general','test');
		
		$n = array();
		foreach($arr as $idx => $key) {
			$n[$key] = 'checked="checked"';
		}
		
		$smarty->assign_by_ref("notification",$n);
		
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
	} elseif ($USER->hasPerm('moderator')) {
		$level = 2;
	} else {
		$level = 0;
	}
	
	$ab=floor($uid/10000);
	
	$cacheid="user$ab|{$uid}|{$level}";
	
	if (isset($_GET['all'])) {
		$limit = 5000;
	} elseif (isset($_GET['more'])) {
		$limit = 1000;
	} else {
		$limit = 100;
	}
	$cacheid.="_$limit";
	if (!empty($_GET['expand'])) {
		$cacheid .= "E";
	}

	if (!$smarty->is_cached($template, $cacheid))
	{
		if (isset($_GET['all']) || isset($_GET['more'])) {
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
		
		$profile->getStats();
		if ($uid==$USER->user_id) {
			$profile->countTickets();
			$USER->tickets = $profile->tickets;
			$USER->last_ticket_time = $profile->last_ticket_time;
			if (!empty($_SESSION['last_ticket_time']) && $profile->last_ticket_time <= $_SESSION['last_ticket_time']) {
				$profile->tickets = 0;
			}
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
		
		if ($uid==$USER->user_id || $USER->hasPerm('moderator'))
			$statuses='';
		else
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

function smarty_function_TruncateWithExpand($input,$more) {
	
	if (strlen($input) > 300 && empty($_GET['expand'])) {
	
		if (strpos($input,'[--more--]') !== FALSE) {
			$bits = explode('[--more--]',$input,2);
			preg_match('/^(.{1000,}?)(?![\w\d\[\]\'])/s',$bits[0],$m); //still impose a hard limit of 1000 chars!
			$input = $m[1]?$m[1]:$bits[0];
		} else {
			preg_match('/^(.{300,}?)(?![\w\d\[\]\'])/s',$input,$m);
			$input = $m[1];
			if (!preg_match("/[\.\n]+\$/",$input)) {
				$input .= " ...";
			}
		}
		$input .= "<div align=\"right\"><a href=\"?expand=1\">$more</a></div>";
	}
	
	return $input;
}
$smarty->register_modifier("TruncateWithExpand", "smarty_function_TruncateWithExpand");


if (!empty($_GET['a']))
	$smarty->assign('credit_realname', $_GET['a']);

$smarty->display($template, $cacheid);

?>
