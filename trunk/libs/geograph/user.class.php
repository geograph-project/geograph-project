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
* Provides the GeographUser class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/


/**
* Geograph User class
*
* Provides facilities for inline login and querying permissions
* of current user (which might be an anonymous)
*
* @package Geograph
*/
class GeographUser
{
	/**
	* current user_id, 0 for guest user
	*/
	var $user_id=0;
	
	/**
	* registered user?
	*/
	var $registered=false;
	
	/**
	* records whether user was automatically logged in via cookie - 
	* there are some operations which should force the user to give
	* their password for additional security in this event
	*/
	var $autologin=false;
	
	/**
	* stats gathered by getStats
	*/
	var $stats=array();

	
	/**
	* Constructor doesn't normally do anything, but if supplied with a user id
	* can be used to create an instance for a particular user. 
	*/
	function GeographUser($uid=0)
	{
		if (($uid>0) && preg_match('/^[0-9]+$/' , $uid))
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');   
			
			
						
			$arr = $db->GetRow("select * from user where user_id='$uid'");	
			if (count($arr))
			{
				$this->registered=strlen($arr['rights'])>0;
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}
			}

		}
	}
	
	/**
	* get stats for user represented by this instance - 
	* all stats are stored in
	*/
	function getStats()
	{
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
		
		$this->stats=array();
		
		$this->stats['ftf']=$db->GetOne("select count(*) from gridimage where user_id='{$this->user_id}' and ftf=1");
		$this->stats['total']=$db->GetOne("select count(*) from gridimage where user_id='{$this->user_id}' and moderation_status<>'rejected'");
		$this->stats['pending']=$db->GetOne("select count(*) from gridimage where user_id='{$this->user_id}' and moderation_status='pending'");
		

	}
	
	/**
	* register user 
	* returns true if successful and false if not. Array of
	* errors returned via $error param
	*/
	function register(&$form, &$errors)
	{
		global $CONF;
		
		//get the inputs
		$name=stripslashes(trim($form['name']));
		$email=stripslashes(trim($form['email']));
		$password1=stripslashes(trim($form['password1']));
		$password2=stripslashes(trim($form['password2']));
		
		//check the registration
		$ok=true;
		
		$errors=array();
		
		//check name
		if (strlen($name)==0)
		{
			$ok=false;
			$errors['name']='You must give your name';
		}
		else
		{
			if (!isValidRealName($name))
			{
				$ok=false;
				$errors['name']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			}
		}
		
		//basic email address check
		if (!isValidEmailAddress($email))
		{
			$ok=false;
			$errors['email']='Please enter a valid email address';
		}
		
		//check password
		if (strlen($password1)==0)
		{
			$ok=false;
			$errors['password1']='You must specify a password';
		}
		elseif ($password1!=$password2)
		{
			$ok=false;
			$errors['password2']='Passwords didn\'t match, please try again';
		}
		
		//if the params check out, lets ensure they aren't 
		//already registered...
		if ($ok)
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			if (!$db) die('Database connection failed');   

			# no need to call connect/pconnect!
			$arr = $db->GetRow("select * from user where email=".$db->Quote($email));	
			if (count($arr))
			{
				//email address already exists in database
				$ok=false;
				$errors['email']='Email address is already registered';
			}
			else
			{
				//ok, user doesn't exist, lets go!
				$sql = sprintf("insert into user (realname,email,password,signup_date) ".
					"values (%s,%s,%s,now())",
					$db->Quote($name),
					$db->Quote($email),
					$db->Quote($password1));
					
				if ($db->Execute($sql) === false) 
				{
					$errors['general']='error inserting: '.$db->ErrorMsg();
					$ok=false;
				}
				else
				{
					//hurrah - it's all good - send user an email so that
					//pick up some basic rights
					$user_id=$db->Insert_ID();
					
					//put the user_id into this user object
					$this->user_id=$user_id;
					
					//build an authentication url
					$register_authentication_url="http://".
						$_SERVER['HTTP_HOST'].'/register.php?u='.$user_id.
						'&confirm='.substr(md5($user_id.$CONF['register_confirmation_secret']),0,16);
					
					
					$msg="To complete your geograph registration, follow the link below\n\n";
					$msg.=$register_authentication_url."\n\n";
					
					@mail($email, 'Confirm your '.$_SERVER['HTTP_HOST'].' registration', $msg,
						"From: Geograph Website <noreply@geograph.co.uk>");
				
				}
			}
		}
		
		return $ok;
	}

	/**
	* verify registration from given hash
	*/
	function verifyRegistration($user_id, $hash)
	{
		global $CONF;
		$ok=true;
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/\d+/', $user_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash==substr(md5($user_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			
			//assign some basic rights to the user
			$sql="update user set rights='basic' where user_id=".$db->Quote($user_id);
			$db->Execute($sql);
			
			$this->user_id=$user_id;
			$this->registered=true;
			
			$arr = $db->GetRow("select * from user where user_id=".$db->Quote($user_id));	
			foreach($arr as $name=>$value)
			{
				if (!is_numeric($name))
					$this->$name=$value;
			
			}
			
			//setup forum user
			$this->_forumUpdateProfile();
				
		}
		
		return $ok;
	}
	
	/**
	* send password reminder to email address
	*/
	function sendReminder($email, &$errors)
	{
		$errors=array();
		$ok=false;
		
		if (isValidEmailAddress($email))
		{
			$db = NewADOConnection($GLOBALS['DSN']);

			//user registered?
			$arr = $db->GetRow("select * from user where email=".$db->Quote($email));	
			if (count($arr))
			{
				$msg="Someone, probably you, requested a password reminder for ".$_SERVER['HTTP_HOST']."\n\n";
				$msg.="Your password is: ".$arr['password']."\n\n";

				@mail($email, 'Password Reminder for '.$_SERVER['HTTP_HOST'], $msg,
				"From: Geograph Website <noreply@geograph.co.uk>");

				$ok=true;
			}
			else
			{
				$errors['email']="This email address isn't registered";
			}
		}
		else
		{
			$errors['email']='This isn\'t a valid email address';
		}
		
		return $ok;
	}
	
	/**
	* update user profile
	* profile array should contain website, nickname, realname flag. A
	* public_email entry, if present, will cause the public_email flag
	* to be set. The idea is to simply pass the $_POST array - all values
	* are checked for validity
	*/
	function updateProfile(&$profile, &$errors)
	{
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
		
		$ok=true;
		
		
		if (strlen($profile['realname']))
		{
			if (!isValidRealName($profile['realname']))
			{
				$ok=false;
				$errors['realname']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			}
		}
		else
		{
			$ok=false;
			$errors['realname']='Please enter your real name, we use it to credit your photographs';
		}
		
		
		if (strlen($profile['website']) && !isValidURL($profile['website']))
		{
			$ok=false;
			$errors['website']='This doesn\'t appear to be a valid URL';
		}
		
		
		//unique nickname, since you can log in with it
		if (isValidRealName($profile['nickname']))
		{
			//lets be sure it's unique
			$sql="select * from user where nickname=".
				$db->Quote(stripslashes($profile['nickname'])).
				" and user_id<>{$this->user_id}";
			$r=$db->GetRow($sql);
			if (count($r))
			{
				$ok=false;
				$errors['nickname']='Sorry, this nickname is already taken by another user';
			}
		}
		else
		{
			$ok=false;
			if (strlen($errors['nickname']))
				$errors['nickname']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			else
				$errors['nickname']='Please enter a nickname for use on the forums';
		}

		
		if ($ok)
		{
		
			$sql = sprintf("update user set realname=%s,nickname=%s,website=%s,public_email=%d ".
				"where user_id=%d",
				$db->Quote(stripslashes($profile['realname'])),
				$db->Quote(stripslashes($profile['nickname'])),
				$db->Quote(stripslashes($profile['website'])),
				isset($profile['public_email'])?1:0,
				$this->user_id
				);

			if ($db->Execute($sql) === false) 
			{
				$errors['general']='error updating: '.$db->ErrorMsg();
				$ok=false;
			}
			else
			{
				//hurrah - it's all good - lets update ourself..
				$this->realname=stripslashes($profile['realname']);
				$this->nickname=stripslashes($profile['nickname']);
				$this->website=stripslashes($profile['website']);
				$this->public_email=isset($profile['public_email'])?1:0;
				
				$this->_forumUpdateProfile();
				
			}
		
		}
		
		return $ok;
	}
	
	/**
	* log the user out
	*/
	function logout()
	{
		//clear member vars
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			unset($this->$name);
		}
		
		$this->_forumLogout();
		
		//initialise a few essentials
		$this->registered=false;
		$this->user_id=0;
		$this->realname="";
		
		//we've changed state, won't hurt to use a new
		//session id...
		session_regenerate_id(); 
		
	}

	
	/**
	* force inline login if user isn't authenticated
	*/
	function mustHavePerm($perm)
	{
		//not logged in? do that first
		if (!$this->registered)
		{
			//do an inline login
			$this->login();
		}
		
		//to reach here, user is logged in, lets check the perms
		if (strpos($this->rights, $perm)===false)
		{
			//user is logged in, but hasn't got sufficient rights
			$smarty = new GeoGraphPage;
			$smarty->assign('required', $perm);
			$smarty->display('no_permission.tpl');
			exit;
		}
		else
		{
			//user has the correct rights.
		}
		
	}
	
	/**
	* got perm?
	*/
	function hasPerm($perm)
	{
		return $this->registered && (strpos($this->rights, $perm)!==false);
	}
	
	/**
	* force inline login if user isn't authenticated
	* only return after successful login
	*/
	function login($inline=true)
	{
		$logged_in=false;
		
		if (!$this->registered)
		{
			$errors=array();
				
			//lets see if we are processing a login?
			if (isset($_POST['email']))
			{
				$email=stripslashes(trim($_POST['email']));
				$password=stripslashes(trim($_POST['password']));
				$remember_me=isset($_POST['remember_me'])?1:0;
				
				
				$db = NewADOConnection($GLOBALS['DSN']);

				$sql="";
				if (isValidEmailAddress($email))
					$sql="select * from user where email=".$db->Quote($email);
				elseif (isValidRealName($email))
					$sql="select * from user where nickname=".$db->Quote($email);
				
				
				if (strlen($sql))
				{
					//user registered?
					$arr = $db->GetRow($sql);	
					if (count($arr))
					{
						//passwords match?
						if ($arr['password']==$password)
						{
							//final test = if they have no rights, they haven't confirmed
							//their registration
							if (strlen($arr['rights']))
							{
								//copy user fields into this object
								foreach($arr as $name=>$value)
								{
									if (!is_numeric($name))
										$this->$name=$value;
								}
								
								//give user a remember me cookie?
								if (isset($remember_me))
								{
									$token = md5(uniqid(rand(),1)); 
									$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
									setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365);  
								}
								
								//we're changing privilege state, so we should
								//generate a new session id to avoid fixation attacks
								session_regenerate_id(); 
								
								$this->registered=true;
								$logged_in=true;
								
								//log into forum too
								$this->_forumLogin();
								
							}
							else
							{
								$errors['general']='You must confirm your registration by following the link in the email sent to '.$email;
							}
						}
						else
						{
							//speak friend and enter					
							$errors['password']='Wrong password - don\'t forget passwords are case-sensitive';
						}

					}
					else
					{
						//sorry son, your name's not on the list
						$errors['email']='This email address or nickname is not registered';
					}
				}
				else
				{
					$errors['email']='This is not a valid email address or nickname';
					
				}
				
			}
			
			//failure to login means we never return - we show a login page
			//instead...
			if (!$logged_in)
			{
				$smarty = new GeoGraphPage;
				
				$smarty->assign('remember_me', isset($_COOKIE['autologin'])?1:0);
				$smarty->assign('inline', $inline);
				$smarty->assign('email', $email);
				$smarty->assign('password', $password);
				$smarty->assign('errors', $errors);
				$smarty->display('login.tpl');
				exit;
			}
			
		
		}
		else
		{
			$logged_in=true;
		}
		
		//we're logged in
		return $logged_in;
	}
	
	/**
	* attempt to authenticate user from persistent cookie
	*/
	function autoLogin()
	{
		if(isset($_COOKIE['autologin']))
		{
			$db = NewADOConnection($GLOBALS['DSN']);
			
			$valid=false;
			$bits=explode('_', $_COOKIE['autologin']);
			if ((count($bits)==2) &&
			    is_numeric($bits[0]) &&
			    preg_match('/^[a-f0-9]{32}$/' , $bits[1]))
			{
				$clause="user_id='{$bits[0]}' and token='{$bits[1]}'";
				$row=$db->GetRow("select * from autologin where $clause");
				if (count($row))
				{
					//log the user in
					$sql="select * from user where user_id=".$db->Quote($bits[0]);
					
					$user = $db->GetRow($sql);	
					if (count($user))
					{
						$valid=true;
						
						foreach($user as $name=>$value)
						{
							if (!is_numeric($name))
								$this->$name=$value;
						}

						//we're changing privilege state, so we should
						//generate a new session id to avoid fixation attacks
						session_regenerate_id(); 

						$this->registered=true;
						$this->autologin=true;
						
						//log into forum
						$this->_forumLogin();
						
						//delete the autologin, we've used it
						$db->query("delete from autologin where $clause");

						//given the user a new one
						$token = md5(uniqid(rand(),1)); 
						$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
						setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365);  
					
					}
								
				}
			}
		
			//clear the cookie?
			if (!$valid)
			{
				setcookie('autologin', '', time()-3600*24*365);  
					
			}
		}
	}
	
	/**
	* Updates forum profile to keep the forum software in sync with us
	*/
	function _forumUpdateProfile()
	{
		$db = NewADOConnection($GLOBALS['DSN']);
		if (!$db) die('Database connection failed');   
	
		//we maintain a direct user_id to user_id mapping with the minibb 
		//forum software....
	
		$username=$this->nickname;
		if ($username=="")
			$username=substr(" ", "", $this->realname);
	
		//do we have a forum user?
		$existing=$db->GetRow("select * from geobb_users where user_id='{$this->user_id}'");
		if (count($existing))
		{
			//update profile
			$sql="update geobb_users set username=".$db->Quote($username).
				", user_email=".$db->Quote($this->email).
				", user_password=md5(".$db->Quote($this->password).")".
				", user_website=".$db->Quote($this->website).
				", user_viewemail=".$this->public_email.
				" where user_id={$this->user_id}";
				
			$db->Execute($sql);	
		}
		else
		{
			//create new profile
			$sql="insert into geobb_users(user_id,username, user_regdate,user_password,user_email,user_website,user_viewemail) values (".
				$this->user_id.",".
				$db->Quote($username).",".
				"now(),".
				"md5(".$db->Quote($this->password)."),".
				$db->Quote($this->email).",".
				$db->Quote($this->website).",".
				$this->public_email.")";
				
			$db->Execute($sql);		
				
		}
		
		
	}

	/**
	* Setup a forum session so use is automatically logged in
	*/
	function _forumLogin()
	{
		$this->_forumUpdateProfile();
		
		$passmd5=md5($this->password);
		$expiry=time()+108000;
		
		setcookie('geographbb', 
			$this->nickname.'|'.$passmd5.'|'.$expiry, 
			$expiry);
	}

	/**
	* Log out of forum
	*/
	function _forumLogout()
	{
		setcookie('geographbb', '', time()-108000);
	}
	
	
}
?>
