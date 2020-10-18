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
class ClearSmartyCache extends EventHandler
{
	function processEvent(&$event)
	{
		//this table MAY be in other database, cant reuse this->_getDB() - although could perhaps use processor->logdb (which might be db2 too!)
		$db = NewADOConnection(!empty($GLOBALS['DSN2'])?$GLOBALS['DSN2']:$GLOBALS['DSN']);

                if (!$db->getOne("SELECT GET_LOCK('".get_class($this)."',10)")) {
                        //only execute if can get a lock
                        $this->_output(2, "Failed to get Lock");
                         return false;
                }

		$db->Execute("drop table if exists smarty_cache_page2");
		$db->Execute("rename table smarty_cache_page to smarty_cache_page2");
		$db->Execute("create table smarty_cache_page like smarty_cache_page2");
		$db->Execute("insert into smarty_cache_page select * from smarty_cache_page2 where Expire > UNIX_TIMESTAMP(NOW())");

		$db->Execute("DO RELEASE_LOCK('".get_class($this)."')");

		return true;
	}
}
