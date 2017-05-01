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
require_once('geograph/conversionslatlong.class.php');

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateContentWithUpdateTrip extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;

		//perform actions
		$trip_id = intval($event['event_param']);
		
		$db=&$this->_getDB();
		
		$page = $db->getRow("
		select *
		from geotrips 
		where id='$trip_id'");
		#where id='$trip_id' and status='visible'");
		
		if (count($page)) {
			require_once($_SERVER['DOCUMENT_ROOT'].'/geotrips/geotrip_func.php');
			$page['grid_reference'] = bbox2gr($page['bbox']);
			$page['nicetype'] = whichtype($page['type']);
			$square = new GridSquare;
			$page['gridsquare_id'] = $square->setByFullGridRef($page['grid_reference']) ? $square->gridsquare_id : 0;
			if ($CONF['lang'] === 'de') { # FIXME use $MESSAGES + use array
				if ($page['title'] === '') {
					$page['title'] = $page['location'] . " vom Ausgangspunkt " . $page['start'];
				}
				$page['extract'] = $page['location'] . ": " . $page['nicetype'] . " vom Ausgangspunkt " . $page['start'];
			} else {
				if ($page['title'] === '') {
					$page['title'] = $page['location'] . " from " . $page['start'];
				}
				$page['extract'] = $page['location'] . ": A " . $page['nicetype'] . " from " . $page['start'];
			}

			$updates = array();
			$updates[] = "`foreign_id` = {$page['id']}";
			$updates[] = "`source` = 'trip'";
			
			$content_id = $db->getOne("SELECT content_id FROM content WHERE ".implode(' AND ',$updates));
			
			
			$updates[] = "`title` = ".$db->Quote($page['title']);
			$updates[] = "`url` = ".$db->Quote("/geotrips/geotrip_show.php?trip=".$trip_id);
			#$updates[] = "`url` = ".$db->Quote("/geotrips/".$trip_id);
			$updates[] = "`user_id` = {$page['uid']}";
			
			$updates[] = "`gridsquare_id` = {$page['gridsquare_id']}";
			
			$updates[] = "`extract` = ".$db->Quote($page['extract']);
			
			$content = $page['descr'];
			$content = str_replace("\r",'',$content);
			
			$content = preg_replace("/\n{2,}/","\n",$content);
			
			$updates[] = "`words` = ".$db->Quote($content);

			$updates[] = "`titles` = ''"; #FIXME
			$updates[] = "`tags` = ''"; #FIXME
			
			$updates[] = "`gridimage_id` = {$page['img']}";
			
			$updates[] = "`type` = 'info'";
			
			$updates[] = "`updated` = '{$page['updated']}'";
			$updates[] = "`created` = '{$page['created']}'";
			
			//we can come here via update too, so replace works, as we have a UNIQUE(foreign_id,source)
			if ($content_id) {
				$sql = "UPDATE `content` SET ".implode(',',$updates)." WHERE content_id = $content_id";
			} else {
				$sql = "REPLACE INTO `content` SET ".implode(',',$updates);
			}

			$db->Execute($sql);
		} else {
			$updates = array();
			$updates[] = "`foreign_id` = $trip_id";
			$updates[] = "`source` = 'trip'";
			
			$sql = "DELETE FROM `content` WHERE ".implode(' AND ',$updates);
			
			$db->Execute($sql);
		}
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>
