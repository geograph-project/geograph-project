<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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
$cacheid = '';

$db=NewADOConnection($GLOBALS['DSN']);

$isadmin=$USER->hasPerm('admin')?1:0;

$prev_fetch_mode = $ADODB_FETCH_MODE;
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;


if (!empty($_GET['action'])) 
	switch ($_GET['action']) {
		case 'listall':
			$USER->mustHavePerm('admin');
		
			$data = $db->getAll("SELECT * FROM conference_registration WHERE cancelled = 0");
		
			$smarty->assign_by_ref("data",$data);
			
			$template = "event_conference_list.tpl";
			break;
		
		case 'sendemail':
			$USER->mustHavePerm('admin');
			
			$data = $db->getAll("SELECT * FROM conference_registration WHERE confirmed = 0 AND cancelled = 0 AND emailed = 0 LIMIT 10");
			
			$from_email = "conference@barryhunter.co.uk";
			$from_name = "Geograph Conference";
			
			foreach ($data as $idx => $row) {
				
				$email = $row['Email'];
				$subject = "[Geograph] Conference Registration Confirmation";
				$body = "Dear {$row['Name']},\n\n";
				
				$body .= "Thank you for your interest in attending the first Geograph Conference, 17th Feb 2010 in Southampton.\n\n";
				
				$body .= "We are pleased to say this message confirms your place should you still be able to attend, please click the link below and confirm!\n\n";
				
				
				$body = wordwrap($body);
				
				$token=new Token;
				$token->magic = md5($CONF['photo_hashing_secret'].$CONF['register_confirmation_secret']);
				$token->setValue("eid", intval($row['entry_id']));
				$token = $token->getToken();
	
				$body .= "http://{$_SERVER['HTTP_HOST']}/events/conference.php?action=confirm&ident=$token\n\n";
							
				$body2 = "Please keep this email safe, in case you need to return to the above page, to update your registration should circumstances change.\n\n";		
							
				$body2.="Kind Regards,\n\n";
				$body2.="Barry\non behalf of the Geograph Team\n\n";
				
				$body2.="P.S. We will be contacting members interested in speaking seperately.\n";
				
				$body = $body.wordwrap($body2);
				
				if (@mail($email, $subject, $body, $received."From: $from_name <$from_email>")) 
				{
					$db->query("UPDATE conference_registration SET emailed = NOW() WHERE entry_id = {$row['entry_id']}");

					print "SENT TO $email<br/>";
				}
				else 
				{
					print "SEND TO $email FAILED<br/>";
					
				}
				
			}
			
			print "<hr/>DONE";
			exit;
			
			break;
			
		case 'confirm':
			
			$token=new Token;
			$token->magic = md5($CONF['photo_hashing_secret'].$CONF['register_confirmation_secret']);
			
			if ($token->parse($_GET['ident'])&& $token->hasValue("eid")) {
				$entry_id = intval($token->getValue("eid"));
			
				$data = $db->getRow("SELECT * FROM conference_registration WHERE entry_id = $entry_id");
				
				if ($data['duplicates']) {
				
					$token=new Token;
					$token->magic = md5($CONF['photo_hashing_secret'].$CONF['register_confirmation_secret']);
					$token->setValue("eid", intval($data['duplicates']));
					$token = $token->getToken();

					$url = "http://{$_SERVER['HTTP_HOST']}/events/conference.php?action=confirm&ident=$token\n\n";
					header("Location: $url");
					exit;
				}
		
				if (!empty($_POST) && $_GET['ident'] == $_POST['ident'] && $data['entry_id']) {
		
					if (!empty($_POST['confirm'])) {
					
						$Speaking = isset($_POST['Speaking'])?'Yes':'No';
					
					
						$db->query("UPDATE conference_registration SET confirmed = NOW(),Speaking = '$Speaking' WHERE entry_id = {$data['entry_id']}");

					} elseif (!empty($_POST['cancel'])) {
					
						
						$db->query("UPDATE conference_registration SET cancelled = NOW() WHERE entry_id = {$data['entry_id']}");

					}
					
					$data = $db->getRow("SELECT * FROM conference_registration WHERE entry_id = $entry_id");
				} else {
					$smarty->assign('get',1);
				}
				
				
				if (isset($_POST['comments'])) {
				
					$updates = array();
					$updates['entry_id'] = $data['entry_id'];
					$updates['user_id'] = $USER->user_id;
					$updates['comment'] = $_POST['comments'];

					$db->Execute('INSERT INTO conference_comment SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

				}
		
		
						
				$smarty->assign($data);
				$smarty->assign('ident',$_GET['ident']);
				
				$template = "event_conference_confirm.tpl";
			} else {
				die('invalid registration  - please <a href="/contact.php">Contact Us</a>');
			}
			break;
		
	}




$smarty->display($template, $cacheid);

