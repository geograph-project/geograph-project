<?php
/**
 * $Project: GeoGraph $
 * $Id: UpdateThumbnailCrossReferencesWithEditedReply.class.php 1647 2005-12-16 04:26:09Z barryhunter $
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
* @version $Revision: 1647 $
*/

require_once("geograph/eventhandler.class.php");
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class InvalidateMemcacheWithEditedReply extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions
		
		global $memcache;
		$mkey = $event['event_param'];
		
		//fails quickly if not using memcached!
		$memcache->name_delete('fp',$mkey);
	
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>