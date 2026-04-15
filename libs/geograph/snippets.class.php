<?php
/**
 * $Project: GeoGraph $
 * $Id: snippets.class.php 8496 2017-05-20 14:04:28Z barry $
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
* Provides the Snippets class
*
* @package Geograph
* @author Barry Hunter <geo@barryhunter.co.uk>
* @version $Revision: 8496 $
*/

/**
* Snippets class
*/
class Snippets
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* array of snippets
	*/
	var $snippets=array();

	/**
	* array of images
	*/
	var $images=array();

#################################################

	function addSnippet($snippet_id) {
		$this->snippets[$snippet_id]=true;
	}

	function addSnippets($snippets) {
		if (!is_array($snippets)) {
			return false;
		}

		foreach ($snippets as $key => $val) {
			// 1. If the value is boolean true, the ID is definitely the key
		        if ($val === true) {
		            $this->addSnippet($key);
		        // 2. If the key is a string (like "8"), it's an associative ID
		        } elseif (is_string($key) && is_numeric($key)) {
		            $this->addSnippet($key);
		        // 3. Otherwise, assume it's a standard list and the value is the ID
		        } else {
		            $this->addSnippet($val);
		        }
		}
	}

	function addUploadImage($upload_id,$user_id) {
		$gid = crc32($upload_id)+4294967296;
		$gid += $user_id * 4294967296;
		$gid = sprintf('%0.0f',$gid);

		@$this->images[$gid]++;
	}
	function addImage($gid) {
		@$this->images[$gid]++;
	}

#################################################

	function commit($gid = 0, $public = true) {
		global $USER;

		if (!empty($gid)) {
			$this->addImage($gid);
		}

		if (empty($this->snippets) || empty($this->images)) {
			return false;
		}

		$db = $this->_getDB(false);

		$u = array();
		$u['user_id'] = $USER->user_id;
		$u['status'] = 2; //only support public snippets

		$total = 0;
		foreach ($this->images as $gid => $dummy) {

			$u['gridimage_id'] = $gid;

			foreach ($this->snippets as $snippet_id => $dummy) {

				$u['snippet_id'] = $snippet_id;

				$db->Execute('INSERT INTO gridimage_snippet_real SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?  ON DUPLICATE KEY UPDATE status = '.$u['status'],array_values($u));
				$total++;
			}
		}
		return $total;
	}

#################################################

	function promoteUploadSnippets($gridimage_id,$upload_id,$user_id) {

		$db = $this->_getDB(false);

		//assign the snippet now we know the real id.
		$gid = crc32($upload_id)+4294967296;
		$gid += $user_id * 4294967296;
		$gid = sprintf('%0.0f',$gid);

		$this->db->Execute($sql = "UPDATE gridimage_snippet_real SET gridimage_id = $gridimage_id WHERE gridimage_id = ".$gid);
	}

#################################################

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
