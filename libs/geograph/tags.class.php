<?php
/**
 * $Project: GeoGraph $
 * $Id$
 * 
 * GeoGraph geographic photo archive project
 * http://geograph.sourceforge.net/
 *
 * This file copyright (C) 2011 Barry Hunter (geo@barryhunter.co.uk)
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
* Provides the Tags class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision$
*/

/**
* Tags class
*/
class Tags
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* array of tags
	*/
	var $tags=array();


	/**
	* array of images
	*/
	var $images=array();


	/**
	* constructor 
	*/
	function Tags()
	{
		
	}
	
	function addTag($tag,$prefix='') {
		if (!empty($prefix)) {
			$tag = preg_replace('/^([\w ]+:)?/',"$prefix:",$tag);
		}
		
		if (!isset($this->tags[$tag])) //dont want to overright if already there
			$this->tags[$tag]=0;
	}
	function addTags($tags,$prefix='') {
		if (!is_array($tags)) {
			return false;
		}
		foreach ($tags as $tag) {
			$this->addTag($tag,$prefix);
		}
	}
	
	function addUploadImage($upload_id,$user_id) {
		$gid = crc32($upload_id)+4294967296;
		$gid += $user_id * 4294967296;
		$gid = sprintf('%0.0f',$gid);
	
		$this->images[$gid]++;
	}
	function addImage($gid) {
		$this->images[$gid]++;
	}
	
	
	function _populateTagIds($create = true) {
		global $USER;
		
		$db = $this->_getDB(true);
		
		$lookup = array();
		foreach ($this->tags as $tag => $tag_id) {
			if (empty($tag_id)) {
				$bits = explode(':',$tag,2);
				if (count($bits) == 2) {
					$lookup[] = $db->Quote($bits[1]);	
				} else {
					$lookup[] = $db->Quote($tag);
				}
			}
		}

		if (!empty($lookup)){
			
			$tags = $db->getAssoc("SELECT tag_id,prefix,tag FROM tag WHERE tag IN (".implode(',',$lookup).")");

			foreach ($tags as $tag_id => $row) {
				if (!empty($row['prefix'])) {
					$tag = $row['prefix'].':'.$row['tag'];
				} else {
					$tag = $row['tag'];
				}

				if (isset($this->tags[$tag])) {
					$this->tags[$tag] = $tag_id;
				}
			}
			
			if ($create) {
				$u = array();
				$u['user_id'] = $USER->user_id;
		
				foreach ($this->tags as $tag => $tag_id) {
					if (empty($tag_id)) {
						
						$db = $this->_getDB(false); //very quick if we already have a connection
						
						$u['tag'] = $tag;
						$bits = explode(':',$u['tag']);
						if (count($bits) > 1) {
							$u['prefix'] = trim($bits[0]);
							$u['tag'] = $bits[1];
						} else {
							$u['prefix'] = '';
						}
						$u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));

						$db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));

						$this->tags[$tag] = mysql_insert_id();
					}
				}
			}
		}
	}
	
	function commit($gid = 0, $public = false) { //todo, more granular control over this?
		global $USER;
		
		if (!empty($gid)) {
			$this->addImage($gid);
		}
		
		if (empty($this->tags) || empty($this->images)) {
			return false;
		}
		
		$this->_populateTagIds(true);
		
		$db = $this->_getDB(false);
		
		$u = array();
		$u['user_id'] = $USER->user_id;
		$u['status'] = $public?2:1;

		$total = 0;
		foreach ($this->images as $gid => $dummy) {
			
			$u['gridimage_id'] = $gid;
			
			foreach ($this->tags as $tag => $tag_id) {
			
				$u['tag_id'] = $tag_id;
				
				$db->Execute('INSERT IGNORE INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
				$total++;
			}
		}
		return $total;
	}
	
	
	
	
	
	
	
	
	function promoteUploadTags($gridimage_id,$upload_id,$user_id) {
		
		$db = $this->_getDB(false);
		
		//assign the tags now we know the real id. 
		$gid = crc32($upload_id)+4294967296;
		$gid += $user_id * 4294967296;
		$gid = sprintf('%0.0f',$gid);
		
		$this->db->Execute($sql = "UPDATE gridimage_tag SET gridimage_id = $gridimage_id WHERE gridimage_id = ".$gid);
	}
	
	
	function assignPrimarySmarty($smarty, $name = 'tops') {
		$db = $this->_getDB(true);
				
		$tops = array();
		$list = $db->getAll("SELECT * FROM category_primary ORDER BY `sort_order`");
		foreach ($list as $line) {
			$line['description'] = preg_replace('/\|.*/','',$line['description']);

			$tops[$line['grouping']][] = $line;
		}
		$smarty->assign_by_ref($name,$tops);
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
	function &_getDB($allow_readonly = false)
	{
		//check we have a db object or if we need to 'upgrade' it
		if (!is_object($this->db) || ($this->db->readonly && !$allow_readonly) ) {
			$this->db=GeographDatabaseConnection($allow_readonly);
		}
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
