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
class RebuildContentLocation extends EventHandler
{
	function processEvent(&$event)
	{
		//perform actions

		$db=&$this->_getDB();

                if (!$db->getOne("SELECT GET_LOCK('".get_class($this)."',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		$db->Execute("DROP TABLE IF EXISTS content_location_tmp");

		$create = "CREATE TABLE content_location_tmp (primary key (content_id,hectad)) ";
		$insert = "INSERT INTO content_location_tmp ";

		$c = 0;
		$minmax = $db->getRow("SELECT MIN(content_id) AS min,MAX(content_id) AS max FROM content");

		for($start = $minmax['min']; $start <  $minmax['max']; $start+=10000) {
			$between = "c.content_id BETWEEN ".($start)." AND ".($start+10000-1);

			$this->Execute($sql = ($c?$insert:$create)."
SELECT content_id, CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1)) AS hectad,
COUNT(DISTINCT x,y) as squares, count(gc.gridimage_id) as images, avg(gs.x) ax, avg(gs.y) ay, stddev(gs.x) sx, stddev(gs.y) sy
from content c 
	inner join gridimage_content gc using (content_id) 
	inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
WHERE $between
group by hectad,content_id 
ORDER BY NULL");
			$c++;
print "$sql;\n";
                        $this->Execute(($c?$insert:$create)."
SELECT content_id, CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1)) AS hectad,
COUNT(DISTINCT x,y) as squares, count(gc.gridimage_id) as images, avg(gs.x) ax, avg(gs.y) ay, stddev(gs.x) sx, stddev(gs.y) sy
from content c 
	inner join gridimage_post gc on (c.foreign_id = gc.topic_id and c.source IN ('themed','gallery', 'gsd') )
	inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
WHERE $between
group by hectad,content_id 
ORDER BY NULL");
			$c++;

                        $this->Execute(($c?$insert:$create)."
SELECT content_id, CONCAT(SUBSTRING(gs.grid_reference,1,LENGTH(gs.grid_reference)-3),SUBSTRING(gs.grid_reference,LENGTH(gs.grid_reference)-1,1)) AS hectad,
COUNT(DISTINCT x,y) as squares, count(gc.gridimage_id) as images, avg(gs.x) ax, avg(gs.y) ay, stddev(gs.x) sx, stddev(gs.y) sy
from content c 
	inner join gridimage_snippet gc on (c.foreign_id = gc.snippet_id and c.source = 'snippet')
	inner join gridimage_search gs on (gs.gridimage_id = gc.gridimage_id)
WHERE $between
group by content_id, hectad
ORDER BY NULL");
			$c++;

		}

		$this->Execute("CREATE TABLE IF NOT EXISTS content_location LIKE content_location_tmp");
		$this->Execute("REPLACE INTO content_location SELECT * FROM content_location_tmp");		

                $db->Execute("DO RELEASE_LOCK('".get_class($this)."')");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
