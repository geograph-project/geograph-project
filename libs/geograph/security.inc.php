<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
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

/**
* Provides routines useful in preventing security issues
*
* Many of these routines are for checking data before it is placed in
* the database or transmitted back to a user, in order to prevent
* cross site scripting and similar attacks
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

function inEmptyRequestInt($key,$def = 0) {
	return (!empty($_REQUEST[$key]))?intval($_REQUEST[$key]):$def;
}

function inSetRequestInt($key,$def = 0) {
	return (isset($_REQUEST[$key]))?intval($_REQUEST[$key]):$def;
}


/**
* basic email address check
*/
function isValidEmailAddress($email) 
{
	return preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._\-\+])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/' , $email)?true:false; 
}

/**
* basic name check
*/
function isValidRealName($name) 
{
	return preg_match('/^[a-zA-Z0-9\-\s\']+$/' , $name)?true:false; 
}

/**
* web url check
*/
function isValidURL($url) 
{
	return preg_match('{^http(s?)\:\/\/[a-zA-Z0-9\-\._]+(\.[a-zA-Z0-9\-\._]+){1,}(\/?)([a-zA-Z0-9\~\-\.\?\,=\'\/\\\+&%\$#_]*)?$}' , $url)?true:false; 
}

/**
 * Heuristic spam check intended for email message checking
 * 
 */
function isSpam($msg)
{
	//some spam features url and entity encoding to hide
	//the real content from filters. Bugger off!
	$msg=html_entity_decode(urldecode($msg)); 
	$msg=strtolower($msg);
	
	//no legitimate use for html or bbedit tags
	if (strstr($msg, '[url')!==false)
		return true;
	if (strstr($msg, 'href')!==false)
		return true;
		
	//how many times does http appear?
	$matches=array();
	preg_match_all("{http}", $msg, $matches);
	$count=count($matches[0]);

	preg_match_all("{http://www.geograph.org.uk}", $msg, $matches);
	$legit=count($matches[0]);
	
	//we'll let you off for using geograph links...
	$count-=$legit;

	if ($count>3)
		return true;

	
	
	return false;
}	

/**
 * Return IP address of user
 * 
 */
function getRemoteIP()
{
	//get IP address of user
	if (strlen($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ips=explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
		$ip=trim($ips[count($ips)-1]);
	}
	else
	{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

?>
