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
* handles the photo_moderated event and maintains a list of
* recently moderated pictures for use in aiding display of recent pictures
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

/*
create table recent_gridimage
(
	gridimage_id int not null,
	added datetime,
	primary key(gridimage_id)
);
*/

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class MaintainRecentlyModeratedList extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();
		
		//get moderation status of image
		list($gridimage_id,$updatemaps) = explode(',',$event['event_param']);
		
		$status=$db->GetOne("select moderation_status from gridimage where gridimage_id='$gridimage_id'");
		if ($status == 'geograph')
		{
			//add this image to the list
			$db->Execute("replace into recent_gridimage(gridimage_id, added) values ('$gridimage_id', now())");
			
			//get the date for the 100th image
			$oldest=$db->GetRow("select * from recent_gridimage order by added desc limit 100,1");
			if ($oldest)
			{
				//delete anything older
				$oldest=$db->GetOne("delete from recent_gridimage where added<'{$oldest['added']}'");
			
			}
		
			
			//delete the 101st image from the list
		}
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>