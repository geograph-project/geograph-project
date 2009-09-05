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

$USER->mustHavePerm('basic');
$isadmin=$USER->hasPerm('moderator')?1:0;

if (empty($_REQUEST['question_id'])) {
	$smarty->display('static_404.tpl');
	exit;
}


$template = 'games_quiz_question_edit.tpl';
$cacheid = '';



	$db=NewADOConnection($GLOBALS['DSN']);
	if ($_REQUEST['question_id'] == 'new') {
		$smarty->assign('question_id', "new");
		$smarty->assign('title', "New Question");
		$smarty->assign('realname', $USER->realname);
		$smarty->assign('user_id', $USER->user_id);
		$page = array();
		
		$answers = array(1=>array('text'=>''));
		$smarty->assign_by_ref('answers', $answers);
	} else {
		$sql_where = " question_id = ".$db->Quote($_REQUEST['question_id']);
		
		$prev_fetch_mode = $ADODB_FETCH_MODE;
		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	
		$page = $db->getRow("
		select *
		from game_quiz_question 
		where $sql_where
		limit 1");
		
		if (count($page) && ($page['user_id'] == $USER->user_id || $USER->hasPerm('moderator'))) {
			

			$smarty->assign($page);
			
			$answers = $db->getAll("
			select *
			from game_quiz_answer 
			where $sql_where
			order by answer_id");
			if (count($answers) == 0) {
				$answers = array(1=>array('content'=>''));
			}
			$smarty->assign_by_ref('answers', $answers);
		} else {
			$template = 'static_404.tpl';
		}
		$ADODB_FETCH_MODE = $prev_fetch_mode;
		
	}

if ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['another'])) {
	foreach (array('type','content','question_cat_id','gridimage_id','hide_option') as $key) {
		$smarty->assign($key, $_POST[$key]);
	}
	$answers = array();
	foreach ($_POST['answer'] as $key => $content) {
		if (!empty($content)) {
			$answers[$key] = array('content'=>$content,'correct'=>$_POST['answer_correct'][$key]);
		}
	}
	
	$answers[] = array('content'=>'');
	
	
} elseif ($template != 'static_404.tpl' && isset($_POST) && isset($_POST['submit'])) {
	$errors = array();
	
;
	//the most basic protection
	$_POST['content'] = strip_tags($_POST['content']);
	$_POST['content'] = preg_replace('/[“”]/','',$_POST['content']);

	
	$updates = array();
	foreach (array('type','content','question_cat_id','gridimage_id','hide_option') as $key) {
		if ($page[$key] != $_POST[$key]) {
			$updates[] = "`$key` = ".$db->Quote($_POST[$key]); 
			$smarty->assign($key, $_POST[$key]);
		} elseif (empty($_POST[$key]) && $key != 'gridimage_id' && $key != 'hide_option') 
			$errors[$key] = "missing required info";		
	}
	
	$answers = array();
	foreach ($_POST['answer'] as $key => $content) {
		if (!empty($content)) {
			$answers[$key] = array('content'=>$content,'correct'=>$_POST['answer_correct'][$key]);
		}
	}
	
	if (!count($updates) && !count($answers)) {
		$smarty->assign('error', "No Changes to Save");
		$errors[1] =1;
	} elseif (!count($updates)) {
		$updates[] = "`updated` = NOW()";//we updated the answers
	}
	if ($_REQUEST['question_id'] == 'new') {
	
		$updates[] = "`user_id` = {$USER->user_id}";
		$updates[] = "`created` = NOW()";
		$sql = "INSERT INTO game_quiz_question SET ".implode(',',$updates);
	} else {
		
		$sql = "UPDATE game_quiz_question SET ".implode(',',$updates)." WHERE question_id = ".$db->Quote($_REQUEST['question_id']);
	}
	if (!count($errors) && count($updates)) {
		
		$db->Execute($sql);
		if ($_REQUEST['question_id'] == 'new') {
			$_REQUEST['question_id'] = $db->Insert_ID();
		}
		
		$sql = "DELETE FROM game_quiz_answer WHERE question_id = ".$db->Quote($_REQUEST['question_id']);
		$db->Execute($sql);
		
		foreach ($answers as $key => $answer) {
			$updates = array();
			$updates[] = "`question_id` = ".$db->Quote($_REQUEST['question_id']);
			$updates[] = "`content` = ".$db->Quote($answer['content']);
			$updates[] = "`correct` = ".$db->Quote($answer['correct']);
			$updates[] = "`created` = NOW()";
			$sql = "INSERT INTO game_quiz_answer SET ".implode(',',$updates);
			$db->Execute($sql);
		}
		
		
		header("Location: /games/quiz/");
		exit;
	} else {
		if ($errors[1] != 1)
			$smarty->assign('error', "Please see messages below...");
		$smarty->assign_by_ref('errors',$errors);
	}
} 

	$smarty->assign('types', array('one' => 'one answer','multi' => 'mutiple answers (must select all)','threeof' => 'must get at least three of the correct answers' ));
	$smarty->assign('question_cats', array(0=>'')+$db->getAssoc("select article_cat_id,category_name from article_cat order by sort_order"));


$smarty->display($template, $cacheid);

	
?>
