<?php
/**
 * $Project: GeoGraph $
 * $Id: places.php 5786 2009-09-12 10:18:04Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2011 barry hunter (geo@barryhunter.co.uk)
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

$USER->mustHavePerm("basic");


if (!empty($_POST)) {
	if (!empty($_POST['message'])) {
		die();
	}
	$db = GeographDatabaseConnection(false);

	######################

	$keys = $db->getAssoc("DESCRIBE comment_thread");
	$u = array();
	$data = '';
	foreach ($keys as $key => $dummy) {
		if (!empty($_POST[$key])) {
			$u[$key] = trim($_POST[$key]);
			$data .= "$key: ".trim($_POST[$key])."\n";
		}
	}
	$data .= "name: ".$USER->nickname."\n";

	if (!empty($u)) {
		$u['user_id'] = $USER->user_id;

		$db->Execute('INSERT INTO comment_thread SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

		######################

		$u2 = array();
		$u2['comment_thread_id'] = $db->Insert_ID();
		$u2['user_id'] = $USER->user_id;
		$u2['comment'] = $_POST['comment'];
		$u2['anon'] = @$_POST['anon'];

		$db->Execute('INSERT INTO comment_post SET created=NOW(),`'.implode('` = ?, `',array_keys($u2)).'` = ?',array_values($u2));

		######################

		$smarty->assign("message",'Thread Created at '.date('r'));

		######################

		$ids = array();
		$ids[] = $USER->user_id; //send it to themselves!
		if (!empty($u['for_user_id']))
			$ids[] = $u['for_user_id'];

		$users = $db->getAll("SELECT email,realname
			FROM user
			WHERE user_id IN (".implode(',',$ids).")");

		if (!empty($u['title'])) {
			$subject = "[Geograph] ".$u['title'];
		} else {
			$subject = "[Geograph] Untitled Comment thread #{$u2['comment_thread_id']}";
		}

		foreach ($users as $user) {
                        $body = "Dear {$user['realname']},\n";
			if (!empty($u['for_topic_id'])) {
				$row = $db->getRow("SELECT * FROM geobb_topics WHERE topic_id = ".intval($u['for_topic_id']));
			        if (!empty($row['topic_title'])) {
                        		$body.="This is a message about the following thread:\n";
		                        $body.="   {$row['topic_title']}\n";
					$body.="{$CONF['SELF_HOST']}/discuss/?action=vthread&topic={$row['topic_id']}\n\n";
					$body .= str_repeat('-',78)."\n\n";
				}
			}
			if ($_POST['anon'] == 'forum') {
				$body .= "Message from Geograph Forum Moderators: \n\n";
			} else {
				$body .= "{$USER->realname} has created a new comment thread: \n\n";
			}
			$body .= "{$_POST['comment']}\n\n";
			$body .= str_repeat('-',78)."\n\n";
	                $body .= "To respond to this message, please visit\n";
        	        $body .= "{$CONF['SELF_HOST']}/discuss/comment-thread.php?id={$u2['comment_thread_id']}\n";
                	$body .= "Please, do NOT reply by email";

	                mail_wrapper($user['email'], $subject, $body, "From: Geograph - Reply Using Link <noreply@geograph.org.uk>");
		}

		######################
	}
}

/////////////////////////////////////////////////////////////////////////////////////

if (empty($db))
	$db = GeographDatabaseConnection(true);

if (!empty($_GET['post_id'])) {
	$row = $db->getRow("SELECT * FROM geobb_posts WHERE post_id = ".intval($_GET['post_id']));
	if (!empty($row['post_id']))
		$smarty->assign('for_post_id', $row['post_id']);

	if (!empty($row['topic_id']))
		$_GET['topic_id'] = $row['topic_id'];
	if (!empty($row['poster_id']) && !isset($_GET['user_id']))
		$_GET['user_id'] = $row['poster_id'];
}

if (!empty($_GET['topic_id'])) {
        $row = $db->getRow("SELECT * FROM geobb_topics WHERE topic_id = ".intval($_GET['topic_id']));
	if (!empty($row['topic_title']))
		$smarty->assign('topic_title', $row['topic_title']);
	if (!empty($row['topic_id']))
		$smarty->assign('for_topic_id', $row['topic_id']);

        if (!empty($row['topic_poster']) && !isset($_GET['user_id']))
                $_GET['user_id'] = $row['topic_poster'];

        $smarty->assign('title',"Discussion Topic: ".$row['topic_title']);
}

if (!empty($_GET['user_id'])) {
	$row = $db->getRow("SELECT user_id,realname,nickname FROM user WHERE user_id = ".intval($_GET['user_id']));
	if (!empty($row['realname']) && !empty($row['nickname']) && $row['nickname'] != $row['realname'])
		$smarty->assign('realname', $row['realname']." (Nickname: ".$row['nickname'].")");
	elseif (!empty($row['realname']))
		$smarty->assign('realname', $row['realname']);

        if (!empty($row['user_id']))
                $smarty->assign('for_user_id', $row['user_id']);

}

/////////////////////////////////////////////////////////////////////////////////////

//check if thread for this topic/etc
// ... note this also used to rediurect to the newly created thread above!


        $where = array();
        $where[] = "status = 1";
	if (!empty($_GET['topic_id']))
        	$where[] = "for_topic_id = ".intval($_GET['topic_id']);
	if (!empty($_GET['post_id']))
        	$where[] = "for_post_id = ".intval($_GET['post_id']);
	else
		$where[] = "for_post_id IS NULL"; //kind of want to allow  creating a thread for the whole topic, even if there is one a particular post??
	if (!empty($_GET['user_id']))
        	$where[] = "for_user_id = ".intval($_GET['user_id']);
	elseif (isset($_GET['user_id']))
        	$where[] = "for_user_id IS NULL";

//make sure this user can see it
/// todo, need to perhaps allow the user to create a thread for a different group, but that could get confusing (if say directors have seperate thread to forum?) 
        if (!empty($USER->user_id)) {
                $in = explode(',',$USER->rights);
                $where[] = "for_right IN ('".implode("','",$in)."')";

		$smarty->assign('has_right', array_flip($in)); //to get an associtive array!

        } else {
                //dont want to check for_user_id, as it might be a private thread, for a specific right, without user_id)
                $where[] = "for_right = 'all'";
        }

//if found redirect to it
        if ($thread = $db->getRow("SELECT * FROM comment_thread WHERE ".implode(' AND ',$where))) {
		header("Location: /discuss/comment-thread.php?id={$thread['comment_thread_id']}", false, 302);
		exit;
	}

/////////////////////////////////////////////////////////////////////////////////////


$smarty->display('discuss_create_comment_thread.tpl');


