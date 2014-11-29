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

if (isset($_REQUEST['login']) && !$USER->hasPerm('basic')) {
	$USER->login(false);
}

$db = GeographDatabaseConnection(false);
$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

if (isset($_REQUEST['login']) || isset($_REQUEST['save'])) {
	$user_id = 0;
	if (!empty($_GET['save']) && $_GET['save'] == 'user') {
		//registered user saving

		$USER->mustHavePerm("basic");

		$user_id = $USER->user_id;

	} elseif (!empty($_POST['save']) && !empty($_POST['username'])) {
		//anon user saving

		if (!empty($_COOKIE['game_user'])) {
			$user_id = intval($_COOKIE['game_user']);
		}
		if (empty($user_id)) {
			die("no data to save?");
		}
	}

	if (!empty($user_id)) {
		$updates = $db->getRow("SELECT quiz_id as level, user_id, SUM(correct) AS score, COUNT(*) AS games FROM quiz_log WHERE user_id = $user_id AND quiz_id = ".intval($_REQUEST['quiz_id'])." GROUP BY quiz_id,user_id");

		if (!empty($updates)) {
			$updates['game_id'] = 3;
			if (!empty($_POST['username'])) {
				$updates['username'] = $_POST['username'];
				$updates['approved'] = 0;
			}
			$updates['ua'] = $_SERVER['HTTP_USER_AGENT'];
			$updates['session'] = session_id();

			$db->Execute('INSERT INTO game_score SET created=NOW(),`ipaddr` = INET_ATON(\''.getRemoteIP().'\'),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates)) or die(mysql_error());
		}

		header("Location: ?");//todo, if public results, redirect there!
		exit;
	}

	$template = 'games_score.tpl';
	$smarty->assign("quiz_id",intval($_REQUEST['quiz_id']));

} elseif (!empty($_POST['create']) && !empty($_POST['title'])) {
	$USER->mustHavePerm("basic");

	//create quiz OR a tag!

	$updates = array();
	foreach (array('title','owner','tag_id','public','results') as $key) {
		if (!empty($_POST[$key]))
			$updates[$key] = $_POST[$key];
	}

	$updates['user_id'] = $USER->user_id;

	$table = empty($_POST['tag_id'])?'quiz_tag':'quiz';

	$db->Execute('INSERT INTO '.$table.' SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates)) or die(mysql_error());

	if (empty($_POST['tag_id'])) {
		//creating a tag
		$tag_id = $db->Insert_ID();
		header("Location: ?questions=".intval($tag_id));
	} else {
		//creating a quiz
		$quiz_id = $db->Insert_ID();
		header("Location: ?go=".intval($quiz_id));
	}
	exit;

} elseif (!empty($_GET['create'])) {
	$USER->mustHavePerm("basic");

	//create a question form
	$template = 'games_quiz_edit.tpl';

	$tag = $db->getRow("SELECT qt.* FROM quiz_tag qt WHERE qt.approved = 1 AND tag_id = ".intval($_GET['create']));
	if (empty($tag)) {
		die('huh');
	}

	$question = array();

	$question['options'] = $db->getOne("SELECT options FROM quiz_question WHERE tag_id = {$tag['tag_id']} ORDER BY user_id = {$USER->user_id} DESC, created DESC");

	$smarty->assign_by_ref('tag',$tag);
	$smarty->assign_by_ref('question',$question);
	if (!empty($question['options'])) {
		$options = array();
		foreach(explode(',',$question['options']) as $option)
			$options[$option] = $option;
		$smarty->assign_by_ref('options',$options);
	}

	if (!empty($_GET['done']))
		$smarty->assign('done',1);

} elseif (!empty($_GET['edit'])) {
	$USER->mustHavePerm("basic");

	//edit a question form
	$template = 'games_quiz_edit.tpl';

	$question = $db->getRow("SELECT * FROM quiz_question qq WHERE qq.approved = 1 AND user_id = {$USER->user_id} AND question_id = ".intval($_GET['edit']));

	if (empty($question)) {
		die('huh');
	}

	$smarty->assign('used',$db->getOne("SELECT COUNT(*) FROM quiz_log WHERE question_id = ".intval($_GET['edit'])));

	$tag = $db->getRow("SELECT qt.* FROM quiz_tag qt WHERE tag_id = {$question['tag_id']}");

	$smarty->assign_by_ref('tag',$tag);
	$smarty->assign_by_ref('question',$question);
	if (!empty($question['options'])) {
		$options = array();
		foreach(explode(',',$question['options']) as $option)
			$options[$option] = $option;
		$smarty->assign_by_ref('options',$options);
	}

} elseif (!empty($_POST['tag_id']) && empty($_POST['question_id'])) {
	$USER->mustHavePerm("basic");

	//create a question submit

	$updates = array();
	foreach (array('question','answer1','answer2','answer3','answer4','answer5','correct','tag_id') as $key) {
		if (!empty($_POST[$key]))
			$updates[$key] = $_POST[$key];
	}
	if (!empty($_POST['options']))
		$updates['options'] = implode(',',array_keys($_POST['options']));
	$updates['user_id'] = $USER->user_id;

	$db->Execute($sql = 'INSERT INTO quiz_question SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates)) or die(mysql_error());

	header("Location: ?create=".intval($_POST['tag_id'])."&done=1");
	exit;

} elseif (!empty($_POST['tag_id']) && !empty($_POST['question_id'])) {
	$USER->mustHavePerm("basic");

	//edit a question submit

	$updates = array();
	foreach (array('question','answer1','answer2','answer3','answer4','answer5','correct') as $key) {
		//if (!empty($_POST[$key])) - cant check if empty, because might want to be emptying a box!
			$updates[$key] = $_POST[$key];
	}
	if (!empty($_POST['options']))
		$updates['options'] = implode(',',array_keys($_POST['options']));
	else
		$updates['options'] = '';

	$db->Execute('UPDATE quiz_question SET `'.implode('` = ?,`',array_keys($updates)).'` = ? WHERE user_id = '.$USER->user_id.' AND question_id = '.intval($_POST['question_id']), array_values($updates)) or die(mysql_error());

	header("Location: ?questions=".intval($_POST['tag_id'])."&done=1");
	exit;

} elseif (!empty($_GET['delete'])) {

	$USER->mustHavePerm("basic");

	$db->Execute('UPDATE quiz_question SET approved = 0 WHERE user_id = '.$USER->user_id.' AND question_id = '.intval($_GET['delete'])) or die(mysql_error());

	header("Location: ?questions=".intval($_GET['tag_id'])."&done=1");
	exit;

} elseif (!empty($_GET['close'])) {

	$USER->mustHavePerm("basic");

	$db->Execute('UPDATE quiz SET approved = 0 WHERE user_id = '.$USER->user_id.' AND quiz_id = '.intval($_GET['close'])) or die(mysql_error());

	header("Location: ?done=1");
	exit;

} elseif (!empty($_GET['questions'])) {

	//view questions in a series
	$template = 'games_quiz_questions.tpl';

	$tag = $db->getRow("SELECT qt.* FROM quiz_tag qt WHERE qt.approved = 1 AND tag_id = ".intval($_GET['questions']));
	if (empty($tag)) {
		die('huh');
	}
	$smarty->assign_by_ref('tag',$tag);

	$where = "tag_id = ".intval($_GET['questions']);
	if (!empty($_GET['user_id'])) {
		$smarty->assign('user_id',intval($_GET['user_id']));
		$where .= " AND qq.user_id = ".intval($_GET['user_id']);
	}
	$questions = $db->getAll($sql = "SELECT qq.*, qt.title as tag
		FROM quiz_question qq
		INNER JOIN quiz_tag qt USING (tag_id)
		WHERE qq.approved = 1 AND $where
		ORDER BY question_id");

	foreach ($questions as $idx => $question) {
		$questions[$idx]['question'] = preg_replace('/\[\[\[\d+\]\]\]/','[thumb]',$question['question']);
		foreach (range(1,5) as $c)
			if (!empty($question['answer'.$c]))
				@$questions[$idx]['count']++;
	}

	$smarty->assign_by_ref('questions',$questions);

} elseif (!empty($_GET['go'])) {
	//play the quiz!
	$template = 'games_quiz_go.tpl';

	$quiz = $db->getRow("SELECT q.* FROM quiz q WHERE q.approved = 1 AND quiz_id = ".intval($_GET['go']));
	if (empty($quiz)) {
		die("unable to load quiz. It may have been closed, or never existed. ");
	}
	if ($quiz['public'] ==0) {

		$quiz['auth'] = substr(hash_hmac('md5', $quiz['quiz_id'], $CONF['register_confirmation_secret']),0,8);

		if ($quiz['user_id'] != $USER->user_id && $_GET['auth'] != $quiz['auth']) {
			die("Unable to load quiz. It may have been closed, or never existed. ");
		}
	}
	$smarty->assign_by_ref('quiz',$quiz);
	$cacheid = $quiz['quiz_id'];

	$where = "tag_id = {$quiz['tag_id']}";
	if ($quiz['owner'])
		$where .= " AND qq.user_id = {$quiz['user_id']}";

	$user_id = $USER->user_id;
	if (empty($user_id)) {
		if (!empty($_COOKIE['game_user'])) {
			$user_id = intval($_COOKIE['game_user']);
		} else {
			$db->Execute("INSERT game_user SET created=NOW()");
			$user_id = -1*$db->Insert_ID();
			setcookie('game_user', $user_id, time()+3600*24*365,'/games/');
		}
	}

	if (!empty($_POST['question_id']) && !empty($_POST['answer'])) {
		$question = $db->getRow("SELECT question_id,correct FROM quiz_question qq WHERE question_id = ".intval($_POST['question_id']));

		$updates = array();
		$updates['user_id'] = $user_id;
		$updates['quiz_id'] = $quiz['quiz_id'];
		$updates['question_id'] = $question['question_id'];
		$updates['answer'] = $_POST['answer'];
		$updates['correct'] = ($_POST['answer']==$question['correct'])?1:0;

		$db->Execute($sql = 'INSERT INTO quiz_log SET created=NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates)) or die(mysql_error());
	}

	$question = $db->getRow("SELECT qq.*
		FROM quiz_question qq
		LEFT JOIN quiz_log ql ON (qq.question_id = ql.question_id AND ql.user_id = $user_id)
		WHERE qq.approved = 1 AND $where
		AND log_id IS NULL
		ORDER BY qq.created
		LIMIT 1");

	if (!empty($question)) {

		$enable = strpos($question['options'],'obscure') !== FALSE;
		$keys = array();
		$question['question'] = obscure($question['question'], $enable);
		foreach(range(1,5) as $idx)
			if (!empty($question['answer'.$idx])) {
				$question['answer'.$idx] = obscure($question['answer'.$idx], $enable);
				$keys[] = 'answer'.$idx;
			}

		if (strpos($question['options'],'shuffle') !== FALSE) {
			shuffle($keys);
		}

		$smarty->assign_by_ref('question',$question);
		$smarty->assign_by_ref('keys',$keys);
	}
	$smarty->assign('user_id',$user_id);

	$quiz['count'] = $db->getOne("SELECT COUNT(*) FROM quiz_question qq WHERE qq.approved = 1 AND $where");

	$stat = $db->getRow("SELECT COUNT(*) AS answers,SUM(correct) AS correct FROM quiz_log ql WHERE quiz_id = {$quiz['quiz_id']} AND question_id > 0 AND user_id = $user_id");

	$quiz['done'] = intval($stat['answers']/$quiz['count']*100)."%";
	if ($stat['answers'])
		$quiz['correct'] = intval($stat['correct']/$stat['answers']*100)."%";


} else {
	//homepage
	$template = 'games_quiz.tpl';

	$quizs = $db->getAll("SELECT q.*,qt.title as tag, COUNT(DISTINCT qq.question_id) AS count, COUNT(DISTINCT ql.user_id) AS players, u.realname
			FROM quiz q
			INNER JOIN user u ON (u.user_id = q.user_id)
			INNER JOIN quiz_tag qt USING (tag_id)
			INNER JOIN quiz_question qq USING (tag_id)
			LEFT JOIN quiz_log ql USING (question_id,quiz_id)
			WHERE q.approved = 1 AND qt.approved = 1 AND qq.approved = 1
			AND (q.user_id = {$USER->user_id} OR `public` = 1)
			AND IF(q.owner=1,(q.user_id = qq.user_id),1)
			GROUP BY q.quiz_id");
	$smarty->assign_by_ref('quizs',$quizs);

	if ($USER->registered) {

		$tags = $db->getAll("SELECT qt.*,COUNT(DISTINCT qq.question_id) AS count,COUNT(DISTINCT qqq.question_id) AS count_user, u.realname
			FROM quiz_tag qt
			INNER JOIN user u ON (u.user_id = qt.user_id)
			LEFT JOIN quiz_question qq ON (qt.tag_id = qq.tag_id AND qq.approved = 1)
			LEFT JOIN quiz_question qqq ON (qt.tag_id = qqq.tag_id AND qqq.approved = 1 AND qqq.user_id = {$USER->user_id})
			WHERE qt.approved = 1
			GROUP BY qt.tag_id");
		$smarty->assign_by_ref('tags',$tags);

		$cacheid = $USER->user_id;
		$smarty->assign('user_id',$USER->user_id);
	}
}


$smarty->display($template,$cacheid);



###########

		function obscure($input,$enable = true) {
			$input = nl2br(htmlentities2($input));
			if ($enable && preg_match_all('/\[\[\[(\d+)\]\]\]/',$input,$g_matches)) {
				foreach ($g_matches[1] as $g_i => $g_id) {
					$token=new Token;
					$token->setValue("id", intval($g_id));
					$token->setValue("large",1);
					$full = "http://t0.geograph.org.uk/stuff/captcha.php?token=".$token->getToken()."&amp;html=1";

					$token=new Token;
					$token->setValue("id", intval($g_id));
					$token->setValue("small",1);
					$small = "http://t0.geograph.org.uk/stuff/captcha.php?token=".$token->getToken()."&amp;/small.jpg";

					$input = str_replace("[[[$g_id]]]","<a href=\"$full\"><img src=\"$small\"/></a>",$input);
				}
			}
			$input = GeographLinks($input,true);
			if (preg_match_all('/\[(small|)map *([STNH]?[A-Z]{1}[ \.]*\d{2,5}[ \.]*\d{2,5})( \w+|)\]/',$input,$m)) {
				$pattern = $replacement = array();

					foreach ($m[0] as $i => $full) {
						//lets add an rastermap too
						$square = new Gridsquare;
						$square->setByFullGridRef($m[2][$i],true);
						$square->grid_reference_full = 	$m[2][$i];
						if (!empty($_GET['epoch'])) {
							$rastermap = new RasterMap($square,false,true,false,$_GET['epoch']);
						} elseif (!empty($m[3][$i])) {
							$rastermap = new RasterMap($square,false,true,false,trim($m[3][$i]));
						} else {
							$rastermap = new RasterMap($square,false);
						}
						if ($rastermap->service == 'OS50k') {
							if ($m[1][$i]) {
								$rastermap->service = 'OS50k-small';
								$rastermap->width = 125;
							}

							$pattern[] = "/".preg_quote($full, '/')."/";
							$replacement[] = $rastermap->getImageTag();

						}
					}

				if ($enable) { //we need re remove the gridsquare link...
					$pattern[] = "/<a href=\"\/gridref\/([\w\+ ]+)\"/"; #"
					$replacement[] = "<a";
				}
				$input=preg_replace($pattern, $replacement, $input);
			}
			return $input;
		}
