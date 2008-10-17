<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2005  Barry Hunter (geo@barryhunter.co.uk)
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

/**
* handles the new_topic event and performs any necessary
* updates to gridsquare_topic table
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateThumbnailCrossReferencesWithNewReply extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		$post_id = $event['event_param'];
		
		$db=&$this->_getDB();
		 
		$post=$db->GetRow("select post_text,topic_id from geobb_posts where post_id=$post_id");

		if (preg_match_all("/\[\[(\[?)(\d+)(\]?)\]\]/",$post['post_text'],$g_matches)) {
			$db->query("delete from gridimage_post where post_id=$post_id");
			foreach ($g_matches[2] as $i => $g_id) {
					
				$type = ($g_matches[1][$i])?'I':'T';
				
				$db->query("INSERT INTO gridimage_post SET gridimage_id = $g_id,post_id	= $post_id, topic_id = {$post['topic_id']},type = '$type'");
					
				
			}
		}

	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>