<?php
/**
 * $Project: GeoGraph $
 * $Id: UpdateDiscussionCrossReferencesWithNewTopic.class.php 4204 2008-03-04 23:33:06Z barry $
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005 Paul Dixon (paul@elphin.com)
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

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateContentWithNewTopic extends EventHandler
{
	var $gridimage_ids = array();
	
	function add_image_to_list($id,$text ='') {
		if (is_numeric($id)) {
			$this->gridimage_ids[] = $id;
		}
		return " $text ";
	}

	function processEvent(&$event)
	{
		//perform actions
		$topic_id = $event['event_param'];

		$db=&$this->_getDB();

		$topic=$db->GetRow("select topic_title,forum_id,topic_time,topic_poster from geobb_topics where topic_id='$topic_id'");
		
		
		if ($topic['forum_id'] == 11) {//gallery -todo gsd and maybe even submitted articles!
			$updates = array();
			$updates[] = "`foreign_id` = {$topic_id}";
						
			$updates[] = "`title` = ".$db->Quote($topic['topic_title']);
			
			$url = trim(strtolower(preg_replace('/[^\w]+/','_',html_entity_decode(preg_replace('/&#\d+;?/','_',$topic['topic_title'])))),'_').'_'.$topic_id;
	
			$updates[] = "`url` = ".$db->Quote("/gallery/".$url);
			$updates[] = "`user_id` = {$topic['topic_poster']}";
			
			
			$posts=$db->GetRow("select max(post_time) as post_time,group_concat(post_text ORDER BY post_id DESC SEPARATOR ' ') as post_text from geobb_posts where topic_id='$topic_id'");
		
			#$updates[] = "`extract` = ".$db->Quote($page['extract']);			
			
			$content = $posts['post_text'];
			$content = str_replace("\r",'',$content);
			
			$content = preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"\$this->add_image_to_list('\$2','\$2')",$content);
			
			$content = strip_tags($content);
			
			
			//todo replace with 4figs?
			
			$content = preg_replace("/\n{2,}/","\n",$content);
			
			$updates[] = "`words` = ".$db->Quote($content);
			
			#todo tags.
			
			if (count($this->gridimage_ids)) {
				$updates[] = "`gridimage_id` = {$this->gridimage_ids[0]}";
			}
			switch($topic['forum_id']) {
				case 5: $updates[] = "`type` = 'gsd'"; break;
				case 6: $updates[] = "`type` = 'submittedarticle'"; break;
				case 11: $updates[] = "`type` = 'gallery'"; break;
			} 
			$updates[] = "`use` = 'info'";
			
			$updates[] = "`updated` = '{$posts['post_time']}'";
			$updates[] = "`created` = '{$topic['topic_time']}'";
			
			//we can come here via update too, so replace works, as we have a UNIQUE(foreign_id,type)
			$sql = "REPLACE INTO `content` SET ".implode(',',$updates);

			$db->Execute($sql);
		} 
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>