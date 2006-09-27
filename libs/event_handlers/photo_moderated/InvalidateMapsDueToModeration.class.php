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
* handles the photo_moderated event and maintains a list of
* recently moderated pictures for use in aiding display of recent pictures
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/

require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class InvalidateMapsDueToModeration extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();
		
		list($gridimage_id,$updatemaps) = explode(',',$event['event_param']);
		
		//invalidate any cached maps (on anything except rejecting a pending image)
		if ($updatemaps) {
			require_once('geograph/mapmosaic.class.php');
			$mosaic=new GeographMapMosaic;

			list($x,$y,$user_id) = $db->getRow("select x,y,user_id from gridimage inner join gridsquare using (gridsquare_id) where gridimage_id = $gridimage_id");
			
			$mosaic->expirePosition($x,$y,$user_id);
		}
		
		//update placename cached column
			//we can disable this for now as placename_id is unused
			//to reable, will need to load up a gridsquare _with_ nateastings (a square created by gridimage)
		#todo $this->updatePlaceNameId($newsq);
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>