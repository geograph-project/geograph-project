<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 2960 2007-01-15 14:33:27Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2008 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('3rdparty/class.phpmailer.php');
	
if (!isLocalIPAddress())
{
	init_session();
        $USER->mustHavePerm("admin");
}

$db=NewADOConnection($GLOBALS['DSN']);
if (!$db) die('Database connection failed');  

set_time_limit(3600*24);


#####################

$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;



$sql = "
SELECT
	tm.*,tm.user_id as ticket_user_id,gi.user_id,gi.title,gs.grid_reference,u.email,u.realname
FROM
	gridimage_ticket_message tm
INNER JOIN
	gridimage gi USING (gridimage_id)
INNER JOIN
	gridsquare gs USING (gridsquare_id)
INNER JOIN 
	user u ON (tm.user_id = u.user_id)
WHERE
	status = 'new'
ORDER BY 
	tm.user_id,gridimage_id,gridimage_ticket_message_id
";


$recordSet = &$db->Execute("$sql");

$rows = array();
$lastuser = 0;
while (!$recordSet->EOF) 
{
	$rs = $recordSet->fields;
	
	if ($lastuser && $lastuser != $rs['ticket_user_id']) {
		send_message($rows);
		
		$rows = array();
	}
	$lastuser = $rs['ticket_user_id'];
	
	$rows[] = $rs;
	
	$recordSet->MoveNext();
}
$recordSet->Close(); 

if ($lastuser)
	send_message($rows);


#########################

function send_message(&$rows) {
	$last = 0;
	$plain = $html = '';
	foreach ($rows as $row) {
		
		if ($last != $row['gridimage_id']) {
			if ($last) {
				$plain .= "\n--\nTo Reply: http://{$_SERVER['HTTP_HOST']}/editimage.php?id=$last\n\n";
				
				$html .= "<p><small>&middot; <a href=\"http://{$_SERVER['HTTP_HOST']}/editimage.php?id=$last\">View suggesion online</a></p>";
			}
			$plain .= "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n";
			$plain .= "{$row['grid_reference']} :: {$row['title']}\n";
			$plain .= "http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$row['gridimage_id']}#{$row['gridimage_ticket_id']}\n";
			
			$html .= "<hr style=\"display:none\"/>";
			$html .= "<div style=\"background-color:#cccccc;padding:2px\">{$row['grid_reference']} :: <a href=\"http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$row['gridimage_id']}#{$row['gridimage_ticket_id']}\"><b>".htmlentities($row['title'])."</b></a></div>\n";
			
			$image = new GridImage();
			$image->fastInit($row);
			$html .= str_replace(' src=',' align="right" src=',$image->getThumbnail(120,120))."<br/>";
			
			
		}
		$last = $row['gridimage_id'];
		
		$plain .= "\n--\n";
		$plain .= "When: {$row['created']}\n\n";
		$plain .= "{$row['message']}\n";
		;
		$html .= "<div style=\"background-color:#dddddd;padding:2px\"><small>When: <b style=\"color:blue\">{$row['created']}</b></small></div>";
		
		$html .= "<pre>".wordwrap($row['message'])."</pre><br/>";
		
		$images[$last]++;
	}
	
	if ($last) {
		$plain .= "\n--\nTo Reply: http://{$_SERVER['HTTP_HOST']}/editimage.php?id=$last\n\n";
		
		$html .= "<p><small>&middot; <a href=\"http://{$_SERVER['HTTP_HOST']}/editimage.php?id=$last\">View suggestion online</a></small></p>";
	}
	$plain .= "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n";
	$html .= "<hr style=\"display:none\"/>";
	$html .= "<div style=\"background-color:#cccccc;padding:2px\">&nbsp;</div>";
	
	$to = "{$row['realname']} <{$row['email']}>";
	$subject = "[Geograph] Summary of ".count($images)." suggestions for ".date("d/m/Y");
	$message = "Dear {$row['realname']},\n\nPlease find below a summary of notifications for today.\n\n";
	$message .= "You can also review these at: \n";
	$message .= " http://{$_SERVER['HTTP_HOST']}/suggestions.php\n\n";
	$message .= " - do NOT reply to this message. \n\n";
	$message .= $plain;
	
	$message .= "Many thanks, \n\n Geograph Team\n\n";
	
	$message .= "P.S. Please do not reply to this message, but rather reply to the individual suggestions so the moderators see comments, or for general concerns http://{$_SERVER['HTTP_HOST']}/contact.php\n";
	
	print "<hr>";
	print "<h3>To: ".htmlentities($to)."</h3>";
	print "<h2>$subject</h2>";
	
	print "<pre>".wordwrap($message)."</pre>";
	
	$htmlmessage = "<div style=\"font-family:Georgia, Verdana, Arial, serif; background-color:#eeeeff;padding:15px\">";
	$htmlmessage .= "<a href=\"http://{$_SERVER['HTTP_HOST']}/\"><img src=\"http://{$_SERVER['HTTP_HOST']}/templates/basic/img/logo.gif\" height=\"74\" width=\"257\" border=\"0\" align=\"right\"/></a>";
	$htmlmessage .= "Dear {$row['realname']},<br/><br/>Please find below a summary of notifications for today.\n\n";
	$htmlmessage .= "<p><small>TIP: You can also <a href=\"http://{$_SERVER['HTTP_HOST']}/suggestions.php\">Review these suggestions online</a></small></p>";
	$htmlmessage .= "<blockquote>- do NOT reply to this message.</blockquote>";
	$htmlmessage .= $html;
	

	$htmlmessage .= "<p>Many thanks, <br>&nbsp;Geograph Team</p>";

	$htmlmessage .= "<p>P.S. Please do not reply to this message, but rather reply to the <a href=\"http://{$_SERVER['HTTP_HOST']}/suggestions.php\">individual suggestions</a> so the moderators see comments, or for general concerns <a href=\"http://{$_SERVER['HTTP_HOST']}/contact.php\">contact us</a></p>";
	$htmlmessage .= "</div>";
	
	print $htmlmessage;
	
	
	
	if (isset($_GET['send'])) {
		$mail = new PHPMailer(); // defaults to using php "mail()"
		$mail->SetFrom('lordelph@gmail.com', 'Geograph - Reply Using Link');
		$mail->AddAddress($row['email'], $row['realname']);
		$mail->Subject    = $subject;
		$mail->AltBody    = $message;
		$mail->MsgHTML($htmlmessage);

		if(!$mail->Send()) {
		  echo "<p>Mailer Error: " . $mail->ErrorInfo."</p>";
		} else {
		  echo "<p>Message sent!</p>";
		}
	}
}

?>
