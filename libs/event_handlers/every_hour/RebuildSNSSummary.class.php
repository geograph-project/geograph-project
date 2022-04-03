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
class RebuildSNSSummary extends EventHandler
{
	function processEvent(&$event)
	{
		$db=&$this->_getDB();

		$db->Execute("DROP TABLE IF EXISTS sns_summary_tmp");

		$db->Execute("
		create table sns_summary_tmp (UNIQUE(email_md5,TimeStamp))
		IGNORE
		select md5(LOWER(TRIM(JSON_VALUE(Message,'$.mail.destination[0]')))) as email_md5, TimeStamp,
			CONCAT_WS(', ',
		         JSON_VALUE(Message,'$.notificationType'),
		         JSON_VALUE(Message,'$.bounce.bounceType'),
		         NULLIF(JSON_VALUE(Message,'$.bounce.bounceSubType'),'General'),
		         JSON_VALUE(Message,'$.complaint.complaintType'),
		         NULLIF(JSON_VALUE(Message,'$.complaint.complaintSubType'),'null'),
		         JSON_VALUE(Message,'$.complaint.complaintFeedbackType')) as type,
			JSON_VALUE(Message,'$.mail.destination[0]') as `email`,
			JSON_VALUE(Message,'$.mail.commonHeaders.subject') as `subject`
		from sns_message
 		where Type = 'Notification' and JSON_VALUE(Message,'$.mail.destination[0]') is not null
		and block_cleared = 0");

		$db->Execute("DROP TABLE IF EXISTS sns_summary");
		$db->Execute("RENAME TABLE sns_summary_tmp TO sns_summary");

		//return true to signal completed processing
		//return false to have another attempt later
		return true;
	}
}
