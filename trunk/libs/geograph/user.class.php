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
	* basic email address check
	*/
	function _isValidEmailAddress($email) 
	{
		if (!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/" , $email)) 
		{
	  		return false;
	 	}
	 	return true;
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
	
		//basic email address check
		if (!$this->_isValidEmailAddress($email))
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
		if($hash==substr(md5($user_id.$CONF['register_confirmation_secret']),0,16))
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
		
		return $ok;
	}
	
	/**
	* force inline login if user isn't authenticated
	*/
	function logout()
	{
		$this->registered=false;
		$this->user_id=0;
		$this->realname="";
		
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
				
				$db = NewADOConnection($GLOBALS['DSN']);
							
				//user registered?
				$arr = $db->GetRow("select * from user where email=".$db->Quote($email));	
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
							
							$this->registered=true;
							$logged_in=true;
						}
						else
						{
							$errors['general']='You must confirm your registration by following the link in the email sent to '.$email;
						}
					}
					else
					{
						//speak friend and enter					
						$errors['password']='Wrong password';
					}
				
				}
				else
				{
					//sorry son, your name's not on the list
					$errors['email']='This email address is not registered';
				}
				
			}
			
			//failure to login means we never return - we show a login page
			//instead...
			if (!$logged_in)
			{
				$smarty = new GeoGraphPage;
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
	function auto_login()
	{
	
	}
	
}
?>
