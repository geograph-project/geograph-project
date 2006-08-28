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
require_once("geograph/gridsquare.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class UpdateWordnetOnUpdateOfTitle extends EventHandler
{
	function processEvent(&$event)
	{
		
		list ($gridimage_id,$field) = explode(',', $event['event_param']);
		
		if ($field == 'title')
		{
			$db=&$this->_getDB();
		
			$db->Execute("DELETE FROM wordnet1 WHERE gid = $gridimage_id");
			$db->Execute("DELETE FROM wordnet2 WHERE gid = $gridimage_id");
			$db->Execute("DELETE FROM wordnet3 WHERE gid = $gridimage_id");
		
			require_once('geograph/wordnet.inc.php');

			$title = $db->getOne("select title from gridimage where gridimage_id = $gridimage_id");


			updateWordnet($db,$title,'title',$gridimage_id);
		}
		
		
		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
	
}
?>