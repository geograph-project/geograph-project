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
class RebuildAGridPrefix extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

                if (!$db->getOne("SELECT GET_LOCK('RebuildAGridPrefix',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		##################################################

		$this->Execute("CREATE TEMPORARY TABLE gridprefix_tmp
			SELECT SUBSTRING(grid_reference,1,3 - reference_index) AS prefix,
			SUM(imagecount) AS imagecount,
			SUM(has_geographs>0) AS geosquares,
			MAX(last_timestamp) as last_timestamp
		        FROM gridsquare WHERE imagecount > 0 GROUP BY prefix ORDER BY NULL");

		##################################################

		$this->Execute("UPDATE gridprefix INNER JOIN gridprefix_tmp USING (prefix)
			SET gridprefix.imagecount = gridprefix_tmp.imagecount
			, gridprefix.geosquares = gridprefix_tmp.geosquares
			, gridprefix.last_timestamp = gridprefix_tmp.last_timestamp");

		##################################################

		$db->Execute("DO RELEASE_LOCK('RebuildAGridPrefix')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
