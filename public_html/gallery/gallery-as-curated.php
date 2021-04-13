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

$db = GeographDatabaseConnection(false);


$topic_id = intval($_GET['id']);
$label = $db->Quote($_GET['label']);

$done = $db->getAssoc("SELECT gridimage_id,region FROM curated WHERE label = $label AND active > 0");
print_r($done);

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

	foreach ($list as $row) {

		$text = str_replace('<br>',"\n", $row['post_text']);

		print "<pre>".htmlentities($text)."</pre>";

		$next = false;
		foreach (explode("\n",$text) as $line) {
			if (preg_match('/<b>(.+?)<\/b>/',$line,$m)) {
				$region = $m[1];
				print "<h3>$region</h3>";
			} elseif (preg_match('/\[\[\[\d+/',$line)) {
				$ids = explode(' ',trim(preg_replace('/[^\d]+/',' ',$line)));
				$next = true;
				print "<p>Ids: ".implode(', ',$ids)."</p>";
			} elseif ($next) {
				$captions = explode('|',$line);

				if (count($captions) == count($ids)) {
					//ok
					$caption = null;
				} elseif (count($captions) == 1 && preg_match('/All( in the|)(.+)/',$captions[0],$m)) {
					$caption = $m[2];
				} else {
					print "Mismatch: ".implode(', ',$captions)."</p>";
				}

					foreach ($ids as $idx => $id) {
						if (isset($done[$id])) {
							print "<p>Already done $id</p>";
						} else {
							$sql = "INSERT INTO curated ";
							$sql .= "SET user_id = {$page['topic_poster']}";
							$sql .= ",label = {$label}";
							$sql .= ",gridimage_id = {$id}";
							$sql .= ",region = ".$db->Quote($region);
							$sql .= ",caption = ".$db->Quote(trim($caption?$caption:$captions[$idx]));
							$sql .= ",created = NOW()";
							$sql .= ",active = 2";
							print "$sql;<br>";
							if (!empty($_GET['run'])) {
								$db->Execute($sql);
								print "rows: ".$db->Affected_Rows()."<br>";
							}
						}
					}
				print "<hr>";

				$next = false;
			}

		}



	}

} else {
	header("HTTP/1.0 404 Not Found");
	header("Status: 404 Not Found");
	print "404 Not Found";
}


