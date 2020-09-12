<?php
/**
 * $Project: GeoGraph $
 * $Id: faq.php 15 2005-02-16 12:23:35Z lordelph $
 * 
 * GeoGraph geographic photo archive project
 * This file copyright (C) 2006 Barry Hunter (geo@barryhunter.co.uk)
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



$topic_id = intval($_GET['id']);



$db = GeographDatabaseConnection(false);

$page = $db->getRow("
select t.topic_id,topic_title,topic_poster,topic_poster_name,topic_time,post_time,posts_count,t.forum_id
	from geobb_topics t
	inner join geobb_posts on (post_id = topic_last_post_id)
	where t.topic_id = $topic_id and (t.forum_id = 11 or t.topic_poster = {$USER->user_id} or {$USER->user_id} = 3)");
	
if (count($page)) {
	
	$prev_fetch_mode = $ADODB_FETCH_MODE;
	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	
	$list = $db->getAll("
		select post_id,poster_id,poster_name,post_text,post_time
		from geobb_posts
		where topic_id = $topic_id
		order by post_id");

if ($page['forum_id'] == 11) {
?>
Use this page to copy the content of a gallery, and create it as an article (<a href="/article/edit.php?page=new" target="_blank">open create new article page</a>). Ideally use the same title, for the article - this will allow old links to the gallery to automatically redirect to the Article. Let <a href="/usermsg.php?to=3">Barry</a> know when ready to delete the Gallery.
<?
}

	print "<hr/>";
	print "<h2>".htmlentities($page['topic_title'])."</h2>";
	print count($list)." posts";
	print "<div style=\"text-align:right\">by <a href=\"/profile/{$page['topic_poster']}\">".htmlentities($page['topic_poster_name'])."</a></div>";
	print "<hr/>";
	
	print "<form method=get>";
	print "<input type=hidden name=id value=\"{$page['topic_id']}\">";
	print "<input type=checkbox name=titles".(empty($_GET['titles'])?'':' checked')." onclick=\"this.form.submit()\">Show Titles &nbsp; ";
	//print "<input type=checkbox name=pages".(empty($_GET['pages'])?'':' checked')." onclick=\"this.form.submit()\">Hide page breaks &nbsp; ";
	print "</form>";
	print "<form>";
	print "<input type=button value=\"select all\" onclick=\"this.form.text_area.focus();this.form.text_area.select();\"/>";
	print "<textarea cols=100 rows=40 name=\"text_area\" style=\"background-color:#eeeeee;width:100%\" wrap=off>";
	$c = 1;
	
	$pattern = $replacement = array();
	
	$pattern[] = '/<(\/?[ubi])>/';
	$replacement[] = '[\1]';
	
	$pattern[]="/<a href=\"(.+?)\" target=\"(_new|_blank)\"( re[fvl]=\"nofollow\")?>(.+?)<\/a>/i";
	$replacement[]="[url=\\1]\\4[/url]";
	
	$pattern[]="/<img src=\"(.+?)\" border=\"0\" align=\"(left|right)?\" alt=\"\">/i";
	$replacement[]="[img\\2]\\1[/img]";
	
	$pattern[]="/<blockquote>(.+?)<\/blockquote>/s";
	$replacement[]="[blockquote]\\1[/blockquote]";
	
	
	foreach ($list as $row) {
	
		if (!empty($_GET['titles'])) {
			print "[h3]".date('D, j M Y H:i:s',strtotime($row['post_time']));
			if ($USER->user_id != $row['poster_id']) {
				print " by ".htmlentities($row['poster_name']);
			}
			print "[/h3]\n";
		}
	
		$text = str_replace('<br>',"\n", $row['post_text']);
		$text = preg_replace($pattern,$replacement, $text);
		
		if(substr_count($text, '[img\\2]')>0) $msg=str_replace('[img\\2]', '[img]', $text);

		print htmlentities($text)."\n\n";
		
		if ($c%10 == 0 && !isset($_GET['pages'])) {
			print "\n~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n";
		}
		$c++;		
	}
	print "</textarea><hr/>";
} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	print "404 Not Found";
}


