<?php
/**
 * $Project: GeoGraph $
 * $Id: viewsearches.php,v 1.5 2005/08/06 12:35:01 barryhunter Exp $
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

$db = GeographDatabaseConnection(false);

flush();

if (!empty($_POST['delete'])) {
	$t1 = intval(time()/1787);
	$t2 = intval($_POST['e']);
	$hash = hash_hmac('sha1',$_POST['delete'],$CONF['token_secret'].$t2);
	if ($_POST['hash'] != $hash || abs($t1-$t2) > 3) {
	        die("[]");
	}

	//needs to cope with either media.geograph or older nearby urls
	$url = "/speculative/view.php?id=".intval($_POST['delete']);

	$sql = "DELETE FROM geobb_posts WHERE topic_id = 12804 AND poster_id=23277 AND post_text LIKE '%$url\"%'";
	$db->Execute($sql) or die("$sql;\n".$db->ErrorMsg()."\n\n");

	if ($rows = $db->Affected_Rows()) {
		$t = "topic_id = 12804";
		$sql = "UPDATE geobb_topics SET topic_last_post_id = (SELECT MAX(post_id) FROM geobb_posts WHERE $t),posts_count=(SELECT COUNT(*) FROM geobb_posts WHERE $t) WHERE $t";
		$db->Execute($sql) or die("$sql;\n".$db->ErrorMsg()."\n\n");

		print "Done $rows\n";
	} else {
		print "None\n";
	}
	exit;
}


if (!empty($_POST['id'])) {
	$t1 = intval(time()/1787);
	$t2 = intval($_POST['e']);
	$hash = hash_hmac('sha1',$_POST['id'],$CONF['token_secret'].$t2);
	if ($_POST['hash'] != $hash || abs($t1-$t2) > 3) {
	        die("[]");
	}

	$url = "https://media.geograph.org.uk/speculative/view.php?id=".intval($_POST['id']);

	$text = str_replace("\n","<br>\n","<b>".htmlentities2($_POST['title'])."</b><a href=\"$url\" target=\"_blank\"><img src=\"".htmlentities2($_POST['url'])."\" align=\"right\"></a><br>".
		"<br>".htmlentities2($_POST['entry'])."<br><br><a href=\"$url\" target=\"_blank\">$url</a><br><br>by ".htmlentities2($_POST['realname']));

	$sql = "INSERT INTO geobb_posts SET topic_id = 12804,forum_id=2,poster_id=23277,poster_name='socket'";
	$sql .= ",post_time = FROM_UNIXTIME(".intval($_POST['time']).")";
	$sql .= ",post_text = ".$db->Quote($text);

	$db->Execute($sql) or die("$sql;\n".$db->ErrorMsg()."\n\n");
	$id = $db->Insert_ID();

	$sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = 12804";
	$db->Execute($sql) or die("$sql;\n".$db->ErrorMsg()."\n\n");

	print "SAVED ".intval($_POST['id']);
	exit;
}

?>
<form method="post">

ID: <input type="text" name="id" value=""/><br/>

Time: <input type="text" name="time" value=""/><br/>

Title: <input type="text" name="title" value="" size=50/><br/>

Url: <input type="text" name="url" value="" size=50/><br/>

Realname: <input type="text" name="realname" value="" size=50/><br/>

Entry: <textarea name="entry" rows="4" cols="80"/></textarea>

<input type=submit>

</form>

