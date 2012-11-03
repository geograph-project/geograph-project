<?php
/**
 * $Project: GeoGraph $
 * $Id: gridimage.class.php 5653 2009-08-10 18:43:17Z hansjorg $
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
* Provides the GridImageNotes class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision: 5653 $
*/

/**
* GridImageNote class
* Provides an abstraction of a grid image annotation
*/
class GridImageNote
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* note id
	*/
	var $note_id;

	/**
	* image id
	*/
	var $gridimage_id;

	/**
	* status - 'pending', 'visible', 'deleted'
	*/
	var $status;

	/**
	* true if also pending trouble tickets have been used to create this object
	*/
	var $pendingchanges; // FIXME TODO

	#/**
	#* note title
	#*/
	#var $title;

	/**
	* note comment
	*/
	var $comment;

	#/**
	#* note title (language 2)
	#*/
	#var $title2;

	#/**
	#* note comment (language 2)
	#*/
	#var $comment2;

	/**
	* box coordinates
	*/
	var $x1;
	var $y1;
	var $x2;
	var $y2;
	var $init_x1;
	var $init_y1;
	var $init_x2;
	var $init_y2;

	/**
	* image size these coordinates refer to
	*/
	var $imgwidth;
	var $imgheight;
	var $init_imgwidth;
	var $init_imgheight;

	/**
	* z index
	*/
	var $z;

	/**
	* constructor
	*/
	function GridImageNote($id = null)
	{
		if (!empty($id)) {
			$this->loadFromId($id);
		}
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
	
	/**
	* assign members from array containing required members
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
		}
		$this->init_x1 = $this->x1;
		$this->init_y1 = $this->y1;
		$this->init_x2 = $this->x2;
		$this->init_y2 = $this->y2;
		$this->init_imgwidth  = $this->imgwidth;
		$this->init_imgheight = $this->imgheight;
	}

	/**
	* create new note
	* returns 0 on error, note_id on success
	*/
	function create($gridimage_id, $x1, $x2, $y1, $y2, $imgwidth, $imgheight, $comment, $z = 0, $status = 'pending')
	{
		$this->_clear();
		$this->x1 = $x1;
		$this->x2 = $x2;
		$this->y1 = $y1;
		$this->y2 = $y2;
		$this->z  = $z;
		$this->imgwidth  = $imgwidth;
		$this->imgheight = $imgheight;
		$this->comment = $comment;
		$this->status = $status;
		$this->gridimage_id = $gridimage_id;

		$db =& $this->_getDB();

		$sql = sprintf("insert into gridimage_notes (".
			"gridimage_id, status, comment, x1, x2, y1, y2, z, imgwidth, imgheight".
			") values (".
			"%d, %s, %s, %d, %d, %d, %d, %d, %d, %d".
			")",
			$gridimage_id, $db->Quote($status), $db->Quote($comment), $x1, $x2, $y1, $y2, $z, $imgwidth, $imgheight);
		
		//$db->Query($sql);
		if($db->Execute($sql) === false) {
			return 0;
		}
		// FIXME error handling
		
		//get the id
		$note_id = $db->Insert_ID();
		if ($note_id === false) {
			return 0;
		}
		$this->note_id = $note_id;

		$this->init_x1 = $this->x1;
		$this->init_y1 = $this->y1;
		$this->init_x2 = $this->x2;
		$this->init_y2 = $this->y2;
		$this->init_imgwidth  = $this->imgwidth;
		$this->init_imgheight = $this->imgheight;

		$this->pendingchanges = false;
		return $note_id;
	}

	/**
	* return true if instance references a valid grid image
	*/
	function isValid()
	{
		return isset($this->note_id) && ($this->note_id>0);
	}

	/**
	* return comment as formatted html
	*/
	function html($substlinks=true)
	{
		$comment = htmlentities2($this->comment);
		if ($substlinks) {
			$comment = preg_replace('/\n/','<br />', $comment);
			$comment = preg_replace('/\[\[(\d+)\]\]/','<a href="/photo/\\1">[[\\1]]</a>', $comment); # TODO add image title
			$comment = preg_replace('/\[\[([a-zA-Z]{1,3}\d+)\]\]/','<a href="/gridref/\\1">[[\\1]]</a>', $comment);
			# TODO http://XXXX
		}
		return $comment;
	}

	/**
	* assign members from recordset containing required members
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		$this->pendingchanges = false;
		return $this->isValid();
	}

	/**
	* apply pending tickets
	 */
	function applyTickets($ticketowner)
	{
		$db=&$this->_getDB();
		$recordSet = &$db->Execute("select ti.field,ti.newvalue from gridimage_ticket t inner join gridimage_ticket_item ti using(gridimage_ticket_id) ".
			"where t.gridimage_id={$this->gridimage_id} and t.user_id={$ticketowner} and t.status!='closed' and ti.note_id={$this->note_id} and ti.status='pending' ".
			"order by gridimage_ticket_id asc");
		while (!$recordSet->EOF) {
			$this->pendingchanges = true;
			$field = $recordSet->fields['field'];
			$this->$field = $recordSet->fields['newvalue'];
			$recordSet->MoveNext();
		}
		$recordSet->Close();
	}

	/**
	* calculate positions for scaled image
	*/
	function calcSize($width, $height)
	{
		$this->x1 = (int) ($this->x1*$width  / $this->imgwidth);
		$this->x2 = (int) ($this->x2*$width  / $this->imgwidth);
		$this->y1 = (int) ($this->y1*$height / $this->imgheight);
		$this->y2 = (int) ($this->y2*$height / $this->imgheight);
		$this->imgwidth = $width;
		$this->imgheight = $height;
	}

	/**
	* assign members from note_id
	*/
	function loadFromId($note_id)
	{
		//todo memcache
		
		$db=&$this->_getDB();
		
		$this->_clear();
		if (preg_match('/^\d+$/', $note_id))
		{
			$row = &$db->GetRow("select * from gridimage_notes where note_id={$note_id} limit 1");
			if (is_array($row))
			{
				$this->_initFromArray($row);
				$this->pendingchanges = false;
			}
		}
		//todo memcache (probably make sure dont serialise the dbs!) 
		
		return $this->isValid();
	}

	/**
	 * clear all member vars
	 * @access private
	 */
	function _clear()
	{
		$vars=get_object_vars($this);
		foreach($vars as $name=>$val)
		{
			if ($name!="db")
				unset($this->$name);
		}
	}

	/**
	* Saves selected members to the gridimage_notes record
	*/
	function commitChanges($fire_event = false)
	{
		$db=&$this->_getDB();
		
		$sql="update gridimage_notes set comment=".$db->Quote($this->comment).
			", status='{$this->status}'". # FIXME?
			", x1='{$this->x1}'".
			", x2='{$this->x2}'".
			", y1='{$this->y1}'".
			", y2='{$this->y2}'".
			", z='{$this->z}'".
			", imgwidth='{$this->imgwidth}'".
			", imgheight='{$this->imgheight}'".
			" where note_id = '{$this->note_id}'";
		$db->Execute($sql);

		if ($fire_event) {
			require_once('geograph/event.class.php');
			new Event(EVENT_UPDATEDPHOTO, "{$this->gridimage_id}");
		}
	}

}

?>
