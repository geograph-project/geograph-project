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
	var $pendingchanges;

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
	}

	function storeInitialValues()
	{
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
	function applyTickets($ticketowner=null, $ticketid=null, $oldvalues=false)
	{
		/*
		 *   The following cases are interesting:
		 *   * Current note:
		 *     Don't apply any tickets
		 *   * Note showing all pending changes $user suggested until now
		 *     Iterate over all fields:
		 *        * Get the ticket item regarding current field and note, with status pending, ticket owner $user, having the highest ticket id
		 *          * If found: apply newvalue
		 *          * If not found: nothing to do
		 *   * Note as it was shown to the "normal user" at the time when ticket with id $ticket was created
		 *     Iterate over all fields:
		 *        * Get the ticket item regarding current field and note, with status immediate or approved, having the highest ticket id < $ticket.
		 *          * If found: apply newvalue
		 *          * If not found: we have to reconstruct the original value:
		 *            Get the ticket item regarding current field and note, with any status, having the lowest ticket id (not limited!).
		 *            * If found: apply oldvalue
		 *            * If not found: nothing to do, value never changed
		 *   * Note showing all changes $user suggested until $ticket was created by $user (we can't say "all pending changes" as we'd needed to take into account the times when the item status changed):
		 *     Iterate over all fields:
		 *        * If field changed in ticket $ticket: apply oldvalue
		 *        * Otherwise:
		 *          Get the ticket item regarding current field and note, with (ticket owner $user, any status) or (status immediate or approved) having the highest ticket id < $ticket.
		 *          * If found: apply newvalue
		 *          * If not found: we have to reconstruct the original value:
		 *            Get the ticket item regarding current field and note, with any status, having the lowest ticket id (not limited!).
		 *            * If found: apply oldvalue
		 *            * If not found: nothing to do, value never changed
		 *   * Note showing all changes $user suggested until $ticket was created by $user, including changes in that ticket :
		 *     Iterate over all fields:
		 *        * If field changed in ticket $ticket: apply newvalue
		 *        * Otherwise:
		 *          Get the ticket item regarding current field and note, with (ticket owner $user, any status) or (status immediate or approved) having the highest ticket id < $ticket.
		 *          * If found: apply newvalue
		 *          * If not found: we have to reconstruct the original value:
		 *            Get the ticket item regarding current field and note, with any status, having the lowest ticket id (not limited!).
		 *            * If found: apply oldvalue
		 *            * If not found: nothing to do, value never changed
		 *
		 *   This is:
		 *   1) No tickets                                        [applyTickets()]                             getNotes($aStatus)
		 *   2) ((pending)&&$user)                                 applyTickets($ticketowner)                  getNotes($aStatus, $ticketowner)
		 *   3) ((immediate,approved)), $ticket                    applyTickets(null,         $ticketid)       getNotes($aStatus, null,         $ticketid)
		 *   4) ((immediate,approved)||$user), $ticket, oldvalue   applyTickets($ticketowner, $ticketid, true) getNotes($aStatus, $ticketowner, $ticketid, true)
		 *   5) ((immediate,approved)||$user), $ticket, newvalue   applyTickets($ticketowner, $ticketid)       getNotes($aStatus, $ticketowner, $ticketid)
		 *
		 *  => applyTickets($ticketowner=null, $ticketid=null, $oldvalues=false)
		 *     getNotes($aStatus, $orderdesc = false, $ticketowner=null, $ticketid=null, $oldvalues=false, $noteids = null, $exclude = null, $aStatusTickets = null)
		 */
		if (is_null($ticketowner) && is_null($ticketid)) {
			return;
		}
		$db=&$this->_getDB();
		$fields = array('x1', 'x2', 'y1', 'y2', 'z', 'imgwidth', 'imgheight', 'status', 'comment');
		if (!is_null($ticketowner) && !is_null($ticketid)) {
			$valkey = $oldvalues ? 'oldvalue' : 'newvalue';
			# apply all changes of the given ticket
			$applied = array();
			# FIXME test if ticketowner matches?
			$recordSet = &$db->Execute("select ti.field,ti.newvalue,ti.oldvalue from gridimage_ticket_item ti ".
				"where ti.gridimage_ticket_id = '{$ticketid}' and ti.note_id={$this->note_id}");
			while (!$recordSet->EOF) {
				$field = $recordSet->fields['field'];
				$applied[] = $field;
				$this->$field = $recordSet->fields[$valkey];
				$recordSet->MoveNext();
			}
			$recordSet->Close();
			$fields = array_diff($fields, $applied);
		}
		if (is_null($ticketowner)) {
			$table = "gridimage_ticket_item ti";
			$where = "ti.gridimage_ticket_id<'$ticketid' and ti.status in ('immediate','approved')";
			$findold = true;
		} elseif (is_null($ticketid)) {
			$table = "gridimage_ticket_item ti inner join gridimage_ticket t using (gridimage_ticket_id)";
			$where = "t.user_id='$ticketowner' and ti.status='pending'";
			$findold = false;
		} else {
			$table = "gridimage_ticket_item ti inner join gridimage_ticket t using (gridimage_ticket_id)";
			$where = "ti.gridimage_ticket_id<'$ticketid' and (t.user_id='$ticketowner' or ti.status in ('immediate','approved'))";
			$findold = true;
		}
		foreach ($fields as $field) {
			$sql = "select ti.newvalue from $table where $where and ti.note_id='{$this->note_id}' and ti.field='$field' order by ti.gridimage_ticket_id desc limit 1"; # limit 1"; adodb adds an additional "LIMIT 1" for GetOne...
			#$value = $db->GetOne($sql);
			## $value === false -> ?
			#if (!is_null($value)) { # this test does not work...
			$row = $db->GetRow($sql);
			# $row === false -> ?
			if (count($row)) {
				$value = $row['newvalue'];
				$this->$field = $value;
				$this->pendingchanges = true;
			} elseif ($findold) {
				$sql = "select ti.oldvalue from gridimage_ticket_item ti where ti.note_id='{$this->note_id}' and ti.field='$field' order by ti.gridimage_ticket_id asc limit 1"; # limit 1";
				#$value = $db->GetOne($sql);
				## $value === false -> ?
				#if (!is_null($value)) {
				$row = $db->GetRow($sql);
				# $row === false -> ?
				if (count($row)) {
					$value = $row['oldvalue'];
					$this->$field = $value;
				}
			}
		}
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
			", status='{$this->status}'".
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
