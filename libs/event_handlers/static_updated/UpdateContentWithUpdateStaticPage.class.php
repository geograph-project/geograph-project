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
class UpdateContentWithUpdateStaticPage extends EventHandler
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
		$filename = $event['event_param'];
		
		$db=&$this->_getDB();
		
		$path = $_SERVER['DOCUMENT_ROOT']."/templates/basic/".$filename;
		
		//the online servers dont have svn
		#$lines = `svn log $path`;
		//a temporally measure:
		$lines = file_get_contents($url = "http://www.nearby.org.uk/geograph/log.php?url=http://svn.geograph.org.uk/svn/trunk/public_html/templates/basic/".$filename);

		if (strlen($lines)) {
		
			#print "<pre>$lines</pre>";

			$updated = '';
			foreach (explode("\n",$lines) as $line) {
				list ($rev,$user,$datestr,$num) = explode(' | ',$line);

				if ($datestr) {
					list($date,$time) = explode(' ',$datestr);
					if (!$updated) {
						$updated = "$date $time";
					}
					$created = "$date $time";
					$username = $user;
				}
			}
			
			$url = preg_replace("/static_(\w+)\.tpl/",'/help/$1',$filename);
			$url = preg_replace("/(\w+)\.tpl/",'/$1.php',$url);
			
			$updates = array();
			$updates[] = "`foreign_id` = CRC32('$filename')";
			$updates[] = "`source` = 'help'";
			
			$content_id = $db->getOne("SELECT content_id FROM content WHERE ".implode(' AND ',$updates));
			
			
			$updates[] = "`url` = ".$db->Quote($url);
			
			
			$title = "untitled";
			
			$content = file_get_contents($path);
			$content = str_replace("\r",'',$content);
			
			if (preg_match('/<div style="text-align:right"><a href="\/profile\/(\d+)">[\w ]+<\/a>/',$content,$matches)) {
				$updates[] = "`user_id` = {$matches[1]}";
			} elseif ($username) {
				$user_id = $db->getOne("select user_id from user where rights like '%admin%' and (realname like '%$username%' OR nickname like '%$username%')");
				$updates[] = "`user_id` = {$user_id}";
			}
			
						
			if (preg_match_all('/<h(\d)[^>]*>([^\n]+?)<\/h(\d)>/',$content,$matches)) {
				$updates[] = "`titles` = ".$db->Quote(implode(', ',$matches[2]));
				$title = $matches[2][0];
			}
			
			if (preg_match('/\{assign var="page_title" value="(.+?)"\}/',$content,$matches)) {
				$title = $matches[1];
			}			
			
			if (preg_match('/<(p)[^>]*>([^{]+?)<\/(p)>/s',$content,$matches)) {
				$updates[] = "`extract` = ".$db->Quote(trim(preg_replace("/[\s\n]+/"," ",strip_tags($matches[2]))));
			}
			
			$updates[] = "`title` = ".$db->Quote(trim($title));
			
			//todo remove other smarty tags!
			
			$content = preg_replace('/<script.*?<\/script>/s','',$content);
			$content = preg_replace('/<style.*?<\/style>/s','',$content);
			
			
			$content = preg_replace('/\{gridimage id="?(\d+)"? text="([^\}]+)"\}/e',"\$this->add_image_to_list('\$1','\$2')",$content);
			
			#$content = preg_replace('/\[\[(\[?)(\w{0,2} ?\d+ ?\d*)(\]?)\]\]/e',"\$this->add_image_to_list('\$2','\$2')",$content);
			
			$content = strip_tags(preg_replace('/\{(\/?)(\w+)[^}]*\}/s','<{$1}tag>',$content));
			
			$content = preg_replace("/\n{2,}/","\n",$content);
			
			$updates[] = "`words` = ".$db->Quote($content);
			
			#todo tags.
			
			if (count($this->gridimage_ids)) {
				$updates[] = "`gridimage_id` = {$this->gridimage_ids[0]}";
			}
			
			
			$updates[] = "`type` = 'document'";
			
			$updates[] = "`updated` = '$updated'";
			
			if ($content_id) {
				$sql = "UPDATE `content` SET ".implode(',',$updates)." WHERE content_id = $content_id";
			} else {
				$updates[] = "`created` = '$created'";
			
				//we can come here via update too, so replace works, as we have a UNIQUE(foreign_id,source)
				$sql = "INSERT INTO `content` SET ".implode(',',$updates);
			}

			$db->Execute($sql);
		} 
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>