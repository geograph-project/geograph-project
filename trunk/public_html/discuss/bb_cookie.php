<?php
/*
This file is part of miniBB. miniBB is free discussion forums/message board software, without any warranty. See COPYING file for more details. Copyright (C) 2004 Paul Puzyrev, Sergei Larionov. www.minibb.net
*/
if (!defined('INCLUDED776')) die ('Fatal error.');

$cookieexptime=time()+$cookie_expires;

function user_logged_in() 
{

	if(isset($GLOBALS['cook']) and trim($GLOBALS['cook'])!='') 
		$c=explode('|',$GLOBALS['cook']);
	elseif(isset($_SESSION['minimalistBBSession'])) 
		$c=explode('|',$_SESSION['minimalistBBSession']);
	else 
		$c=getMyCookie();

	$username=$c[0]; 
	$userpassword=$c[1]; 
	$exptime=$c[2];

	$returned=FALSE;
	$resetCookie=FALSE;

	if($username=='') 
	{ 
		$returned=FALSE; return; 
	}

	$GLOBALS['user_usr']=$username;

	$pasttime=$exptime-time();

	if ($username==$GLOBALS['admin_usr'] and $userpassword==md5($GLOBALS['admin_pwd'])) 
	{
		$returned=TRUE;
		$GLOBALS['logged_user']=0; 
		$GLOBALS['logged_admin']=1; 
		$GLOBALS['user_id']=1;

		if($row=db_simpleSelect(0,$GLOBALS['Tu'],$GLOBALS['dbUserSheme']['user_sorttopics'][1].','.$GLOBALS['dbUserSheme']['language'][1],$GLOBALS['dbUserId'],'=',1))
			$GLOBALS['user_sort']=$row[0]; $GLOBALS['langu']=$row[1];

		if ($pasttime<=$GLOBALS['cookie_renew']) {
			// if expiration time of cookie is less than defined in setup, we redefine it below
			$resetCookie=TRUE;
		}

	} 
	elseif ($userpassword!='' and 
		$row=db_simpleSelect(0,$GLOBALS['Tu'],$GLOBALS['dbUserId'].','. $GLOBALS['dbUserSheme']['user_sorttopics'][1].','. $GLOBALS['dbUserSheme']['language'][1].','. $GLOBALS['dbUserAct'],$GLOBALS['dbUserSheme']['username'][1],'=',mysql_escape_string($username),'',1, $GLOBALS['dbUserSheme']['user_password'][1],'=',$userpassword))
	{
		$returned=TRUE;
		$GLOBALS['user_id']=$row[0]; 
		$GLOBALS['user_sort']=$row[1]; 
		$GLOBALS['logged_user']=1; 
		$GLOBALS['logged_admin']=0;
		$GLOBALS['langu']=$row[2];
		$GLOBALS['user_activity']=$row[3];

		
		//modify based on geograph user status
		global $USER;
		if ($USER->hasPerm('admin'))
		{
			$GLOBALS['logged_user']=0; 
			$GLOBALS['logged_admin']=1; 
		}
		
		if ($pasttime<=$GLOBALS['cookie_renew']) 
		{
			$resetCookie=TRUE;
		}

	}
	else
	{
		$returned=FALSE;
		if ($pasttime<=$GLOBALS['cookie_renew']) 
		{
			$userpassword='';
			$resetCookie=TRUE;
		}
	}



	if($resetCookie) 
	{
		deleteMyCookie();
		setMyCookie($username,$userpassword,$GLOBALS['cookieexptime']);
	}

	return $returned;
}

function setMyCookie($userName,$userPass,$userExpTime){
if($userPass!='') $userPass=md5($userPass);
setcookie($GLOBALS['cookiename'], $userName.'|'.$userPass.'|'.$userExpTime, $GLOBALS['cookieexptime'], $GLOBALS['cookiepath'], $GLOBALS['cookiedomain'], $GLOBALS['cookiesecure']);
}

function getMyCookie(){
if(isset($_COOKIE[$GLOBALS['cookiename']])) $cookievalue=explode ('|', $_COOKIE[$GLOBALS['cookiename']]);
else $cookievalue=array('','','');
return $cookievalue;
}

function deleteMyCookie(){
setcookie($GLOBALS['cookiename'], '', (time()-2592000), $GLOBALS['cookiepath'], $GLOBALS['cookiedomain'], $GLOBALS['cookiesecure']);
}

?>