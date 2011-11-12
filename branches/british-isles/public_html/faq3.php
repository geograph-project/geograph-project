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

customGZipHandlerStart();
customExpiresHeader(600,false,true);


        
$db = GeographDatabaseConnection(true);


$mkey = md5($_SERVER['QUERY_STRING']);


if (!empty($_GET['q'])) {
    $q = mysql_real_escape_string(trim($_GET['q']));
    if ($q == 'os') {
        $where = " AND (tags REGEXP '[[:<:]]{$q}[[:>:]]')";
    } else {
        $where = " AND (content LIKE '%$q%' OR title LIKE '%$q%' OR tags LIKE '%$q%')";
    }
} else {
    $where = '';
}

if (isset($_GET['l'])) {
    $where .= " AND level = ".intval($_GET['l']);
}


$by = 'target+0';
if (!empty($_GET['by']) && preg_match('/^\w+$/',$_GET['by']) && preg_match('/section|user_id|level|wiki|realname/',$_GET['by'])) {
    $by = $_GET['by'];
}
if ($by == 'level') {
    $by = "level asc,answer_id asc";
}

$data = $db->getAssoc("SELECT a.*,realname,question 
FROM answer_answer a INNER JOIN user USING (user_id) INNER JOIN answer_question q USING (question_id) 
WHERE a.status = 1 AND q.status = 1 $where
GROUP BY $by,level ASC,answer_id ASC LIMIT 5000");

$by = preg_replace('/[ \+].*/','',$by);

$extra = '';



$editable = ($CONF['template'] != 'charcoal');

if (!empty($_GET['q'])) {
        $smarty->assign('page_title',htmlentities(strip_tags($_GET['q'])).' :: Geograph Knowledgebase');
} elseif (isset($_GET['l'])) {
        $smarty->assign('page_title','FAQ');
} else {
        $smarty->assign('page_title','Knowledgebase');
}

$smarty->display('_std_begin.tpl',$mkey);


?>


<style> 

h2 {
    margin-right:190px;
    font-family:georgia;
    text-size:1.3em;
}
h3 b {
    background-color:#CCFF99;
}
h4 {
    margin:0;
    font-size:0.8em;
    margin-top:6px;
    padding-top:3px;
    margin-left:-5px;
    border-top:1px solid #e5e5e5;
}
.tags {
    line-height:1.8em;
}
.tags a {
    border:1px solid silver;
    padding:1px;
    line-height:1.8em;
    color:brown;
    background-color:white;
    text-decoration:none;
    white-space:nowrap;
}
.tags a:hover {
    text-decoration:underline;
}

dl {
    margin-right:190px;
}

dt {
    margin-top:8px;
    background-color:#f9f9f9;
    font-weight:bold;
    color:#333333;
}
dt b {
    font-weight:bolder;
    font-size:1.1em;
    /* color:navy; */
}
dt a {
    color:blue;
    text-decoration:none;
}
dt a.close {
    color:red;
}
dd {
    margin-top:5px;
    font-size:0.9em;
    margin-bottom:20px;
    margin-left:20px;
}
dd div {
    margin-top:20px;
    border-top:1px solid silver;
    color:#cccccc;    
    font-size:0.8em;
}
dd div a {
    text-decoration:none;
    color:silver;
    white-space:nowrap;
}
dd div.selected {
    color:gray;
}
dd div.selected a {
    color:blue;
}

.sidebar {
    float:right;
    width:180px;
    top:-50px;
    position:relative;
    height:<? print (count($data)>10)?1200:400; ?>px;
    border-left:1px solid gray;
    font-size:0.8em;
    margin-left:4px;
    padding-left:4px;
}

.sidebar div {
    font-size:0.9em;
}
.sidebar div a {
    text-decoration:none;
}
.sidebar div small {
    color:silver;
}
</style>


<h2>Geograph Knowledgebase / FAQ</h2>

<?

$smarty->display('_doc_search.tpl');

?>

<div class=sidebar>

        <a href="/content/documentation.php">More Help Pages</a><br/><br/>
    
<? 

if ($data) {
        $tags = array();
        foreach ($data as $idx => $row) {
                if (!empty($row['tags'])) {
                        $bits = preg_split('/\s*,\s*/',strtolower(trim($row['tags'])));
                        foreach ($bits as $bit) {
                if (strlen($bit) > 3 && $bit != 'creative commons')
                    $bit = preg_replace('/s$/','',$bit);
                                $tags[$bit]++;
                        }
                }
        }
        if (!empty($tags)) {
                ksort($tags);
                print "<div><b>Topics</b>:";
                if (!empty($_GET['q'])) {
                        print " (<a href=\"faq3.php".($extra?"?$extra":'')."\">show all</a>)";
                }
                print "<br/><br/>";
                foreach ($tags as $tag => $count) {
                        $u = urlencode($tag);$h=htmlentities($tag);
                        if (!empty($_GET['q']) && $_GET['q'] == $tag) {
                                print "&middot; <b>$h</b>";
                        } else {
                                print "&middot; <a href=\"faq3.php?q=$u".($extra?"&amp;$extra":'')."\" title=\"Click to view $h questions\">$h</a>";
                        }
                        if ($count > 1)
                                print " <small>x $count</small>";
                        print "<br/>";
                }
                if (!empty($_GET['q'])) {
                        print "<br> (<a href=\"faq3.php".($extra?"?$extra":'')."\">show all questions</a>)";
                }
                print "</div>";
        }
}

?>


</div>
<? if (count($data) > 2) { ?>
        <input type=button value="Expand All" onclick="showAll()"/>
        <input type=button value="Collapse All" onclick="hideAll()"/>
<? }

$bys = array('target'=>'Subject','level'=>'Level');

if (empty($_GET['q'])) {
    print " <small>Order By : ";
    foreach ($bys as $key => $value) {
        if ($by == $key) {
            print " <b>$value</b> ";
        } elseif ($key == 'target') {
            print " <a href=\"faq3.php".($extra?"?$extra":'')."\">$value</a> ";
        } else {
            print " <a href=\"faq3.php?by=$key".($extra?"&amp;$extra":'')."\" >$value</a> ";
        }
    }
    print "</small>";
}

if (isset($_GET['l']) && empty($_GET['l'])) {
    print "[<a href=\"faq3.php".($extra?"?$extra":'')."\">View <b>even more</b> questions!</a>]";
}

    print "<dl>";
    $last = '';
    $question = 0;
    foreach ($data as $idx => $row) {
        if ($row['question_id'] != $question) {
            
            if ($question)
                print "</dd>";
            
            $question = $row['question_id'];

            if ($last != $row[$by]) {
                print "<h4>".$row[$by];
                if (isset($_GET['l']) && empty($_GET['l'])) {
                    print " <small>[<a href=\"faq3.php".($extra?"?$extra":'')."#$idx\">View more</a>]</small>";
                }
                print "</h4>";
            }
            $last = $row[$by];
        
            $title = "CLICK TO SEE FULL ANSWER\n\n".htmlentities(substr($row['content'],0,250)."...");

            if (count($data) > 2) {    
                print "<dt id=\"dt$idx\" title=\"$title\"><a name=\"$idx\"></a>";
                print "<a href=\"/faq3.php#$idx\" onclick=\"showIt($idx);return false\" onmouseover=\"hoverIt($idx)\" onmouseout=\"exitIt($idx)\" id=\"a$idx\">".title_escape($row['title']?$row['title']:$row['question'])."</a> ";
                print " <a href=\"javascript:void(hideIt($idx));\" class=\"close\" id=\"cl$idx\" style=\"display:none\">Close</a></dt>";
                print "<dd id=\"dd$idx\" style=\"display:none\">";
            } else {
                print "<dt id=\"dt$idx\">".title_escape($row['title']?$row['title']:$row['question'])."</dt>";
                print "<dd id=\"dd$idx\">";
            }    

        } else {
            print "<br/><br/>";
        }


        $row['content'] = preg_replace('/(?<!["\'\[\]>F=])(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)(?<!\.)(?!["\'])/e',"smarty_function_external(array('href'=>\"\$1\",'text'=>\"\$1\",'nofollow'=>1,'title'=>\"\$1\"))",htmlentities($row['content']));

        if (preg_match('/(youtube\.com\/watch\?v=|youtu\.be\/)(\w+)/',$row['content'],$m)) {
                $row['content'] .= "<div style=\"width:490px;margin-left:auto;margin-right:auto;\">".
                '<object width="480" height="385"><param name="movie" value="http://www.youtube-nocookie.com/v/'.$m[2].'&hl=en_US&fs=1&rel=0"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube-nocookie.com/v/'.$m[2].'&hl=en_US&fs=1&rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>'.
                "</div>";
        }

        print nl2br($row['content']);

        print "<div onmouseover=\"this.className='selected'\" onmouseout=\"this.className=''\">";

        if (!empty($row['link'])) {
            $bits = parse_url($row['link']);
            if (!empty($bits['host'])) 
                print " &middot; <b><a href=\"".htmlentities($row['link'])."\">More information on this topic...</a></b>";
        }
        
        if (empty($row['anon'])) { 
            if (!empty($row['user_id'])) {
                print " &middot; contributed by <a href=\"/profile/{$row['user_id']}\">".htmlentities($row['realname'])."</a>"; 
            } else {
                print " &middot; by ".htmlentities($row['realname']); 
            }
            $date = strtotime($row['created']);
            print ", <span title=\"created:{$row['created']} updated:{$row['updated']}\">".date('M Y',$date)."</span>";
        } else if ($USER->user_id == $row['user_id']) { 
            print " &middot; anonymous by YOU"; 
        }

        if ($editable) {

                if (!empty($row['wiki']) || $USER->user_id == $row['user_id']) { 
                    print " &middot; <a href=\"faq-edit.php?id=$idx\">Edit this answer</a>";  
                    if (!empty($row['wiki'])) {
                        print " (Open for editing by anyone)";
                    }
                }

                print " &middot; <a href=\"faq-answer.php?id={$row['question_id']}\">Provide an alternative answer!</a>";
        }

        print "</div>";
        
    }
    if ($question)
        print "</dd>";
    print "</dl>";

?>


<br style="clear:both"/>

<div style="text-align:center;">

<? if (isset($_GET['l']) && empty($_GET['l'])) { ?>
    &middot; <a href="faq3.php<? echo ($extra?"?$extra":''); ?>"><b>View even more questions!</b></a> &middot;<br/><br/>
<? } elseif ($editable) { ?>

    &middot; <b>Can't find the answer you looking for?</b> <a href="faq-ask.php">Ask a question now</a>! &middot;<br/><br/>

<? } ?>

<hr/>
<a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/"><img alt="Creative Commons Licence [Some Rights Reserved]" src="http://creativecommons.org/images/public/somerights20.gif" border="0" style="vertical-align: middle"></a>
the content of this page is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-sa/2.0/" class="nowrap">Creative Commons Licence</a>.</div>

<br/><br/>
 
<script>

function showAll() {
    var re = /^a(\d+)/;
    for(q=document.links.length-1;q>=0;q--) {
        if (m = document.links[q].id.match(re)) {
            idx = m[1];
            if (document.getElementById('dd'+idx).style.display=='none')
                showIt(idx);
        }
    }
}
function hideAll() {
    var re = /^a(\d+)/;
    for(q=document.links.length-1;q>=0;q--) {
        if (m = document.links[q].id.match(re)) {
            idx = m[1];
            if (document.getElementById('dd'+idx).style.display=='block')
                hideIt(idx);
        }
    }
}


function showIt(idx) {
    if (timerIt)
        clearTimeout(timerIt);
    var show = document.getElementById('dd'+idx).style.display=='none';
    document.getElementById('dd'+idx).style.display=show?'block':'none';
    document.getElementById('cl'+idx).style.display=show?'inline':'none';
    if (show)
        document.getElementById('dt'+idx).title = '';
    return void('');
}
var timerIt = null;
function hoverIt(idx) {
    if (timerIt)
        clearTimeout(timerIt);

    timerIt = setTimeout(function() { showIt(idx) },1800);
}

function exitIt(idx) {
        if (timerIt)
                clearTimeout(timerIt);
}
function hideIt(idx) {
        var show = false;
        document.getElementById('dd'+idx).style.display=show?'block':'none';
        document.getElementById('cl'+idx).style.display=show?'inline':'none';
        return void('');
}

AttachEvent(window,'load',function () {
    if (window.location.hash && window.location.hash.length > 1) {
        m = window.location.hash.match(/(\d+)/);
        idx = m[1];
                if (document.getElementById('dd'+idx).style.display=='none') {
            showIt(idx);
            scrollBy(0,-40);
        }
    }
},false);

</script>

<?

        print "&middot; <a href=\"faq.php#top\">Old FAQ page</a> in case you still looking for it. But please let us know why so we can update this one!";


$smarty->display('_std_end.tpl');




    function title_escape($in) {
        $in = htmlentities($in);
        $in = preg_replace('/\[([^\]]+?)\]/','<b>$1</b>',$in);
        return $in;
    }