<?php
/**
 * $Project: GeoGraph $
 * $Id: tickets.php 1568 2005-11-15 14:36:34Z barryhunter $
 * 
 * GeoGraph geographic photo archive project
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


require_once('geograph/global.inc.php');
init_session();

$USER->mustHavePerm("basic");

$smarty = new GeographPage;
$template = "games_quiz_questions.tpl";

$db = NewADOConnection($GLOBALS['DSN']);


$isadmin=$USER->hasPerm('moderator')?1:0;
$smarty->assign_by_ref('isadmin', $isadmin);

if ($isadmin) {
	if (!empty($_GET['question_id']) && preg_match('/^[\d]+$/',$_GET['question_id'])) {
		$db=NewADOConnection($GLOBALS['DSN']);
		
		$a = intval($_GET['approve']);	
		
		$sql = "UPDATE game_quiz_question SET approved = $a WHERE question_id = ".$db->Quote($_GET['question_id']);
		$db->Execute($sql);
	}
}


if (isset($_GET['others'])) {
	$USER->mustHavePerm("moderator");
	
	$where = "1";
} else {
	$where = "user_id = {$USER->user_id} ";
}

$questions=$db->GetAll(
	"select q.*,category_name,count(distinct answer_id) as answers
	from game_quiz_question q
	left join game_quiz_answer a on (q.question_id = a.question_id)
	left join game_quiz_cat c on (q.question_cat_id = c.question_cat_id)
	where $where and approved>=0
	group by q.question_id
	order by created");
$smarty->assign_by_ref('questions', $questions);

$smarty->display($template);

	
?>
