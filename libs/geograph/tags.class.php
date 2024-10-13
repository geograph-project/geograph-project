<?php
/**
 * $Project: GeoGraph $
 * $Id: tags.class.php 8496 2017-05-20 14:04:28Z barry $
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
* @version $Revision: 8496 $
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

	function getTagId($tag, $auto_create = true) {
		global $USER;

		$db = $this->_getDB(false);

                $prefix = '';
                if (strpos($tag,':') !== FALSE) {
                        list($prefix,$tag) = explode(':',$tag,2);
                }
                $u = array();
                $u['tag'] = str_replace('\\','',$tag);
                $u['prefix'] = trim(strtolower($prefix));

                $u['tag'] = trim(preg_replace('/[ _]+/',' ',$u['tag']));
                $u['tag'] = str_replace("'",'',$u['tag']);

                if (!($tag_id= $db->getOne("SELECT tag_id FROM tag WHERE tag=".$db->Quote($u['tag'])." AND prefix=".$db->Quote($u['prefix'])) ) && $auto_create) {

                        $u['user_id'] = $USER->user_id;

                        $db->Execute('INSERT INTO tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?',array_values($u));
                        $tag_id = $db->Insert_ID();
                }

		return $tag_id;
	}

#################################################

	function addTag($tag,$prefix='') {
		if (!empty($prefix)) {
	                $prefix = trim(strtolower($prefix));
			$tag = preg_replace('/^([\w ]+:)?/',"$prefix:",$tag);
		}

		if (!isset($this->tags[$tag])) //dont want to overright if already there
			$this->tags[$tag]=0;
	}

	//a specific function - so could hand special prefixes etc.
	function addSubject($tag,$prefix='subject') {
		$this->addTag($tag,$prefix);
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

		@$this->images[$gid]++;
	}
	function addImage($gid) {
		@$this->images[$gid]++;
	}

#################################################

	function _populateTagIds($create = true) {
		global $USER;

		$db = $this->_getDB(true);

		$lookup = array();
		foreach ($this->tags as $tag => $tag_id) {
			if (empty($tag_id)) {
				$tag = str_replace('\\','',$tag);
				$bits = explode(':',$tag,2);
				if (count($bits) == 2) {
					$lookup[] = $db->Quote(trim($bits[1]));
				} else {
					$lookup[] = $db->Quote(trim($tag));
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
				foreach ($this->tags as $tag => $tag_id) {
					if (empty($tag_id)) {
						//reuse getTagId, which WILL create the tag, if doesnt find it. Ineffient as it tries lookup up the tag AGAIN, but saves duplicating code here
						//AND sorts out a bug, that the above optimised lookup fails when case of the tags dont match (eg some subjects)
						//... not a big deal, because only will be called, when tag really doesnt exist OR, tag case doesnt match
						$this->tags[$tag] = $this->getTagId($tag, $create);
					}
				}
			}
		}
	}

#################################################

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

				$db->Execute('INSERT INTO gridimage_tag SET created=NOW(),`'.implode('` = ?, `',array_keys($u)).'` = ?  ON DUPLICATE KEY UPDATE status = '.$u['status'],array_values($u));

		if (empty($tag_id)) {
			ob_start();
                        debug_print_backtrace();
                        print_r($_POST);
			print_r($USER->user_id);
                        $con = ob_get_clean();
                        debug_message('[Geograph] TAG FAILED '.date('r'),$con);
		}


				$total++;
			}
		}
		return $total;
	}

#################################################
	//adds a tag to multiple images, uses gridimage_search, so only adds to live images, as well as possible to filter to user!

	function multiCommit($gids, $tag_id, $user_id, $onlymine = true, $status = 2) {
		$db = $this->_getDB(false);

		$tag_id = intval($tag_id);
		$user_id = intval($user_id);
		$status = intval($status);

		$where = array();
		$where[] = "gridimage_id IN (".implode(',',array_map('intval',$gids)).")";
		if ($onlymine)
			$where[] = "user_id = $user_id";

		$where = implode(" AND ",$where);
		$sql = "INSERT INTO gridimage_tag
		SELECT gridimage_id,$tag_id AS tag_id,$user_id AS user_id,NOW() as created,$status AS status,NOW() AS updated
		FROM gridimage_search WHERE $where ON DUPLICATE KEY UPDATE status = $status";

		$this->db->Execute($sql);
		return $this->db->Affected_Rows();
	}

#################################################
	//this special function will blindly add/remove tag to image
	// most functions only allow the contributor to add a tag. This is used by mods to add/remove tag

	function commitAdminTag($status, $tag_id, $gridimage_id, $user_id = 0) {
		$db = $this->_getDB(false);

		if ($status) {
			$u = array();
			$u['tag_id'] = $tag_id;
	                $u['user_id'] = $user_id;
                        $u['gridimage_id'] = $gridimage_id;
        	        $u['status'] = intval($status);

			$db->Execute('INSERT INTO gridimage_tag SET created=NOW(), `'.implode('` = ?, `',array_keys($u)).'` = ?  ON DUPLICATE KEY UPDATE status = '.$u['status'],array_values($u));
		} else {
			//for deleting, specifically removing public tags (whoever created it!) - ignores user_id
			$db->Execute("UPDATE gridimage_tag SET status=0 WHERE status=2 AND gridimage_id=".intval($gridimage_id)." AND tag_id=".intval($tag_id));
		}
	}

#################################################

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

	function assignSubjectSmarty($smarty, $name = 'subjects') {
		$db = $this->_getDB(true);

		$subjects = $db->getCol("SELECT LOWER(subject) AS subject FROM subjects ORDER BY subject");

		$smarty->assign($name, array_combine($subjects,$subjects) );
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
