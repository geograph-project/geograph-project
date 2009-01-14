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
init_session();

$USER->mustHavePerm("admin");

$smarty = new GeographPage;

$db = NewADOConnection($GLOBALS['DSN']);

$smarty->display('_std_begin.tpl');
flush();
	
if ($_POST && $CONF['forum_topic_announce'] >= 0) {
	$text = str_replace("\n","<br>\n","<b>{$_POST['title']}</b><br><br>{$_POST['entry']}");

	$sql = "INSERT INTO geobb_posts SET topic_id = {$CONF['forum_topic_announce']},forum_id={$CONF['forum_announce']},poster_id={$USER->user_id},poster_name='{$USER->nickname}'";
	$sql .= ",post_time = '".mysql_real_escape_string($_POST['date'])."'";
	$sql .= ",post_text = '".mysql_real_escape_string($text)."'";

	$result = mysql_query($sql) or die ("Couldn't insert : $sql " . mysql_error() . "\n");
	$id = mysql_insert_id();
	
	$sql = "UPDATE geobb_topics SET topic_last_post_id = $id,posts_count=posts_count+1 WHERE topic_id = $CONF['forum_topic_announce']";
	$result = mysql_query($sql) or die ("Couldn't insert : $sql " . mysql_error() . "\n");
	
	print "SAVED {$_POST['title']}";
} 

?>
<form method="post">

Date: <input type="text" name="date" value="<?php echo empty($_POST['date'])?date('Y-m-d'):$_POST['date']; ?>"/><br/>

Title: <input type="text" name="title" value="<?php echo empty($_POST['title'])?'':$_POST['title']; ?>" size=50/><br/>

Entry: <textarea name="entry" rows="4" cols="80"/></textarea> 

<input type=submit>

</form>

<?php
$smarty->display('_std_end.tpl');
exit;
?>
