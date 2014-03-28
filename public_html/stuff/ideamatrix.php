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

$USER->mustHavePerm("basic");

$db = GeographDatabaseConnection(false);

if (!empty($_POST['idea_id'])) {
	$updates = array();
	$updates['idea_id'] = intval($_POST['idea_id']);
	$updates['user_id'] = $USER->user_id;
	if (!empty($_POST['comment'])) {
		//add a comment to an idea...
		$updates['tone'] = $_POST['tone'];
		$updates['comment'] = $_POST['comment'];
		$db->Execute('INSERT INTO idea_comment SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	} elseif (!empty($_POST['comment_id'])) {
		//voting for a comment
		$updates['comment_id'] = intval($_POST['comment_id']);
		$db->Execute('INSERT IGNORE INTO idea_vote SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	} else {
		//voting for the idea
		$db->Execute('INSERT IGNORE INTO idea_vote SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
	}

} elseif (!empty($_POST['title'])) {
	//add a new idea
	$updates = array();
	$updates['user_id'] = $USER->user_id;
	$updates['title'] = $_POST['title'];
	$updates['description'] = $_POST['description'];
	$db->Execute('INSERT INTO idea SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
}

$ideas = $db->getAssoc("SELECT i.*, COUNT(vote_id) AS votes, SUM(v.user_id = {$USER->user_id}) AS ownvote
	FROM idea i LEFT JOIN idea_vote v ON (v.idea_id = i.idea_id AND v.comment_id = 0)
	WHERE status =1 GROUP BY idea_id DESC"); //todo, add and group_id = X maybe?
foreach ($ideas as $idea_id => $idea) {
	$ideas[$idea_id]['columns'] = array();
	$ideas[$idea_id]['columns']['for'] = array();
	$ideas[$idea_id]['columns']['against'] = array();
	$ideas[$idea_id]['columns']['neutral'] = array();
}

$comments = $db->getAll("SELECT c.*, realname, SUBSTRING(c.created,1,10) AS `day`, COUNT(vote_id) AS votes, SUM(v.user_id = {$USER->user_id}) AS ownvote
	FROM idea_comment c INNER JOIN user USING (user_id) LEFT JOIN idea_vote v USING (idea_id,comment_id)
	WHERE status =1 GROUP BY comment_id");
foreach ($comments as $comment) {
	if (empty($ideas[ $comment['idea_id'] ]))
		continue;
	$ideas[ $comment['idea_id'] ]['columns'][ $comment['tone'] ][] = $comment;
}

$smarty->assign_by_ref('ideas',$ideas);

$smarty->display("stuff_ideamatrix.tpl");
