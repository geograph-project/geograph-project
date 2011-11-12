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


if (!empty($_POST) && !empty($_POST['question'])) {
    $db = GeographDatabaseConnection(false);

    $updates = array();
        
    $updates['user_id'] = $USER->user_id;
    $updates['question'] = trim($_POST['question']);
    if (!empty($_POST['anon'])) {
        $updates['anon'] = 1;
    }
    $updates['status'] = 1;

    $db->Execute('INSERT INTO answer_question SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

    $id = mysql_insert_id();

    $message = "Thank you. Question [<a href=\"faq-answer.php?id=$id\">".htmlentities($updates['question'])."</a>] saved. You may add another below";
}


$smarty->display('_std_begin.tpl',$mkey);


?>
<h2>Geograph Knowledgebase / FAQ </h2>


<p><a href="faq3.php">Home</a> | <a href="faq-ask.php">Ask a question</a> | <a href="faq-unanswered.php">Answer a question</a></p>

<h3>Ask a question...</h3>

<p><b>Confused about something on Geograph? Don't know what something means? Want to find out more about something?</b> ... seek answers here!</p>

<?

if (!empty($message)) {
    print "<p>$message</p>";
}
?>


<form action="faq-ask.php" method="post" style="background-color:lightgrey;padding:10px;border:1px solid gray">

<b>Question</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="question" value="" maxlength="128" size="80"/> (128 charactors max)<br/>
 
 Examples:
 <ul>
     <li>Ask a question like "How do I view recent photos?"</li>
     <li>Make a short statement like "I don't know how TPoints work"</li>
     <li>Prompt for more detail "I've used the search, but how do I sort by date?"</li>
     <li>Or a simple question "Where is the Depth leaderboard?"</li>
 </ul>
 (the more detailed the better, but try to keep it consise. This will make it easier to answer!)<br/>
 
<br/> 
<b>Anonymous</b>? <input type="checkbox" name="anon"/><br/>
 &nbsp; &nbsp; &nbsp;  (tick to submit this question anonymously?)<br/>
<br/> 
 <input type="submit" value="Submit Question"/>
 
</form>
(If you are just wanting to provide answers on this knowledgebase, can answer your own question in a moment)
    



<? 

$smarty->display('_std_end.tpl',$mkey);
