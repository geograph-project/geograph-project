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

$db = GeographDatabaseConnection(false);

if (!empty($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if (!($row = $db->getRow("SELECT a.*,q.question,q.anon AS q_anon,realname 
            FROM answer_question q INNER JOIN answer_answer a USING (question_id) 
            INNER JOIN user s ON (q.user_id = s.user_id) 
            WHERE q.status=1 AND a.status=1 AND answer_id = $id AND (wiki=1 OR q.user_id = {$USER->user_id})"))) {
        die("invalid question");
    }
} else {
    die("no question");
}

if (!empty($_POST) && !empty($_POST['content'])) {
    $updates = array();

    $updates['title'] = trim($_POST['title']);
    $updates['content'] = trim($_POST['content']);
    $updates['link'] = trim($_POST['link']);
    $updates['tags'] = trim($_POST['tags']);
    $updates['target'] = trim($_POST['target']);
    $updates['level'] = intval(trim($_POST['level']));

    if ($USER->user_id == $row['user_id']) {
        $updates['anon'] = empty($_POST['anon'])?0:1;

        $updates['wiki'] = empty($_POST['wiki'])?0:1;
    }

    if ($updates['title'] == $row['question']) {
        unset($updates['title']);
    }
    
    $db->Execute("UPDATE answer_answer SET `".implode('` = ?,`',array_keys($updates)).'` = ? WHERE answer_id = '.$id,array_values($updates));

                foreach ($updates as $key => $value) {
                        if (!is_null($value) && $value != $row[$key]) {
                                $u = array();
                                $u['table'] = 'answer_answer';
                                $u['table_id'] = $id;
                                $u['name'] = $key;
                                $u['value'] = $value;
                                $u['user_id'] = $USER->user_id;
                                $db->Execute('INSERT INTO answer_log SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
                        }
                }


    
    $message = "Thank you! Edit(s) saved. ";
    
    print "$message<br><br><a href=\"faq3.php#{$row['answer_id']}\">Continue</a>";
    print "<meta http-equiv=\"refresh\" content=\"2;url=faq3.php#{$row['answer_id']}\">";
    exit;
}


$smarty->display('_std_begin.tpl',$mkey);


?>
<h2>Geograph Knowledgebase / FAQ </h2>


<p><a href="faq3.php">View Answers</a> | <a href="faq-ask.php">Ask a question</a> | <a href="faq-unanswered.php">Answer a question</a></p>

<h3>Reply to a question...</h3>

<?

if (!empty($message)) {
    print "<p>$message</p>";
}
?>

<div style="background-color:yellow;padding:10px">
<b>Question</b>:<br/>
 &nbsp; &nbsp; &nbsp; <big><? print htmlentities($row['question']); ?></big>
 <? if (empty($row['q_anon'])) { print "<br/><i>by ".htmlentities($row['realname'])."</i>"; } ?>
</div> <br/><br/>
 
 
<h2>Edit Reply</h2>
<form action="edit.php?id=<? echo $id; ?>" method="post" style="background-color:lightgrey;padding:10px;border:1px solid gray">

<b>Question Title</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="title" value="<? print htmlentities($row['title']?$row['title']:$row['question']); ?>" maxlength="128" size="80"/> (128 charactors max)<br/>
 (optional - rewrite the question as a 'FAQ' style question, ie a simple consise question)<br/><br/>

<b>Answer</b>:<br/>

<? if ($row['level'] == 0) { ?>
    <div style="background-color:pink; padding:30px;border:3px solid orange">
    <big>NOTE</big>: 
    This is copy of a question from the original FAQ. While you can edit the answer below, <b>please only use it to make small changes</b> (typos, or clarification of the wording).<br/><br/>

    If you have substational information to add, or a total rewrite, please use the <a href="answer.php?id=<? echo $row['question_id']; ?>">Provide an alternative answer</a>. THANK YOU!<br/><br/>

    <b>Although please do add tags!</b></div>
<? } ?>

 &nbsp; &nbsp; &nbsp; <textarea name="content" rows="12" cols="100"><? print htmlentities($row['content']); ?></textarea><br/>
(your actual answer to the question, ideally aim for a few paragraphs at most)<br/><br/>

<b>More information Link</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="link" value="<? print htmlentities($row['link']); ?>" size="80"/><br/>
 (optional - link to page to read more information)<br/><br/>

<b>Tags</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="tags" value="<? print htmlentities($row['tags']); ?>" size="80"/><br/>
 (optional - seperate tags by commas)<br/><br/>

<? if ($row['level'] > 0 || $row['user_id'] != 2) { ?>
<b>Level</b>:<br/>
 &nbsp; &nbsp; &nbsp; <select name="level">
<option></option>
<option value="1"<? if ($row['level'] == '1') { echo " selected"; }?>>1 - Beginners</option>
<option value="2"<? if ($row['level'] == '2') { echo " selected"; }?>>2</option>
<option value="3"<? if ($row['level'] == '3') { echo " selected"; }?>>3 - Intermediate</option>
<option value="4"<? if ($row['level'] == '4') { echo " selected"; }?>>4</option>
<option value="5"<? if ($row['level'] == '5') { echo " selected"; }?>>5 - Advanced</option>
</select><br/>
 (marks the approximate target audience for this answer)<br/><br/>
<? } ?>

<b>Subject/Target</b>:<br/>
 &nbsp; &nbsp; &nbsp; <select name="target">
<option></option>
<option<? if ($row['target'] == 'General') { echo " selected"; }?>>General</option>
<option<? if ($row['target'] == 'Viewing Images') { echo " selected"; }?>>Viewing Images</option>
<option<? if ($row['target'] == 'Reusing Geograph Content') { echo " selected"; }?>>Reusing Geograph Content</option>
<option<? if ($row['target'] == 'Points and Moderation') { echo " selected"; }?>>Points and Moderation</option>
<option<? if ($row['target'] == 'Photo Contributors') { echo " selected"; }?>>Photo Contributors</option>
<option<? if ($row['target'] == 'Photo Contributors :: Contributing') { echo " selected"; }?>>Photo Contributors :: Contributing</option>
<option<? if ($row['target'] == 'Other Contributors') { echo " selected"; }?>>Other Contributors</option>
<option<? if ($row['target'] == 'Finding way in the forum') { echo " selected"; }?>>Finding way in the forum</option>
<option<? if ($row['target'] == 'Moderators') { echo " selected"; }?>>Moderators</option>
<option<? if ($row['target'] == 'External Developers') { echo " selected"; }?>>External Developers</option>
<option<? if ($row['target'] == 'Other') { echo " selected"; }?>>Other</option>
</select><br/><br/>


<? if ($USER->user_id == $row['user_id']) { ?>
<br/> 
<b>Wiki Style Answer</b>? <input type="checkbox" name="wiki" <? if (!empty($row['wiki'])) { print "checked"; } ?>/><br/>
 &nbsp; &nbsp; &nbsp;  (optional - tick to allow others to refine your answer)<br/>

<br/> 
<b>Anonymous</b>? <input type="checkbox" name="anon" <? if (!empty($row['anon'])) { print "checked"; } ?>/><br/>
 &nbsp; &nbsp; &nbsp;  (optional - tick to submit this reply anonymously? - not recommended)<br/><br/>
<? } ?>

<hr/>
By clicking the button below, you agree to release your contribution under the <b class="nowrap">Creative Commons Attribution-Share Alike 3.0</b> Licence.<br/> <a title="View licence" href="http://creativecommons.org/licenses/by-sa/3.0/" target="_blank" class="nowrap">Here is the Commons Deed outlining the licence terms</a>
<br/> <br/>
 <input type="submit" value="Submit Reply"/>
 
</form>



<?

$smarty->display('_std_end.tpl',$mkey);
