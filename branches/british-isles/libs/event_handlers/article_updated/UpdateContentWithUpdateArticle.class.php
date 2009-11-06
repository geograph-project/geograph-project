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
class UpdateContentWithUpdateArticle extends EventHandler
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
		$article_id = $event['event_param'];
		
		$db=&$this->_getDB();
		
		$page = $db->getRow("
		select article.*,category_name
		from article 
		left join article_cat on (article.article_cat_id = article_cat.article_cat_id)
		where (licence != 'none' and approved > 0) and article_id = $article_id");
		
		$updates = array();
		$updates[] = "`foreign_id` = {$article_id}";
		$updates[] = "`source` = 'article'";

		$content_id = $db->getOne("SELECT content_id FROM content WHERE ".implode(' AND ',$updates));
		
		if (count($page)) {
			
			$updates[] = "`title` = ".$db->Quote($page['title']);
			$updates[] = "`url` = ".$db->Quote("/article/".$page['url']);
			$updates[] = "`user_id` = {$page['user_id']}";
			
			
			$updates[] = "`gridsquare_id` = {$page['gridsquare_id']}";
			
			$updates[] = "`extract` = ".$db->Quote($page['extract']);			
			
			//we working on the unhtmlified content!
			if (preg_match_all('/\[h(\d)\]([^\n]+?)\[\/h(\d)\]/',$page['content'],$matches)) {
				$updates[] = "`titles` = ".$db->Quote(implode(', ',$matches[2]));
			}
			
			$content = $page['content'];
			$content = str_replace("\r",'',$content);
			
			$content = preg_replace('/\[image id=(\d+) text=([^\}]+)\]/e',"\$this->add_image_to_list('\$1','\$2')",$content);
			
			$content = preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"\$this->add_image_to_list('\$2','\$2')",$content);
			
			$content = strip_tags(preg_replace('/\[(\/?)(\w+)=?(https?:\/\/[\w\.-]+\.\w{2,}\/?[\w\~\-\.\?\,=\'\/\\\+&%\$#\(\)\;\:]*)?\]/','<{$1}tag>',$content));
			
			
			//todo replace with 4figs?
			$content = preg_replace('/\[(small|)map *([STNH]?[A-Z]{1}[ \.]*\d{2,5}[ \.]*\d{2,5})\]/',"'\$2'",$content);
			
			$content = preg_replace("/\n{2,}/","\n",$content);
			
			$updates[] = "`words` = ".$db->Quote($content);
			
			#todo tags.
			
			if (count($this->gridimage_ids)) {
				$updates[] = "`gridimage_id` = {$this->gridimage_ids[0]}";
			}
			
			
			$updates[] = "`type` = '".(preg_match('/\bGeograph\b/',$page['category_name'])?'document':'info')."'";
			
			$updates[] = "`updated` = '{$page['update_time']}'";
			$updates[] = "`created` = '{$page['create_time']}'";
			
			//we can come here via update too, so replace works, as we have a UNIQUE(foreign_id,source)
			if ($content_id) {
				$sql = "UPDATE `content` SET ".implode(',',$updates)." WHERE content_id = $content_id";
			} else {
				$sql = "REPLACE INTO `content` SET ".implode(',',$updates);
				
				if (count($this->gridimage_ids)) {
					$updates = array();
					$updates[] = "`foreign_id` = {$article_id}";
					$updates[] = "`source` = 'article'";

					$content_id = $db->getOne("SELECT content_id FROM content WHERE ".implode(' AND ',$updates));
				}
			}

			$db->Execute($sql);
			
			$words = preg_split("/\s+/s",$content);
			$words = count($words);
			
			$images = count($this->gridimage_ids);
			
			$sql = "INSERT INTO `article_stat` SET article_id = {$page['article_id']}, words = $words, images = $images ON DUPLICATE KEY UPDATE words = $words, images = $images"; 
			
			$db->Execute($sql);
			
			if (count($this->gridimage_ids) && $content_id) {
				$db->Execute("DELETE FROM gridimage_content WHERE content_id=$content_id");
				foreach ($this->gridimage_ids as $i => $g_id) {

					$db->Execute("INSERT INTO gridimage_content SET gridimage_id = $g_id,content_id = $content_id");
				}
			}
		} elseif ($content_id) {
			$sql = "DELETE FROM `content` WHERE ".implode(' AND ',$updates);
			
			$db->Execute($sql);
			
			$db->Execute("DELETE FROM gridimage_content WHERE content_id=$content_id");
		}
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>