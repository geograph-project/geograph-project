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
class RebuildSnippetDuplicate extends EventHandler
{
	function processEvent(&$event)
	{
		global $CONF;

		$db=&$this->_getDB();

		$db->Execute("create temporary table snippet_dup (unique index (title))
		select title,count(*) as has_dup from snippet where enabled=1 group by regexp_replace(title,'[^\\\\w]+',' ') having has_dup>1");

		$db->Execute("update snippet inner join snippet_dup using (title) set snippet.has_dup = snippet_dup.has_dup, updated=updated");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}


