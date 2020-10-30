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
		$sql = "UPDATE answer_question SET status = 0 WHERE question_id = $idx";
		$db->Execute($sql);

		if (mysql_affected_rows())
			print "<p>Deleted</p>";

		print " <a href=?>Back</a>";

	} else {
		$row = $db->getRow("SELECT * FROM answer_question q WHERE question_id = $idx");

	        print "<ul><li><a href=\"faq-answer.php?id={$idx}\">".htmlentities($row['question'])."</a></li></ul>";

		print "<form method=post action=?delete=$idx>";
		print "<input type=submit name=confirm value=\"Confirm Delete Question\">";

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

$data = $db->getAssoc("SELECT * FROM answer_question q
  WHERE q.status = 1 AND question_id NOT IN (SELECT question_id FROM answer_answer WHERE status=1) ORDER BY q.question_id DESC");


if ($data) {
    print "<h3>Latest unanswered questions....</h3>";

	if ($isadmin)
		print "<p>Questions that are irrelivent, or already answered can be deleted.</p>";

    print "<ul>";

    foreach ($data as $idx => $row) {

        print "<li><a href=\"faq-answer.php?id={$idx}\">".htmlentities2($row['question'])."</a>";

	if ($isadmin) {
		print " <a href=\"?delete=$idx\" style=color:red>delete</a>";
	}

	print "</li>";
    }
    print "</ul>";
} else {
    print "There are no unanswered questions right now.";
}

?>

<br/><br/>

<?


$smarty->display('_std_end.tpl',$mkey);
