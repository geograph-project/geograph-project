<?php
/**
 * $Project: GeoGraph $
 * $Id: xmas.php 6235 2009-12-24 12:33:07Z barry $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2005 Barry Hunter (geo@barryhunter.co.uk)
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
require_once('geograph/map.class.php');
require_once('geograph/mapmosaic.class.php');
init_session();

$smarty = new GeographPage;

$USER->mustHavePerm("basic");


$smarty->display('_std_begin.tpl',md5($_SERVER['PHP_SELF']));

print "<h2>Bulk Tag/Shared Description editor</h2>";


$moderated = !in_array($USER->user_id, array(4264,2520,1469,9181,10354,3,139));
$moderated = false;


if (!empty($_POST)) {
	$sqls = array();
	$ids = array();
	if (!empty($_POST['ids'])) {
		$str = trim(preg_replace('/[^\d]+/',' ',$_POST['ids']));
		foreach (explode(' ',$str) as $id) {
			$ids[$id] = 1;
		}
	}

	$db = GeographDatabaseConnection(false);

	if (!empty($_POST['i']) && !empty($_POST['page'])) {
		require_once('geograph/searchcriteria.class.php');
		require_once('geograph/searchengine.class.php');

		$pg = intval($_POST['page']);

		$engine = new SearchEngine(intval($_POST['i']));

		$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

		$images = $engine->ReturnAssoc($pg);

		foreach ($images as $id => $row) {
			$ids[$id] = 1;
		}
	}
	if (empty($ids)) {
		print "NO IDs specified.";
	        $smarty->display('_std_end.tpl');
		exit;
	}

	print "ID count (input) = ".count($ids)."<hr/>";
	$idstr = implode(',',array_keys($ids));

	if (!empty($_POST['tag'])) {

		$where = array();
		$where['prefix'] = "prefix = ''";

		if (isset($_POST['prefix'])) {
			$where['prefix'] = "prefix = ".$db->Quote($_POST['prefix']);
			$smarty->assign('theprefix', $_POST['prefix']);

		} elseif (strpos($_POST['tag'],':') !== FALSE) {
			list($prefix,$_POST['tag']) = explode(':',$_POST['tag'],2);

			$where['prefix'] = "prefix = ".$db->Quote($prefix);
			$smarty->assign('theprefix', $prefix);
		}
		$where['tag'] = "tag = ".$db->Quote($_POST['tag']);
		$smarty->assign('tag',$_POST['tag']);

		$tag= $db->getRow("SELECT tag_id,prefix,tag,description,canonical FROM tag WHERE status = 1 AND ".implode(' AND ',$where));
	}
	if (!empty($_POST['snippet'])) {
		$snippet= $db->getRow("SELECT snippet_id,title FROM snippet WHERE snippet_id = ".intval($_POST['snippet']));
	}

	$uid = intval($USER->user_id);

	switch ($_POST['action']) {
		case 'add_tag':
			if (empty($tag)) die("Invalid tag");
			if (!empty($_POST['public']))
				$sqls[] = "INSERT INTO gridimage_tag SELECT gridimage_id,{$tag['tag_id']} AS tag_id,$uid AS user_id,NOW(),2 AS status,NOW() FROM gridimage_search WHERE user_id = $uid AND gridimage_id IN ($idstr) ON DUPLICATE KEY UPDATE status = 2";

			if (empty($_POST['public']) || empty($_POST['mine']))
				$sqls[] = "INSERT INTO gridimage_tag SELECT gridimage_id,{$tag['tag_id']} AS tag_id,$uid AS user_id,NOW(),1 AS status,NOW() FROM gridimage_search WHERE gridimage_id IN ($idstr)".(!empty($_POST['mine'])?" AND user_id = $uid":'')." ON DUPLICATE KEY UPDATE status = 1";
			break;

		case 'remove_tag':
			if (empty($tag)) die("Invalid tag");
			if (!empty($_POST['mine'])) {
				//$sqls[] = "DELETE gridimage_tag.* FROM gridimage_tag INNER JOIN gridimage_search USING (gridimage_id) WHERE gridimage_tag.user_id = $uid AND gridimage_search.user_id = $uid AND tag_id = {$tag['tag_id']} AND gridimage_id IN ($idstr)";
				$sqls[] = "UPDATE gridimage_tag INNER JOIN gridimage_search USING (gridimage_id) SET status = 0 WHERE gridimage_tag.user_id = $uid AND gridimage_search.user_id = $uid AND tag_id = {$tag['tag_id']} AND gridimage_id IN ($idstr)";
			} else {
				//$sqls[] = "DELETE FROM gridimage_tag WHERE user_id = $uid AND tag_id = {$tag['tag_id']} AND gridimage_id IN ($idstr)";
				$sqls[] = "UPDATE gridimage_tag SET status = 0 WHERE user_id = $uid AND tag_id = {$tag['tag_id']} AND gridimage_id IN ($idstr)";
			}
			break;

		case 'add_snippet':
			if (empty($snippet)) die("Invalid snippet");
			$sqls[] = "INSERT IGNORE INTO gridimage_snippet SELECT gridimage_id,{$snippet['snippet_id']} AS snippet_id,$uid AS user_id,NOW() FROM gridimage_search WHERE user_id = $uid AND gridimage_id IN ($idstr)";
			break;

		case 'remove_snippet':
			if (empty($snippet)) die("Invalid snippet");
			$sqls[] = "DELETE gridimage_snippet.* FROM gridimage_snippet INNER JOIN gridimage_search USING (gridimage_id) WHERE gridimage_snippet.user_id = $uid AND snippet_id = {$snippet['snippet_id']} AND gridimage_search.user_id = $uid AND gridimage_id IN ($idstr)";
			break;
		default:
			print "Please select what action to perform.";
		        $smarty->display('_std_end.tpl');
			exit;
	}


	if ($moderated) {
		$unique = date('r')."/".uniqid();

		print "<h4>Tracking Reference: $unique</h4>";

		$str = print_r($_POST,1)."\n";
		$str .= print_r($USER->realname,1)."\n";
		$str .= print_r($USER->user_id,1)."\n";
		$str .= print_r($USER->email,1)."\n\n\n";
		$str .= implode(";\n\n",array_map('htmlentities',$sqls))."\n\n#\n";

		mail_wrapper("barry@barryhunter.co.uk","bulk attach :: $unique",$str);

		print "<h2>Saved</h2>";
	} else {
		print "<h4>Applying changes...</h4>";flush();
		foreach ($sqls as $sql) {
		        $starttime = microtime(true);
			$db->Execute($sql);
		        $endtime = microtime(true);
			$rows = $db->Affected_Rows();
		}
        	$timetaken = sprintf('%0.4f', $endtime - $starttime);

		print "<p>Done. <b>$rows</b> images affected, in $timetaken seconds.</p>";
	}

	$smarty->display('_std_end.tpl');
	exit;
}

?>

<form method="post" name="theForm">

<h3>Images</h3>

<h4>Paste Image IDs</h4>
Long list of ids, seperated by spaces, commas, newlines, whatever. Can even past in whole links to the photo page, or the [[[..]]] [[[...]]] format from marked lists.<br/>

<script type="text/javascript">
current = readCookie('markedImages')
if (current && current != '') {
splited = current.commatrim().split(',');
document.write('<div style="border: 1px solid lightgrey;">Marked Images['+(splited.length+0)+']: <a title="Insert marked image list" href="#" onclick="return paste_strinL(returnMarkedImages(),0)" onMouseOver="window.status=\'Insert marked image list\'; return true" onMouseOut="window.status=\'\'; return true"><b>Insert into Box</b></a> (<a href="javascript:void(clearMarkedImages())" style="color:red">Clear</a>)</div>');
}

function paste_strinL(str,dummy) {
	var ele = document.forms['theForm'].elements['ids'];

	ele.value = ele.value + ' ' + str;
}

</script>


<textarea rows=3 cols=50 name="ids"></textarea><br/>
(max 1000 - doesn't work on pending/rejected images)<br/><br/>

and/or<br/>
<h4>Search Results</h4>
i Number:<input size=10 name="i"> page:<input size=3 name="page"> (both required)<br/>
(Only works on a single page of results at a time)
<br/>
<hr/>
<h3>Choose One Action</h3>
<table border=1 cellspacing=0 cellpadding=5>

 <tr>
  <td><input type=radio name="action" value="add_tag"/> Add Tag <br/>
  &nbsp;&nbsp;&nbsp;<input type=checkbox name="public" checked /> Public tag (if your image)<br/></td>

  <td rowspan=2 valign=middle>tag: <input size=30 name="tag" onkeyup="if (this.value.length > 2) {loadTagSuggestions(this,event);}" onpaste="loadTagSuggestions(this,event);" onmouseup="loadTagSuggestions(this,event);" oninput="loadTagSuggestions(this,event);"> (eg place:Epping)<br/>
    <input type="hidden" name="tag_id"/>
    <div id="tag-message"></div>
    <input type=checkbox name="mine" checked /> Only act on your images</td>
 </tr>

 <tr>
  <td><input type=radio name="action" value="remove_tag"/> Remove Tag<br/>
   &nbsp;&nbsp;&nbsp;(Removes both public and private tags)</td>
 </tr>

<tr><td colspan=2></td></tr>

 <tr>
  <td><input type=radio name="action" value="add_snippet"/> Add Shared Description</td>

  <td rowspan=2 valign=middle>Shared Description: <input size=3 name="snippet" id="snip"> (id number)<br/>
    <small>After entering id, <a href="#" onclick="window.open('/snippet/'+document.getElementById('snip').value);return false;">click here</a> to check the right SD opens.<br/>
	Note: Only acts on <b>your</b> images</small></td>
 </tr>

 <tr>
  <td><input type=radio name="action" value="remove_snippet"/> Remove Shared Description</td>
 </tr>

</table>

<p><small>Note: You can only remove tags/SD's you specifically added to the image(s)</small></p>

<hr/>
<input type="submit" name="submit" value="<? if ($moderated) { ?>request change<? } else { ?>apply now<? } ?>"/>
</form>

<ul>
	<li>Double and triple check all values above. There is <b>no</b> confimation, and little error checking, and some mistakes may be hard to undo.</li>
<? if ($moderated) { ?>
	<li>The results of this form are not actioned right away. They are placed in a queue, and will be approved by a developer (a short term measure to avoid major mishaps)</li>
	<li>Even after being applied, it can take 24 hours or so for the changes to be fully visible on the website. (but will be visible in the tags/sd 'boxes')</li>
<? } else { ?>
	<li>The changes on this form <b>will</b> apply right away, but might take 24 hours to show everywhere due to caching</li>
<? } ?>
</ul>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script>

	function loadTagSuggestions(that,event) {

		var unicode=event.keyCode? event.keyCode : event.charCode;
		if (unicode == 13) {
			return;
		}

		if (that.value.length == 0) {
			that.form.elements['submit'].disabled = false;
			return;
		}

		param = 'q='+encodeURIComponent(that.value);

		$.getJSON("/tags/tag.json.php?"+param+"&callback=?"+((that.name == 'tag')?'&expand=1':''),

		// on search completion, process the results
		function (data) {
			var div = document.getElementById(that.name+'-message');
			that.form.elements[that.name+'_id'].value = '';

			if (data && data.tag_id) {

				var text = data.tag;
				if (data.prefix) {
					text = data.prefix+':'+text;
				}
				text = text.replace(/<[^>]*>/ig, "");
				text = text.replace(/['"]+/ig, " ");


				str = "Found '<b>"+text+"</b>'";

				if (data.images) {
					str = str + " used by "+data.images+" images";
				}

				if (data.users) {
					str = str + ", by "+data.users+" users";
				}

				that.form.elements[that.name+'_id'].value = data.tag_id;

				that.form.elements['submit'].disabled = false;

			} else if (data.error) {
				if (that.name == 'tag') {
					str = data.error;
					that.form.elements['submit'].disabled = true;
				} else {
					str = 'no tags/images';
				}
			} else {
				if (that.name == 'tag') {
					str = "tag not found!";
					that.form.elements['submit'].disabled = true;
				} else {
					str = "no tags/images";
				}
			}
			div.innerHTML = str;
		});
	}

</script>

<?


$smarty->display('_std_end.tpl');


