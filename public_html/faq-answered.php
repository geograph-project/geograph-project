<?

/**
 * $Project: GeoGraph $
 * $Id: glossary.php 2960 2007-01-15 14:33:27Z barry $
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

init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");
$isadmin=$USER->hasPerm('moderator')?1:0;


$db = GeographDatabaseConnection(false);


$smarty->display('_std_begin.tpl',$mkey);

if (!empty($_GET['delete']) && $isadmin) {
	$idx = intval($_GET['delete']);
	if (!empty($_POST['confirm'])) {
		if (!empty($_GET['a'])) {
			$sql = "UPDATE answer_answer SET status = 0 WHERE answer_id = $idx";
		} else {
			$sql = "UPDATE answer_question SET status = 0 WHERE question_id = $idx";
		}
		$db->Execute($sql);

		if ($db->Affected_Rows())
			print "<p>Deleted</p>";

		print " <a href=?>Back</a>";

	} else {
		print "<h2>Confirm Deletion</h2>";

		if (!empty($_GET['a'])) {
			$rows = $db->getAll("SELECT * FROM answer_answer a WHERE answer_id = $idx");
			$button = "Delete Answer";
			$a=1;
		} else {
			$row = $db->getRow("SELECT * FROM answer_question q WHERE question_id = $idx");
			print "<h4>Question</h4>";
	        	print "<ul><li><a href=\"faq-answer.php?id={$idx}\">".htmlentities($row['question'])."</a></li></ul>";

			$rows = $db->getAll("SELECT * FROM answer_answer a WHERE question_id = $idx");
			$button = "Confirm Delete Question and ALL answers";
			$a=0;
		}

		print "<h4>Answer(s)</h4><ul>";
		foreach ($rows as $row) {
		        print "<li><a href=\"faq-edit.php?id={$row['answer_id']}\">".htmlentities($row['title']?$row['title']:'untitled')."</a></li>";
		}

		print "</ul><form method=post action=?delete=$idx&a=$a>";
		print "<input type=submit name=confirm value=\"$button\">";

		print " <a href=?>Back</a>";
		print "</form>";
	}
	$smarty->display('_std_end.tpl',$mkey);

	exit;
}



?>
<h2>Geograph Knowledgebase / FAQ </h2>


<p><a href="faq3.php">View Answers</a> | <a href="faq-ask.php">Ask a question</a> | <a href="faq-unanswered.php">Answer a question</a></p>

<?

$data = $db->getAll("SELECT q.question,a.*,u.realname FROM answer_question q INNER JOIN answer_answer a USING (question_id) 
	INNER JOIN user u ON (u.user_id = a.user_id)
  WHERE q.status = 1 AND a.status = 1 ORDER BY level,q.question_id DESC");


if ($data) {
    print "<h3>All Answers to all Questions</h3>";

	if ($isadmin)
		print "<p>Questions that are irrelivent, or already answered can be deleted.</p>";

	$last = 0;
    foreach ($data as $idx => $row) {
	if ($last != $row['question_id']) {
		if ($last)
			 print "</ul>";
		print "<h3>".htmlentities2($row['question'])." - <a href=\"/faq-answer.php?id={$row['question_id']}\">another answer</a>";
	        if ($isadmin) {
	                print " <a href=\"?delete={$row['question_id']}\" style=color:red>delete</a>";
        	}
		print "</h3>";

		print "<ul>";
		$last = $row['question_id'];
	}

        print "<li>";
	if (!empty($row['title']) && $row['title'] != $row['question']) {
		print "<b>".htmlentities2($row['title'])."</b><br>";
	}
	print "<small>".nl2br(htmlentities2($row['content']))."</small><br>";
	if (empty($row['anon']))
		print "&middot; by ".htmlentities2($row['realname']);
	if (!empty($row['wiki']) || $USER->user_id == $row['user_id'] || $isadmin) {
		print " &middot; <a href=\"faq-edit.php?id={$row['answer_id']}\">Edit</a>";
	}

	if ($isadmin) {
		print " &middot; <a href=\"?delete={$row['answer_id']}&a=1\" style=color:red>delete</a>";
	}

	print "</li>";
    }
    print "</ul>";
} else {
    print "There are no answered questions right now.";
}

?>

<br/><br/>

<?


$smarty->display('_std_end.tpl',$mkey);
