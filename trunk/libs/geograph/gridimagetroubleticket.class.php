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
* Provides the GridImageTroubleTicket class
*
* @package Geograph
* @author Paul Dixon <paul@elphin.com>
* @version $Revision$
*/

/**

moderated changes system 

the principle here is the a picture owner can make immediate changes
a moderator can make immediate changes, and anyone else can make
moderated changes. In addition, a moderator can "approve" moderated changes

owner: moderated: gridref (and picture in future) unmoderated: everything else
moderator: unmoderated: everything
other user: moderated: everything

Every unmoderated edits can be tracked with this system as a changelog feature


create table gridimage_ticket
(
	gridimage_ticket_id int not null auto_increment,

	gridimage_id int,
	suggested datetime,
	user_id int,
	moderator_id int,

	updated datetime,
	status enum('pending', 'open', 'closed'),
	
	notes text,
	
	primary key(gridimage_ticket_id),
	index(gridimage_id)
);

create table gridimage_ticket_item
(
	gridimage_ticket_item_id int not null auto_increment,

	gridimage_ticket_id int not null,

	approver_id int,
	
	
	field varchar(64),
	oldvalue text,
	newvalue text,
	
	status enum('pending', 'immediate', 'approved', 'rejected'),
	
	primary key(gridimage_ticket_item_id),
	index(gridimage_ticket_id)
);

create table gridimage_ticket_comment
(
	gridimage_ticket_comment_id int not null auto_increment,
	gridimage_ticket_id int not null,
	user_id int,
	comment text,
	added  datetime,
	primary key(gridimage_ticket_comment_id),
	index(gridimage_ticket_id)
);


*/

class GridImageTroubleTicket
{
	/**
	* internal db handle
	*/
	var $db;

	/**
	* ticket id
	*/
	var $gridimage_ticket_id;

	/**
	* associated image
	*/
	var $gridimage_id;

	/**
	* suggested time
	*/
	var $suggested;

	/**
	* suggesting user
	*/
	var $user_id;

	/**
	* handling moderator
	*/
	var $moderator_id;

	/**
	* ticket update time
	*/
	var $updated;


	/**
	* status
	*/
	var $status;

	/**
	* moderator notes
	*/
	var $notes;

	/**
	* commit count - number of changes yet to be commit
	*/
	var $commit_count;
	
	/**
	* array of gridimage_ticket_item records yet to be written
	*/
	var $changes=array();
	
	

	/**
	* array of comment records, including realname
	*/
	var $comments=array();
	
	/**
	* Constructor, call load a tick if given an ID, otherwise prepares a new ticket
	* (without committng anything to the database)
	* @access public
	*/
	function GridImageTroubleTicket($id=null)
	{	
		if (!is_null($id))
		{
			$this->loadFromId($id);
		}
		else
		{
			$this->_newTicket();
		}
		
	}
	
	/**
	* Clears the instance in preparation for a new ticket 
	* @access private
	*/
	function _newTicket()
	{
	 	$this->_clear();
	 	$this->gridimage_ticket_id=0;
	 	$this->gridimage_id=0;
	 	$this->suggested=strftime("%Y-%m-%d %H:%M:%S", time());
	 	$this->user_id=0;
	 	$this->updated=$this->suggested;
	 	$this->status="pending";
	 	$this->notes="";
	 	
	 	
	}

	/**
	* set user id of suggesting user
	* @access public
	*/
	function setSuggester($user_id)
	{
		$this->user_id=intval($user_id);
	}

	/**
	* set user id of moderating user
	* @access public
	*/
	function setModerator($user_id)
	{
		$this->moderator_id=intval($user_id);
	}
	
	/**
	* set image
	* @access public
	*/
	function setImage($gridimage_id)
	{
		$this->gridimage_id=intval($gridimage_id);
	}
	
	/**
	* set moderator notes
	* @access public
	*/
	function setNotes($notes)
	{
		$this->notes=$notes;
	}
	
	/**
	* Updates a given field of the image, holding it for moderation if necessary
	* A series of calls to this function should be followed up with a call
	* to commit(), which persist the ticket and any unmoderated changes
	* @access public
	*/
	function updateField($fieldname, $oldvalue, $newvalue, $moderated)
	{
		//no change?
		if ($oldvalue==$newvalue)
			return $ok;
	
		if (!$moderated)
		{
			//make the changes right away...
			$img=&$this->_getImage();
			$img->$fieldname=$newvalue;
			
			if ($fieldname=="grid_reference")
			{
				$ok=$img->reassignGridsquare($newvalue, $err);
				if ($ok)
				{
					$this->commit_count++;
				} 
				else 
				{
					die("Sorry, wasn't expecting reassignGridsquare to fail ($err) please contact us for assistance");
				}
			}
			elseif ($fieldname=="photographer_gridref")
			{
				//need to parse value for nat coords
				$sq=new GridSquare;
				if ($sq->setByFullGridRef($newvalue,true))
				{
					$img->viewpoint_eastings=$sq->nateastings;
					$img->viewpoint_northings=$sq->natnorthings;
					$this->commit_count++;
				} elseif(empty($newvalue)) {
					// we are setting to 'blank'
					$img->viewpoint_eastings = 0;
					$img->viewpoint_northings = 0;
					$this->commit_count++;
				}
				
			}
			else
			{
				if ($fieldname=='title')
				{
					//fire an event (only title is relevent to update wordnet)
					require_once('geograph/event.class.php');
					new Event(EVENT_UPDATEDPHOTO, "{$img->gridimage_id},title");
				}
				
				$img->$fieldname=$newvalue;
				
				//we'll do this commit later
				$this->commit_count++;
				
			}
			
			$status="immediate";
			$approver_id=$this->user_id;
		}
		else
		{
			$status="pending";
			$approver_id=0;
		}
		
		//have we already got a change record?
		$found=false;
		foreach($this->changes as $c)
		{
			if ($c['field']==$fieldname)
				$found=true;
		}
		
		if (!$found)
		{
			//create a change record
			$change=array(
				"field"=>$fieldname,
				"oldvalue"=>$oldvalue,
				"newvalue"=>$newvalue,
				"status"=>$status,
				"approver_id"=>$approver_id
			);
			
			$this->changes[]=$change;
		}
	}
	
	/**
	* Updates the ticket and commits any necessary changes
	* @access public
	*/
	function commit($ticket_status=null)
	{
		$db=&$this->_getDB();
		
		//commit changes to image
		if ($this->commit_count)
		{
			$img=&$this->_getImage();
			$img->commitChanges();
			$this->commit_count=0;
			
		}
		
		//write a trouble ticket
		$this->updated=strftime("%Y-%m-%d %H:%M:%S", time());
		
		$newticket=$this->gridimage_ticket_id==0;
		if ($newticket)
		{
			//new ticket
			$sql=sprintf("insert into gridimage_ticket(gridimage_id, suggested, updated,user_id, moderator_id,status, notes) ".
				"values(%d, '%s', '%s', %d, %d, '%s', '%s')",
				$this->gridimage_id,
				$this->suggested,
				$this->updated,
				$this->user_id,
				$this->moderator_id,
				"pending",
				mysql_escape_string($this->notes));
			$db->Execute($sql);
			$this->gridimage_ticket_id=$db->Insert_ID();
		}
		else
		{
			//update ticket 
			$sql="update gridimage_ticket set updated='{$this->updated}', moderator_id='{$this->moderator_id}' where gridimage_ticket_id={$this->gridimage_ticket_id}";
			$db->Execute($sql);
		}
		
		//write the change records
		$statuscount=array("pending"=>0, "immediate"=>0);
		foreach($this->changes as $change)
		{
			if ($change['gridimage_ticket_item_id'])
			{
				//we're updating	
				$sql=sprintf("update gridimage_ticket_item set status='%s', approver_id='%d' where gridimage_ticket_item_id=%d",
					$change["status"],
					$change["approver_id"],
					$change['gridimage_ticket_item_id']);
					
					
			}
			else
			{
				$sql=sprintf("insert into gridimage_ticket_item(gridimage_ticket_id, field, oldvalue, newvalue, status, approver_id) ".
					"values(%d, '%s', '%s', '%s', '%s', '%d')",
					$this->gridimage_ticket_id,
					$change["field"],
					mysql_escape_string($change["oldvalue"]),
					mysql_escape_string($change["newvalue"]),
					$change["status"],
					$change["approver_id"]);
			}
			
			$db->Execute($sql);
			
			$statuscount[$change["status"]]++;
		}
		
		//should we close the ticket?
		if (is_null($ticket_status))
		{
			$ticket_status="pending";
			if (($statuscount["pending"]==0) && ($statuscount["immediate"]>0))
			{
				//there are no pending changes, and some immediate ones were made, which 
				//most likely means the comment can be ignored and we can close the ticket...
				$ticket_status="closed";
			}
		}
		
		//update ticket status
		$sql="update gridimage_ticket set status='$ticket_status' where gridimage_ticket_id={$this->gridimage_ticket_id}";
		$db->Execute($sql);
		$this->status=$ticket_status;
		
		
		//if ticket is open and a new ticket, we should alert moderators
		if ($newticket)
		{
			$img=&$this->_getImage();


			if ($this->status=="pending")
			{
				//email alert to moderators
				$msg =& $this->_buildEmail("A new change request has been submitted.\n\n".$this->notes);
				$this->_sendModeratorMail($msg);

				//if suggester isn't the owner of the image, alert the owner too
				if ($this->user_id != $img->user_id)
				{
					$msg =& $this->_buildEmail("A visitor to the site has suggested changes to this photo submission. ".
						"The changes will be reviewed by site moderators, who may need to contact you ".
						"if further information is required. If you wish, you can review and comment on these ".
						"changes by following the links in this message. ");
					$submitter=new GeographUser($img->user_id);
					$this->_sendMail($submitter->email, $msg);
				}
			}
			elseif ($this->status=="closed")
			{
				//a new ticket has been closed - if the ticket wasn't from the owner,
				//then we should alert them to changes that have been made
				if ($this->user_id != $img->user_id)
				{
					$comment="A site moderator has just modified this photo submission. ".
						"You can review these changes by following the links in this message. ";
					if (strlen($this->notes))
						$comment.="\n\nModerator Comment: {$this->notes}";
						
					$msg =& $this->_buildEmail($comment);
					$submitter=new GeographUser($img->user_id);
					$this->_sendMail($submitter->email, $msg);
				
				}
				
			}
			
		}
		
		
		
		//return ticket status
		return $this->status;
	}

	/**
	* update updated timestamp
	* @access private
	*/
	function _touch()
	{
		//we're updating	
		$db=&$this->_getDB();
		$db->Execute("update gridimage_ticket set updated=now() where gridimage_ticket_id={$this->gridimage_ticket_id}");
	}
	
	/**
	* Add a comment to the ticket
	* @access private
	*/
	function _addComment($user_id, $comment)
	{
		if (!$this->isValid())
			die("_addComment - bad ticket");

		$db=&$this->_getDB();

		//add comment to db
		$sql=sprintf("insert into gridimage_ticket_comment(gridimage_ticket_id, user_id, comment, added) ".
			"values(%d, %d, '%s', now())",
			$this->gridimage_ticket_id,
			$user_id,
			mysql_escape_string($comment));
		$db->Execute($sql);
		
		$this->_touch();
	}
	
	/**
	* returns an array containing email body and subject
	* @access private
	*/
	function & _buildEmail($comment)
	{
		$msg=array();
		
		$image=& $this->_getImage();
		
		
		$msg['subject']="[Geograph] Changes to {$image->grid_reference} {$image->title} [#{$this->gridimage_ticket_id}]";
		
		$msg['body']="Re: {$image->grid_reference} {$image->title}\n";
		$msg['body'].="http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$this->gridimage_id}\n";
		$msg['body'].="---------------------------------------\n";
		$msg['body'].=$comment."\n";
		$msg['body'].="---------------------------------------\n";
		
		$msg['body'].="To respond to this message, please visit\n";
		$msg['body'].="http://{$_SERVER['HTTP_HOST']}/editimage.php?id={$this->gridimage_id}\n";
		$msg['body'].="Please, do NOT reply by email";
		
		
		return $msg;
	}
	
	/**
	* Companion to _buildEmail - sends a message with appropriate headers + return address
	* @access private
	*/
	function _sendMail($to, &$msg)
	{
		mail($to, $msg['subject'], $msg['body'],
				"From: Geograph DO NOT REPLY <lordelph@gmail.com>");
		
	}
	
	/**
	* Sends a message to handling moderator, or all moderators if not yet handled
	* @access private
	*/
	function _sendModeratorMail(&$msg)
	{
		//if moderator_id is set, just send there, otherwise 
		//we send to all users with moderator status
		$mods=array();
		if ($this->moderator_id)
		{
			$mod=new GeographUser($this->moderator_id);
			$mods[]=$mod->email;
		}
		else
		{
			//no moderator has handled this ticket yet, so lets forward to message to all of them
			$db=&$this->_getDB();
			$mods=$db->GetCol("select email from user where FIND_IN_SET('moderator',rights)>0;");
		}
		
		
		$this->_sendMail(implode(',',$mods), $msg);	
	}
	
	
	/**
	 * add a moderator comment to existing ticket
	 * moderator comment is added to ticket and emailed to photo submitter
	 * @access public
	 */
	function addModeratorComment($user_id, $comment)
	{
		$db=&$this->_getDB();
		if (!$this->isValid())
			die("addModeratorComment - bad ticket");
	
		$comment=trim($comment);
		if (strlen($comment)==0)
			return;
		
		//add database comment
		$this->_addComment($user_id, $comment);
		
		//associate this moderator with the ticket
		$this->moderator_id=$user_id;
		$db->Execute("update gridimage_ticket set status='open', moderator_id={$user_id} where gridimage_ticket_id={$this->gridimage_ticket_id}");
		
		$moderator=new GeographUser($user_id);
		$comment.="\n\n".$moderator->realname."\nGeograph Moderator\n";
		
		//email comment to submitter
		$msg =& $this->_buildEmail($comment);
		$image=& $this->_getImage();
		$submitter=new GeographUser($image->user_id);
		
		$this->_sendMail($submitter->email, $msg);
			
	}
	
	/**
	 * add a owner comment to existing ticket
	 * owner comment is added to ticket and emailed to photo moderators
	 * @access public
	 */
	function addOwnerComment($user_id, $comment)
	{
		$db=&$this->_getDB();
		if (!$this->isValid())
			die("addOwnerComment - bad ticket");
	
		$comment=trim($comment);
		if (strlen($comment)==0)
			return;
		
		$this->_addComment($user_id, $comment);
		
		$owner=new GeographUser($user_id);
		$comment.="\n\n".$owner->realname."\nPhoto owner\n";
		
		//email comment to moderators
		$msg =& $this->_buildEmail($comment);
		$this->_sendModeratorMail($msg);
	}
	
	
	
	/**
	 * ticket is closed, the comment is sent to owner and suggester
	 * aChanges should map item ids to boolean acceptance flag
	 * @access public
	 */
	function closeTicket($user_id,$comment, $aChanges=null)
	{
	
		$db=&$this->_getDB();
		if (!$this->isValid())
			die("closeTicket - bad ticket");



		//add comment to ticket
		$comment=trim($comment);
		$dbcomment=$comment;
		if (strlen($dbcomment)) {
			if (!preg_match("/[\.\!]\s*$/",$dbcomment))
				$dbcomment.=".";
			$dbcomment.="\n ";
		}
		$dbcomment.="Ticket is now closed";

		$this->_addComment($user_id, $dbcomment);

		$image=& $this->_getImage();

		//apply and summarise changes
		$changes="";
		$this->commit_count=0;
		$this->loadItems();
		foreach($this->changes as $idx=>$item)
		{
			if ($aChanges[$item['gridimage_ticket_item_id']])
			{
				//apply this change
				$this->changes[$idx]['status']='approved';
				$this->changes[$idx]['approver_id']=$user_id;

				//updateField does the hard work
				$this->updateField($item['field'], $item['oldvalue'], $item['newvalue'], false);

				if ($item['oldvalue']!=$item['newvalue'])
				{
					$changes.="{$item['field']} changed from \"{$item['oldvalue']}\" to \"{$item['newvalue']}\"\n";
				}
			}
			else
			{
				$this->changes[$idx]['status']='rejected';
				$this->changes[$idx]['approver_id']=$user_id;
			}
		}
		$this->commit('closed');

		//message to suggester (if not the owner)
		if ($this->user_id != $image->user_id)
		{
			$suggester_msg="Many thanks for your feedback on this photo, ".
				"we've now closed this issue.";
			if (strlen($changes))
			{
				$suggester_msg.=" The following changes were made:\n\n";
				$suggester_msg.=$changes;
			}

			$msg =& $this->_buildEmail($suggester_msg);
			$suggester=new GeographUser($this->user_id);

			$this->_sendMail($suggester->email, $msg);


		}

		//message to owner
		$owner_msg="We've now closed this issue. ";
		if (strlen($changes))
		{
			$owner_msg.=" The following changes were made:\n\n";
			$owner_msg.=$changes;
		}
		$msg =& $this->_buildEmail($owner_msg);
		$owner=new GeographUser($image->user_id);
		$this->_sendMail($owner->email, $msg);
	}

	/**
	 * get stored gridimage object, creating if necessary
	 * @access private
	 */
	function &_getImage()
	{
		if (!is_object($this->gridimage))
		{
			$this->gridimage=new GridImage();
			$this->gridimage->loadFromId($this->gridimage_id);
		}	
		return $this->gridimage;
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
		$this->commit_count=0;
		$this->changes=array();
	}
	
	/**
	* assign members from array containing required members
	* @access private
	*/
	function _initFromArray(&$arr)
	{
		foreach($arr as $name=>$value)
		{
			if (!is_numeric($name))
				$this->$name=$value;
													
		}
	}
	
	/**
	* return true if instance references a valid grid image tic
	* @access public
	*/
	function isValid()
	{
		return isset($this->gridimage_ticket_id) && ($this->gridimage_ticket_id>0);
	}
	
	/**
	* assign members from recordset containing required members
	* @access public
	*/
	function loadFromRecordset(&$rs)
	{
		$this->_clear();
		$this->_initFromArray($rs->fields);
		return $this->isValid();
	}

	/**
	* load all change items
	* @access public
	*/
	function loadItems()
	{
		$db=&$this->_getDB();
		if ($this->isValid())
		{
			$this->changes=$db->GetAll("select * from gridimage_ticket_item where gridimage_ticket_id={$this->gridimage_ticket_id}");
		}
		
	}

	/**
	* load all comments
	* @access public
	*/
	function loadComments()
	{
		$db=&$this->_getDB();
		if ($this->isValid())
		{
			$this->comments=$db->GetAll("select c.*,u.realname , ".
				"FIND_IN_SET('moderator',u.rights)>0 as moderator ".
				"from gridimage_ticket_comment as c inner join user as u using(user_id) ".
				"where gridimage_ticket_id={$this->gridimage_ticket_id} order by c.added");
		}
		
	}



	
	/**
	* assign members from gridimage_ticket_id
	* @access public
	*/
	function loadFromId($gridimage_ticket_id)
	{
		$db=&$this->_getDB();
		
		$this->_clear();
		if (preg_match('/^\d+$/', $gridimage_ticket_id))
		{
			$row = &$db->GetRow("select * from gridimage_ticket where gridimage_ticket_id={$gridimage_ticket_id} limit 1");
			if (is_array($row))
			{
				$this->_initFromArray($row);
			}
		}
		return $this->isValid();
	}	
}
