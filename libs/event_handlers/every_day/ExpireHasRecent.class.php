<?php
/**
 * $Project: GeoGraph $
 * $Id: RebuildUserStats.class.php 3288 2007-04-20 11:32:27Z barry $
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
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 3288 $
*/

require_once("geograph/eventhandler.class.php");

//filename of class file should correspond to class name, e.g.  myhandler.class.php
class ExpireHasRecent extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

                if (!$db->getOne("SELECT GET_LOCK('ExpireHasRecent',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		##################################################

		//we can't optimize this query using date filters etc, as whole point of ths is to catch squares NOT updated
			//last_timestamp is updated sometimes, even if no submissions (so can't use it it identify squares NOT updated!)
		$this->Execute("CREATE TEMPORARY TABLE no_recent (primary key (gridsquare_id))
			SELECT gridsquare_id,IF(SUM(imagetaken > DATE(DATE_SUB(NOW(), INTERVAL 5 YEAR)) AND moderation_status='geograph')>0,1,0) AS has_recent
			FROM gridsquare INNER JOIN gridimage_search USING (grid_reference)
			WHERE has_recent=1
			GROUP BY grid_reference HAVING has_recent = 0
			ORDER BY gridsquare_id");

		##################################################

		$this->Execute("
			UPDATE gridsquare INNER JOIN no_recent USING (gridsquare_id)
			SET gridsquare.has_recent = no_recent.has_recent");

		##################################################

		$db->Execute("DO RELEASE_LOCK('ExpireHasRecent')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
