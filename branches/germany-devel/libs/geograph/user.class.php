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


require_once('geograph/gridsquare.class.php');

include_messages('class_user');

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
	* number of seconds until we allow next authentication
	*/
	var $adminmode=false;

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
			$db = $this->_getDB();
						
			$arr = $db->GetRow("select * from user where user_id=$uid limit 1");	
			if (count($arr))
			{
				$this->registered=strlen($arr['rights'])>0;
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}

				// get user homesquare
				if (isset($this->home_gridsquare)) {
					$gs = new GridSquare();
					$gs->loadFromId($this->home_gridsquare);
					$this->grid_reference = $gs->grid_reference;
				}

			}
		}
	}
	
	function loadByNickname($nickname=0)
	{
		if (!empty($nickname))
		{
			$db = $this->_getDB();

			$nickname = $db->Quote($nickname);
			
			$arr = $db->GetRow("select * from user where nickname = $nickname limit 1");
			
			
			//todo check seperate table
			
			if (count($arr))
			{
				$this->registered=strlen($arr['rights'])>0;
				foreach($arr as $name=>$value)
				{
					if (!is_numeric($name))
						$this->$name=$value;

				}
				$this->adminmode = false;

				// get user homesquare
				if (isset($this->home_gridsquare)) {
					$gs = new GridSquare();
					$gs->loadFromId($this->home_gridsquare);
					$this->grid_reference = $gs->grid_reference;
				}
			}
		}
	}

	/* Calculates random string consisting of the characters [./a-zA-Z0-9]. */
	# TODO use better generator?
	function randomSalt($len) {
		$bytes = (int)(($len * 3 + 3) /4);
		$bin = '';
		for ($i = 0; $i < $bytes; $i++) {
			$bin .= chr(mt_rand(0, 255));
		}
		return str_replace('+', '.', substr(base64_encode($bin), 0, $len));
	}

	/* Try to estimate if the given password is easy to beak by a brute force attack.
	 * Used for rejecting weak passwords, so a compromise between security and usability is implemented.
	 */
	function isPasswordWeak($pass) {
		# TODO use something like john for finding standard passwords?
		# TODO make configurable?
		$charstat = array();
		$numupper = 0;
		$numlower = 0;
		$numdigits = 0;
		$numother = 0;
		$hasupper = 0;
		$haslower = 0;
		$hasdigits = 0;
		$hasother = 0;
		$len = strlen($pass);
		for ($i = 0; $i < $len; $i++) {
			$c = $pass[$i];
			#$charstat[ord($c)]++;
			$charstat[ord($c)] = 1;
			if ($c >= 'a' && $c <= 'z') { // warning: does not work for EBCDIC ;-)
				$numlower++;
				$haslower = 1;
			} elseif ($c >= 'A' && $c <= 'Z') {
				$numupper++;
				$hasupper = 1;
			} elseif ($c >= '0' && $c <= '9') {
				$numdigits++;
				$hasdigits = 1;
			} else {
				$numother++;
				$hasother = 1;
			}
		}
		$numchargroups = $haslower + $hasupper + $hasdigits + $hasother;
		$numcharacters = count($charstat);

		$minlen = max(10 - $numchargroups, 8); # TODO make configurable?
		$minchars = 6;                         # TODO make configurable?
		return $numcharacters < $minchars || $len < $minlen;
	}

	/* Calculates hash/salt pair for password.
	 * Falls back to md5 on failure.
	 * Currently, $hash is up to 60 characters long,
	 *            $salt is up to 8 characters long
	 * For non md5 hashes, $salt == '' and
	 *                     $hash starts with '$' and contains the salt.
	 */
	function password_hash($pass, &$hash, &$salt) {
		if (function_exists('password_hash')) {
			$salt = '';
			$hash = password_hash($pass, PASSWORD_BCRYPT); # 60 characters long
			if ($hash !== FALSE)
				return;
			trigger_error("password_hash failed, falling back to crypt", E_USER_WARNING);
		}
		if (CRYPT_BLOWFISH) {
			if (version_compare(PHP_VERSION, '5.3.7', '>=')) {
				$hsalt = '$2y$10$';
			} else {
				$hsalt = '$2a$10$';
			}
			$hsalt .= $this->randomSalt(22);
			$hash = crypt($pass, $hsalt); # 60 characters long
			$salt = '';
			if (strlen($hash) == 60)
				return;
		}
		trigger_error("password_hash error, falling back to md5", E_USER_WARNING);
		$salt = $this->randomSalt(8);
		$hash = md5($salt.$pass);
	}

	/* Checks if the given password matches the given hash/salt combination.
	 * Returns TRUE if the password matches, FALSE otherwise.
	 * Rate limit class: 'login', 'register', 'mailchange', 'pwdchange', see rate_limit()
	 */
	function password_verify($pass, $hash, $salt, $rlimitclass = null, $uid = null) {
		$pwdok = false;
		if ($hash[0] === '$') {
			if ($salt === '') {
				if (function_exists('password_verify')) {
					$pwdok = password_verify($pass, $hash);
				} else {
					$pwdok = crypt($pass, $hash) === $hash; /* this works because
										 * $hash starts with the salt and crypt ignores any following characters
										 * the return value in case of an error is guaranteed to be shorter */
				}
			}
		} else {
			$pwdok = $hash === md5($salt.$pass);
		}
		if (is_null($rlimitclass)) {
			trigger_error("rlimitclass not given", E_USER_WARNING); // FIXME remove
			$this->lock_seconds = 0;
			return $pwdok;
		} else {
			return $this->rate_limit($rlimitclass, $pwdok, $uid);
		}
	}

	/* Checks if the given hash/salt combination should be recalculated using password_hash. */
	function hashNeedsUpdate($hash, $salt) {
		#return false; #FIXME test
		return $hash[0] !== '$';
	}

	/* rate limiting:
	 * $authfailed == true:  save failed attempt in database
	 * $calcdelta == true:   calculate (block time - time since last try), i.e. the number of seconds until the block time runs off
	 * $calcdelta == false:  calculate block time
	 */
	function rlimit_calc_time($rlimitclass, $authfailed, $calcdelta, $uid = null) {
		if (is_null($uid)) {
			$uid = $this->user_id;
		}
		# TODO this is a very simple implementation which could probably be improved
		$calccutoff = 60*60;
		$mailcutoff = 60*60*4;
		$maxlocktime = 60*60;

		$db = $this->_getDB();

		if ($authfailed) {
			$db->Execute("delete from user_log where user_id=".$db->Quote($uid)." and created < TIMESTAMPADD(SECOND,".-max($mailcutoff,$calccutoff).",NOW()) and event='$rlimitclass'");
		}

		$authcount = $db->GetOne("select count(*) from user_log where user_id=".$db->Quote($uid)." and created >= TIMESTAMPADD(SECOND,-$calccutoff,NOW()) and event='$rlimitclass'");
		if ($authcount === false) {
			trigger_error("db error getting authcount", E_USER_WARNING);
			$authcount = 0;
		}

		if ($authfailed) {
			$authcount++;
			$newmailsent = 0;
			if ($authcount > 10) {
				$mailsent = $db->GetOne("select exists(select 1 from user_log where user_id=".$db->Quote($uid)." and event='$rlimitclass' and mailsent=1)");
				if ($mailsent !== false && $mailsent != 1) { # do nothing on db error [false] or if already sent [1]
					global $CONF;
					$geofrom = "From: Geograph <{$CONF['mail_from']}>";
					$envfrom = is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}";
					$subject = "[Geograph Error]: authentication failed for user $uid";
					$msg = "Authentication failed $authcount times for user $uid ($rlimitclass).";
					mail($CONF['admin_email'], $subject, $msg, $geofrom, $envfrom); #FIXME test
					$newmailsent = 1;
					trigger_error($msg, E_USER_WARNING);
				}
			}
			$db->Execute("insert into user_log (user_id, event, mailsent) values (".$db->Quote($uid).",'$rlimitclass',$newmailsent)");
		}

		if ($authcount == 0) {
			$lock_seconds = 0;
		} elseif ($authcount >= 6) {
			$lock_seconds = $maxlocktime;
		} else {
			$lock_seconds = pow(4, $authcount);
		}

		if ($lock_seconds > $maxlocktime) {
			$lock_seconds = $maxlocktime;
		}

		if ($calcdelta) { /* subtract time since last try */
			$last_try = $db->GetOne("select TIMESTAMPDIFF(SECOND, created, NOW()) from user_log where user_id=".$db->Quote($uid)." and event='$rlimitclass' order by userlog_id desc");
			//$last_try = $db->GetOne("select TIMESTAMPDIFF(SECOND, created, NOW()) from user_log where user_id=".$db->Quote($uid)." and event='$rlimitclass' order by userlog_id desc limit 1");
			if ($last_try === false || is_null($last_try)) {
				if ($db->ErrorNo()) {
					trigger_error("db error getting last_try", E_USER_WARNING);
				}
				$last_try = $maxlocktime + 1;
			}
			$lock_seconds -= $last_try;
		}

		return $lock_seconds;
	}

	function rate_limit($rlimitclass, $pwdok, $uid = null) {
		#$this->lock_seconds = 0; # FIXME test
		#return $pwdok; # FIXME test
		if (!in_array($rlimitclass, array('login', 'register', 'mailchange', 'pwdchange'))) { # FIXME remove after testing # FIXME add 'autologin'?
			trigger_error("invalid rlimitclass '$rlimitclass'", E_USER_WARNING);
			$this->lock_seconds = 0;
			return $pwdok;
		}
		if ($pwdok) {
			if ($this->rlimit_calc_time($rlimitclass, false, true, $uid) < 0) {
				# should we clear previous failed tries from the db?
				$this->lock_seconds = 0;
				return true;
			}
		}
		$this->lock_seconds = $this->rlimit_calc_time($rlimitclass, true, false, $uid);
		return false;
	}

	function getForumSortOrder() {
		$db = $this->_getDB();
	
		$this->sortBy = $db->getOne("select user_sorttopics from geobb_users where user_id='{$this->user_id}'");
		return $this->sortBy;
	}
	
	function setDefaultStyle($style) {
		$db = $this->_getDB();

		$db->Execute("update user set default_style = '$style' where user_id='{$this->user_id}'");
		$this->default_style = $style;
	}
	
	
	
	function setCreditDefault($realname) {
		$db = $this->_getDB();

		$db->Execute(sprintf("update user set credit_realname = %s where user_id=%d",$db->Quote($realname),$this->user_id));
		$this->credit_realname = $realname;
	}
	
	
	function getStyle($style='white') {
		$valid_style=array('white', 'black','gray');
		if (isset($_GET['style']) && in_array($_GET['style'], $valid_style))
		{
			$style=$_GET['style'];
			$_SESSION['style']=$style;

			if ($this->registered) 
				$this->setDefaultStyle($style);
		}
		elseif ($this->registered && in_array($this->default_style, $valid_style)) 
		{
			$style=$this->default_style;
		}
		elseif (isset($_SESSION['style']))
		{
			$style=$_SESSION['style'];
		}
		return $style;
	}
	
	function setDefaultForumOption($name,$value) {
		$db = $this->_getDB();

		$db->Execute("update geobb_users set user_$name = '$value' where user_id='{$this->user_id}'");
		$this->default_style = $style;
	}
	
	function getForumOption($name,$def = '',$save = true) {
		$value = $def;
		if (isset($_GET[$name]))
		{
			$value=$_GET[$name];
			$_SESSION[$name]=$value;

			if ($save && $this->registered) 
				$this->setDefaultForumOption($name,$value);
		}
		elseif (isset($_SESSION[$name]))
		{
			$value=$_SESSION[$name];
		}
		elseif ($this->registered)
		{
			if (isset($this->$name)) {
				$value=$this->$name;
			} else {
				$db = $this->_getDB();
				
				$value = $db->getOne("select user_$name from geobb_users where user_id='{$this->user_id}'");
				$this->$name = $value;
			}
			$_SESSION[$name]=$value;
		}
		return $value;
	}
	
	/**
	* get stats for user represented by this instance - 
	* all stats are stored in
	*/
	function getStats()
	{
		$db = $this->_getDB();
		
		$this->stats=$db->GetRow("select * from user_stat where user_id='{$this->user_id}'");

		$data = $db->GetRow("SHOW TABLE STATUS LIKE 'user_stat'");
		$this->stats['updated'] = $data['Update_time'];

	}
	
	/**
	* count the number of tickets this user has
	*/
	function countTickets()
	{
		$db = $this->_getDB();
		
		list($this->tickets,$this->last_ticket_time)=$db->GetRow("select count(*),unix_timestamp(max(suggested)) from gridimage inner join gridimage_ticket using (gridimage_id) where gridimage.user_id='{$this->user_id}' and status != 'closed'");
		return $this->tickets;
	}
	
	/**
	* register user 
	* returns true if successful and false if not. Array of
	* errors returned via $error param
	*/
	function register(&$form, &$errors)
	{
		global $CONF, $MESSAGES;
		
		//get the inputs
		$name=stripslashes(trim($form['name']));
		$email=stripslashes(trim($form['email']));
		$password1=stripslashes(trim($form['password1']));
		$password2=stripslashes(trim($form['password2']));
		
		//check the registration
		$ok=true;
		
		$errors=array();
		
		if (!isset($form['CSRF_token']) || $form['CSRF_token'] !== $_SESSION['CSRF_token']) {
			$errors['csrf'] = true;
			$ok=false;
		}

		//check name
		if (strlen($name)==0)
		{
			$ok=false;
			$errors['name']=$MESSAGES['class_user']['name_missing'];
		}
		else
		{
			if (!isValidRealName($name))
			{
				$ok=false;
				$errors['name']=$MESSAGES['class_user']['name_chars'];
			}
		}
		
		//basic email address check
		if (!isValidEmailAddress($email))
		{
			$ok=false;
			$errors['email']=$MESSAGES['class_user']['email_invalid'];
		}
		
		//check password
		if ($this->isPasswordWeak($password1))
		{
			$ok=false;
			$errors['password1']=$MESSAGES['class_user']['password1'];
		}
		elseif ($password1!=$password2)
		{
			$ok=false;
			$errors['password2']=$MESSAGES['class_user']['password2'];
		}
		
		//if the params check out, lets ensure they aren't 
		//already registered...
		if ($ok)
		{
			$db = $this->_getDB();

			# no need to call connect/pconnect!
			$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' and rights is not null limit 1');
			if (count($arr))
			{
				//email address already exists in database
				$ok=false;
				$errors['email']=$MESSAGES['class_user']['already_registered'];
			}
			else
			{
				$salt = '';
				$hash = '';
				$this->password_hash($password1, $hash, $salt);

				//we know there is no confirmed user with email address, so if we have
				//an unconfirmed one, we can overwrite it with the new details
				$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' and rights is null limit 1');	
				if (count($arr))
				{
					//user already exists, but didn't respond to email - probably trying
					//to send a fresh one so lets just refresh the existing record
					$user_id=$arr['user_id'];
					
					$sql = sprintf("update user set realname=%s,email=%s,password=%s,salt=%s,signup_date=now(),http_host=%s where user_id=%s",
						$db->Quote($name),
						$db->Quote($email),
						$db->Quote($hash),
						$db->Quote($salt),
						$db->Quote($_SERVER['HTTP_HOST']),
						$db->Quote($user_id));
						
					if ($db->Execute($sql) === false) 
					{
						$errors['general']=$MESSAGES['class_user']['error_dbupdate'].$db->ErrorMsg();
						$ok=false;
					}
				
				}
				else
				{
					//ok, user doesn't exist, insert a new row
					$sql = sprintf("insert into user (realname,email,password,salt,signup_date,http_host) ".
						"values (%s,%s,%s,%s,now(),%s)",
						$db->Quote($name),
						$db->Quote($email),
						$db->Quote($hash),
						$db->Quote($salt),
						$db->Quote($_SERVER['HTTP_HOST']));
					
					if ($db->Execute($sql) === false) 
					{
						$errors['general']=$MESSAGES['class_user']['error_dbinsert'].$db->ErrorMsg();
						$ok=false;
					}
					else
					{
						$user_id=$db->Insert_ID();
					}
				}
				
				if ($ok)
				{
					$db->Execute(sprintf("insert into user_change set 
						user_id = %d,
						field = 'realname',
						value = %s
						",
						$user_id,
						$db->Quote($name)
						));
					
					//put the user_id into this user object
					$this->user_id=$user_id;
					
					//build an authentication url
					$register_authentication_url="http://".
						$_SERVER['HTTP_HOST'].'/reg/'.$user_id.
						'/'.substr(md5($user_id.$CONF['register_confirmation_secret']),0,16);
					$register_geograph_url="http://".$_SERVER['HTTP_HOST'];
					$register_mail_body = $MESSAGES['class_user']['mailbody_register'];
					$register_mail_subject = $MESSAGES['class_user']['mailsubject_register'];
					$msg = sprintf($register_mail_body,$register_geograph_url,$register_authentication_url,$email);

					@mail($email, mb_encode_mimeheader($CONF['mail_subjectprefix'].$register_mail_subject, $CONF['mail_charset'], $CONF['mail_transferencoding']), $msg,
						"From: Geograph <{$CONF['mail_from']}>\n".
						"MIME-Version: 1.0\n".
						"Content-Type: text/plain; charset={$CONF['mail_charset']}\n".
						"Content-Disposition: inline\n".
						"Content-Transfer-Encoding: 8bit",
						is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}");
				}
			}
		}
		
		return $ok;
	}

	/**
	* verify registration from given hash
	* can only do this once, returns ok, fail or alreadycomplete
	*
	* checks whether $pass matches the password (if not null)
	*/
	function verifyRegistration($user_id, $hash, $pass = null)
	{
		global $CONF;
		$ok=true;
		$status="ok";
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/\d+/', $user_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash===substr(md5($user_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = $this->_getDB();
			
			
			$arr = $db->GetRow('select *,TIMESTAMPDIFF(SECOND, signup_date, NOW()) AS signupdiff from user where user_id='.$db->Quote($user_id).' limit 1');
			if ($arr === FALSE || !count($arr)) {
				$status="fail";
			} elseif ($arr['signupdiff'] > 7*24*3600) {
				$status="expired";
			} elseif (strlen($arr['rights'])) {
				$status="alreadycomplete";
			
			}
			else
			{
				if (!is_null($pass) && !$this->password_verify($pass, $arr['password'], $arr['salt'], 'register', $arr['user_id'])) {
					$status="auth";
				} else {
				
					//assign some basic rights to the user
					$sql="update user set rights='basic' where user_id=".$db->Quote($user_id);
					$db->Execute($sql);
					$arr['rights'] = 'basic';

					$this->user_id=$user_id;
					$this->registered=true;
					$this->adminmode = false;

					foreach($arr as $name=>$value)
					{
						if (!is_numeric($name))
							$this->$name=$value;

					}

					//temporary nickname fix for beta accounts
					if (strlen($this->nickname)==0)
						$this->nickname=str_replace(" ", "", $this->realname);


					//setup forum user
					$this->_forumUpdateProfile();

					//log into forum too
					$this->_forumLogin();
					
					$status="ok";
				}
			}
				
		}
		else
		{
			//hash mismatch or param problem
			$status="fail";
		}
		
		return $status;
	}
	
	/**
	* send password reminder to email address
	*/
	function sendReminder($email, $password1, $password2, &$errors)
	{
		global $CONF, $MESSAGES;
		$errors=array();
		$ok=false;

		if (!isValidEmailAddress($email)) {
			$errors['email']=$MESSAGES['class_user']['reminder_email_invalid'];
		} elseif ($this->isPasswordWeak($password1)) {
			$errors['password1']=$MESSAGES['class_user']['password1'];
		} elseif ($password1!=$password2) {
			$errors['password2']=$MESSAGES['class_user']['password2'];
		} else {
			$db = $this->_getDB();

			//user registered?
			$arr = $db->GetRow('select * from user where email='.$db->Quote($email).' limit 1');	
			if (count($arr))
			{
				$salt = '';
				$hash = '';
				$this->password_hash($password1, $hash, $salt);
				$db->Execute("insert into user_emailchange ".
					"(user_id, oldemail,newemail,requested,status)".
					"values(?,?,?,now(), 'pending')",
					array($arr['user_id'], $arr['salt'].'$'.$arr['password'], $salt.'$'.$hash));
					
				$id=$db->Insert_ID();

				$url="http://".
					$_SERVER['HTTP_HOST'].'/reg/p'.$id.
					'/'.substr(md5('p'.$id.$CONF['register_confirmation_secret']),0,16);
						
				$mail_body = $MESSAGES['class_user']['mailbody_reminder'];
				$mail_subject = $MESSAGES['class_user']['mailsubject_reminder'];
				$msg = sprintf($mail_body, $email, $_SERVER['HTTP_HOST'], $url);
				$sub = sprintf($mail_subject, $_SERVER['HTTP_HOST']);

				@mail($email, mb_encode_mimeheader($CONF['mail_subjectprefix'].$sub, $CONF['mail_charset'], $CONF['mail_transferencoding']), $msg,
					"From: Geograph <{$CONF['mail_from']}>\n".
					"MIME-Version: 1.0\n".
					"Content-Type: text/plain; charset={$CONF['mail_charset']}\n".
					"Content-Disposition: inline\n".
					"Content-Transfer-Encoding: 8bit",
					is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}");
				$ok=true;
			}
			else
			{
				$errors['email']=$MESSAGES['class_user']['not_registered'];
			}
		}

		return $ok;
	}

	/**
	* verify password from given hash
	* can only do this once, returns ok, fail or alreadycomplete
	*
	* checks whether $pass matches the new password (if not null)
	*/
	function verifyPasswordChange($change_id, $hash, $pass = null)
	{
		global $CONF;
		$ok=true;
		$status="ok";
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/p\d+/', $change_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash===substr(md5($change_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = $this->_getDB();
			
			$user_emailchange_id=substr($change_id,1);
			
			$arr = $db->GetRow('select *,TIMESTAMPDIFF(SECOND, requested, NOW()) AS timediff from user_emailchange where user_emailchange_id='.$db->Quote($user_emailchange_id));

			if ($arr === FALSE || !count($arr)) {
				$status="fail";
			} elseif ($arr['timediff'] > 7*24*3600) {
				$status="expired";
			} elseif ($arr['status'] == 'completed') {
				$status="alreadycomplete";
			} else {
				$parts = explode('$', $arr['newemail'], 2);
				if (count($parts) > 1) {
					list($salt, $md5pw) = $parts;
				} else { # old version, can be removed when the old requests are gone
					$salt = substr($arr['newemail'], 0, 8);
					$md5pw = substr($arr['newemail'], 8);
				}

				if (!is_null($pass) && !$this->password_verify($pass, $md5pw, $salt, 'pwdchange', $arr['user_id'])) {
					$status="auth";
				} else {
					//change password
					//#FIXME test with md5 and bcrypt

					$sql="update user set password=".$db->Quote($md5pw).",salt=".$db->Quote($salt)." where user_id=".$db->Quote($arr['user_id']);
					$db->Execute($sql);

					$sql="update user_emailchange set completed=now(), status='completed' where user_emailchange_id=$user_emailchange_id";
					$db->Execute($sql);


					$this->user_id=$arr['user_id'];
					$this->registered=true;
					$this->adminmode = false;

					$arr = $db->GetRow('select * from user where user_id='.$db->Quote($this->user_id).' limit 1');	
					foreach($arr as $name=>$value)
					{
						if (!is_numeric($name))
							$this->$name=$value;

					}

					//temporary nickname fix for beta accounts
					if (strlen($this->nickname)==0)
						$this->nickname=str_replace(" ", "", $this->realname);


					//setup forum user
					$this->_forumUpdateProfile();

					//log into forum too
					$this->_forumLogin();
					
					$status="ok";
				}
			}
		}
		else
		{
			//hash mismatch or param problem
			$status="fail";
		}
		
		return $status;
	}

	/**
	* verify registration from given hash
	* can only do this once, returns ok, fail or alreadycomplete
	*
	* checks whether $pass matches the password (if not null)
	*/
	function verifyEmailChange($change_id, $hash, $pass = null)
	{
		global $CONF;
		$ok=true;
		$status="ok";
		
		//validate inputs, they came from outside
		$ok=$ok && preg_match('/m\d+/', $change_id);
		$ok=$ok && preg_match('/[0-9a-f]+/', $hash);
		
		//validate hash
		$ok=$ok && ($hash===substr(md5($change_id.$CONF['register_confirmation_secret']),0,16));
		if ($ok)
		{
			$db = $this->_getDB();
			
			$user_emailchange_id=substr($change_id,1);
			
			$arr = $db->GetRow('select *,TIMESTAMPDIFF(SECOND, requested, NOW()) AS timediff from user_emailchange where user_emailchange_id='.$db->Quote($user_emailchange_id));

			if ($arr === FALSE || !count($arr)) {
				$status="fail";
			} elseif ($arr['timediff'] > 7*24*3600) {#FIXME test
				$status="expired";
			} elseif ($arr['status'] == 'completed') {
				$status="alreadycomplete";
			} else {
				$arr2 = $db->GetRow('select * from user where user_id='.$db->Quote($arr['user_id']).' limit 1');
				if (!is_null($pass) && !$this->password_verify($pass, $arr2['password'], $arr2['salt'], 'mailchange', $arr['user_id'])) {
					$status="auth";
				} else {
				
					//change email address
					$sql="update user set email=".$db->Quote($arr['newemail'])." where user_id=".$db->Quote($arr['user_id']);
					$db->Execute($sql);
					$arr2['email'] = $arr['newemail'];

					$sql="update user_emailchange set completed=now(), status='completed' where user_emailchange_id=$user_emailchange_id";
					$db->Execute($sql);


					$this->user_id=$arr['user_id'];
					$this->registered=true;
					$this->adminmode = false;

					foreach($arr2 as $name=>$value)
					{
						if (!is_numeric($name))
							$this->$name=$value;

					}

					//temporary nickname fix for beta accounts
					if (strlen($this->nickname)==0)
						$this->nickname=str_replace(" ", "", $this->realname);


					//setup forum user
					$this->_forumUpdateProfile();

					//log into forum too
					$this->_forumLogin();
					
					$status="ok";
				}
			}
		}
		else
		{
			//hash mismatch or param problem
			$status="fail";
		}
		
		return $status;
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
		global $CONF, $MESSAGES;
		$db = $this->_getDB();
		
		$ok=true;
		
		$profile['realname']=stripslashes($profile['realname']);
		$profile['nickname']=stripslashes($profile['nickname']);
		$profile['website']=stripslashes($profile['website']);

		$profile['oldpassword']=stripslashes($profile['oldpassword']);
		$profile['password1']=stripslashes($profile['password1']);
		$profile['password2']=stripslashes($profile['password2']);

		// verify CSRF token

		if (!isset($profile['CSRF_token']) || $profile['CSRF_token'] !== $_SESSION['CSRF_token']) {
			$errors['CSRF_token'] = true;
			$ok=false;
		}

		// valid homesquare?
		$profile['grid_reference']=stripslashes($profile['grid_reference']);
		$gridreference='';
		$gs=new GridSquare();
		if (strlen($profile['grid_reference']))
		{
			$gsok=$gs->setByFullGridRef($profile['grid_reference']);
			if (!$gsok)
			{
				$ok=false;
				$errors['grid_reference']=$gs->errormsg;
			}
		}

			
		if (strlen($profile['realname']))
		{
			if (!isValidRealName($profile['realname']))
			{
				$ok=false;
				$errors['realname']=$MESSAGES['class_user']['name_chars'];
			}
		}
		else
		{
			$ok=false;
			$errors['realname']=$MESSAGES['class_user']['realname'];
		}
		
		
		if (strlen($profile['website']) && !isValidURL($profile['website']))
		{
			//can we fix it?
			if (isValidURL("http://".$profile['website']))
			{
				$profile['website']="http://".$profile['website'];
			}
			else
			{
				$ok=false;
				$errors['website']=$MESSAGES['class_user']['website'];
			}
		}
		
		
		//unique nickname, since you can log in with it
		if (isValidRealName($profile['nickname'])) // FIXME empty string!
		{
			//lets be sure it's unique
			$sql='select * from user where nickname='.$db->Quote(stripslashes($profile['nickname']))." and user_id<>{$this->user_id} limit 1";
			$r=$db->GetRow($sql);
			if (count($r))
			{
				$ok=false;
				$errors['nickname']=$MESSAGES['class_user']['nickname_in_use'];
			}
			//todo check seperate table
		}
		else
		{
			$ok=false;
			#if (strlen($errors['nickname']))
			#	$errors['nickname']='Only letters A-Z, a-z, hyphens and apostrophes allowed';
			#else
			#	$errors['nickname']='Please enter a nickname for use on the forums';
			$errors['nickname']=$MESSAGES['class_user']['name_chars'];
		}

		if (strlen($profile['password1'])) {
			if (!$this->password_verify($profile['oldpassword'], $this->password, $this->salt)) {
				$ok=false;
				$errors['oldpassword']=$MESSAGES['class_user']['oldpassword'];
			} elseif ($profile['password1'] != $profile['password2']) {
				$ok=false;
				$errors['password2']=$MESSAGES['class_user']['password2'];
			} else {
				$salt = '';
				$password = '';
				$this->password_hash($profile['password1'], $password, $salt);
			}
		} else {
			$password = $this->password;
			$salt = $this->salt;
		}

		//attempting to change email address?
		if ($profile['email']!=$this->email)
		{
			if (isValidEmailAddress($profile['email']))
			{
				$errors['general']=sprintf($MESSAGES['class_user']['mail_change'], $profile['email']);
				$ok=false;
				
				
				//we need to send the user an email with a confirmation link
				//so we put the information into a table
				
				$db->Execute("insert into user_emailchange ".
					"(user_id, oldemail,newemail,requested,status)".
					"values(?,?,?,now(), 'pending')",
					array($this->user_id, $this->email, $profile['email']));
					
				$id=$db->Insert_ID();
				
				$url="http://".
					$_SERVER['HTTP_HOST'].'/reg/m'.$id.
					'/'.substr(md5('m'.$id.$CONF['register_confirmation_secret']),0,16);

				$mail_body = $MESSAGES['class_user']['mailbody_mail_change'];
				$mail_subject = $MESSAGES['class_user']['mailsubject_mail_change'];
				$msg = sprintf($mail_body, $_SERVER['HTTP_HOST'], $profile['email'], $url);
				$sub = sprintf($mail_subject, $_SERVER['HTTP_HOST']);

				@mail($profile['email'], mb_encode_mimeheader($CONF['mail_subjectprefix'].$sub, $CONF['mail_charset'], $CONF['mail_transferencoding']), $msg,
					"From: Geograph <{$CONF['mail_from']}>\n".
					"MIME-Version: 1.0\n".
					"Content-Type: text/plain; charset={$CONF['mail_charset']}\n".
					"Content-Disposition: inline\n".
					"Content-Transfer-Encoding: 8bit",
					is_null($CONF['mail_envelopefrom'])?null:"-f {$CONF['mail_envelopefrom']}");
			}
			else
			{
				$errors['email']=$MESSAGES['class_user']['new_email_invalid'];
				$ok=false;
			}
			
		}

		
		if ($ok)
		{
			//about box is always public - col to be removed
			$profile['public_about']=1;
			$profile['use_age_group']=0;
			$profile['age_group']=0;
			
			//age info is useless to others, nice for us, no need
			//to give use a public option
			
			if ($this->realname != $profile['realname']) 
			{
				$db->Execute(sprintf("insert into user_change set 
					user_id = %d,
					field = 'realname',
					value = %s
					",
					$this->user_id,
					$db->Quote($profile['realname'])
					));
			}
			if ($this->nickname != $profile['nickname']) 
			{
				$db->Execute(sprintf("insert into user_change set 
					user_id = %d,
					field = 'nickname',
					value = %s
					",
					$this->user_id,
					$db->Quote($profile['nickname'])
					));
			}
			
			
			$sql = sprintf("update user set 
				realname=%s,
				nickname=%s,
				website=%s,
				public_email=%d,
				search_results=%d,
				slideshow_delay=%d,
				about_yourself=%s,
				public_about=%d,
				age_group=%d,
				use_age_group=%d,
				home_gridsquare=%s,
				ticket_public=%s,
				calendar_public=%s,
				ticket_option=%s,
				message_sig=%s,
				upload_size=%d,
				clear_exif=%d,
				salt=%s,
				password=%s
				where user_id=%d",
				$db->Quote($profile['realname']),
				$db->Quote($profile['nickname']),
				$db->Quote($profile['website']),
				empty($profile['public_email'])?0:1,
				$profile['search_results'],
				$profile['slideshow_delay'],
				$db->Quote(strip_tags(stripslashes($profile['about_yourself']))),
				$profile['public_about']?1:0,
				$profile['age_group'],
				$profile['use_age_group']?1:0,
				$gs->gridsquare_id,
				$db->Quote($profile['ticket_public']),
				$db->Quote($profile['calendar_public']),
				$db->Quote($profile['ticket_option']),
				$db->Quote(stripslashes($profile['message_sig'])),
				intval($profile['upload_size']), #FIXME check values!
				$profile['clear_exif']?1:0,
				$db->Quote($salt),
				$db->Quote($password),
				$this->user_id
				);

			if ($db->Execute($sql) === false) 
			{
				$errors['general']=$MESSAGES['class_user']['error_dbupdate'].$db->ErrorMsg();
				$ok=false;
			}
			else
			{
				//hurrah - it's all good - lets update ourself..
				
				//update gridimage_search too
				if ($this->realname != stripslashes($profile['realname'])) {
					$sql="update gridimage_search set realname=".$db->Quote(stripslashes($profile['realname'])).
						" where user_id = {$this->user_id}";
					$db->Execute($sql);
				}
				
				
				$this->realname=$profile['realname'];
				$this->nickname=$profile['nickname'];
				$this->password=$password;
				$this->salt=$salt;
				$this->website=$profile['website'];
				$this->public_email=isset($profile['public_email'])?1:0;
				if (isset($profile['sortBy'])) 
					$this->sortBy=stripslashes($profile['sortBy']);
				$this->search_results=stripslashes($profile['search_results']);
				$this->slideshow_delay=stripslashes($profile['slideshow_delay']);
				$this->about_yourself=stripslashes($profile['about_yourself']);
				$this->public_about=stripslashes($profile['public_about']);
				$this->age_group=stripslashes($profile['age_group']);
				$this->use_age_group=stripslashes($profile['use_age_group']);
				$this->grid_reference=$gs->grid_reference;	
				$this->calendar_public=stripslashes($profile['calendar_public']);
				$this->ticket_public=stripslashes($profile['ticket_public']);
				$this->ticket_option=stripslashes($profile['ticket_option']);				
				$this->message_sig=stripslashes($profile['message_sig']);
				$this->upload_size=intval($profile['upload_size']);
				$this->clear_exif=!empty($profile['clear_exif']);
				$this->_forumUpdateProfile();
				$this->_forumLogin();
				
				if (!empty($profile['ticket_public_change'])) {

					$sql = sprintf("update gridimage_ticket set `public`=%s where user_id = %d",
						$db->Quote($profile['ticket_public_change']),
						$this->user_id
						);

					if ($db->Execute($sql) === false) 
					{
						$errors['general']=$MESSAGES['class_user']['error_dbupdate'].$db->ErrorMsg();
						$ok=false;
					}
				}

			}
		
		}
		
		return $ok;
	}
	
	/**
	* log the user out
	*/
	function logout()
	{
		$db = $this->_getDB();

		//clear any moderation locks
		$db->Execute("DELETE FROM gridsquare_moderation_lock WHERE user_id = {$this->user_id}");
		$db->Execute("DELETE FROM gridimage_moderation_lock WHERE user_id = {$this->user_id}");
		
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
		$this->adminmode = false;
		
		//we've changed state, won't hurt to use a new
		//session id...
		session_regenerate_id(); 
		
		//delete the autologin token - needed to prevent someone contining to use a highjacked cookie
		if(isset($_COOKIE['autologin']))
		{
			$db = $this->_getDB();
			
			$bits=explode('_', $_COOKIE['autologin']);
			if ((count($bits)==2) &&
			    is_numeric($bits[0]) &&
			    preg_match('/^[a-f0-9]{32}$/' , $bits[1]))
			{
				$clause="user_id='{$bits[0]}' and token='{$bits[1]}'";
				
				//delete the autologin, we've used it
				$db->query("delete from autologin where $clause");

			}
		}

		//also clear the autologin cookie as doesnt make sence to keep
		setcookie('autologin', '', time()-3600*24*365,'/');  
		setcookie('autologin', '', time()-3600*24*365);  

	}

	function getAdminMode()
	{
		# return(!empty($_SESSION['admin_mode'])) # not good if session expires
		# => use cookie similar to autologin
		if (!$this->registered) {
			$this->adminmode = false;
			return false;
		}
		if ($this->adminmode) {
			return true; // already verified
		}
		if (isset($_COOKIE['adminmode'])) {
			// FIXME must we really check the cookie against the db?
			// The cookie must guarantee that a CSRF token has been validated.
			$db = $this->_getDB();
			
			$bits=explode('_', $_COOKIE['adminmode']);
			if ((count($bits)==2) &&
			    is_numeric($bits[0]) &&
			    preg_match('/^[a-f0-9]{32}$/' , $bits[1]) &&
			    intval($bits[0]) == $this->user_id
			)
			{
				$clause="user_id='{$this->user_id}' and token='{$bits[1]}'";
				$row = $db->GetRow("select * from adminmode where $clause limit 1");

				if ($row !== false && count($row)) {
					$db->Execute("delete from adminmode where $clause");

					$token = md5(uniqid(rand(),1));
					$db->Execute("insert into adminmode(user_id,token) values ('{$this->user_id}', '$token')");
					setcookie('adminmode', $this->user_id.'_'.$token, time()+3600*24*365,'/');
					$this->adminmode = true;
					return true;
				}
			}
		}
		return false;
	}

	/* IMPORTANT: admin mode should only be activated if the CSRF token is valid! */
	function setAdminMode($state)
	{
		if (!$this->registered) {
			$this->adminmode = false;
			return false;
		}
		#if ($this->adminmode === $state) {
		#	return true;
		#}
		$db = $this->_getDB();
		$db->Execute("delete from adminmode where user_id={$this->user_id}"); // FIXME only delete entry matching cookie
		if ($state) {
			$token = md5(uniqid(rand(),1));
			$db->Execute("insert into adminmode(user_id,token) values ('{$this->user_id}', '$token')");
			setcookie('adminmode', $this->user_id.'_'.$token, time()+3600*24*365,'/');
			$this->adminmode = true;
		} else {
			setcookie('adminmode', '', time()-3600*24*365,'/');
			setcookie('adminmode', '', time()-3600*24*365);
			$this->adminmode = false;
		}
		return true;
	}
	
	/**
	* force inline login if user isn't authenticated
	*/
	function mustHavePerm($perm, $uid = 0, $verifyadmin = true)
	{
		//not logged in? do that first
		if (!$this->registered)
		{
			//do an inline login
			$this->login();
		}
		
		//to reach here, user is logged in, lets check the perms
		if (
			   $verifyadmin && in_array($perm, array('admin', 'forum', 'mapmod')) && !$this->getAdminMode()
			|| strpos($this->rights, $perm)===false
			|| $uid != 0 && $uid != $this->user_id
		) {
			//user is logged in, but hasn't got sufficient rights
			$smarty = new GeoGraphPage;
			$smarty->assign('required', $perm);
			$smarty->assign('adminmoderequired', in_array($perm, array('admin', 'forum', 'mapmod')));
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
	function hasPerm($perm,$allowNonRef = false,$verifyadmin=true)
	{
		if ($verifyadmin && in_array($perm, array('admin', 'forum', 'mapmod')) && !$this->getAdminMode()) {
			return false;
		}
		return (($this->registered || $allowNonRef) && (strpos($this->rights, $perm)!==false));
	}
	
	function basicAuthLogin() {
		global $MESSAGES;
		if (isset($_SERVER['PHP_AUTH_USER']))
		{
			$email=stripslashes(trim($_SERVER['PHP_AUTH_USER']));
			
			$db = $this->_getDB();

			$sql="";
			if (isValidEmailAddress($email))
				$sql='select * from user where email='.$db->Quote($email).' limit 1';
			elseif (isValidRealName($email))
				$sql='select * from user where nickname='.$db->Quote($email).' limit 1';


			if (strlen($sql))
			{
				//user registered?
				$arr = $db->GetRow($sql);	
				if (count($arr))
				{
					//passwords match?
					if ($this->password_verify(stripslashes(trim($_SERVER['PHP_AUTH_PW'])), $arr['password'], $arr['salt'], 'login', $arr['user_id']))
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
							$this->adminmode = false;
							$logged_in=true;
						}
						else
						{
							$error = sprintf($MESSAGES['class_user']['must_confirm'], $email);
						}
					}
					else
					{
						//speak friend and enter					
						$error = $MESSAGES['class_user']['invalid_password'];
					}

				}
				else
				{
					//sorry son, your name's not on the list
					$error = $MESSAGES['class_user']['user_unknown'];
				}
			}
			else
			{
				$error = $MESSAGES['class_user']['E-Mail-Adresse bzw. Benutzername ungültig'];

			}
		} 
		else 
		{
			$error ='No Credentials Supplied';
		}
		
		
		//failure to login means we never return - we show a login page
		//instead...
		if (!$logged_in)
		{
			header('WWW-Authenticate: Basic realm="Geograph"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'Error: Unable to Authenticate - '.$error;
			exit;
		}
	}
	
	/**
	* force inline login if user isn't authenticated
	* only return after successful login unless $always_return is true
	* Determines user by user_id $uid if given, or $_POST['email'] otherwise.
	* Returns boolean error status (true on success) if $return_error is false,
	* or an array describing the errors (empty array on success) otherwise.
	*/
	function login($inline=true, $uid=0, $always_return=false, $return_error=false)
	{
		global $MESSAGES;
		
		$logged_in=false;
		$errors=array();
		
		if (!$this->registered)
		{
			//lets see if we are processing a login?
			if ($uid != 0) {
				$email = '';
				$password=stripslashes(trim($_POST['password'])); // FIXME stripslashes?
				$remember_me=isset($_POST['remember_me'])?1:0;
			} elseif (isset($_POST['email'])) {
				$email=stripslashes(trim($_POST['email']));
				$password=stripslashes(trim($_POST['password']));
				$remember_me=isset($_POST['remember_me'])?1:0;
			} else {
				$email = '';
				$password = '';
				$remember_me = 0;
			}
			if ($uid != 0 || isset($_POST['email'])) {
				if (!isset($_POST['CSRF_token']) || $_POST['CSRF_token'] !== $_SESSION['CSRF_token']) {
					$errors['csrf'] = true;
				} else {
					unset($_POST['CSRF_token']); /* This has been replaced with the valid token of the login form,
					                                which might not have been present in the original request.
					                                Gives user the chance to verify. */
				
					$db = $this->_getDB();

					$sql="";
					if ($uid != 0)
						$sql='select * from user where user_id='.$db->Quote($uid).' limit 1';
					if (isValidEmailAddress($email))
						$sql='select * from user where email='.$db->Quote($email).' limit 1';
					elseif (isValidRealName($email))
						$sql='select * from user where nickname='.$db->Quote($email).' limit 1';
					
					
					if (strlen($sql))
					{
						//user registered?
						$arr = $db->GetRow($sql);	
						if (count($arr))
						{
							//passwords match?
							if ($this->password_verify($password, $arr['password'], $arr['salt'], 'login', $arr['user_id']))
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
									
									//temporary nickname fix for beta accounts
									if (strlen($this->nickname)==0)
										$this->nickname=str_replace(" ", "", $this->realname);

									// do we have a better hashing method?
									if ($this->hashNeedsUpdate($arr['password'], $arr['salt'])) {
										$newsalt = '';
										$newhash = '';
										$this->password_hash($password, $newhash, $newsalt);
										if (    $this->password_verify($password, $newhash, $newsalt)
										    &&! $this->hashNeedsUpdate($newhash,  $newsalt)) { # just to be sure there was no error
											$sql =  'update user set password='.$db->Quote($newhash).
												',salt='.$db->Quote($newsalt).
												" where user_id={$this->user_id}";
											if ($db->Execute($sql) !== FALSE) {
												$this->password = $newhash;
												$this->salt = $newsalt;
												$sql = "update geobb_users set user_password=".$db->Quote($newhash).
													" where user_id={$this->user_id}";
												$db->Execute($sql);
											} else {
												trigger_error("Could not save new hash and salt.", E_USER_WARNING);
											}
										} else {
											trigger_error("New hash and salt invalid!", E_USER_WARNING);
										}
									}

									//give user a remember me cookie?
									if ($remember_me)
									{
										$token = md5(uniqid(rand(),1)); 
										$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
										setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365,'/');  
									}
									
									//we're changing privilege state, so we should
									//generate a new session id to avoid fixation attacks
									session_regenerate_id(); 
									# FIXME we should probably regenerate the CSRF token at any occurence of session_regenerate_id()
									
									$this->registered=true;
									$this->adminmode = false;
									$logged_in=true;
									
									//log into forum too
									$this->_forumLogin();

									if (isset($_SESSION['maptt'])) 
										unset($_SESSION['maptt']);								
								}
								else
								{
									$errors['general']=sprintf($MESSAGES['class_user']['must_confirm'], $email);
								}
							}
							else
							{
								//speak friend and enter					
								$errors['password']=$MESSAGES['class_user']['invalid_password'];
							}

						}
						else
						{
							//sorry son, your name's not on the list
							$errors['email']=$MESSAGES['class_user']['user_unknown'];
						}
					}
					else
					{
						$errors['email']=$MESSAGES['class_user']['user_invalid'];
						
					}
				}
			}
		}
		else
		{
			$logged_in=true;
		}
		//failure to login means we never return - we show a login page
		//instead...
		if (!$logged_in && !$always_return)
		{
			$smarty = new GeoGraphPage;
			
			$smarty->assign('remember_me', isset($_COOKIE['autologin'])?1:0); # FIXME why not $remember_me ?
			$smarty->assign('inline', $inline);
			$smarty->assign('email', $email);
			$smarty->assign('password', $password);
			$smarty->assign('errors', $errors);
			if (isset($errors['password'])) {
				$smarty->assign('lock_seconds', $this->lock_seconds);
			}
			$smarty->assign_by_ref('_post', $_POST);
			$smarty->display('login.tpl');
			exit;
		}

		if (!$return_error) {
			return $logged_in;
		}

		if ($logged_in) {
			return array();
		}

		if (!count($errors)) {
			$errors['unknown'] = true;
		}

		return $errors;
	}
	
	/**
	* attempt to authenticate user from persistent cookie
	*/
	function autoLogin()
	{
		if(isset($_COOKIE['autologin']))
		{
			$db = $this->_getDB();
			
			$errorNumber = -1;
			$valid=false;
			$bits=explode('_', $_COOKIE['autologin']);
			if ((count($bits)==2) &&
			    is_numeric($bits[0]) &&
			    preg_match('/^[a-f0-9]{32}$/' , $bits[1]))
			{
				$clause="user_id='{$bits[0]}' and token='{$bits[1]}'";
				$row=$db->GetRow("select * from autologin where $clause limit 1");
				
				//log the errornumber (we use in case the db lookup failed)
				$errorNumber = $db->ErrorNo();
					
				if (count($row))
				{
					//log the user in
					$sql='select * from user where user_id='.$db->Quote($bits[0]).' limit 1';
					$user = $db->GetRow($sql);
					
					//log the errornumber (we use in case the db lookup failed) 
					$errorNumber = $db->ErrorNo();
					
					if (count($user))
					{
						$valid=true;
						
						foreach($user as $name=>$value)
						{
							if (!is_numeric($name))
								$this->$name=$value;
						}

						//temporary nickname fix for beta accounts
						if (strlen($this->nickname)==0)
							$this->nickname=str_replace(" ", "", $this->realname);

						//we're changing privilege state, so we should
						//generate a new session id to avoid fixation attacks
						session_regenerate_id(); 

						$this->registered=true;
						$this->adminmode = false;
						$this->autologin=true;

						//log into forum
						$this->_forumLogin();

						//delete the autologin, we've used it
						$db->query("delete from autologin where $clause");

						//given the user a new one
						$token = md5(uniqid(rand(),1)); 
						$db->query("insert into autologin(user_id,token) values ('{$this->user_id}', '$token')");
						setcookie('autologin', $this->user_id.'_'.$token, time()+3600*24*365,'/');
					}
				}
			}
			if ($errorNumber != 0) {
				die("Server Error, please wait 10 seconds then press F5, and click Yes if asked to confirm. This measure is to hopefully perserve what you are working on. If you still get this message after repeated tries then there is nothing for it but to click back and try again.");
				exit;
			}

			//clear the cookie?
			if (!$valid)
			{
				setcookie('autologin', '', time()-3600*24*365,'/');
				setcookie('autologin', '', time()-3600*24*365);
			}
		}
	}
	
	/**
	* Updates forum profile to keep the forum software in sync with us
	*/
	function _forumUpdateProfile()
	{
		$db = $this->_getDB();
	
		//we maintain a direct user_id to user_id mapping with the minibb 
		//forum software....
	
		
		//do we have a forum user?
		$existing=$db->GetRow("select * from geobb_users where user_id='{$this->user_id}' limit 1");
		if (count($existing))
		{
			//update profile
			$sql="update geobb_users set username=".$db->Quote($this->nickname).
				", user_email=".$db->Quote($this->email).
				", user_password=".$db->Quote($this->password).
				", user_website=".$db->Quote($this->website).
				", user_viewemail=".$this->public_email.
				(isset($this->sortBy)?', user_sorttopics = '.$this->sortBy:'').
				" where user_id={$this->user_id}";
				
			$db->Execute($sql);	
		}
		else
		{
			//create new profile
			$sql="insert into geobb_users(user_id,username, user_regdate,user_password,user_email,user_website,user_viewemail) values (".
				$this->user_id.",".
				$db->Quote($this->nickname).",".
				"now(),".
				$db->Quote($this->password).",".
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
		
		$passmd5=$this->password;
		$expiry=time()+108000;
		
		//we don't need a permanent cookie
		//setcookie('geographbb', 
		//	$this->nickname.'|'.$passmd5.'|'.$expiry, 
		//	$expiry);
			
		$_SESSION['minimalistBBSession']=$this->nickname.'|'.$passmd5.'|'.$expiry;
	}

	/**
	* Log out of forum
	*/
	function _forumLogout()
	{
		//we clear the miniBB cookie here as early betas
		//did set it
		setcookie('geographbb', '', time()-108000);
		unset($_SESSION['minimalistBBSession']);
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!isset($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed'); 
		return $this->db;
	}	
	
	/**
	 * remove the stored db object ready to serialize
	 * @access private
	 */
	function __sleep() {
		if (isset($this->db)) {
			#$this->db->Close();
			unset($this->db);
		}
		
		$vars = get_object_vars($this);
		return array_keys($vars);
	}
}
?>
