<?php
/**
 * $Project: GeoGraph $
 * $Id$
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

/**
* handles the new_topic event and performs any necessary
* updates to gridsquare_topic table
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateDiscussionCrossReferencesWithDeletedTopic extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();
		
		$topic_id = $event['event_param'];
		
		//delete any smarty caches for this square
		$gridsquare_id = $db->getOne("select gridsquare_id from gridsquare_topic where topic_id = $topic_id");
		if ($gridsquare_id) {
			$images = $db->getCol("select gridimage_id from gridimage where gridsquare_id = $gridsquare_id");
			$smarty = new GeographPage;

			foreach ($images as $gridimage_id) {
				//clear any caches involving this photo
				$ab=floor($gridimage_id/10000);
				$smarty->clear_cache(null, "img$ab|{$gridimage_id}");
			}

			// -trash any mappings to this topic
			$db->Execute("delete from gridsquare_topic where topic_id = $topic_id");
		}
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>