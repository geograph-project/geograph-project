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

if (!empty($_GET['login'])) //we dont always require login. The page will prompt to login if needed!
	$USER->mustHavePerm("basic");

###############################################################################

$db = GeographDatabaseConnection(false);

$where = array();
$where[] = "status = 1";
$where[] = "comment_thread_id = ".intval($_GET['id']);
if (!empty($USER->user_id)) {
	$in = explode(',',$USER->rights);
	$in[] = 'all';
	$where[] = "( for_user_id = {$USER->user_id} OR for_right IN ('".implode("','",$in)."') )";
} else {
	//dont want to check for_user_id, as it might be a private thread, for a specific right, without user_id) 
	$where[] = "for_right = 'all'";
}

$thread = $db->getRow("SELECT * FROM comment_thread WHERE ".implode(' AND ',$where));

###############################################################################

if (!empty($thread) && !empty($_POST)) {
	if (!empty($_POST['message'])) {
		die();
	}

	######################

	$keys = $db->getAssoc("DESCRIBE comment_post");
	$u = array();
	foreach ($keys as $key => $dummy) {
		if (!empty($_POST[$key])) {
			$u[$key] = trim($_POST[$key]);
		}
	}

	if (!empty($u)) {
		$u['comment_thread_id'] = intval($_GET['id']);
		$u['user_id'] = $USER->user_id;

		$db->Execute('INSERT INTO comment_post SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

		$smarty->assign("message",'Comment Posted '.date('r'));

		$users = $db->getAll("SELECT DISTINCT email,realname
			FROM comment_post
			INNER JOIN user USING (user_id)
			WHERE comment_thread_id = {$u['comment_thread_id']}");

		if (!empty($thread['title'])) {
			$subject = "[Geograph] ".$thread['title'];
		} else {
			$subject = "[Geograph] Untitled Comment thread #{$u['comment_thread_id']}";
		}
		if ($_POST['anon'] == 'forum') {
                        $body .= "Message from Geograph Forum Moderators: \n\n";
                } else {
			$body = "{$USER->realname} as posted a reply to thread: \n\n";
		}
		$body .= "{$_POST['comment']}\n\n";
		$body .= str_repeat('-',78)."\n\n";
                $body .= "To respond to this message, please visit\n";
                $body .= "{$CONF['SELF_HOST']}/discuss/comment-thread.php?id={$u['comment_thread_id']}\n";
                $body .= "Please, do NOT reply by email";

		foreach ($users as $user) {
	                mail_wrapper($user['email'], $subject, $body, "From: Geograph - Reply Using Link <noreply@geograph.org.uk>");
		}
	}
}

/////////////////////////////////////////////////////////////////////////////////////

if (!empty($thread)) {
	if (!empty($thread['for_topic_id'])) {
	        $row = $db->getRow("SELECT * FROM geobb_topics WHERE topic_id = ".intval($thread['for_topic_id']));
		if (!empty($row['topic_title']))
			$smarty->assign('topic_title', $row['topic_title']);
		if (!empty($row['topic_id']))
			$smarty->assign('for_topic_id', $row['topic_id']);
	}

	if (!empty($thread['for_user_id']) && $thread['for_user_id'] != $USER->user_id) {
		$row = $db->getRow("SELECT user_id,realname,nickname FROM user WHERE user_id = ".intval($thread['for_user_id']));
		if (!empty($row['realname']) && !empty($row['nickname']) && $row['nickname'] != $row['realname'])
			$smarty->assign('realname', $row['realname']." (Nickname: ".$row['nickname'].")");
		elseif (!empty($row['realname']))
			$smarty->assign('realname', $row['realname']);

	        if (!empty($row['user_id']))
        	        $smarty->assign('for_user_id', $row['user_id']);
	}

	$posts = $db->getAssoc("SELECT comment_post_id, user_id, realname, nickname, created, comment, anon
				FROM comment_post
				LEFT JOIN user USING (user_id)
				WHERE comment_thread_id = {$thread['comment_thread_id']}
				AND status = 1
				ORDER BY comment_post_id");

	if (!in_array($in,'forum')) {
		foreach ($posts as $idx => $post)
			if ($post['anon'] == 'forum')
				$posts[$idx]['realname'] = 'Geograph Forum Moderator';
	}

	$smarty->assign('posts',$posts);
} else {
	//even if can't view the thread they need the id, for links!
	$thread = array('comment_thread_id' => intval($_GET['id']));
}

$smarty->assign('thread',$thread);

$smarty->display('discuss_comment_thread.tpl');


