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
* Provides the ImageList class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**
* ImageList class
* Provides facilities for building a list of GridImage instances
* The resulting list is easily attached to Smarty for display
*/
class ImageList
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* array of GridImage instances
	*/
	var $images=array();


	/**
	* constructor - can be used to build a basic list (See getImages)
	*/
	function ImageList($statuses=null, $sort=null, $count=null)
	{
		if (!is_null($statuses))
			$this->getImages($statuses, $sort, $count);
	}
	
	/**
	* build a basic image list from basic criteria
	* @param statuses - either an array of statuses or a single status (pending, rejected or accepted)
	* @param sort - optional sort field and direction, e.g. submitted desc
	* @param count - optional upper limit on images returned
	*/
	function getImages($statuses, $sort=null, $count=null)
	{
		$db=&$this->_getDB();
		
		//we accept an array or a single status...
		if (is_array($statuses))
			$statuslist="'".implode("','", $statuses)."'";
		else
			$statuslist="'$statuses'";
		
		if (is_null($sort))
			$orderby="";
		else
			$orderby="order by $sort";
		
		if (is_null($count))
			$limit="";
		else
			$limit="limit $count";
		
		//lets find some recent photos
		$this->images=array();
		$i=0;
		$recordSet = &$db->Execute("select gridimage.*,user.realname ".
			"from gridimage ".
			"inner join user using(user_id) ".
			"where moderation_status in ($statuslist) ".
			"$orderby $limit");
		while (!$recordSet->EOF) 
		{
			$this->images[$i]=new GridImage;
			$this->images[$i]->loadFromRecordset($recordSet);
			$recordSet->MoveNext();
			$i++;
		}
		$recordSet->Close(); 
		
		return $i;
	}
	
	/**
	 * store image list as $basename in $smarty instance
	 * a $basenamecount field is also stored
	 */
	function assignSmarty(&$smarty, $basename)
	{
		$smarty->assign_by_ref($basename, $this->images);		
		$smarty->assign($basename.'count', count($this->images));
	}
	
	/**
	 * get stored db object, creating if necessary
	 * @access private
	 */
	function &_getDB()
	{
		if (!is_object($this->db))
			$this->db=NewADOConnection($GLOBALS['DSN']);
		if (!$this->db) die('Database connection failed');  
		return $this->db;
	}

	/**
	 * set stored db object
	 * @access private
	 */
	function _setDB(&$db)
	{
		$this->db=$db;
	}
	
	
}
?>
