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
    
    if (!($row = $db->getRow("SELECT q.*,realname FROM answer_question q INNER JOIN user USING (user_id) WHERE q.status=1 AND question_id = $id"))) {
        die("invalid question");
    }
} else {
    die("no question");
}

if (!empty($_POST) && !empty($_POST['content'])) {
    
    
    $updates = $_POST;
        
    $updates['user_id'] = $USER->user_id;
    
    $updates['question_id'] = $id;
    
    if (!empty($_POST['anon'])) {
        $updates['anon'] = 1;
    }
    if (!empty($_POST['wiki'])) {
        $updates['wiki'] = 1;
    }
    $updates['status'] = 1;

    if ($updates['title'] == $row['question']) {
        unset($updates['title']);
    }    

    $db->Execute('INSERT INTO answer_answer SET created = NOW(),`'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));
    $id = mysql_insert_id();

                foreach ($updates as $key => $value) {
                        if (!is_null($value)) {
                                $u = array();
                                $u['table'] = 'answer_answer';
                                $u['table_id'] = $id;
                                $u['name'] = $key;
                                $u['value'] = $value;
                                $u['user_id'] = $USER->user_id;
                                $db->Execute('INSERT INTO answer_log SET `'.implode('` = ?,`',array_keys($updates)).'` = ?',array_values($updates));

                        }
                }

    
    $message = "Thank you! Reply saved. ";
    
    print "$message<br><br><a href='./faq3.php'>Continue</a>";
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
 <? if (empty($row['anon'])) { print "<br/><i>by ".htmlentities($row['realname'])."</i>"; } ?>
</div> <br/><br/>
 
 
<h2>Your reply</h2>
<form action="answer.php?id=<? echo $id; ?>" method="post" style="background-color:lightgrey;padding:10px;border:1px solid gray" name="theForm">

<b>Question Title</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="title" value="<? print htmlentities($row['question']); ?>" maxlength="128" size="80"/> (128 charactors max)<br/>
 (optional - rewrite the question as a 'FAQ' style question, ie a simple consise question)<br/><br/>

<b>Reply</b>:<br/>
 &nbsp; &nbsp; &nbsp; <textarea name="content" rows="12" cols="100"></textarea><br/>
(your actual answer to the questionn, ideally aim for a few paragraphs at most)<br/><br/>

<b>More information Link</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="link" value="" size="80"/><br/>
 (optional - link to page to read more information)<br/><br/>

<b>Level</b>:<br/>
 &nbsp; &nbsp; &nbsp; <select name="level">
<option></option>
<option value="1"<? if ($row['level'] == '1') { echo " selected"; }?>>1 - Beginners</option>
<option value="2"<? if ($row['level'] == '2') { echo " selected"; }?>>2</option>
<option value="3"<? if ($row['level'] == '3') { echo " selected"; }?>>3 - Intermediate</option>
<option value="4"<? if ($row['level'] == '4') { echo " selected"; }?>>4</option>
<option value="5"<? if ($row['level'] == '5') { echo " selected"; }?>>5 - Advanced</option>
</select><br/>
 (marks the approximate target audience for this answer - based on their experience level on the website)<br/><br/>


<b>Subject/Target</b>:<br/>
 &nbsp; &nbsp; &nbsp; <select name="target">
<option></option>
<option>General</option>
<option>Viewing Images</option>
<option>Reusing Geograph Content</option>
<option>Points and Moderation</option>
<option>Photo Contributors</option>
<option>Photo Contributors :: Contributing</option>
<option>Other Contributors</option>
<option>Finding way in the forum</option>
<option>Moderators</option>
<option>External Developers</option>
<option>Other</option>
</select><br/><br/>


<b>Site Section</b>:<br/>
 &nbsp; &nbsp; &nbsp; <select name="section">
<option></option>
<option>Home</option>
<option>Image Search</option>
<option>.. Advanced</option>
<option>.. By Square</option>
<option>.. Place</option>
<option>.. Multi</option>
<option>Map</option>
<option>.. Depth</option>
<option>.. Recent</option>
<option>.. Draggable</option>
<option>.. .. Centisquare</option>
<option>.. Clusters</option>
<option>.. Hectad Coverage</option>
<option>Browse</option>
<option>Explore</option>
<option>.. Featured Stuff</option>
<option>.. Mosaics</option>
<option>.. Popular Images</option>
<option>.. Routes</option>
<option>.. Places</option>
<option>.. Calendar</option>
<option>.. Featured Searches</option>
<option>Collections</option>
<option>.. Articles</option>
<option>.. Galleries</option>
<option>.. Themed Topics</option>
<option>.. Shared Descriptions</option>
<option>.. User Profiles</option>
<option>.. Categories</option>
<option>Contribute</option>
<option>.. Submit Photos</option>
<option>.. .. Submit v2</option>
<option>.. .. Others</option>
<option>.. Collections</option>
<option>My Photos</option>
<option>.. My Profile</option>
<option>.. My Submissions</option>
<option>.. My Thumbed Images</option>
<option>.. Personal Map</option>
<option>.. Check Submissions</option>
<option>.. CSV Export</option>
<option>Activities</option>
<option>.. Games</option>
<option>.. Imagine</option>
<option>Interact</option>
<option>.. Discussions</option>
<option>.. .. Search</option>
<option>.. Blog</option>
<option>.. Chat</option>
<option>.. Events</option>
<option>Statistics</option>
<option>.. More Stats</option>
<option>.. Contributors</option>
<option>.. Current Stats</option>
<option>.. Leaderboard</option>
<option>Export</option>
<option>.. Google Earth/Maps</option>
<option>.. Memory Map</option>
<option>.. GPX</option>
<option>.. API</option>
<option>Further Info</option>
<option>.. FAQ</option>
<option>.. Information</option>
<option>.. More Pages</option>
<option>.. Sitemap</option>
<option>.. Experimental Features</option>
<option>.. Contact Us</option>
<option>.. The Team</option>
<option>.. Credits</option>
<option value="OTHER">OTHER <- Select this if not covered above</option>
</select><br/><br/>

<b>Tags</b>:<br/>
 &nbsp; &nbsp; &nbsp; <input type="text" name="tags" value="" size="80"/><br/>
 (optional - seperate tags by commas - helps makes answers categorizable and keyword searchable)<br/>

<?


$data = $db->getAll("SELECT tags
FROM answer_answer a INNER JOIN answer_question q USING (question_id)
WHERE a.status = 1 AND q.status = 1");


if ($data) {
        $tags = array();
        foreach ($data as $row) {
                if (!empty($row['tags'])) {
                        $bits = preg_split('/\s*,\s*/',strtolower(trim($row['tags'])));
                        foreach ($bits as $bit) {
                                $tags[$bit]++;
                        }
                }
        }
        if (!empty($tags)) {
                ksort($tags);
                print "<div style=\"\"><b>Current</b>:";
                foreach ($tags as $tag => $count) {
                        $h=htmlentities($tag);
                        print "<a href=\"#$h\" onclick=\"return useTag('$h')\" style=\"white-space:nowrap\">$h</a> ";
                }
                print "</div>";
        }
}


?><br/>
<script>
function useTag(tag) {
    var ele = document.forms['theForm'].elements['tags'];
    if (ele.value.length == 0) {
        ele.value = tag;
    } else {
        ele.value = ele.value + ', '+tag;
    }
    return false;
}
</script>


<br/> 
<b>Wiki Style Answer</b>? <input type="checkbox" name="wiki" checked/><br/>
 &nbsp; &nbsp; &nbsp;  (optional - tick to allow others to refine your answer)<br/>

<br/> 
<b>Anonymous</b>? <input type="checkbox" name="anon"/><br/>
 &nbsp; &nbsp; &nbsp;  (optional - tick to submit this reply anonymously? - not recommended)<br/><br/>

<hr/>
By clicking the button below, you agree to release your contribution under the <b class="nowrap">Creative Commons Attribution-Share Alike 3.0</b> Licence.<br/> <a title="View licence" href="http://creativecommons.org/licenses/by-sa/3.0/" target="_blank" class="nowrap">Here is the Commons Deed outlining the licence terms</a>
<br/> <br/>
 <input type="submit" value="Submit Reply"/>
 
</form>
    


<? 

$smarty->display('_std_end.tpl',$mkey);
