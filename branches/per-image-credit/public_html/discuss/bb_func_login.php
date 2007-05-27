<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');
$cook='';

$user_usr=trim($_POST['user_usr']);
$user_pwd=trim($_POST['user_pwd']);

$queryStr=rawurldecode($queryStr);
if(strstr($queryStr,'vtopic')) 
	$queryStr.='#newtopic';
elseif(strstr($queryStr,'vthread')) 
	$queryStr.='#newreply';

if(($action=='pthread' and isset($_POST['postText']) and $_POST['postText']=='') OR ($action=='ptopic' and isset($_POST['topicText']) and $_POST['topicText']=='')) 
	$action='';

if ($user_usr==$admin_usr) 
{
	if ($user_pwd==$admin_pwd) 
	{
		$logged_admin=1;
		$cook=$user_usr."|".md5($user_pwd)."|".$cookieexptime;
		if($useSessions) 
		{ 
			if(!session_is_registered('minimalistBBSession')) 
				session_register('minimalistBBSession'); 
			$_SESSION['minimalistBBSession']=$cook;
		}
		deleteMyCookie();
		setMyCookie($user_usr,$user_pwd,$cookieexptime);
		if ($action=='') 
		{
			if(isset($metaLocation)) 
			{ 
				$meta_relocate="{$main_url}/{$indexphp}{$queryStr}"; 
				echo ParseTpl(makeUp($metaLocation)); 
				exit; 
			} 
			else 
				header("Location: {$main_url}/{$indexphp}{$queryStr}");
		}
	}
	else 
	{
		include ($pathToFiles."lang/$lang.php");
		$errorMSG=$l_loginpasswordincorrect; 
		$correctErr="<a href=\"JavaScript:history.back(-1)\">$l_correctLoginpassword</a>";
		$loginError=1;
		echo load_header(); 
		echo ParseTpl(makeUp('main_warning'));
	}
	// if this is not admin, this is anonymous or registered user; check registered first
}
else 
{
	if($row=db_simpleSelect(FALSE,$Tu,$dbUserSheme['username'][1].','.$dbUserSheme['user_password'][1],$dbUserSheme['username'][1],'=',$user_usr,'',1))
	{
		// It means that username exists in database; so let's check a password
		$username=$row[0]; 
		$userpassword=$row[1];
		if ($username==$user_usr and $userpassword==md5($user_pwd)) 
		{
			$logged_user=1;
			$cook=$user_usr."|".md5($user_pwd)."|".$cookieexptime;
			if($useSessions) 
			{ 
				if(!session_is_registered('minimalistBBSession')) 
					session_register('minimalistBBSession'); 
				$_SESSION['minimalistBBSession']=$cook;
			}
			deleteMyCookie();
			setMyCookie($user_usr,$user_pwd,$cookieexptime);
			if ($action=='')
			{
				if(isset($metaLocation)) 
				{ 
					$meta_relocate="{$main_url}/{$indexphp}{$queryStr}"; 
					echo ParseTpl(makeUp($metaLocation)); 
					exit; 
				} 
				else 
					header("Location: {$main_url}/{$indexphp}{$queryStr}");
			}
		}
		else 
		{
			include ($pathToFiles."lang/$lang.php");
			$errorMSG=$l_loginpasswordincorrect; 
			$correctErr="<a href=\"JavaScript:history.back(-1)\">$l_correctLoginpassword</a>";
			$loginError=1;
			echo load_header(); 
			echo ParseTpl(makeUp('main_warning'));
		}
	}
	else 
	{
		// There are now rows - this is Anonymous
		require($pathToFiles.'bb_func_txt.php');
		$reqTxt=1;
		$user_usr=textFilter($user_usr,40,20,0,TRUE,0,0);
		$user_usr=str_replace('|', '', $user_usr);
		
		if (isset($_COOKIE[$cookiename])) 
		{
			$cookievalue=explode ("|", $_COOKIE[$cookiename]);
			$user_usrOLD=$cookievalue[0];
		} 
		else 
		{ 
			$user_usrOLD=''; 
		}
		if ($user_usr != $user_usrOLD) 
		{
			// We don't need to set a cookie if the same 'anonymous name' specified
			$cook=$user_usr.'||'.$cookieexptime;
			if($useSessions) 
			{ 
				if(!session_is_registered('minimalistBBSession')) 
					session_register('minimalistBBSession'); 
				$_SESSION['minimalistBBSession']=$cook;
			}
			
			deleteMyCookie();
			setMyCookie($user_usr,'',$cookieexptime);
		}
	}
}

?>