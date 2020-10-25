<?php
/**
 * $Project: GeoGraph $
 * $Id: InvalidateOldAndNewMaps.class.php 8910 2019-03-02 20:12:18Z barry $
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

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class InvalidateOldAndNewMaps extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();

		list($gridimage_id,$old_grid_reference,$new_grid_reference) = explode(',',$event['event_param']);

		//invalidate any cached maps
		require_once('geograph/mapmosaic.class.php');
		$mosaic=new GeographMapMosaic;

		$user_id = $db->getOne("select user_id from gridimage where gridimage_id = $gridimage_id");

		extract($db->getRow("select x,y from gridsquare where grid_reference = '$old_grid_reference'"),
			 EXTR_PREFIX_INVALID, 'numeric'); //need to cope with row being either Assoc or Both. Can't assume with be Both. But can assume not Num only.
		$mosaic->expirePosition($x,$y,$user_id);

		extract($db->getRow("select x,y from gridsquare where grid_reference = '$new_grid_reference'"),
			EXTR_PREFIX_INVALID, 'numeric');
		$mosaic->expirePosition($x,$y,$user_id);

		//update placename cached column
			//we can disable this for now as placename_id is unused
			//to reable, will need to load up a gridsquare _with_ nateastings (a square created by gridimage)
		#todo $this->updatePlaceNameId($newsq);

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}

