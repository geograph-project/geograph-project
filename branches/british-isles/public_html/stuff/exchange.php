<?php
/**
 * $Project: GeoGraph $
 * $Id: conversion.php 5502 2009-05-13 14:18:23Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 BArry Hunter (geo@barryhunter.co.uk)
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

$template='stuff_exchange.tpl';
$cacheid='';


if (!empty($_POST)) {
	$db = GeographDatabaseConnection(false);
	
	if (!empty($_POST['exchange_id']) && !empty($_POST['gridimage_id'])) {
		
		$exchange = $db->getRow("SELECT * FROM `exchange` WHERE exchange_id = ".intval($_POST['exchange_id']));
		
		if (!empty($exchange)) {
			
			$_POST['topic'] = $exchange['topic'];
			$create = true;
		}
	}
	
	if (!empty($_POST['create']) || !empty($create)) {
		$updates = array();
		foreach (array('topic','gridimage_id','email') as $key) {
			if (empty($_POST[$key])) {
				if ($key != 'email')
					$errors[$key] = "missing required info";	
			} else {	
				$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
			}
			$smarty->assign($key, $_POST[$key]);
		}
		if (intval($_POST['gridimage_id']) < 1)
			$errors['gridimage_id'] = "not a valid id";
			
		if (empty($errors)) {
		
			if (empty($_POST['create'])) {
				//if dont want a reply, mark the new reply as not open
				$updates[] = "`open` = 'N'";
			}
			
			$updates[] = "`user_id` = {$USER->user_id}";
			$updates[] = "`created` = NOW()";
			$sql = "INSERT INTO `exchange` SET ".implode(',',$updates);

			$db->Execute($sql);
			
			$exchange_id = $db->Insert_ID();
			
			$smarty->assign('message',"Request saved!");
		} else {
			if ($errors[1] != 1)
				$smarty->assign('error', "Please see messages below...");
			$smarty->assign_by_ref('errors',$errors);
		}
	}
	
	if (!empty($exchange) && $exchange_id && empty($errors)) {
		$updates = array();
		
		$updates[] = "`reply_id` = $exchange_id";
				
		$sql = "UPDATE `exchange` SET ".implode(',',$updates)." WHERE exchange_id = ".$db->Quote($_REQUEST['exchange_id']);
		$db->Execute($sql);
		
		$image=new GridImage();
		$ok = $image->loadFromId($exchange['gridimage_id']);

		if (!$ok || $image->moderation_status=='rejected') {
			//clear the image
			$image=new GridImage;
			header("HTTP/1.0 410 Gone");
			header("Status: 410 Gone");
			$template = "static_404.tpl";
		} else {
			$smarty->assign_by_ref('image', $image);
			
			$profile=new GeographUser($exchange['user_id']);	
					
			if ($profile->user_id!=0) {
				$smarty->assign_by_ref('user_id',$profile->user_id);
				$smarty->assign_by_ref('realname',$profile->realname);
				
				if ($exchange['email'] == 'Y' && !empty($profile->email) && !$profile->hasPerm('dormant')) {
					
					$reply=new GridImage();
					$ok = $reply->loadFromId($_POST['gridimage_id']);

					if ($ok && $image->moderation_status!='rejected') {
					
						$subject = "[Geograph] Reply to your exchange request!";

						$email = $profile->email;

						$body = "Dear {$profile->realname}, \n\n";

						$body .= "This is a message to let you know that {$USER->realname}\n";
						$body .= "http://{$_SERVER['HTTP_HOST']}/profile/{$USER->user_id}\n";
						$body .= "has replied to your exchange request!\n\n";

						$body .= "They provided the following photo in response to your '{$_POST['topic']}' request\n\n";

						$body .= "{$reply->grid_reference} :: {$reply->title}\n";
						$body .= " by {$reply->realname}\n";
						$body .= "http://{$_SERVER['HTTP_HOST']}/photo/{$reply->gridimage_id}\n\n";
						
						$body .= "Thanks, \n the Geograph Website\n\n";
						
						$body .= "-------------------------------\n";
						$body .= " Forward abuse complaints to: support@geograph.org.uk\n\n";
						
						if (@mail($email, $subject, $body, "From: Do NOT reply <noreply@geograph.org.uk>")) 
						{
							$smarty->assign('sent', 1);
						}
					}
				}
			}
		}
	}
	
}


if (empty($db)) {
	$db = GeographDatabaseConnection(true);
}

if (isset($_GET['replies'])) {
	$template='stuff_exchange_replies.tpl';
	$cacheid = 'q';

	$replies = $db->getAll("
		SELECT 
			`left`.topic,
			`left`.gridimage_id as left_gridimage_id,
			`left`.user_id as left_user_id,
			`right`.gridimage_id as rigth_gridimage_id,
			`right`.user_id as rigth_user_id
		FROM
			`exchange` AS `left`
			INNER JOIN `exchange` AS `right` ON (`left`.reply_id = `right`.exchange_id)
		WHERE
			`left`.user_id = {$USER->user_id}
			OR `right`.user_id = {$USER->user_id}
		ORDER BY `left`.exchange_id DESC
		LIMIT 25
		");
		
	if (!empty($replies)) {
		$gids = $uids = array();
		foreach ($replies as $row) {
			$gids[$row['left_gridimage_id']]=1;
			$gids[$row['rigth_gridimage_id']]=1;
			$uids[$row['left_user_id']]=1;
			$uids[$row['rigth_user_id']]=1;
		}
		
		$imagelist = new ImageList();
		$imagelist->getImagesByIdList(array_keys($gids),"gridimage_id,title,realname,user_id,grid_reference,credit_realname,x,y");
		
		if (!empty($imagelist->images)) {
			$images = array();
			foreach ($imagelist->images as $image) {
				$images[$image->gridimage_id] = $image;
			}
			$smarty->assign_by_ref('replies',$replies);
			$smarty->assign_by_ref('images',$images);
			
			$users = $db->getAssoc("SELECT user_id,realname FROM `user` WHERE user_id IN (".implode(',',array_keys($uids)).")");
			
			$smarty->assign_by_ref('users',$users);
		}

	}

} elseif ($topics = $db->getAssoc("SELECT exchange_id,topic FROM `exchange` WHERE open = 'Y' and `reply_id` = 0 AND `user_id` != {$USER->user_id} GROUP BY topic")) {
	$smarty->assign_by_ref('topics',$topics);
}

$smarty->display($template, $cacheid);

