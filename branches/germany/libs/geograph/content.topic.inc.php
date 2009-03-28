<?php

/**
 * $Project: GeoGraph $
 * $Id: functions.inc.php 2911 2007-01-11 17:37:55Z barry $
 *
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2007 Barry Hunter (geo@barryhunter.co.uk)
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

/**************************************************
*
******/

$gridimage_ids = array();
	
function add_image_to_list($id,$text ='') {
	global $gridimage_ids;
	if (is_numeric($id)) {
		$gridimage_ids[] = $id;
	}
	return " $text ";
}

	
function add_topic_to_content($topic_id,& $db) {
	global $gridimage_ids;
	
	$topic=$db->GetRow("select topic_title,forum_id,topic_time,topic_poster from geobb_topics where topic_id='$topic_id'");
	
	$gridimage_ids = array();
	
	if ($topic['forum_id'] == 6 || $topic['forum_id'] == 11) {//todo gsd
		$updates = array();
		$updates[] = "`foreign_id` = {$topic_id}";
		switch($topic['forum_id']) {
			case 5: $updates[] = "`source` = 'gsd'"; break;
			case 6: $updates[] = "`source` = 'themed'"; break;
			case 11: $updates[] = "`source` = 'gallery'"; break;
		} 
		
		$content_id = $db->getOne("SELECT content_id FROM content WHERE ".implode(' AND ',$updates));
		
		$updates[] = "`title` = ".$db->Quote($topic['topic_title']);

		$url = trim(strtolower(preg_replace('/[^\w]+/','_',html_entity_decode(preg_replace('/&#\d+;?/','_',$topic['topic_title'])))),'_').'_'.$topic_id;
		if ($topic['forum_id'] == 11) {
			$updates[] = "`url` = ".$db->Quote("/gallery/".$url);
		} else {
			$updates[] = "`url` = ".$db->Quote("/discuss/?action=vthread&amp;forum={$topic['forum_id']}&amp;topic={$topic_id}");
		}
		$updates[] = "`user_id` = {$topic['topic_poster']}";


		$posts=$db->GetRow("select max(post_time) as post_time,group_concat(post_text ORDER BY post_id SEPARATOR ' ') as post_text from geobb_posts where topic_id='$topic_id'");

		#$updates[] = "`extract` = ".$db->Quote($page['extract']);			

		$content = $posts['post_text'];
		$content = str_replace("\r",'',$content);

		$content = preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"add_image_to_list('\$2','\$2')",$content);

		$content = strip_tags($content);


		//todo replace with 4figs?

		$content = preg_replace("/\n{2,}/","\n",$content);

		$updates[] = "`words` = ".$db->Quote($content);

		#todo tags.

		if (count($gridimage_ids)) {
			$updates[] = "`gridimage_id` = {$gridimage_ids[0]}";
		}
		$updates[] = "`type` = 'info'";

		$updates[] = "`updated` = '{$posts['post_time']}'";

		if ($content_id) {
			$sql = "UPDATE `content` SET ".implode(',',$updates)." WHERE content_id = $content_id";
		} else {
			$updates[] = "`created` = '{$topic['topic_time']}'";

			//we can come here via update too, so replace works, as we have a UNIQUE(foreign_id,type)
			$sql = "INSERT INTO `content` SET ".implode(',',$updates);
		}
		
		$db->Execute($sql);
	} 
}